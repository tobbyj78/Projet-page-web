<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
require_once 'models/orders.php';

$pdo = getDatabaseConnection();

// Vérifier que l'utilisateur est connecté et est restaurateur
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

// Récupérer l'ID de la commande
$orderId = (int) ($_GET['id'] ?? 0);
if ($orderId <= 0) {
    header('Location: commandes_resto.php');
    exit;
}

// Récupérer la commande
$order = getOrderById($pdo, $orderId);
if (!$order) {
    header('Location: commandes_resto.php');
    exit;
}

// Récupérer les articles
$items = getOrderItems($pdo, $orderId);

// Calculer le total
$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Récupérer les livreurs disponibles
$livreurs = getAllLivreurs($pdo);

// Récupérer le livreur assigné
$assignedLivreur = null;
if ($order['delivery_person_id']) {
    $stmtL = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = :id");
    $stmtL->execute(['id' => $order['delivery_person_id']]);
    $assignedLivreur = $stmtL->fetch(PDO::FETCH_ASSOC);
}

// Statuts possibles pour les boutons
$statusTransitions = [
    'payee'              => ['en_preparation'],
    'en_preparation'     => ['en_attente_livreur'],
    'en_attente_livreur' => ['en_livraison'],
    'en_livraison'       => ['livree'],
];
$nextStatuses = $statusTransitions[$order['status']] ?? [];

$statusLabels = [
    'en_attente'         => 'En attente',
    'payee'              => 'Payée',
    'en_attente_livreur' => 'En attente d\'un livreur',
    'en_preparation'     => 'En préparation',
    'en_livraison'       => 'En livraison',
    'livree'             => 'Livrée',
    'refusee'            => 'Refusée',
    'abandonnee'         => 'Abandonnée',
];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail commande n°<?= $orderId ?></title>
</head>

<body>

    <h1>Détail de la commande n°<?= $orderId ?></h1>

    <a href="commandes_resto.php">Retour à la liste</a>

    <!-- ══════════════════════════════════════
         INFORMATIONS COMMANDE
         ══════════════════════════════════════ -->
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
            <th>Préparation prévue</th>
            <td><?= $order['scheduled_datetime'] ? h($order['scheduled_datetime']) : 'Immédiate' ?></td>
        </tr>
        <?php if ($order['order_type'] === 'livraison'): ?>
            <tr>
                <th>Adresse de livraison</th>
                <td><?= h($order['delivery_address']) ?></td>
            </tr>
        <?php endif; ?>
    </table>

    <!-- ══════════════════════════════════════
         INFORMATIONS CLIENT
         ══════════════════════════════════════ -->
    <h2>Client</h2>

    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Nom</th>
            <td><?= h($order['first_name'] . ' ' . $order['last_name']) ?></td>
        </tr>
        <tr>
            <th>Téléphone</th>
            <td><?= h($order['phone']) ?></td>
        </tr>
        <tr>
            <th>Adresse</th>
            <td><?= h($order['address']) ?></td>
        </tr>
        <tr>
            <th>Complément</th>
            <td><?= h($order['address_info']) ?></td>
        </tr>
    </table>

    <!-- ══════════════════════════════════════
         ARTICLES
         ══════════════════════════════════════ -->
    <h2>Articles commandés</h2>

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

    <!-- ══════════════════════════════════════
         ACTIONS (VISUELS UNIQUEMENT)
         ══════════════════════════════════════ -->
    <h2>Actions</h2>

    <h3>Changer le statut</h3>
    <?php if (!empty($nextStatuses)): ?>
        <?php foreach ($nextStatuses as $next): ?>
            <button type="button" disabled>Passer en : <?= h($statusLabels[$next] ?? $next) ?></button>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Aucune transition disponible pour le statut actuel.</p>
    <?php endif; ?>

    <h3>Assigner un livreur</h3>
    <?php if ($order['order_type'] === 'livraison'): ?>
        <?php if ($assignedLivreur): ?>
            <p>Livreur assigné : <strong><?= h($assignedLivreur['first_name'] . ' ' . $assignedLivreur['last_name']) ?></strong></p>
        <?php endif; ?>

        <select disabled>
            <option value="">-- Choisir un livreur --</option>
            <?php foreach ($livreurs as $livreur): ?>
                <option value="<?= $livreur['id'] ?>"
                    <?= ($order['delivery_person_id'] == $livreur['id']) ? 'selected' : '' ?>>
                    <?= h($livreur['first_name'] . ' ' . $livreur['last_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="button" disabled>Assigner</button>
    <?php else: ?>
        <p>Pas de livraison pour cette commande (<?= h($order['order_type']) ?>).</p>
    <?php endif; ?>

</body>

</html>
