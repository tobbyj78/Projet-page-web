<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
require_once 'models/orders.php';
require_once 'models/users.php';

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

// Filtre par statut
$filterStatus = $_GET['statut'] ?? null;

$validStatuses = ['en_attente', 'payee', 'en_attente_livreur', 'en_preparation', 'en_livraison', 'livree', 'refusee', 'abandonnee'];

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
if ($filterStatus && !in_array($filterStatus, $validStatuses)) {
    $filterStatus = null;
}

$orders = getOrdersByStatus($pdo, $filterStatus);

// Calculer les totaux
$orderTotals = [];
foreach ($orders as $order) {
    $orderTotals[$order['id']] = getOrderTotal($pdo, $order['id']);
}
?>

<?php $pageTitle = 'Gestion des commandes'; ?>
<?php include 'includes/header.php'; ?>

    <h1>Gestion des commandes (Restaurateur)</h1>

    <a href="index.php">Accueil</a>

    <!-- ══════════════════════════════════════
         FILTRES PAR STATUT
         ══════════════════════════════════════ -->
    <h2>Filtrer par statut</h2>

    <a href="commandes_resto.php">Toutes</a> |
    <a href="commandes_resto.php?statut=en_attente">En attente</a> |
    <a href="commandes_resto.php?statut=payee">À préparer</a> |
    <a href="commandes_resto.php?statut=en_attente_livreur">En attente livreur</a> |
    <a href="commandes_resto.php?statut=en_preparation">En cours</a> |
    <a href="commandes_resto.php?statut=en_livraison">En livraison</a> |
    <a href="commandes_resto.php?statut=livree">Livrées</a> |
    <a href="commandes_resto.php?statut=refusee">Refusées</a>

    <?php if ($filterStatus): ?>
        <p>Filtre actif : <strong><?= h($statusLabels[$filterStatus] ?? $filterStatus) ?></strong></p>
    <?php endif; ?>

    <!-- ══════════════════════════════════════
         LISTE DES COMMANDES
         ══════════════════════════════════════ -->
    <h2>Commandes (<?= count($orders) ?>)</h2>

    <?php if (empty($orders)): ?>
        <p>Aucune commande trouvée.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Téléphone</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th>Total</th>
                    <th>Programmée</th>
                    <th>Détail</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= $order['id'] ?></td>
                        <td><?= h($order['created_at']) ?></td>
                        <td><?= h($order['first_name'] . ' ' . $order['last_name']) ?></td>
                        <td><?= h($order['phone']) ?></td>
                        <td><?= h($order['order_type']) ?></td>
                        <td><?= h($statusLabels[$order['status']] ?? $order['status']) ?></td>
                        <td><?= number_format($orderTotals[$order['id']], 2, ',', ' ') ?> €</td>
                        <td><?= $order['scheduled_datetime'] ? h($order['scheduled_datetime']) : 'Immédiate' ?></td>
                        <td><a href="detail_commande.php?id=<?= $order['id'] ?>">Voir</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php include 'includes/footer.php'; ?>