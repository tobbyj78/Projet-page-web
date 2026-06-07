<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
require_once 'models/orders.php';
require_once 'models/users.php';

$pdo = getDatabaseConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser || ($currentUser['role'] !== 'restaurateur' && $currentUser['role'] !== 'admin')) {
    header('Location: index.php');
    exit;
}

// Filtre par statut
$filterStatus = $_GET['statut'] ?? null;
$validStatuses = ['en_attente', 'payee', 'en_attente_livreur', 'en_preparation', 'prete', 'en_livraison', 'livree', 'refusee', 'abandonnee'];

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

if ($filterStatus && !in_array($filterStatus, $validStatuses)) {
    $filterStatus = null;
}

$orders = getOrdersByStatus($pdo, $filterStatus);

$orderTotals = [];
foreach ($orders as $order) {
    $orderTotals[$order['id']] = getOrderTotal($pdo, $order['id']);
}

$typeLabels = [
    'sur_place' => 'Sur place',
    'emporter'  => 'À emporter',
    'livraison' => 'Livraison',
];
?>

<?php
$staffRole       = $currentUser['role'];
$staffActivePage = 'orders';
$staffPageTitle  = 'Gestion des commandes';
include 'includes/staff_header.php';
?>

<main class="staff-page">
  <div class="staff-inner">

    <header class="staff-header">
      <p class="staff-label"><?= $currentUser['role'] === 'admin' ? 'Espace administrateur' : 'Espace restaurateur' ?></p>
      <h1 class="staff-title">Gestion des <em>commandes</em></h1>
    </header>

    <!-- Filtres par statut -->
    <div class="staff-card" style="margin-bottom: 32px;">
      <div class="staff-card-header">
        <span class="staff-card-title">Filtrer par statut</span>
        <?php if ($filterStatus): ?>
          <a href="commandes_restaurateur.php" class="staff-card-link">Réinitialiser</a>
        <?php endif; ?>
      </div>
      <div class="staff-card-body" style="padding: 16px 20px; display: flex; flex-wrap: wrap; gap: 8px;">
        <a href="commandes_restaurateur.php" class="staff-badge staff-badge--<?= $filterStatus ? 'client' : 'admin' ?>" style="text-decoration:none;">Toutes</a>
        <a href="commandes_restaurateur.php?statut=payee" class="staff-badge staff-badge--payee" style="text-decoration:none; <?= $filterStatus === 'payee' ? 'background: var(--accent); color: var(--surface-dark);' : '' ?>">À préparer</a>
        <a href="commandes_restaurateur.php?statut=en_attente_livreur" class="staff-badge staff-badge--en_attente_livreur" style="text-decoration:none; <?= $filterStatus === 'en_attente_livreur' ? 'background:#e0b870;color:var(--surface-dark);' : '' ?>">En attente livreur</a>
        <a href="commandes_restaurateur.php?statut=en_preparation" class="staff-badge staff-badge--en_preparation" style="text-decoration:none; <?= $filterStatus === 'en_preparation' ? 'background:#82b482;color:var(--surface-dark);' : '' ?>">En préparation</a>
        <a href="commandes_restaurateur.php?statut=prete" class="staff-badge staff-badge--prete" style="text-decoration:none; <?= $filterStatus === 'prete' ? 'background:#d4a843;color:var(--surface-dark);' : '' ?>">Prêtes</a>
        <a href="commandes_restaurateur.php?statut=en_livraison" class="staff-badge staff-badge--en_livraison" style="text-decoration:none; <?= $filterStatus === 'en_livraison' ? 'background:#82a0c8;color:var(--surface-dark);' : '' ?>">En livraison</a>
        <a href="commandes_restaurateur.php?statut=livree" class="staff-badge staff-badge--livree" style="text-decoration:none; <?= $filterStatus === 'livree' ? 'background:#7ec87e;color:var(--surface-dark);' : '' ?>">Livrées</a>
        <a href="commandes_restaurateur.php?statut=refusee" class="staff-badge staff-badge--refusee" style="text-decoration:none; <?= $filterStatus === 'refusee' ? 'background:#c97a7a;color:var(--surface-dark);' : '' ?>">Refusées</a>
        <a href="commandes_restaurateur.php?statut=abandonnee" class="staff-badge staff-badge--abandonnee" style="text-decoration:none; <?= $filterStatus === 'abandonnee' ? 'background:#c97a7a;color:var(--surface-dark);' : '' ?>">Abandonnées</a>
      </div>
    </div>

    <?php if ($filterStatus): ?>
      <p style="margin-bottom: 24px; color: var(--text-muted); font-size: 0.8rem;">
        Filtre actif : <strong style="color: var(--accent);"><?= h($statusLabels[$filterStatus] ?? $filterStatus) ?></strong>
      </p>
    <?php endif; ?>

    <!-- Tableau des commandes -->
    <div class="staff-card">
      <div class="staff-card-header">
        <span class="staff-card-title">Commandes (<?= count($orders) ?>)</span>
      </div>
      <div class="staff-card-body">
        <?php if (empty($orders)): ?>
          <div class="staff-empty">Aucune commande trouvée.</div>
        <?php else: ?>
          <div class="staff-table-wrap">
            <table class="staff-table">
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
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $order): ?>
                  <tr>
                    <td><?= $order['id'] ?></td>
                    <td><?= h($order['created_at']) ?></td>
                    <td><?= h($order['first_name'] . ' ' . $order['last_name']) ?></td>
                    <td><?= h($order['phone']) ?></td>
                    <td><?= h($typeLabels[$order['order_type']] ?? $order['order_type']) ?></td>
                    <td><span class="staff-badge staff-badge--<?= $order['status'] ?>"><?= h($statusLabels[$order['status']] ?? $order['status']) ?></span></td>
                    <td><?= number_format($orderTotals[$order['id']], 2, ',', ' ') ?> €</td>
                    <td><?= $order['scheduled_datetime'] ? h($order['scheduled_datetime']) : 'Immédiate' ?></td>
                    <td><a href="detail_commande.php?id=<?= $order['id'] ?>" class="staff-btn staff-btn--sm">Voir</a></td>
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
