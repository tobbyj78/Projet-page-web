<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
require_once 'models/users.php';

$pdo = getDatabaseConnection();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// ── Mode admin : consulter le profil d'un autre utilisateur ──
$viewUserId = (int)($_GET['id'] ?? 0);
$isAdminView = ($viewUserId > 0 && $_SESSION['user_role'] === 'admin' && $viewUserId !== $_SESSION['user_id']);

if ($isAdminView) {
    // Bloquer toutes les actions POST : l'admin ne peut pas modifier un autre profil
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Location: profil.php?id=' . $viewUserId);
        exit;
    }
}

// ── Récupérer le panier d'une commande en attente ────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'recover_cart') {
    $orderId = (int)($_POST['order_id'] ?? 0);

    $stmtCheck = $pdo->prepare("SELECT id, status FROM orders WHERE id = :id AND user_id = :uid AND status = 'en_attente'");
    $stmtCheck->execute(['id' => $orderId, 'uid' => $_SESSION['user_id']]);
    $pending = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($pending) {
        $stmtItems = $pdo->prepare("SELECT item_id, item_type, quantity FROM order_items WHERE order_id = :oid");
        $stmtItems->execute(['oid' => $orderId]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Restaurer le panier
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        foreach ($items as $it) {
            $key = $it['item_type'] . '_' . $it['item_id'];
            if (isset($_SESSION['cart'][$key])) {
                $_SESSION['cart'][$key]['quantity'] += (int)$it['quantity'];
            } else {
                $_SESSION['cart'][$key] = [
                    'item_id'   => (int)$it['item_id'],
                    'item_type' => $it['item_type'],
                    'quantity'  => (int)$it['quantity'],
                ];
            }
        }

        // Supprimer la commande en attente
        $pdo->prepare("DELETE FROM order_items WHERE order_id = :oid")->execute(['oid' => $orderId]);
        $pdo->prepare("DELETE FROM orders WHERE id = :id")->execute(['id' => $orderId]);

        $_SESSION['profil_message'] = 'Panier restauré depuis la commande n°' . $orderId . '.';
        header('Location: panier.php');
        exit;
    }
    $_SESSION['profil_error'] = 'Commande introuvable ou déjà modifiée.';
    header('Location: profil.php');
    exit;
}

// ── Modifier une commande payée ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'modify_order') {
    $orderId = (int)($_POST['order_id'] ?? 0);

    $stmtCheck = $pdo->prepare("SELECT id, status FROM orders WHERE id = :id AND user_id = :uid AND status = 'payee'");
    $stmtCheck->execute(['id' => $orderId, 'uid' => $_SESSION['user_id']]);
    $paidOrder = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($paidOrder) {
        $stmtItems = $pdo->prepare("SELECT item_id, item_type, quantity FROM order_items WHERE order_id = :oid");
        $stmtItems->execute(['oid' => $orderId]);
        $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Restaurer les articles dans le panier
        $_SESSION['cart'] = [];
        foreach ($items as $it) {
            $key = $it['item_type'] . '_' . $it['item_id'];
            $_SESSION['cart'][$key] = [
                'item_id'   => (int)$it['item_id'],
                'item_type' => $it['item_type'],
                'quantity'  => (int)$it['quantity'],
            ];
        }

        // Stocker les infos de modification en session
        $_SESSION['modify_order_id'] = $orderId;
        $_SESSION['modify_original_total'] = getOrderTotal($pdo, $orderId);

        header('Location: panier.php');
        exit;
    }
    $_SESSION['profil_error'] = 'Commande introuvable ou déjà modifiée.';
    header('Location: profil.php');
    exit;
}

// ── Supprimer une commande en attente ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_order') {
    $orderId = (int)($_POST['order_id'] ?? 0);

    $stmtCheck = $pdo->prepare("SELECT id FROM orders WHERE id = :id AND user_id = :uid AND status = 'en_attente'");
    $stmtCheck->execute(['id' => $orderId, 'uid' => $_SESSION['user_id']]);
    if ($stmtCheck->fetch()) {
        $pdo->prepare("DELETE FROM order_items WHERE order_id = :oid")->execute(['oid' => $orderId]);
        $pdo->prepare("DELETE FROM orders WHERE id = :id")->execute(['id' => $orderId]);
        $_SESSION['profil_message'] = 'Commande n°' . $orderId . ' supprimée.';
    } else {
        $_SESSION['profil_error'] = 'Commande introuvable ou déjà modifiée.';
    }
    header('Location: profil.php');
    exit;
}

