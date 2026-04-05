<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
require_once 'getapikey.php';

$pdo = getDatabaseConnection();

// ══════════════════════════════════════════════
// Configuration CY Bank
// ══════════════════════════════════════════════
$vendeur = 'MI-3_G';
$api_key = getAPIKey($vendeur);
$cybank_url = 'https://www.plateforme-smc.fr/cybank/index.php';

// URL de retour (construite dynamiquement)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$retour = $protocol . '://' . $host . '/paiement.php';

// ══════════════════════════════════════════════
// CAS 1 : Retour de CY Bank (paramètres GET)
// ══════════════════════════════════════════════
if (isset($_GET['transaction'], $_GET['montant'], $_GET['vendeur'], $_GET['status'], $_GET['control'])) {

    $txn       = $_GET['transaction'];
    $montant   = $_GET['montant'];
    $vnd       = $_GET['vendeur'];
    $statut    = $_GET['status'];
    $control   = $_GET['control'];

    // Vérifier l'intégrité avec le hash MD5
    $expected_control = md5($api_key . "#" . $txn . "#" . $montant . "#" . $vnd . "#" . $statut . "#");

    $payment_valid = false;
    $error_message = '';

    if ($control !== $expected_control) {
        $error_message = "Erreur : le contrôle d'intégrité a échoué.";
        // On marque quand même la commande comme refusée
        if (preg_match('/^ORD(\d+)/', $txn, $matches)) {
            $orderId = (int) $matches[1];
            $stmt = $pdo->prepare("UPDATE orders SET status = 'refusee' WHERE id = :id");
            $stmt->execute(['id' => $orderId]);
        }
    } elseif ($statut === 'accepted') {
        // Paiement accepté — extraire l'order_id de la transaction
        if (preg_match('/^ORD(\d+)/', $txn, $matches)) {
            $orderId = (int) $matches[1];

            // Déterminer le nouveau statut selon le type de commande
            $stmtOrder = $pdo->prepare("SELECT order_type FROM orders WHERE id = :id");
            $stmtOrder->execute(['id' => $orderId]);
            $orderData = $stmtOrder->fetch(PDO::FETCH_ASSOC);

            if ($orderData && $orderData['order_type'] === 'livraison') {
                $newStatus = 'en_attente_livreur';
            } else {
                $newStatus = 'payee';
            }

            // Mettre à jour le statut de la commande
            $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $newStatus, 'id' => $orderId]);

            // Enregistrer le paiement
            $stmtPay = $pdo->prepare("
                INSERT INTO payments (order_id, bank_details, transaction_date)
                VALUES (:order_id, :bank_details, datetime('now'))
            ");
            $stmtPay->execute([
                'order_id'     => $orderId,
                'bank_details' => 'CYBank txn: ' . $txn,
            ]);

            $payment_valid = true;
        } else {
            $error_message = "Erreur : identifiant de transaction invalide.";
        }
    } else {
        // Paiement refusé (denied / declined)
        if (preg_match('/^ORD(\d+)/', $txn, $matches)) {
            $orderId = (int) $matches[1];
            $stmt = $pdo->prepare("UPDATE orders SET status = 'refusee' WHERE id = :id");
            $stmt->execute(['id' => $orderId]);
        }
        $error_message = "Le paiement a été refusé par la banque.";
    }

    // Nettoyer la session
    unset($_SESSION['pending_order_id']);
    ?>

    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Résultat du paiement</title>
    </head>
    <body>

        <h1>Résultat du paiement</h1>

        <?php if ($payment_valid): ?>
            <p>Paiement accepté ! Votre commande n°<?= (int) $orderId ?> a été confirmée.</p>
            <p>Transaction : <?= h($txn) ?></p>
            <p>Montant : <?= h($montant) ?> €</p>
        <?php else: ?>
            <p><?= h($error_message) ?></p>
        <?php endif; ?>

        <br>
        <a href="catalogue.php">Retour au catalogue</a>

    </body>
    </html>

    <?php
    exit;
}

