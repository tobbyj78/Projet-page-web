<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
require_once 'models/orders.php';

$pdo = getDatabaseConnection();

// Verifier que l'utilisateur est connecte et est restaurateur
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser || $currentUser['role'] !== 'restaurateur') {
    header('Location: index.php');
    exit;
}

// Recuperer l'ID de la commande
$orderId = (int) ($_GET['id'] ?? 0);
if ($orderId <= 0) {
    header('Location: commandes_resto.php');
    exit;
}

$message = '';
$error = '';

// Traitement de l'assignation livreur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'assign_livreur') {
    $selectedLivreurId = (int) ($_POST['livreur_id'] ?? 0);

    $stmtOrder = $pdo->prepare("SELECT id, order_type, status FROM orders WHERE id = :id");
    $stmtOrder->execute(['id' => $orderId]);
    $orderToAssign = $stmtOrder->fetch(PDO::FETCH_ASSOC);

    if (!$orderToAssign) {
        $error = 'Commande introuvable.';
    } elseif ($orderToAssign['order_type'] !== 'livraison') {
        $error = 'Cette commande n\'est pas une livraison.';
    } elseif ($orderToAssign['status'] !== 'en_attente_livreur') {
        $error = 'La commande doit etre en attente d\'un livreur pour pouvoir etre assignee.';
    } elseif ($selectedLivreurId <= 0) {
        $error = 'Veuillez selectionner un livreur.';
    } else {
        $stmtLivreur = $pdo->prepare("SELECT id FROM users WHERE id = :id AND role = 'livreur'");
        $stmtLivreur->execute(['id' => $selectedLivreurId]);
        $livreurExists = $stmtLivreur->fetch(PDO::FETCH_ASSOC);

        if (!$livreurExists) {
            $error = 'Livreur invalide.';
        } else {
            $stmtBusy = $pdo->prepare("SELECT id FROM orders WHERE delivery_person_id = :livreur_id AND status = 'en_livraison' LIMIT 1");
            $stmtBusy->execute(['livreur_id' => $selectedLivreurId]);
            $busyOrder = $stmtBusy->fetch(PDO::FETCH_ASSOC);

            if ($busyOrder) {
                $error = 'Ce livreur est deja en livraison.';
            } else {
                $stmtAssign = $pdo->prepare("\n                    UPDATE orders\n                    SET delivery_person_id = :livreur_id, status = 'en_livraison'\n                    WHERE id = :id\n                      AND order_type = 'livraison'\n                      AND status = 'en_attente_livreur'\n                ");
                $stmtAssign->execute([
                    'livreur_id' => $selectedLivreurId,
                    'id' => $orderId,
                ]);

                if ($stmtAssign->rowCount() > 0) {
                    $_SESSION['resto_message'] = 'Livreur assigne avec succes. La commande est maintenant en livraison.';
                } else {
                    $_SESSION['resto_error'] = 'Assignation impossible. La commande a peut-etre deja ete modifiee.';
                }

                header('Location: detail_commande.php?id=' . $orderId);
                exit;
            }
        }
    }
}

if (isset($_SESSION['resto_message'])) {
    $message = $_SESSION['resto_message'];
    unset($_SESSION['resto_message']);
}

if (isset($_SESSION['resto_error'])) {
    $error = $_SESSION['resto_error'];
    unset($_SESSION['resto_error']);
}

// Recuperer la commande
$order = getOrderById($pdo, $orderId);
if (!$order) {
    header('Location: commandes_resto.php');
    exit;
}

// Recuperer les articles
$items = getOrderItems($pdo, $orderId);

// Calculer le total
$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Recuperer les livreurs
$livreurs = getAllLivreurs($pdo);

// Recuperer les livreurs indisponibles (deja en livraison)
$stmtBusyLivreurs = $pdo->query("\n    SELECT DISTINCT delivery_person_id\n    FROM orders\n    WHERE status = 'en_livraison'\n      AND delivery_person_id IS NOT NULL\n");
$busyLivreurIds = array_map('intval', array_column($stmtBusyLivreurs->fetchAll(PDO::FETCH_ASSOC), 'delivery_person_id'));

