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

if (!$currentUser || ($currentUser['role'] !== 'restaurateur' && $currentUser['role'] !== 'admin')) {
    header('Location: index.php');
    exit;
}

$orderId = (int) ($_GET['id'] ?? 0);
if ($orderId <= 0) {
    header('Location: commandes_restaurateur.php');
    exit;
}

$message = '';
$error = '';

// Assignation livreur
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
        $error = 'La commande doit être en attente d\'un livreur.';
    } elseif ($selectedLivreurId <= 0) {
        $error = 'Veuillez sélectionner un livreur.';
    } else {
        $stmtLivreur = $pdo->prepare("SELECT id FROM users WHERE id = :id AND role = 'livreur'");
        $stmtLivreur->execute(['id' => $selectedLivreurId]);
        if (!$stmtLivreur->fetch()) {
            $error = 'Livreur invalide.';
        } else {
            $stmtBusy = $pdo->prepare("SELECT id FROM orders WHERE delivery_person_id = :livreur_id AND status = 'en_livraison' LIMIT 1");
            $stmtBusy->execute(['livreur_id' => $selectedLivreurId]);
            if ($stmtBusy->fetch()) {
                $error = 'Ce livreur est déjà en livraison.';
            } else {
                $stmtAssign = $pdo->prepare("
                    UPDATE orders
                    SET delivery_person_id = :livreur_id, status = 'en_livraison'
                    WHERE id = :id AND order_type = 'livraison' AND status = 'en_attente_livreur'
                ");
                $stmtAssign->execute(['livreur_id' => $selectedLivreurId, 'id' => $orderId]);

                if ($stmtAssign->rowCount() > 0) {
                    $_SESSION['resto_message'] = 'Livreur assigné avec succès. La commande est maintenant en livraison.';
                } else {
                    $_SESSION['resto_error'] = 'Assignation impossible. La commande a peut-être déjà été modifiée.';
                }
                header('Location: detail_commande.php?id=' . $orderId);
                exit;
            }
        }
    }
}

