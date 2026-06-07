<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
require_once 'models/orders.php';

$pdo = getDatabaseConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT first_name, role FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'livreur') {
    header('Location: index.php');
    exit;
}

$message = '';

if (isset($_SESSION['livreur_message'])) {
    $message = $_SESSION['livreur_message'];
    unset($_SESSION['livreur_message']);
}

// ── Données ─────────────────────────────────────────────────
$delivery = getDeliveryOrder($pdo, $_SESSION['user_id']);

$stmtWaiting = $pdo->query("
    SELECT o.*, u.first_name, u.last_name, u.phone, o.delivery_address, u.address, u.address_info
    FROM orders o
    INNER JOIN users u ON u.id = o.user_id
    WHERE o.status = 'en_attente_livreur'
      AND o.delivery_person_id IS NULL
    ORDER BY o.created_at ASC
");
$waitingOrders = $stmtWaiting->fetchAll(PDO::FETCH_ASSOC);

// Dernières livraisons effectuées par ce livreur
$stmtHistory = $pdo->prepare("
    SELECT o.id, o.created_at, o.delivery_address,
           u.first_name, u.last_name
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE o.delivery_person_id = :lid
      AND o.status IN ('livree', 'abandonnee')
    ORDER BY o.id DESC
    LIMIT 10
");
$stmtHistory->execute(['lid' => $_SESSION['user_id']]);
$deliveryHistory = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

// Stats
$statsToday = $pdo->prepare("
    SELECT COUNT(*) FROM orders
    WHERE delivery_person_id = :lid
      AND status = 'livree'
      AND date(created_at) = date('now')
");
$statsToday->execute(['lid' => $_SESSION['user_id']]);
$deliveredToday = $statsToday->fetchColumn();

$statsTotal = $pdo->prepare("
    SELECT COUNT(*) FROM orders
    WHERE delivery_person_id = :lid AND status = 'livree'
");
$statsTotal->execute(['lid' => $_SESSION['user_id']]);
$deliveredTotal = $statsTotal->fetchColumn();
?>

<?php
$staffRole       = 'livreur';
$staffActivePage = 'dashboard';
$staffPageTitle  = 'Tableau de bord Livreur';
include 'includes/staff_header.php';
?>

<main class="staff-page">
  <div class="staff-inner">

    <header class="staff-header">
      <p class="staff-label">Espace livreur</p>
      <h1 class="staff-title">Bonjour <em><?= h($user['first_name']) ?></em></h1>
    </header>

    <?php if ($message): ?>
      <div class="staff-flash staff-flash--success"><?= h($message) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="staff-stats">
      <div class="staff-stat-card">
        <span class="staff-stat-value"><?= $deliveredToday ?></span>
        <span class="staff-stat-label">Livrées aujourd'hui</span>
      </div>
      <div class="staff-stat-card">
        <span class="staff-stat-value"><?= $deliveredTotal ?></span>
        <span class="staff-stat-label">Total livré</span>
      </div>
      <div class="staff-stat-card">
        <span class="staff-stat-value"><?= count($waitingOrders) ?></span>
        <span class="staff-stat-label">En attente</span>
      </div>
      <?php if ($delivery): ?>
      <div class="staff-stat-card">
        <span class="staff-stat-value">1</span>
        <span class="staff-stat-label">En cours</span>
      </div>
      <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════
         LIVRAISON EN COURS
         ══════════════════════════════════════ -->
    <?php if ($delivery): ?>
      <div class="staff-card">
        <div class="staff-card-header">
          <span class="staff-card-title">Livraison en cours — Commande n°<?= $delivery['id'] ?></span>
        </div>
        <div class="staff-card-body">
          <div class="staff-table-wrap">
            <table class="staff-table">
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
              <?php if (!empty($delivery['address_info'])): ?>
              <tr>
                <th>Complément</th>
                <td><?= h($delivery['address_info']) ?></td>
              </tr>
              <?php endif; ?>
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
          </div>

          <div class="staff-actions" style="padding: 0 20px 24px;">
            <form action="livraison.php" method="post" style="display:inline;">
              <input type="hidden" name="action" value="livree">
              <input type="hidden" name="order_id" value="<?= $delivery['id'] ?>">
              <button type="submit" class="staff-btn">Marquer comme Livrée</button>
            </form>
            <form action="livraison.php" method="post" style="display:inline;">
              <input type="hidden" name="action" value="abandonnee">
              <input type="hidden" name="order_id" value="<?= $delivery['id'] ?>">
              <button type="submit" class="staff-btn staff-btn--danger">Abandonner</button>
            </form>
          </div>

        </div>
      </div>

    <?php else: ?>

      <!-- ══════════════════════════════════════
           COMMANDES DISPONIBLES
           ══════════════════════════════════════ -->
      <div class="staff-card">
        <div class="staff-card-header">
          <span class="staff-card-title">Commandes en attente d'un livreur</span>
          <span class="staff-card-link"><?= count($waitingOrders) ?> commande(s)</span>
        </div>
        <div class="staff-card-body">
          <?php if (empty($waitingOrders)): ?>
            <div class="staff-empty">Aucune commande à livrer pour le moment. Revenez plus tard !</div>
          <?php else: ?>
            <div class="staff-table-wrap">
              <table class="staff-table">
                <thead>
                  <tr>
                    <th>N°</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Téléphone</th>
                    <th>Adresse</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($waitingOrders as $wo): ?>
                    <tr>
                      <td><?= $wo['id'] ?></td>
                      <td><?= h($wo['created_at']) ?></td>
                      <td><?= h($wo['first_name'] . ' ' . $wo['last_name']) ?></td>
                      <td><?= h($wo['phone']) ?></td>
                      <td><?= h($wo['delivery_address'] ?? $wo['address']) ?></td>
                      <td>
                        <form action="livraison.php" method="post">
                          <input type="hidden" name="action" value="prendre">
                          <input type="hidden" name="order_id" value="<?= $wo['id'] ?>">
                          <button type="submit" class="staff-btn staff-btn--sm">Prendre</button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

    <?php endif; ?>

    <!-- ══════════════════════════════════════
         DERNIÈRES LIVRAISONS
         ══════════════════════════════════════ -->
    <?php if (!empty($deliveryHistory)): ?>
    <div class="staff-card">
      <div class="staff-card-header">
        <span class="staff-card-title">Dernières livraisons</span>
        <a href="livraison.php" class="staff-card-link">Tout voir →</a>
      </div>
      <div class="staff-card-body">
        <div class="staff-table-wrap">
          <table class="staff-table">
            <thead>
              <tr>
                <th>N°</th>
                <th>Date</th>
                <th>Client</th>
                <th>Adresse</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($deliveryHistory as $dh): ?>
                <tr>
                  <td><?= $dh['id'] ?></td>
                  <td><?= h($dh['created_at']) ?></td>
                  <td><?= h($dh['first_name'] . ' ' . $dh['last_name']) ?></td>
                  <td><?= h($dh['delivery_address']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</main>

<?php include 'includes/staff_footer.php'; ?>