// Recuperer le livreur assigne
$assignedLivreur = null;
if ($order['delivery_person_id']) {
    $stmtL = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = :id");
    $stmtL->execute(['id' => $order['delivery_person_id']]);
    $assignedLivreur = $stmtL->fetch(PDO::FETCH_ASSOC);
}

$statusLabels = [
    'en_attente' => 'En attente',
    'payee' => 'Payee',
    'en_attente_livreur' => 'En attente d\'un livreur',
    'en_preparation' => 'En preparation',
    'en_livraison' => 'En livraison',
    'livree' => 'Livree',
    'refusee' => 'Refusee',
    'abandonnee' => 'Abandonnee',
];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail commande n°<?= $orderId ?></title>
</head>

<body>

    <h1>Detail de la commande n°<?= $orderId ?></h1>

    <a href="commandes_resto.php">Retour a la liste</a>

    <?php if ($message): ?>
        <p><strong><?= h($message) ?></strong></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p><strong><?= h($error) ?></strong></p>
    <?php endif; ?>

    <h2>Informations</h2>

    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Statut</th>
            <td><strong><?= h($statusLabels[$order['status']] ?? $order['status']) ?></strong></td>
        </tr>
        <tr>
            <th>Type</th>
            <td><?= h($order['order_type']) ?></td>
        </tr>
        <tr>
            <th>Date de commande</th>
            <td><?= h($order['created_at']) ?></td>
        </tr>
        <tr>
            <th>Preparation prevue</th>
            <td><?= $order['scheduled_datetime'] ? h($order['scheduled_datetime']) : 'Immediate' ?></td>
        </tr>
        <?php if ($order['order_type'] === 'livraison'): ?>
            <tr>
                <th>Adresse de livraison</th>
                <td><?= h($order['delivery_address']) ?></td>
            </tr>
        <?php endif; ?>
    </table>

    <h2>Client</h2>

    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Nom</th>
            <td><?= h($order['first_name'] . ' ' . $order['last_name']) ?></td>
        </tr>
        <tr>
            <th>Telephone</th>
            <td><?= h($order['phone']) ?></td>
        </tr>
        <tr>
            <th>Adresse</th>
            <td><?= h($order['address']) ?></td>
        </tr>
        <tr>
            <th>Complement</th>
            <td><?= h($order['address_info']) ?></td>
        </tr>
    </table>

    <h2>Articles commandes</h2>

    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Type</th>
                <th>Article</th>
                <th>Prix unitaire</th>
                <th>Quantite</th>
                <th>Sous-total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= $item['item_type'] === 'menu' ? 'Menu' : 'Plat' ?></td>
                    <td><?= h($item['name']) ?></td>
                    <td><?= number_format($item['price'], 2, ',', ' ') ?> €</td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= number_format($item['price'] * $item['quantity'], 2, ',', ' ') ?> €</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p><strong>Total : <?= number_format($total, 2, ',', ' ') ?> €</strong></p>

    <h2>Actions</h2>

    <h3>Assigner un livreur</h3>
    <?php if ($order['order_type'] === 'livraison'): ?>
        <?php if ($assignedLivreur): ?>
            <p>Livreur assigne : <strong><?= h($assignedLivreur['first_name'] . ' ' . $assignedLivreur['last_name']) ?></strong></p>
        <?php endif; ?>

        <?php if ($order['status'] === 'en_attente_livreur'): ?>
            <form action="detail_commande.php?id=<?= $orderId ?>" method="post">
                <input type="hidden" name="action" value="assign_livreur">

                <select name="livreur_id" required>
                    <option value="">-- Choisir un livreur --</option>
                    <?php foreach ($livreurs as $livreur): ?>
                        <?php $isBusy = in_array((int) $livreur['id'], $busyLivreurIds, true); ?>
                        <option value="<?= $livreur['id'] ?>" <?= $isBusy ? 'disabled' : '' ?>>
                            <?= h($livreur['first_name'] . ' ' . $livreur['last_name']) ?><?= $isBusy ? ' (indisponible)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Assigner</button>
            </form>
        <?php else: ?>
            <p>Assignation disponible uniquement quand la commande est en attente d'un livreur.</p>
        <?php endif; ?>
    <?php else: ?>
        <p>Pas de livraison pour cette commande (<?= h($order['order_type']) ?>).</p>
    <?php endif; ?>

</body>

</html>