// ── Actions restaurateur : changer le statut ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'preparer') {
    $stmtOrder = $pdo->prepare("SELECT id, status, scheduled_datetime FROM orders WHERE id = :id");
    $stmtOrder->execute(['id' => $orderId]);
    $orderToPrep = $stmtOrder->fetch(PDO::FETCH_ASSOC);

    if (!$orderToPrep) {
        $error = 'Commande introuvable.';
    } elseif ($orderToPrep['status'] !== 'payee') {
        $error = 'Seule une commande payée peut passer en préparation.';
    } elseif ($orderToPrep['scheduled_datetime'] && strtotime($orderToPrep['scheduled_datetime']) > time()) {
        $error = 'Cette commande est programmée pour ' . $orderToPrep['scheduled_datetime'] . '. Impossible de la préparer maintenant.';
    } else {
        $stmtPrep = $pdo->prepare("UPDATE orders SET status = 'en_preparation' WHERE id = :id AND status = 'payee'");
        $stmtPrep->execute(['id' => $orderId]);
        if ($stmtPrep->rowCount() > 0) {
            $_SESSION['resto_message'] = 'Commande n°' . $orderId . ' passée en préparation.';
        } else {
            $_SESSION['resto_error'] = 'Impossible de changer le statut.';
        }
        header('Location: detail_commande.php?id=' . $orderId);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'preter') {
    $stmtOrder = $pdo->prepare("SELECT id, status, order_type FROM orders WHERE id = :id");
    $stmtOrder->execute(['id' => $orderId]);
    $orderToMark = $stmtOrder->fetch(PDO::FETCH_ASSOC);

    if (!$orderToMark) {
        $error = 'Commande introuvable.';
    } elseif ($orderToMark['status'] !== 'en_preparation') {
        $error = 'La commande doit être en préparation.';
    } else {
        // Si livraison → en_attente_livreur automatiquement
        // Sinon → prete (le resto pourra ensuite servir/remettre)
        $newStatus = ($orderToMark['order_type'] === 'livraison') ? 'en_attente_livreur' : 'prete';
        $stmtMark = $pdo->prepare("UPDATE orders SET status = :new WHERE id = :id AND status = 'en_preparation'");
        $stmtMark->execute(['new' => $newStatus, 'id' => $orderId]);
        if ($stmtMark->rowCount() > 0) {
            $msg = ($newStatus === 'en_attente_livreur')
                ? 'Commande prête. En attente d\'un livreur.'
                : 'Commande marquée comme prête.';
            $_SESSION['resto_message'] = $msg;
        } else {
            $_SESSION['resto_error'] = 'Impossible de changer le statut.';
        }
        header('Location: detail_commande.php?id=' . $orderId);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'servir') {
    $stmtOrder = $pdo->prepare("SELECT id, status, order_type FROM orders WHERE id = :id");
    $stmtOrder->execute(['id' => $orderId]);
    $orderToServe = $stmtOrder->fetch(PDO::FETCH_ASSOC);

    if (!$orderToServe) {
        $error = 'Commande introuvable.';
    } elseif ($orderToServe['status'] !== 'prete') {
        $error = 'La commande doit être prête.';
    } elseif ($orderToServe['order_type'] === 'livraison') {
        $error = 'Une commande livraison ne peut pas être servie directement. Assignez un livreur.';
    } else {
        $stmtServe = $pdo->prepare("UPDATE orders SET status = 'livree' WHERE id = :id AND status = 'prete'");
        $stmtServe->execute(['id' => $orderId]);
        if ($stmtServe->rowCount() > 0) {
            $_SESSION['resto_message'] = 'Commande n°' . $orderId . ' marquée comme servie / remise.';
        } else {
            $_SESSION['resto_error'] = 'Impossible de changer le statut.';
        }
        header('Location: detail_commande.php?id=' . $orderId);
        exit;
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

// Récupérer la commande
$order = getOrderById($pdo, $orderId);
if (!$order) {
    header('Location: commandes_restaurateur.php');
    exit;
}

$items = getOrderItems($pdo, $orderId);
$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['quantity'];
}

$livreurs = getAllLivreurs($pdo);
$stmtBusyLivreurs = $pdo->query("
    SELECT DISTINCT delivery_person_id FROM orders
    WHERE status = 'en_livraison' AND delivery_person_id IS NOT NULL
");
$busyLivreurIds = array_map('intval', array_column($stmtBusyLivreurs->fetchAll(PDO::FETCH_ASSOC), 'delivery_person_id'));

$assignedLivreur = null;
if ($order['delivery_person_id']) {
    $stmtL = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = :id");
    $stmtL->execute(['id' => $order['delivery_person_id']]);
    $assignedLivreur = $stmtL->fetch(PDO::FETCH_ASSOC);
}

$statusLabels = [
    'en_attente'         => 'En attente',
    'payee'              => 'Payée',
    'en_attente_livreur' => 'En attente d\'un livreur',
    'en_preparation'     => 'En préparation',
    'prete'              => 'Prête',
    'en_livraison'       => 'En livraison',
    'livree'             => 'Livrée',
    'refusee'            => 'Refusée',
    'abandonnee'         => 'Abandonnée',
];
?>

<?php
$staffRole       = $currentUser['role'];
$staffActivePage = 'orders';
$staffPageTitle  = 'Détail commande n°' . $orderId;
include 'includes/staff_header.php';
?>

<main class="staff-page">
  <div class="staff-inner">

    <header class="staff-header">
      <a href="commandes_restaurateur.php" style="display:inline-block; margin-bottom:12px; color:var(--accent); font-size:0.8rem; text-decoration:none;">← Retour à la liste</a>
      <h1 class="staff-title">Commande <em>n°<?= $orderId ?></em></h1>
    </header>

    <?php if ($message): ?>
      <div class="staff-flash staff-flash--success"><?= h($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="staff-flash staff-flash--error"><?= h($error) ?></div>
    <?php endif; ?>

    <!-- Statut & Type -->
    <div class="staff-stats" style="margin-bottom: 32px;">
      <div class="staff-stat-card">
        <span class="staff-stat-value" style="font-size: 1.25rem;"><?= h($statusLabels[$order['status']] ?? $order['status']) ?></span>
        <span class="staff-stat-label">Statut</span>
      </div>
      <div class="staff-stat-card">
        <span class="staff-stat-value" style="font-size: 1.25rem;"><?= h(ucfirst($order['order_type'])) ?></span>
        <span class="staff-stat-label">Type</span>
      </div>
      <div class="staff-stat-card">
        <span class="staff-stat-value" style="font-size: 1.25rem;"><?= $order['scheduled_datetime'] ? h($order['scheduled_datetime']) : 'Immédiate' ?></span>
        <span class="staff-stat-label">Prévue</span>
      </div>
      <div class="staff-stat-card">
        <span class="staff-stat-value" style="font-size: 1.25rem;"><?= h($order['created_at']) ?></span>
        <span class="staff-stat-label">Passée le</span>
      </div>
    </div>

    <!-- Client -->
    <div class="staff-card" style="margin-bottom: 24px;">
      <div class="staff-card-header">
        <span class="staff-card-title">Client</span>
      </div>
      <div class="staff-card-body">
        <div class="staff-table-wrap">
          <table class="staff-table">
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
            <?php if (!empty($order['address_info'])): ?>
            <tr>
              <th>Complément</th>
              <td><?= h($order['address_info']) ?></td>
            </tr>
            <?php endif; ?>
          </table>
        </div>
      </div>
    </div>

    <!-- Livraison -->
    <?php if ($order['order_type'] === 'livraison'): ?>
    <div class="staff-card" style="margin-bottom: 24px;">
      <div class="staff-card-header">
        <span class="staff-card-title">Livraison</span>
      </div>
      <div class="staff-card-body">
        <div class="staff-table-wrap">
          <table class="staff-table">
            <tr>
              <th>Adresse de livraison</th>
              <td><?= h($order['delivery_address']) ?></td>
            </tr>
            <?php if ($assignedLivreur): ?>
            <tr>
              <th>Livreur assigné</th>
              <td><?= h($assignedLivreur['first_name'] . ' ' . $assignedLivreur['last_name']) ?></td>
            </tr>
            <?php endif; ?>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Articles -->
    <div class="staff-card" style="margin-bottom: 24px;">
      <div class="staff-card-header">
        <span class="staff-card-title">Articles commandés</span>
        <span style="font-size:0.75rem;color:var(--text-muted);"><?= count($items) ?> article(s)</span>
      </div>
      <div class="staff-card-body">
        <div class="staff-table-wrap">
          <table class="staff-table">
            <thead>
              <tr>
                <th>Type</th>
                <th>Article</th>
                <th>Prix unitaire</th>
                <th>Qté</th>
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
        </div>
        <div style="padding: 16px 20px; text-align: right; font-family: var(--font-display); font-size: 1.5rem; color: var(--accent);">
          Total : <?= number_format($total, 2, ',', ' ') ?> €
        </div>
      </div>
    </div>

    <!-- Actions restaurateur : changer le statut -->
    <?php if ($currentUser['role'] === 'restaurateur'): ?>
    <?php
      $canPrepare  = ($order['status'] === 'payee' && (!$order['scheduled_datetime'] || strtotime($order['scheduled_datetime']) <= time()));
      $canMarkPrete = ($order['status'] === 'en_preparation');
      $canServe    = ($order['status'] === 'prete' && $order['order_type'] !== 'livraison');
      $isScheduled = ($order['scheduled_datetime'] && strtotime($order['scheduled_datetime']) > time());
    ?>
    <?php if ($canPrepare || $canMarkPrete || $canServe || $isScheduled): ?>
    <div class="staff-card" style="margin-bottom: 24px;">
      <div class="staff-card-header">
        <span class="staff-card-title">Actions</span>
      </div>
      <div class="staff-card-body" style="padding: 20px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">

        <?php if ($isScheduled && $order['status'] === 'payee'): ?>
          <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0; width: 100%;">
            ⏳ Commande programmée pour le <strong><?= h($order['scheduled_datetime']) ?></strong>.
            La préparation pourra démarrer à cette date.
          </p>
        <?php endif; ?>

        <?php if ($canPrepare): ?>
          <form action="detail_commande.php?id=<?= $orderId ?>" method="post" style="display:inline;">
            <input type="hidden" name="action" value="preparer">
            <button type="submit" class="staff-btn">🔪 Préparer</button>
          </form>
        <?php endif; ?>

        <?php if ($canMarkPrete): ?>
          <form action="detail_commande.php?id=<?= $orderId ?>" method="post" style="display:inline;">
            <input type="hidden" name="action" value="preter">
            <button type="submit" class="staff-btn">✅ Marquer comme prête</button>
          </form>
        <?php endif; ?>

        <?php if ($canServe): ?>
          <form action="detail_commande.php?id=<?= $orderId ?>" method="post" style="display:inline;">
            <input type="hidden" name="action" value="servir">
            <button type="submit" class="staff-btn">🍽️ <?= $order['order_type'] === 'emporter' ? 'Remise au client' : 'Servie' ?></button>
          </form>
        <?php endif; ?>

      </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <!-- Assignation livreur -->
    <?php if ($order['order_type'] === 'livraison' && $order['status'] === 'en_attente_livreur'): ?>
    <div class="staff-card">
      <div class="staff-card-header">
        <span class="staff-card-title">Assigner un livreur</span>
      </div>
      <div class="staff-card-body" style="padding: 20px;">
        <form action="detail_commande.php?id=<?= $orderId ?>" method="post" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
          <input type="hidden" name="action" value="assign_livreur">
          <select name="livreur_id" required style="
            padding: 10px 16px;
            font-family: var(--font-ui);
            font-size: 0.8rem;
            color: var(--text-primary);
            background: var(--surface);
            border: 1px solid var(--border-subtle);
            min-width: 240px;
          ">
            <option value="">— Choisir un livreur —</option>
            <?php foreach ($livreurs as $livreur): ?>
              <?php $isBusy = in_array((int) $livreur['id'], $busyLivreurIds, true); ?>
              <option value="<?= $livreur['id'] ?>" <?= $isBusy ? 'disabled' : '' ?>>
                <?= h($livreur['first_name'] . ' ' . $livreur['last_name']) ?><?= $isBusy ? ' (indisponible)' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="staff-btn">Assigner</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

  </div>
</main>

<?php include 'includes/staff_footer.php'; ?>
