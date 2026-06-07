<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
require_once 'models/orders.php';
require_once 'models/users.php';

$pdo = getDatabaseConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT first_name, role FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'restaurateur') {
    header('Location: index.php');
    exit;
}

// ── Stats ───────────────────────────────────────────────────
$statsDayOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE date(created_at) = date('now')")->fetchColumn();
$statsPending   = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('payee', 'prete', 'en_attente_livreur')")->fetchColumn();
$statsInPrep    = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'en_preparation'")->fetchColumn();
$statsInTransit = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'en_livraison'")->fetchColumn();

// CA du jour (commandes payées/livrées aujourd'hui)
$caStmt = $pdo->query("
    SELECT COALESCE(SUM(oi.quantity *
        CASE WHEN oi.item_type = 'dish' THEN d.price ELSE m.total_price END
    ), 0)
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    LEFT JOIN dishes d ON oi.item_type = 'dish' AND d.id = oi.item_id
    LEFT JOIN menus m  ON oi.item_type = 'menu'  AND m.id = oi.item_id
    WHERE o.status IN ('payee', 'en_attente_livreur', 'en_preparation', 'prete', 'en_livraison', 'livree')
      AND date(o.created_at) = date('now')
");
$caDay = $caStmt->fetchColumn();

// Dernières commandes (10)
$recentOrders = $pdo->query("
    SELECT o.id, o.created_at, o.order_type, o.status, o.scheduled_datetime,
           u.first_name, u.last_name
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.id DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Totaux pour les dernières commandes
$orderTotals = [];
foreach ($recentOrders as $ro) {
    $orderTotals[$ro['id']] = getOrderTotal($pdo, $ro['id']);
}

$statusLabels = [
    'en_attente'         => 'En attente',
    'payee'              => 'Payée',
    'en_attente_livreur' => 'En attente livreur',
    'en_preparation'     => 'En préparation',
    'prete'              => 'Prête',
    'en_livraison'       => 'En livraison',
    'livree'             => 'Livrée',
    'refusee'            => 'Refusée',
    'abandonnee'         => 'Abandonnée',
];

$typeLabels = [
    'sur_place' => 'Sur place',
    'emporter'  => 'À emporter',
    'livraison' => 'Livraison',
];
?>

<?php
$staffRole       = 'restaurateur';
$staffActivePage = 'dashboard';
$staffPageTitle  = 'Tableau de bord Restaurateur';
include 'includes/staff_header.php';
?>

<main class="staff-page">
  <div class="staff-inner">

    <header class="staff-header">
      <p class="staff-label">Espace restaurateur</p>
      <h1 class="staff-title">Bonjour <em><?= h($user['first_name']) ?></em></h1>
    </header>

    <!-- Stats -->
    <div class="staff-stats">
      <div class="staff-stat-card">
        <span class="staff-stat-value"><?= $statsDayOrders ?></span>
        <span class="staff-stat-label">Commandes aujourd'hui</span>
      </div>
      <div class="staff-stat-card">
        <span class="staff-stat-value"><?= $statsPending ?></span>
        <span class="staff-stat-label">À traiter</span>
      </div>
      <div class="staff-stat-card">
        <span class="staff-stat-value"><?= $statsInPrep ?></span>
        <span class="staff-stat-label">En préparation</span>
      </div>
      <div class="staff-stat-card">
        <span class="staff-stat-value"><?= $statsInTransit ?></span>
        <span class="staff-stat-label">En livraison</span>
      </div>
      <div class="staff-stat-card">
        <span class="staff-stat-value"><?= number_format($caDay, 2, ',', ' ') ?> €</span>
        <span class="staff-stat-label">CA du jour</span>
      </div>
    </div>

    <!-- Actions rapides -->
    <div class="staff-actions" style="margin-bottom: 40px;">
      <a href="commandes_restaurateur.php" class="staff-btn">Voir toutes les commandes</a>
      <a href="commandes_restaurateur.php?statut=payee" class="staff-btn">Commandes à préparer</a>
      <a href="commandes_restaurateur.php?statut=en_attente_livreur" class="staff-btn">En attente livreur</a>
    </div>

    <!-- Dernières commandes -->
    <div class="staff-card">
      <div class="staff-card-header">
        <span class="staff-card-title">Dernières commandes</span>
        <a href="commandes_restaurateur.php" class="staff-card-link">Tout voir →</a>
      </div>
      <div class="staff-card-body">
        <?php if (empty($recentOrders)): ?>
          <div class="staff-empty">Aucune commande pour le moment.</div>
        <?php else: ?>
          <div class="staff-table-wrap">
            <table class="staff-table">
              <thead>
                <tr>
                  <th>N°</th>
                  <th>Date</th>
                  <th>Client</th>
                  <th>Type</th>
                  <th>Statut</th>
                  <th>Total</th>
                  <th>Programmée</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentOrders as $ro): ?>
                  <tr>
                    <td><?= $ro['id'] ?></td>
                    <td><?= h($ro['created_at']) ?></td>
                    <td><?= h($ro['first_name'] . ' ' . $ro['last_name']) ?></td>
                    <td><?= h($typeLabels[$ro['order_type']] ?? $ro['order_type']) ?></td>
                    <td><span class="staff-badge staff-badge--<?= $ro['status'] ?>"><?= h($statusLabels[$ro['status']] ?? $ro['status']) ?></span></td>
                    <td><?= number_format($orderTotals[$ro['id']] ?? 0, 2, ',', ' ') ?> €</td>
                    <td><?= $ro['scheduled_datetime'] ? h($ro['scheduled_datetime']) : 'Immédiate' ?></td>
                    <td><a href="detail_commande.php?id=<?= $ro['id'] ?>" class="staff-btn staff-btn--sm">Détail</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</main>

<?php include 'includes/staff_footer.php'; ?>