// ── Traitement AJAX de mise à jour du profil ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    header('Content-Type: application/json; charset=utf-8');

    $field = $_POST['field'] ?? '';
    $value = trim($_POST['value'] ?? '');

    $allowedFields = ['nickname', 'first_name', 'last_name', 'phone', 'birthday', 'address', 'address_info'];

    if (!in_array($field, $allowedFields, true)) {
        echo json_encode(['success' => false, 'message' => 'Champ non autorisé.']);
        exit;
    }

    if (in_array($field, ['first_name', 'last_name'], true) && $value === '') {
        echo json_encode(['success' => false, 'message' => 'Ce champ ne peut pas être vide.']);
        exit;
    }

    if ($field === 'phone' && $value !== '') {
        $value = preg_replace('/[^\d\s\-\+\.]/', '', $value);
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET `{$field}` = :value WHERE id = :id");
        $stmt->execute(['value' => $value, 'id' => $_SESSION['user_id']]);

        if ($field === 'nickname') {
            $_SESSION['user_nickname'] = $value;
        }

        echo json_encode([
            'success' => true,
            'value'   => $value,
            'display' => $value !== '' ? h($value) : '<span class="profil-placeholder">—</span>',
        ]);
    } catch (PDOException $e) {
        error_log("Erreur update profil : " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur technique.']);
    }
    exit;
}

// ── Récupération utilisateur ─────────────────────────────────
$targetUserId = $isAdminView ? $viewUserId : $_SESSION['user_id'];
$user = getUserById($pdo, $targetUserId);
if (!$user) {
    if ($isAdminView) {
        $_SESSION['admin_message'] = 'Utilisateur introuvable.';
        header('Location: admin.php');
        exit;
    }
    header('Location: deconnexion.php');
    exit;
}

// Messages flash (notation + actions profil)
$flashSuccess = $_SESSION['rating_success'] ?? $_SESSION['profil_message'] ?? null;
$flashError   = $_SESSION['rating_error']   ?? $_SESSION['profil_error']   ?? null;
unset($_SESSION['rating_success'], $_SESSION['rating_error'], $_SESSION['profil_message'], $_SESSION['profil_error']);

// Récupérer les commandes (TOUTES, y compris en_attente)
$orders = getOrdersByUserId($pdo, $targetUserId);

