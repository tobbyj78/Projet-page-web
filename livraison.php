<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
require_once 'models/orders.php';

$pdo = getDatabaseConnection();

// Vérifier que l'utilisateur est connecté et est livreur
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser || $currentUser['role'] !== 'livreur') {
    header('Location: index.php');
    exit;
}

$message = '';

// ── Traitement des actions ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $action = $_POST['action'];
    $orderId = (int) ($_POST['order_id'] ?? 0);

    if ($action === 'prendre' && $orderId > 0) {
        // Le livreur prend une commande en attente
        $stmtCheck = $pdo->prepare("SELECT id FROM orders WHERE id = :id AND status = 'en_attente_livreur' AND delivery_person_id IS NULL");
        $stmtCheck->execute(['id' => $orderId]);
        if ($stmtCheck->fetch()) {
            $stmtUp = $pdo->prepare("UPDATE orders SET status = 'en_livraison', delivery_person_id = :livreur_id WHERE id = :id");
            $stmtUp->execute(['livreur_id' => $_SESSION['user_id'], 'id' => $orderId]);
            $message = "Commande n°$orderId prise en charge !";
        }
    }

    if ($action === 'livree' && $orderId > 0) {
        $stmtUp = $pdo->prepare("UPDATE orders SET status = 'livree' WHERE id = :id AND delivery_person_id = :livreur_id AND status = 'en_livraison'");
        $stmtUp->execute(['id' => $orderId, 'livreur_id' => $_SESSION['user_id']]);
        $message = "Commande n°$orderId marquée comme livrée.";
    }

    if ($action === 'abandonnee' && $orderId > 0) {
        $stmtUp = $pdo->prepare("UPDATE orders SET status = 'abandonnee' WHERE id = :id AND delivery_person_id = :livreur_id AND status = 'en_livraison'");
        $stmtUp->execute(['id' => $orderId, 'livreur_id' => $_SESSION['user_id']]);
        $message = "Commande n°$orderId marquée comme abandonnée.";
    }

    // PRG
    $_SESSION['livreur_message'] = $message;
    header('Location: livraison.php');
    exit;
}

// Message flash
if (isset($_SESSION['livreur_message'])) {
    $message = $_SESSION['livreur_message'];
    unset($_SESSION['livreur_message']);
}

// ── Récupérer la commande en cours du livreur ──
$delivery = getDeliveryOrder($pdo, $_SESSION['user_id']);

// ── Récupérer les commandes en attente d'un livreur ──
$stmtWaiting = $pdo->query("
    SELECT o.*, u.first_name, u.last_name, u.phone, u.delivery_address, u.address
    FROM orders o
    INNER JOIN users u ON u.id = o.user_id
    WHERE o.status = 'en_attente_livreur'
      AND o.delivery_person_id IS NULL
    ORDER BY o.created_at ASC
");
$waitingOrders = $stmtWaiting->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livraison</title>
</head>

<body>

    <h1>Espace Livreur</h1>

    <a href="index.php">Accueil</a>

    <?php if ($message): ?>
        <p><strong><?= h($message) ?></strong></p>
    <?php endif; ?>

    <!-- ══════════════════════════════════════
         LIVRAISON EN COURS
         ══════════════════════════════════════ -->
    <?php if ($delivery): ?>

        <h2>Livraison en cours — Commande n°<?= $delivery['id'] ?></h2>

        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                <th>Client</th>
                <td><?= h($delivery['first_name'] . ' ' . $delivery['last_name']) ?></td>
            </tr>
            <tr>
                <th>Téléphone</th>
                <td><?= h($delivery['phone']) ?></td>
            </tr>
            <tr>
                <th>Adresse de livraison</th>
                <td><?= h($delivery['delivery_address'] ?? $delivery['address']) ?></td>
            </tr>
            <tr>
                <th>Code interphone / Complément</th>
                <td><?= h($delivery['address_info']) ?></td>
            </tr>
            <tr>
                <th>Date de commande</th>
                <td><?= h($delivery['created_at']) ?></td>
            </tr>
            <?php if ($delivery['scheduled_datetime']): ?>
                <tr>
                    <th>Livraison prévue</th>
                    <td><?= h($delivery['scheduled_datetime']) ?></td>
                </tr>
            <?php endif; ?>
        </table>

        <h3>Actions</h3>

        <form action="livraison.php" method="post" style="display:inline;">
            <input type="hidden" name="action" value="livree">
            <input type="hidden" name="order_id" value="<?= $delivery['id'] ?>">
            <button type="submit">Marquer comme Livrée</button>
        </form>

        <form action="livraison.php" method="post" style="display:inline;">
            <input type="hidden" name="action" value="abandonnee">
            <input type="hidden" name="order_id" value="<?= $delivery['id'] ?>">
            <button type="submit">Marquer comme Abandonnée</button>
        </form>

    <?php else: ?>

        <!-- ══════════════════════════════════════
             COMMANDES DISPONIBLES
             ══════════════════════════════════════ -->
        <h2>Commandes en attente d'un livreur (<?= count($waitingOrders) ?>)</h2>

        <?php if (empty($waitingOrders)): ?>
            <p>Aucune commande à livrer pour le moment.</p>
        <?php else: ?>
            <table border="1" cellpadding="5" cellspacing="0">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Adresse</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($waitingOrders as $wo): ?>
                        <tr>
                            <td><?= $wo['id'] ?></td>
                            <td><?= h($wo['created_at']) ?></td>
                            <td><?= h($wo['first_name'] . ' ' . $wo['last_name']) ?></td>
                            <td><?= h($wo['delivery_address'] ?? $wo['address']) ?></td>
                            <td>
                                <form action="livraison.php" method="post">
                                    <input type="hidden" name="action" value="prendre">
                                    <input type="hidden" name="order_id" value="<?= $wo['id'] ?>">
                                    <button type="submit">Prendre cette commande</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php endif; ?>

</body>

</html>