// ══════════════════════════════════════════════
// CAS 2 : Affichage du formulaire de paiement
// ══════════════════════════════════════════════

// Vérifier qu'il y a une commande en attente
if (!isset($_SESSION['pending_order_id'])) {
    header('Location: catalogue.php');
    exit;
}

$orderId = $_SESSION['pending_order_id'];

// Récupérer la commande
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
$stmt->execute(['id' => $orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: catalogue.php');
    exit;
}

// Récupérer les articles de la commande
$stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
$stmtItems->execute(['order_id' => $orderId]);
$orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

// Calculer le montant total
$totalPrice = 0;
$detailItems = [];

foreach ($orderItems as $oi) {
    if ($oi['item_type'] === 'dish') {
        $stmt = $pdo->prepare("SELECT name, price FROM dishes WHERE id = :id");
    } else {
        $stmt = $pdo->prepare("SELECT name, total_price AS price FROM menus WHERE id = :id");
    }
    $stmt->execute(['id' => $oi['item_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $subtotal = $row['price'] * $oi['quantity'];
        $totalPrice += $subtotal;
        $detailItems[] = [
            'name'     => $row['name'],
            'type'     => $oi['item_type'] === 'menu' ? 'Menu' : 'Plat',
            'price'    => $row['price'],
            'quantity' => $oi['quantity'],
            'subtotal' => $subtotal,
        ];
    }
}

// Générer les paramètres CY Bank
// Format : ORD + orderId (zéro-paddé) + suffixe alphanum = 10..24 chars, uniquement [0-9a-zA-Z]
$transaction = 'ORD' . str_pad($orderId, 4, '0', STR_PAD_LEFT) . substr(md5(uniqid(mt_rand(), true)), 0, 10);
$montant     = number_format($totalPrice, 2, '.', '');

// Calculer le hash de contrôle
$control = md5($api_key . "#" . $transaction . "#" . $montant . "#" . $vendeur . "#" . $retour . "#");
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement – CY Bank</title>
</head>

<body>

    <h1>Paiement de votre commande n°<?= (int) $orderId ?></h1>

    <!-- Résumé de la commande -->
    <h2>Résumé</h2>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Type</th>
                <th>Article</th>
                <th>Prix unitaire</th>
                <th>Quantité</th>
                <th>Sous-total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detailItems as $di): ?>
                <tr>
                    <td><?= h($di['type']) ?></td>
                    <td><?= h($di['name']) ?></td>
                    <td><?= number_format($di['price'], 2, ',', ' ') ?> €</td>
                    <td><?= $di['quantity'] ?></td>
                    <td><?= number_format($di['subtotal'], 2, ',', ' ') ?> €</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p><strong>Total à payer : <?= number_format($totalPrice, 2, ',', ' ') ?> €</strong></p>

    <hr>

    <!-- Formulaire envoyé à CY Bank -->
    <h2>Paiement sécurisé via CY Bank</h2>
    <p>Vous allez être redirigé vers la plateforme de paiement CY Bank.</p>

    <form action="<?= h($cybank_url) ?>" method="post" id="cybank-form">
        <input type="hidden" name="transaction" value="<?= h($transaction) ?>">
        <input type="hidden" name="montant"     value="<?= h($montant) ?>">
        <input type="hidden" name="vendeur"     value="<?= h($vendeur) ?>">
        <input type="hidden" name="retour"      value="<?= h($retour) ?>">
        <input type="hidden" name="control"     value="<?= h($control) ?>">

        <p>Transaction : <?= h($transaction) ?></p>
        <p>Montant : <?= h($montant) ?> €</p>
        <p>Vendeur : <?= h($vendeur) ?></p>

        <br>
        <button type="submit">Payer maintenant</button>
    </form>



</body>

</html>