// Pré-charger notations et totaux
$ratings = [];
$orderTotals = [];
foreach ($orders as $order) {
    $r = getOrderRating($pdo, $order['id']);
    if ($r) {
        $ratings[$order['id']] = $r;
    }
    $orderTotals[$order['id']] = getOrderTotal($pdo, $order['id']);
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

// Champs éditables du profil
$editableFields = [
    'nickname'     => ['label' => 'Pseudo',         'type' => 'text'],
    'first_name'   => ['label' => 'Prénom',         'type' => 'text'],
    'last_name'    => ['label' => 'Nom',            'type' => 'text'],
    'phone'        => ['label' => 'Téléphone',      'type' => 'tel'],
    'birthday'     => ['label' => 'Date de naissance', 'type' => 'date'],
    'address'      => ['label' => 'Adresse',        'type' => 'text'],
    'address_info' => ['label' => 'Complément',     'type' => 'text'],
];

$readonlyFields = [
    'login'      => ['label' => 'Login',              'value' => $user['login']],
    'role'       => ['label' => 'Rôle',               'value' => $user['role']],
    'created_at' => ['label' => 'Inscrit le',          'value' => $user['created_at']],
    'last_login' => ['label' => 'Dernière connexion', 'value' => $user['last_login'] ?? '—'],
];

// Stats pour les filtres
$statusCounts = [];
foreach ($orders as $o) {
    $s = $o['status'];
    $statusCounts[$s] = ($statusCounts[$s] ?? 0) + 1;
}
$totalOrders = count($orders);
?>

<?php $pageTitle = 'Mon Profil'; $pageCss = 'profil.css'; ?>
<?php include 'includes/header.php'; ?>

<div class="profil-page">

  <!-- ══════════════════════════════════════
       En-tête
       ══════════════════════════════════════ -->
  <header class="profil-header">
    <h1 class="profil-title">Bonjour <em><?= h($user['first_name']) ?></em></h1>

    <?php if ($isAdminView): ?>
    <p class="profil-admin-badge">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M3 21v-2a7 7 0 0 1 7-7h4a7 7 0 0 1 7 7v2"/></svg>
      Profil de <strong><?= h($user['login']) ?></strong> — Rôle&nbsp;: <span class="role-badge role-<?= h($user['role']) ?>"><?= h($user['role']) ?></span>
      <a href="admin.php" class="profil-admin-back">← Retour à l'admin</a>
    </p>
    <?php endif; ?>

    <div class="profil-actions">
      <a href="catalogue.php" class="profil-pill profil-pill--catalogue">
        <span class="profil-pill-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>
        </span>
        <span>Catalogue</span>
      </a>
      <a href="panier.php" class="profil-pill profil-pill--panier">
        <span class="profil-pill-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </span>
        <span>Panier</span>
        <?php
          $cartQty = 0;
          if (!empty($_SESSION['cart'])) {
              foreach ($_SESSION['cart'] as $item) $cartQty += (int)($item['quantity'] ?? 0);
          }
        ?>
        <?php if ($cartQty > 0): ?>
          <span class="profil-pill-badge"><?= $cartQty ?></span>
        <?php endif; ?>
      </a>
      <a href="deconnexion.php" class="profil-pill profil-pill--logout">
        <span class="profil-pill-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        </span>
        <span>Déconnexion</span>
      </a>
    </div>
  </header>

  <!-- ── Flash messages ──────────────────────────────────────── -->
  <?php if ($flashSuccess): ?>
    <div class="profil-flash profil-flash--success"><?= h($flashSuccess) ?></div>
  <?php endif; ?>
  <?php if ($flashError): ?>
    <div class="profil-flash profil-flash--error"><?= h($flashError) ?></div>
  <?php endif; ?>

  <!-- ══════════════════════════════════════
       Tabs
       ══════════════════════════════════════ -->
  <nav class="profil-tabs" data-tabs>
    <button type="button" class="profil-tab is-active" data-tab="info">
      <span class="profil-tab-label">Mes informations</span>
      <span class="profil-tab-line" aria-hidden="true"></span>
    </button>
    <button type="button" class="profil-tab" data-tab="orders">
      <span class="profil-tab-label">Historique</span>
      <span class="profil-tab-count"><?= $totalOrders ?></span>
      <span class="profil-tab-line" aria-hidden="true"></span>
    </button>
  </nav>

  <!-- ══════════════════════════════════════
       Panel : Mes informations
       ══════════════════════════════════════ -->
  <div class="profil-tab-panel is-active" data-panel="info">
    <section class="profil-section">

      <div class="profil-card">
        <?php foreach ($editableFields as $field => $cfg):
          $rawValue = $user[$field] ?? '';
          $display  = $rawValue !== '' ? h($rawValue) : '<span class="profil-placeholder">—</span>';
        ?>
        <div class="profil-row<?= $isAdminView ? ' profil-row--readonly' : '' ?>" data-editable data-field="<?= $field ?>" data-type="<?= $cfg['type'] ?>">
          <span class="profil-row-label"><?= h($cfg['label']) ?></span>

          <div class="profil-row-value-wrap">
            <span class="profil-row-value" data-display><?= $display ?></span>

            <?php if (!$isAdminView): ?>
            <div class="profil-row-edit" data-edit-wrap style="display:none;">
              <input
                type="<?= $cfg['type'] ?>"
                class="profil-row-input"
                data-input
                value="<?= h($rawValue) ?>"
                placeholder="<?= h($cfg['label']) ?>"
              >
              <div class="profil-row-edit-actions">
                <button type="button" class="profil-row-save" data-save aria-label="Enregistrer">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20,6 9,17 4,12"/></svg>
                </button>
                <button type="button" class="profil-row-cancel" data-cancel aria-label="Annuler">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <?php if (!$isAdminView): ?>
          <button type="button" class="profil-row-pencil" data-pencil aria-label="Modifier <?= h($cfg['label']) ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
          </button>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div class="profil-divider"></div>

        <?php foreach ($readonlyFields as $field => $cfg): ?>
        <div class="profil-row profil-row--readonly">
          <span class="profil-row-label"><?= h($cfg['label']) ?></span>
          <span class="profil-row-value profil-row-value--muted"><?= h($cfg['value']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="profil-toast" data-toast aria-live="polite"></div>
    </section>
  </div>

  <!-- ══════════════════════════════════════
       Panel : Historique des commandes
       ══════════════════════════════════════ -->
  <div class="profil-tab-panel" data-panel="orders" style="display:none;">
    <section class="profil-section">

      <?php if (empty($orders)): ?>
        <div class="profil-empty">
          <span class="profil-empty-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14,2 14,8 20,8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          </span>
          <p class="profil-empty-text">Vous n'avez pas encore passé de commande.</p>
          <a href="catalogue.php" class="profil-empty-cta">Découvrir la carte</a>
        </div>
      <?php else: ?>

        <!-- ── Filtres (intégrés au-dessus du tableau) ──────────── -->
        <div class="profil-orders-toolbar" data-orders-toolbar>

          <!-- Filtres statut (pills) -->
          <div class="profil-orders-filters" data-filters>
            <button type="button" class="profil-filter-pill is-active" data-filter="all">
              Toutes<span class="profil-filter-pill-count"><?= $totalOrders ?></span>
            </button>
            <?php
            $filterOrder = ['en_attente', 'payee', 'en_preparation', 'prete', 'en_attente_livreur', 'en_livraison', 'livree', 'refusee', 'abandonnee'];
            foreach ($filterOrder as $st):
              if (!isset($statusCounts[$st])) continue;
            ?>
            <button type="button" class="profil-filter-pill" data-filter="<?= $st ?>">
              <?= h($statusLabels[$st]) ?><span class="profil-filter-pill-count"><?= $statusCounts[$st] ?></span>
            </button>
            <?php endforeach; ?>
          </div>

          <!-- Tri -->
          <div class="profil-orders-sort">
            <label for="profil-sort" class="profil-sort-label">Trier</label>
            <select id="profil-sort" class="profil-sort-select" data-sort>
              <option value="date-desc">Plus récentes</option>
              <option value="date-asc">Plus anciennes</option>
              <option value="price-desc">Prix ↓</option>
              <option value="price-asc">Prix ↑</option>
            </select>
          </div>

        </div>

        <!-- ── Liste des commandes ──────────────────────────────── -->
        <div class="profil-orders" data-orders-list>
          <?php foreach ($orders as $i => $order):
            $isPending = ($order['status'] === 'en_attente');
          ?>
          <div class="profil-order"
               style="animation-delay: <?= $i * 40 ?>ms;"
               data-order-status="<?= $order['status'] ?>"
               data-order-date="<?= strtotime($order['created_at']) ?>"
               data-order-total="<?= $orderTotals[$order['id']] ?>"
               data-order-id="<?= $order['id'] ?>">

            <div class="profil-order-top">
              <span class="profil-order-num">N° <?= $order['id'] ?></span>
              <span class="profil-order-date"><?= h($order['created_at']) ?></span>
              <span class="profil-order-badge profil-order-badge--type"><?= h($typeLabels[$order['order_type']] ?? $order['order_type']) ?></span>
              <span class="profil-order-badge profil-order-badge--status <?= $order['status'] === 'livree' ? 'profil-order-badge--done' : '' ?> <?= $order['status'] === 'refusee' || $order['status'] === 'abandonnee' ? 'profil-order-badge--fail' : '' ?>"><?= h($statusLabels[$order['status']] ?? $order['status']) ?></span>
            </div>

            <p class="profil-order-items"><?= h($order['items_summary']) ?></p>

            <div class="profil-order-bottom">
              <span class="profil-order-total"><?= number_format($orderTotals[$order['id']], 2, ',', ' ') ?>&nbsp;€</span>

              <div class="profil-order-actions">
                <?php $isPaid = ($order['status'] === 'payee'); ?>
                <?php if (!$isAdminView): ?>
                <?php if ($isPending): ?>
                  <!-- Actions pour commande en attente -->
                  <form action="profil.php" method="post" style="display:inline;">
                    <input type="hidden" name="action" value="recover_cart">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <button type="submit" class="profil-order-action profil-order-action--recover" title="Remettre ces articles dans votre panier">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1,4 1,10 7,10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                      Récupérer
                    </button>
                  </form>
                  <form action="profil.php" method="post" style="display:inline;" onsubmit="return confirm('Supprimer cette commande en attente ?');">
                    <input type="hidden" name="action" value="delete_order">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <button type="submit" class="profil-order-action profil-order-action--delete" title="Supprimer cette commande">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3,6 5,6 21,6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                    </button>
                  </form>
                <?php elseif ($isPaid): ?>
                  <!-- Actions pour commande payée : modifier -->
                  <form action="profil.php" method="post" style="display:inline;">
                    <input type="hidden" name="action" value="modify_order">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <button type="submit" class="profil-order-action profil-order-action--modify" title="Ajouter ou retirer des articles">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                      Modifier
                    </button>
                  </form>
                <?php endif; ?>
                <?php endif; ?>

                <!-- Notation -->
                <div class="profil-order-rating">
                  <?php if ($order['status'] === 'livree'): ?>
                    <?php if (isset($ratings[$order['id']])): ?>
                      <span class="profil-order-stars">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                          <span class="<?= $s <= $ratings[$order['id']]['rating'] ? 'filled' : 'empty' ?>">★</span>
                        <?php endfor; ?>
                      </span>
                      <?php if ($ratings[$order['id']]['comment']): ?>
                        <span class="profil-order-comment">«&nbsp;<?= h($ratings[$order['id']]['comment']) ?>&nbsp;»</span>
                      <?php endif; ?>
                    <?php else: ?>
                      <?php if (!$isAdminView): ?>
                      <a href="notation.php?id=<?= $order['id'] ?>" class="profil-rate-btn">Noter</a>
                      <?php endif; ?>
                    <?php endif; ?>
                  <?php elseif (!$isPending): ?>
                    <span class="profil-rate-btn profil-rate-btn--muted">En cours</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>

          </div>
          <?php endforeach; ?>
        </div>

        <p class="profil-orders-empty" data-orders-empty style="display:none;">
          Aucune commande ne correspond à ce filtre.
        </p>

      <?php endif; ?>
    </section>
  </div>

</div>

<!-- ── Script édition inline + tabs + filtres ────────────────── -->
<script>
(function() {
  // ═══════════════════════════════════════
  // Tabs
  // ═══════════════════════════════════════
  const tabs = document.querySelectorAll('[data-tab]');
  const panels = document.querySelectorAll('[data-panel]');

  tabs.forEach(function(tab) {
    tab.addEventListener('click', function() {
      const target = tab.dataset.tab;
      tabs.forEach(function(t) { t.classList.remove('is-active'); });
      tab.classList.add('is-active');
      panels.forEach(function(p) {
        p.style.display = (p.dataset.panel === target) ? '' : 'none';
      });
    });
  });

  // ═══════════════════════════════════════
  // Filtres + tri
  // ═══════════════════════════════════════
  const filterPills = document.querySelectorAll('[data-filter]');
  const sortSelect  = document.querySelector('[data-sort]');
  const ordersList  = document.querySelector('[data-orders-list]');
  const emptyMsg    = document.querySelector('[data-orders-empty]');
  let activeFilter = 'all';

  function applyFilters() {
    if (!ordersList) return;
    const orders = ordersList.querySelectorAll('.profil-order');
    let visible = 0;

    // Collecter les commandes visibles pour le tri
    var visibleOrders = [];

    orders.forEach(function(order) {
      var status = order.dataset.orderStatus;
      var match = (activeFilter === 'all' || status === activeFilter);
      if (match) {
        visibleOrders.push(order);
      }
      order.style.display = match ? '' : 'none';
      if (match) visible++;
    });

    // Tri
    var sortVal = sortSelect ? sortSelect.value : 'date-desc';
    visibleOrders.sort(function(a, b) {
      if (sortVal === 'date-asc')  return parseInt(a.dataset.orderDate) - parseInt(b.dataset.orderDate);
      if (sortVal === 'date-desc') return parseInt(b.dataset.orderDate) - parseInt(a.dataset.orderDate);
      if (sortVal === 'price-asc') return parseFloat(a.dataset.orderTotal) - parseFloat(b.dataset.orderTotal);
      if (sortVal === 'price-desc') return parseFloat(b.dataset.orderTotal) - parseFloat(a.dataset.orderTotal);
      return 0;
    });

    // Ré-ordonner dans le DOM
    visibleOrders.forEach(function(o) { ordersList.appendChild(o); });

    // Afficher/masquer le message "aucune commande"
    if (emptyMsg) {
      emptyMsg.style.display = (visible === 0) ? '' : 'none';
    }
  }

  filterPills.forEach(function(pill) {
    pill.addEventListener('click', function() {
      filterPills.forEach(function(p) { p.classList.remove('is-active'); });
      pill.classList.add('is-active');
      activeFilter = pill.dataset.filter;
      applyFilters();
    });
  });

  if (sortSelect) {
    sortSelect.addEventListener('change', applyFilters);
  }

  // ═══════════════════════════════════════
  // Édition inline (profil)
  // ═══════════════════════════════════════
  const rows = document.querySelectorAll('[data-editable]');
  const toast = document.querySelector('[data-toast]');
  let toastTimer = null;

  function showToast(msg, type) {
    if (!toast) return;
    toast.textContent = msg;
    toast.className = 'profil-toast profil-toast--' + type + ' profil-toast--visible';
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(function() {
      toast.classList.remove('profil-toast--visible');
    }, 2800);
  }

  function resetRow(row) {
    const display  = row.querySelector('[data-display]');
    const editWrap = row.querySelector('[data-edit-wrap]');
    const input    = row.querySelector('[data-input]');
    const pencil   = row.querySelector('[data-pencil]');

    row.classList.remove('is-editing', 'is-saving', 'is-error');
    editWrap.style.display = 'none';
    display.style.display = '';
    pencil.style.display = '';
    input.value = display.textContent.replace(/^—$/, '').trim();
  }

  rows.forEach(function(row) {
    const field     = row.dataset.field;
    const display   = row.querySelector('[data-display]');
    const editWrap  = row.querySelector('[data-edit-wrap]');
    const input     = row.querySelector('[data-input]');
    const saveBtn   = row.querySelector('[data-save]');
    const cancelBtn = row.querySelector('[data-cancel]');
    const pencil    = row.querySelector('[data-pencil]');
    const original  = input.value;

    function enterEdit() {
      row.classList.add('is-editing');
      row.classList.remove('is-saving', 'is-error');
      editWrap.style.display = '';
      display.style.display = 'none';
      pencil.style.display = 'none';
      input.value = display.textContent.replace(/^—$/, '').trim();
      input.focus();
      input.select();
    }

    function save() {
      const newValue = input.value.trim();
      if (newValue === original && newValue === (display.textContent.replace(/^—$/, '').trim())) {
        resetRow(row);
        return;
      }

      row.classList.add('is-saving');

      var formData = new FormData();
      formData.append('action', 'update_profile');
      formData.append('field', field);
      formData.append('value', newValue);

      fetch('profil.php', {
        method: 'POST',
        body: formData,
      })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          display.innerHTML = data.display;
          showToast('«&nbsp;' + row.querySelector('[data-display]').textContent.trim() + '&nbsp;» enregistré', 'success');
          resetRow(row);
        } else {
          row.classList.add('is-error');
          showToast(data.message || 'Erreur', 'error');
          setTimeout(function() { row.classList.remove('is-error'); }, 600);
        }
      })
      .catch(function() {
        row.classList.add('is-error');
        showToast('Erreur réseau', 'error');
        setTimeout(function() { row.classList.remove('is-error'); }, 600);
      });
    }

    pencil.addEventListener('click', enterEdit);
    saveBtn.addEventListener('click', save);

    cancelBtn.addEventListener('click', function() {
      input.value = original;
      resetRow(row);
    });

    input.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') { e.preventDefault(); save(); }
      else if (e.key === 'Escape') { input.value = original; resetRow(row); }
    });

    document.addEventListener('click', function(e) {
      if (row.classList.contains('is-editing') && !row.contains(e.target)) {
        if (input.value.trim() !== original && input.value.trim() !== display.textContent.replace(/^—$/, '').trim()) {
          save();
        } else {
          input.value = original;
          resetRow(row);
        }
      }
    });
  });
})();
</script>

<?php include 'includes/footer.php'; ?>
