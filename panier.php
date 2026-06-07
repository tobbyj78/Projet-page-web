<?php
session_start();
require_once 'database.php';
require_once 'functions.php';

$pdo = getDatabaseConnection();

// Non connecté → login ; staff → dashboard
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php?redirect=panier.php');
    exit;
}
if ($_SESSION['user_role'] !== 'client') {
    header('Location: ' . getRoleRedirect($_SESSION['user_role']));
    exit;
}

// Le panier est stocké en session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Ajouter un article au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $action = $_POST['action'];

    if ($action === 'add') {
        $itemId   = (int) ($_POST['item_id'] ?? 0);
        $itemType = $_POST['item_type'] ?? '';
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

        if ($itemId > 0 && in_array($itemType, ['menu', 'dish'])) {
            $key = $itemType . '_' . $itemId;
            if (isset($_SESSION['cart'][$key])) {
                $_SESSION['cart'][$key]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$key] = [
                    'item_id'   => $itemId,
                    'item_type' => $itemType,
                    'quantity'  => $quantity,
                ];
            }
        }
    }

    if ($action === 'remove') {
        $key = $_POST['cart_key'] ?? '';
        unset($_SESSION['cart'][$key]);
    }

    if ($action === 'update') {
        $key      = $_POST['cart_key'] ?? '';
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        if (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['quantity'] = $quantity;
        }
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        unset($_SESSION['modify_order_id'], $_SESSION['modify_original_total'], $_SESSION['modify_cart_snapshot'], $_SESSION['modify_order_meta']);
    }

    if ($action === 'cancel_modify') {
        unset($_SESSION['modify_order_id'], $_SESSION['modify_original_total'], $_SESSION['modify_cart_snapshot'], $_SESSION['modify_order_meta']);
    }

    // PRG : éviter la resoumission
    $redirect = $_POST['redirect'] ?? 'panier.php';
    // Sécurité : n'accepter que des redirections locales
    if (!in_array($redirect, ['panier.php', 'catalogue.php', 'profil.php'])) {
        $redirect = 'panier.php';
    }
    header('Location: ' . $redirect);
    exit;
}

// Charger les détails des articles du panier
$cartItems = [];
$totalPrice = 0;

