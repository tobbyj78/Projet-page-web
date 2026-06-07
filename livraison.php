<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
require_once 'models/orders.php';

$pdo = getDatabaseConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $orderId = (int) ($_POST['order_id'] ?? 0);

    if ($action === 'prendre' && $orderId > 0) {
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

    $_SESSION['livreur_message'] = $message;
    header('Location: livraison.php');
    exit;
}

if (isset($_SESSION['livreur_message'])) {
    $message = $_SESSION['livreur_message'];
    unset($_SESSION['livreur_message']);
}

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

// Historique complet
$stmtHistory = $pdo->prepare("
    SELECT o.id, o.created_at, o.status, o.delivery_address,
           u.first_name, u.last_name
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE o.delivery_person_id = :lid
      AND o.status IN ('livree', 'abandonnee')
    ORDER BY o.id DESC
    LIMIT 50
");
$stmtHistory->execute(['lid' => $_SESSION['user_id']]);
$deliveryHistory = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

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
?>

<?php
$staffRole       = 'livreur';
$staffActivePage = 'deliveries';
$staffPageTitle  = 'Mes livraisons';
include 'includes/staff_header.php';
?>

<main class="staff-page">
  <div class="staff-inner">

    <header class="staff-header">
      <p class="staff-label">Espace livreur</p>
      <h1 class="staff-title">Mes <em>livraisons</em></h1>
    </header>

    <?php if ($message): ?>
      <div class="staff-flash staff-flash--success"><?= h($message) ?></div>
    <?php endif; ?>

    <!-- Livraison en cours -->
    <?php if ($delivery): ?>
      <div class="staff-card" style="margin-bottom: 32px;">
        <div class="staff-card-header">
          <span class="staff-card-title">Livraison en cours — Commande n°<?= $delivery['id'] ?></span>
        </div>
        <div class="staff-card-body">
          <div class="staff-table-wrap">
            <table class="staff-table">
              <tr><th>Client</th><td><?= h($delivery['first_name'] . ' ' . $delivery['last_name']) ?></td></tr>
              <tr><th>Téléphone</th><td><?= h($delivery['phone']) ?></td></tr>
              <tr><th>Adresse de livraison</th><td><?= h($delivery['delivery_address'] ?? $delivery['address']) ?></td></tr>
              <?php if (!empty($delivery['address_info'])): ?>
              <tr><th>Complément</th><td><?= h($delivery['address_info']) ?></td></tr>
              <?php endif; ?>
              <tr><th>Date de commande</th><td><?= h($delivery['created_at']) ?></td></tr>
              <?php if ($delivery['scheduled_datetime']): ?>
              <tr><th>Livraison prévue</th><td><?= h($delivery['scheduled_datetime']) ?></td></tr>
              <?php endif; ?>
            </table>
          </div>

          <?php
            $navAddress = $delivery['delivery_address'] ?? $delivery['address'] ?? '';
            $navEncoded = urlencode($navAddress);
            $mapsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . $navEncoded;
            $wazeUrl = 'https://waze.com/ul?q=' . $navEncoded;
          ?>
          <div class="staff-actions" style="padding: 16px 20px;">
            <span style="display:block; font-size:0.7rem; color:var(--text-muted); margin-bottom:10px;">Naviguer vers : <strong><?= h($navAddress) ?></strong></span>
            <div style="display:flex; gap:12px;">
              <a href="<?= h($mapsUrl) ?>" target="_blank" rel="noopener" class="staff-btn staff-btn--maps">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.4 8 12 8 12s8-6.6 8-12a8 8 0 0 0-8-8Z"/></svg>
                Google Maps
              </a>
              <a href="<?= h($wazeUrl) ?>" target="_blank" rel="noopener" class="staff-btn staff-btn--waze">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><path d="m12 2 10 7.5-3 8.5H5L2 9.5Z"/><circle cx="12" cy="10" r="3"/></svg>
                Waze
              </a>
            </div>
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
    <?php endif; ?>

    <!-- Commandes disponibles -->
    <div class="staff-card" style="margin-bottom: 32px;">
      <div class="staff-card-header">
        <span class="staff-card-title">Commandes en attente d'un livreur</span>
        <span class="staff-card-link"><?= count($waitingOrders) ?> commande(s)</span>
      </div>
      <div class="staff-card-body">
        <?php if (empty($waitingOrders)): ?>
          <div class="staff-empty">Aucune commande à livrer pour le moment.</div>
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

    <!-- Historique -->
    <?php if (!empty($deliveryHistory)): ?>
    <div class="staff-card">
      <div class="staff-card-header">
        <span class="staff-card-title">Historique des livraisons</span>
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
                <th>Statut</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($deliveryHistory as $dh): ?>
                <tr>
                  <td><?= $dh['id'] ?></td>
                  <td><?= h($dh['created_at']) ?></td>
                  <td><?= h($dh['first_name'] . ' ' . $dh['last_name']) ?></td>
                  <td><?= h($dh['delivery_address']) ?></td>
                  <td><span class="staff-badge staff-badge--<?= $dh['status'] ?>"><?= h($statusLabels[$dh['status']] ?? $dh['status']) ?></span></td>
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