foreach ($_SESSION['cart'] as $key => $item) {
    if ($item['item_type'] === 'dish') {
        $stmt = $pdo->prepare("SELECT id, name, price FROM dishes WHERE id = :id");
        $stmt->execute(['id' => $item['item_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT id, name, total_price AS price FROM menus WHERE id = :id");
        $stmt->execute(['id' => $item['item_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($row) {
        $subtotal = $row['price'] * $item['quantity'];
        $totalPrice += $subtotal;
        $cartItems[] = [
            'key'       => $key,
            'name'      => $row['name'],
            'type'      => $item['item_type'] === 'menu' ? 'Menu' : 'Plat',
            'price'     => $row['price'],
            'quantity'  => $item['quantity'],
            'subtotal'  => $subtotal,
        ];
    }
}
?>

<?php
// Contexte de modification de commande payée
$modifyOrderId = $_SESSION['modify_order_id'] ?? null;
$modifyOriginalTotal = $_SESSION['modify_original_total'] ?? null;
?>

<?php $pageTitle = 'Panier'; $pageCss = 'panier.css'; ?>
<?php include 'includes/header.php'; ?>

<div class="cart-page">

  <header class="cart-header">
    <a href="catalogue.php" class="cart-back">← Retour au catalogue</a>
    <h1 class="cart-title">Votre <em>Panier</em></h1>
  </header>

  <?php if ($modifyOrderId): ?>
    <!-- Bannière modification commande payée -->
    <div class="cart-modify-banner">
      <div class="cart-modify-banner-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
      </div>
      <div class="cart-modify-banner-text">
        <span class="cart-modify-banner-title">Modification de la commande n°<?= (int)$modifyOrderId ?></span>
        <span class="cart-modify-banner-hint">Vous pouvez ajouter ou retirer des articles. Si le nouveau total est <strong>plus élevé</strong>, vous paierez uniquement la différence. S'il est <strong>moins élevé</strong>, la différence n'est pas remboursée.</span>
        <div class="cart-modify-totals">
          <div class="cart-modify-total-row">
            <span>Total initial</span>
            <span><?= number_format($modifyOriginalTotal, 2, ',', ' ') ?> €</span>
          </div>
          <div class="cart-modify-total-row <?= $totalPrice > $modifyOriginalTotal ? 'cart-modify-total-row--up' : ($totalPrice < $modifyOriginalTotal ? 'cart-modify-total-row--down' : '') ?>">
            <span>Nouveau total</span>
            <span><?= number_format($totalPrice, 2, ',', ' ') ?> €</span>
          </div>
          <?php $diff = $totalPrice - $modifyOriginalTotal; ?>
          <?php if ($diff > 0): ?>
          <div class="cart-modify-total-row cart-modify-total-row--diff">
            <span>Supplément à payer</span>
            <span>+<?= number_format($diff, 2, ',', ' ') ?> €</span>
          </div>
          <?php elseif ($diff < 0): ?>
          <div class="cart-modify-total-row cart-modify-total-row--diff-down">
            <span>Différence non remboursée</span>
            <span>−<?= number_format(abs($diff), 2, ',', ' ') ?> €</span>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <a href="profil.php" class="cart-modify-banner-cancel" title="Annuler la modification"
         onclick="event.preventDefault(); document.getElementById('cancel-modify-form').submit();">✕</a>
    </div>
  <?php endif; ?>

  <!-- Formulaire caché pour annuler la modification -->
  <?php if ($modifyOrderId): ?>
    <form id="cancel-modify-form" action="panier.php" method="post" style="display:none;">
      <input type="hidden" name="action" value="cancel_modify">
      <input type="hidden" name="redirect" value="profil.php">
    </form>
  <?php endif; ?>

  <?php if (empty($cartItems)): ?>

    <div class="cart-empty">
      <span class="cart-empty-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span>
      <p class="cart-empty-text">Votre panier est vide.</p>
      <a href="catalogue.php" class="cart-empty-cta">Découvrir la carte</a>
    </div>

  <?php else: ?>

    <div class="cart-list">
      <?php foreach ($cartItems as $ci): ?>
        <div class="cart-item">

          <div class="cart-item-main">
            <span class="cart-item-type"><?= h($ci['type']) ?></span>
            <span class="cart-item-name"><?= h($ci['name']) ?></span>
            <span class="cart-item-price"><?= number_format($ci['price'], 2, ',', ' ') ?>&nbsp;€ l'unité</span>
          </div>

          <div class="cart-item-actions">

            <!-- Quantité -->
            <form action="panier.php" method="post" class="cart-qty-group">
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="cart_key" value="<?= h($ci['key']) ?>">
              <button type="submit" name="quantity" value="<?= max(1, $ci['quantity'] - 1) ?>" class="cart-qty-btn" aria-label="Réduire la quantité"<?= $ci['quantity'] <= 1 ? ' disabled' : '' ?>>−</button>
              <span class="cart-qty-value"><?= $ci['quantity'] ?></span>
              <button type="submit" name="quantity" value="<?= $ci['quantity'] + 1 ?>" class="cart-qty-btn" aria-label="Augmenter la quantité">+</button>
            </form>

            <span class="cart-item-subtotal"><?= number_format($ci['subtotal'], 2, ',', ' ') ?>&nbsp;€</span>

            <!-- Supprimer -->
            <form action="panier.php" method="post">
              <input type="hidden" name="action" value="remove">
              <input type="hidden" name="cart_key" value="<?= h($ci['key']) ?>">
              <button type="submit" class="cart-item-remove" aria-label="Retirer l'article">✕</button>
            </form>

          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="cart-footer">

      <div class="cart-summary">
        <span class="cart-summary-label">Total</span>
        <span class="cart-summary-total"><?= number_format($totalPrice, 2, ',', ' ') ?>&nbsp;€</span>
      </div>

      <div class="cart-actions">
        <a href="validation.php" class="cart-btn cart-btn--primary">
          <?= $modifyOrderId ? 'Mettre à jour la commande' : 'Valider la commande' ?>
        </a>

        <form action="panier.php" method="post">
          <input type="hidden" name="action" value="clear">
          <button type="submit" class="cart-btn cart-btn--ghost">Vider le panier</button>
        </form>
      </div>

    </div>

  <?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>