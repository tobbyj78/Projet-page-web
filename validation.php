<?php
session_start();
require_once 'database.php';
require_once 'functions.php';

$pdo = getDatabaseConnection();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php?redirect=validation.php');
    exit;
}

// Staff interdit
if ($_SESSION['user_role'] !== 'client') {
    header('Location: ' . getRoleRedirect($_SESSION['user_role']));
    exit;
}

// Récupérer l'adresse du profil client (pré-remplissage livraison)
$stmtUser = $pdo->prepare("SELECT address, address_info FROM users WHERE id = :id");
$stmtUser->execute(['id' => $_SESSION['user_id']]);
$userProfile = $stmtUser->fetch(PDO::FETCH_ASSOC);
$defaultAddress = trim(($userProfile['address'] ?? '') . ($userProfile['address_info'] ? ', ' . $userProfile['address_info'] : ''));

// Si on revient du paiement (pending_order_id présent mais panier vidé),
// restaurer le panier depuis la commande en attente
if (empty($_SESSION['cart']) && !empty($_SESSION['pending_order_id']) && !isset($_SESSION['supplement_amount'])) {
    $restoreOrderId = $_SESSION['pending_order_id'];

    // Vérifier que la commande existe et appartient bien à l'utilisateur
    $stmtCheck = $pdo->prepare("SELECT id, status FROM orders WHERE id = :id AND user_id = :uid AND status = 'en_attente'");
    $stmtCheck->execute(['id' => $restoreOrderId, 'uid' => $_SESSION['user_id']]);
    $pendingOrder = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($pendingOrder) {
        // Récupérer les articles de la commande en attente
        $stmtItems = $pdo->prepare("SELECT item_id, item_type, quantity FROM order_items WHERE order_id = :oid");
        $stmtItems->execute(['oid' => $restoreOrderId]);
        $restoredItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Restaurer le panier
        $_SESSION['cart'] = [];
        foreach ($restoredItems as $ri) {
            $key = $ri['item_type'] . '_' . $ri['item_id'];
            $_SESSION['cart'][$key] = [
                'item_id'   => (int) $ri['item_id'],
                'item_type' => $ri['item_type'],
                'quantity'  => (int) $ri['quantity'],
            ];
        }

        // Supprimer la commande en attente et ses articles
        $pdo->prepare("DELETE FROM order_items WHERE order_id = :oid")->execute(['oid' => $restoreOrderId]);
        $pdo->prepare("DELETE FROM orders WHERE id = :id")->execute(['id' => $restoreOrderId]);
    }

    // Nettoyer le pending_order_id dans tous les cas
    unset($_SESSION['pending_order_id']);
}

// Vérifier que le panier n'est pas vide
if (empty($_SESSION['cart'])) {
    header('Location: panier.php');
    exit;
}

$errors = [];

// Traitement du formulaire de validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $orderType       = $_POST['order_type'] ?? '';
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $scheduling      = $_POST['scheduling'] ?? 'immediate';
    $scheduledDate   = $_POST['scheduled_date'] ?? '';
    $scheduledTime   = $_POST['scheduled_time'] ?? '';

    // Validations
    if (!in_array($orderType, ['sur_place', 'emporter', 'livraison'])) {
        $errors[] = "Veuillez choisir un type de commande.";
    }

    if ($orderType === 'livraison' && $deliveryAddress === '') {
        $errors[] = "L'adresse de livraison est requise.";
    }

    $scheduledDatetime = null;
    if ($scheduling === 'later') {
        if ($scheduledDate === '' || $scheduledTime === '') {
            $errors[] = "Veuillez indiquer la date et l'heure souhaitées.";
        } else {
            $scheduledDatetime = $scheduledDate . ' ' . $scheduledTime;
        }
    }

    // Si pas d'erreur, on crée la commande puis on redirige vers le paiement
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Vérifier si c'est une modification de commande payée
            $modifyOrderId = $_SESSION['modify_order_id'] ?? null;
            $modifyOriginalTotal = $_SESSION['modify_original_total'] ?? null;

            if ($modifyOrderId && $modifyOriginalTotal !== null) {
                // ── Modification d'une commande existante ──

                // Vérifier que la commande est toujours payée
                $stmtCheck = $pdo->prepare("SELECT id, status FROM orders WHERE id = :id AND user_id = :uid AND status = 'payee'");
                $stmtCheck->execute(['id' => $modifyOrderId, 'uid' => $_SESSION['user_id']]);
                $existingOrder = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if (!$existingOrder) {
                    $pdo->rollBack();
                    unset($_SESSION['modify_order_id'], $_SESSION['modify_original_total']);
                    $errors[] = "Cette commande n'est plus modifiable.";
                } else {
                    // Calculer le nouveau total AVANT toute écriture en base
                    $newTotal = 0;
                    foreach ($_SESSION['cart'] as $item) {
                        if ($item['item_type'] === 'dish') {
                            $s = $pdo->prepare("SELECT price FROM dishes WHERE id = :id");
                        } else {
                            $s = $pdo->prepare("SELECT total_price AS price FROM menus WHERE id = :id");
                        }
                        $s->execute(['id' => $item['item_id']]);
                        $row = $s->fetch(PDO::FETCH_ASSOC);
                        if ($row) $newTotal += $row['price'] * $item['quantity'];
                    }

                    $diff = $newTotal - $modifyOriginalTotal;

                    if ($diff > 0.005) {
                        // Plus cher → supplément à payer : on NE modifie PAS encore la commande.
                        // On stocke le panier en session et on redirige vers le paiement.
                        // La commande sera mise à jour dans paiement.php UNIQUEMENT si le paiement réussit.
                        $pdo->rollBack();

                        $_SESSION['modify_cart_snapshot'] = $_SESSION['cart'];
                        $_SESSION['modify_order_meta'] = [
                            'order_type'       => $orderType,
                            'delivery_address' => $orderType === 'livraison' ? $deliveryAddress : null,
                            'scheduled_datetime' => $scheduledDatetime,
                        ];
                        $_SESSION['pending_order_id'] = $modifyOrderId;
                        $_SESSION['supplement_amount'] = round($diff, 2);
                        // Garder modify_order_id et modify_original_total pour paiement.php

                        $_SESSION['cart'] = [];

                        header('Location: paiement.php');
                        exit;
                    } else {
                        // Moins cher ou égal → pas de paiement additionnel, on applique directement
                        // Mettre à jour les métadonnées de la commande
                        $pdo->prepare("
                            UPDATE orders SET order_type = :ot, delivery_address = :da, scheduled_datetime = :sd
                            WHERE id = :id
                        ")->execute([
                            'ot' => $orderType,
                            'da' => $orderType === 'livraison' ? $deliveryAddress : null,
                            'sd' => $scheduledDatetime,
                            'id' => $modifyOrderId,
                        ]);

                        // Supprimer les anciens articles
                        $pdo->prepare("DELETE FROM order_items WHERE order_id = :oid")->execute(['oid' => $modifyOrderId]);

                        // Insérer les nouveaux articles
                        $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, item_id, item_type, quantity) VALUES (:order_id, :item_id, :item_type, :quantity)");
                        foreach ($_SESSION['cart'] as $item) {
                            $stmtItem->execute([
                                'order_id'  => $modifyOrderId,
                                'item_id'   => $item['item_id'],
                                'item_type' => $item['item_type'],
                                'quantity'  => $item['quantity'],
                            ]);
                        }

                        $pdo->commit();

                        // Vider le panier
                        $_SESSION['cart'] = [];

                        unset($_SESSION['modify_order_id'], $_SESSION['modify_original_total']);
                        $_SESSION['profil_message'] = 'Commande n°' . $modifyOrderId . ' mise à jour avec succès.';
                        if ($diff < -0.005) {
                            $_SESSION['profil_message'] .= ' La différence de ' . number_format(abs($diff), 2, ',', ' ') . ' € n\'est pas remboursée.';
                        }
                        header('Location: profil.php');
                        exit;
                    }
                }
            } else {
                // ── Nouvelle commande (flux normal) ──

                // Créer la commande (statut en_attente)
                $stmt = $pdo->prepare("
                    INSERT INTO orders (user_id, order_type, delivery_address, status, scheduled_datetime)
                    VALUES (:user_id, :order_type, :delivery_address, :status, :scheduled_datetime)
                ");
                $stmt->execute([
                    'user_id'            => $_SESSION['user_id'],
                    'order_type'         => $orderType,
                    'delivery_address'   => $orderType === 'livraison' ? $deliveryAddress : null,
                    'status'             => 'en_attente',
                    'scheduled_datetime' => $scheduledDatetime,
                ]);
                $orderId = $pdo->lastInsertId();

                // Insérer les articles
                $stmtItem = $pdo->prepare("
                    INSERT INTO order_items (order_id, item_id, item_type, quantity)
                    VALUES (:order_id, :item_id, :item_type, :quantity)
                ");
                foreach ($_SESSION['cart'] as $item) {
                    $stmtItem->execute([
                        'order_id'  => $orderId,
                        'item_id'   => $item['item_id'],
                        'item_type' => $item['item_type'],
                        'quantity'  => $item['quantity'],
                    ]);
                }

                $pdo->commit();

                // Vider le panier
                $_SESSION['cart'] = [];

                // Stocker l'order_id en session pour le paiement
                $_SESSION['pending_order_id'] = $orderId;

                // Rediriger vers la page de paiement
                header('Location: paiement.php');
                exit;
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur commande : " . $e->getMessage());
            $errors[] = "Une erreur technique est survenue.";
        }
    }
}

// Charger le résumé du panier pour l'affichage
$cartItems = [];
$totalPrice = 0;

foreach ($_SESSION['cart'] as $key => $item) {
    if ($item['item_type'] === 'dish') {
        $stmt = $pdo->prepare("SELECT name, price FROM dishes WHERE id = :id");
    } else {
        $stmt = $pdo->prepare("SELECT name, total_price AS price FROM menus WHERE id = :id");
    }
    $stmt->execute(['id' => $item['item_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $subtotal = $row['price'] * $item['quantity'];
        $totalPrice += $subtotal;
        $cartItems[] = [
            'name'     => $row['name'],
            'type'     => $item['item_type'] === 'menu' ? 'Menu' : 'Plat',
            'price'    => $row['price'],
            'quantity' => $item['quantity'],
            'subtotal' => $subtotal,
        ];
    }
}
?>

<?php $pageTitle = 'Validation de commande'; $pageCss = 'validation.css'; ?>
<?php include 'includes/header.php'; ?>

<div class="validation-page">

  <!-- Progress steps -->
  <div class="val-steps">
    <div class="val-step val-step--inactive">
      <span class="val-step-num">1</span>
      <span>Panier</span>
    </div>
    <span class="val-steps-sep val-steps-sep--done"></span>
    <div class="val-step val-step--active">
      <span class="val-step-num">2</span>
      <span>Validation</span>
    </div>
    <span class="val-steps-sep"></span>
    <div class="val-step val-step--inactive">
      <span class="val-step-num">3</span>
      <span>Paiement</span>
    </div>
  </div>

  <!-- Header -->
  <header class="val-header">
    <a href="panier.php" class="val-back">← Retour au panier</a>
    <h1 class="val-title">Valider<br><em>la commande</em></h1>
  </header>

  <!-- Errors -->
  <?php if (!empty($errors)): ?>
    <div class="val-errors" style="margin-bottom:1.75rem;">
      <?php foreach ($errors as $error): ?>
        <div class="val-error"><?= h($error) ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Two-column grid -->
  <div class="val-grid">

    <!-- Left: Order Summary -->
    <div class="val-col-summary">
      <div class="val-summary-card">
        <div class="val-summary-header">
          <span class="val-summary-title">Votre commande</span>
          <span class="val-summary-count"><?= count($cartItems) ?> article<?= count($cartItems) > 1 ? 's' : '' ?></span>
        </div>
        <div class="val-summary-items">
          <?php foreach ($cartItems as $ci): ?>
            <div class="val-summary-item">
              <div class="val-summary-item-info">
                <span class="val-summary-item-name"><?= h($ci['name']) ?></span>
                <span class="val-summary-item-meta"><?= h($ci['type']) ?> × <?= $ci['quantity'] ?></span>
              </div>
              <span class="val-summary-item-price"><?= number_format($ci['subtotal'], 2, ',', ' ') ?> €</span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="val-summary-total">
          <span class="val-summary-total-label">Total</span>
          <span class="val-summary-total-value"><?= number_format($totalPrice, 2, ',', ' ') ?> €</span>
        </div>
      </div>
    </div>

    <!-- Right: Form -->
    <div class="val-col-form">
      <form action="validation.php" method="post" class="val-form" novalidate>

        <!-- Delivery Type -->
        <fieldset class="val-fieldset">
          <legend class="val-legend">Type de commande</legend>
          <div class="val-radio-cards">
            <label class="val-radio-card">
              <input type="radio" name="order_type" value="sur_place"
                <?= (($_POST['order_type'] ?? '') === 'sur_place') ? 'checked' : '' ?>>
              <span class="val-radio-card-content">
                <span class="val-radio-card-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg></span>
                <span class="val-radio-card-label">Sur place</span>
              </span>
            </label>
            <label class="val-radio-card">
              <input type="radio" name="order_type" value="emporter"
                <?= (($_POST['order_type'] ?? '') === 'emporter') ? 'checked' : '' ?>>
              <span class="val-radio-card-content">
                <span class="val-radio-card-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/><path d="M12 22V12"/><path d="m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7"/><path d="m7.5 4.27 9 5.15"/></svg></span>
                <span class="val-radio-card-label">À emporter</span>
              </span>
            </label>
            <label class="val-radio-card">
              <input type="radio" name="order_type" value="livraison"
                <?= (($_POST['order_type'] ?? '') === 'livraison') ? 'checked' : '' ?>>
              <span class="val-radio-card-content">
                <span class="val-radio-card-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg></span>
                <span class="val-radio-card-label">Livraison</span>
              </span>
            </label>
          </div>

          <!-- Delivery Address (shown for livraison via JS) -->
          <div id="delivery-address-group" class="val-input-group" style="display:none;">
            <label>Adresse de livraison</label>

            <!-- Adresse par défaut (profil) -->
            <div id="delivery-default" class="val-delivery-card">
              <div class="val-delivery-card-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
              </div>
              <div class="val-delivery-card-text">
                <span class="val-delivery-card-address"><?= h($defaultAddress ?: 'Aucune adresse enregistrée') ?></span>
                <span class="val-delivery-card-hint">Adresse de votre profil</span>
              </div>
            </div>

            <!-- Lien pour changer d'adresse -->
            <button type="button" id="delivery-change-btn" class="val-delivery-change">
              <?= $defaultAddress ? '↳ Utiliser une autre adresse' : '↳ Saisir une adresse' ?>
            </button>

            <!-- Input (pré-rempli avec l'adresse du profil, affiché seulement si "autre adresse") -->
            <input
              type="text"
              name="delivery_address"
              id="delivery_address"
              class="val-input"
              placeholder="Ex : 12 rue de la Paix, 75001 Paris"
              value="<?= h($_POST['delivery_address'] ?? $defaultAddress) ?>"
              data-default="<?= h($defaultAddress) ?>"
              style="display:none;"
            >

            <button type="button" id="delivery-reset-btn" class="val-delivery-reset" style="display:none;">
              ← Revenir à l'adresse de mon profil
            </button>
          </div>
        </fieldset>

        <!-- Scheduling -->
        <fieldset class="val-fieldset">
          <legend class="val-legend">Quand ?</legend>
          <div class="val-scheduling-cards">
            <label class="val-scheduling-card">
              <input type="radio" name="scheduling" value="immediate"
                <?= (($_POST['scheduling'] ?? 'immediate') === 'immediate') ? 'checked' : '' ?>>
              <span class="val-scheduling-card-content">
                <span class="val-scheduling-dot"></span>
                Préparation immédiate
              </span>
            </label>
            <label class="val-scheduling-card">
              <input type="radio" name="scheduling" value="later"
                <?= (($_POST['scheduling'] ?? '') === 'later') ? 'checked' : '' ?>>
              <span class="val-scheduling-card-content">
                <span class="val-scheduling-dot"></span>
                Choisir une date et heure
              </span>
            </label>
          </div>

          <!-- Date & Time pickers -->
          <div id="schedule-fields" class="val-datetime-row" style="display:none;">
            <div class="val-input-group">
              <label for="scheduled_date">Date</label>
              <input type="date" name="scheduled_date" id="scheduled_date" class="val-input"
                value="<?= h($_POST['scheduled_date'] ?? '') ?>">
            </div>
            <div class="val-input-group">
              <label for="scheduled_time">Heure</label>
              <input type="time" name="scheduled_time" id="scheduled_time" class="val-input"
                value="<?= h($_POST['scheduled_time'] ?? '') ?>">
            </div>
          </div>
        </fieldset>

        <button type="submit" class="val-submit">Procéder au paiement</button>

      </form>
    </div>

  </div>

</div>

<script src="/assets/js/form-validation.js"></script>
<script>
// ── Show/hide des champs conditionnels ──
(function() {
  const deliveryRadios = document.querySelectorAll('input[name="order_type"]');
  const deliveryGroup = document.getElementById('delivery-address-group');
  const schedulingRadios = document.querySelectorAll('input[name="scheduling"]');
  const scheduleFields = document.getElementById('schedule-fields');

  function toggleDelivery() {
    const checked = document.querySelector('input[name="order_type"]:checked');
    if (deliveryGroup) {
      deliveryGroup.style.display = (checked && checked.value === 'livraison') ? '' : 'none';
    }
  }

  function toggleScheduling() {
    const checked = document.querySelector('input[name="scheduling"]:checked');
    if (scheduleFields) {
      scheduleFields.style.display = (checked && checked.value === 'later') ? '' : 'none';
    }
  }

  deliveryRadios.forEach(function(r) { r.addEventListener('change', toggleDelivery); });
  schedulingRadios.forEach(function(r) { r.addEventListener('change', toggleScheduling); });

  toggleDelivery();
  toggleScheduling();

  // ── Toggle adresse profil / adresse personnalisée ──
  const changeBtn  = document.getElementById('delivery-change-btn');
  const resetBtn   = document.getElementById('delivery-reset-btn');
  const addressInput = document.getElementById('delivery_address');
  const defaultCard  = document.getElementById('delivery-default');

  if (changeBtn && addressInput) {
    changeBtn.addEventListener('click', function(e) {
      e.preventDefault();
      // Bascule en mode édition
      if (defaultCard) defaultCard.style.display = 'none';
      changeBtn.style.display = 'none';
      if (resetBtn) resetBtn.style.display = '';
      addressInput.style.display = '';
      addressInput.value = '';  // vide le champ pour que le client tape sa nouvelle adresse
      addressInput.focus();
    });
  }

  if (resetBtn && addressInput) {
    resetBtn.addEventListener('click', function(e) {
      e.preventDefault();
      // Revenir à l'adresse du profil
      if (defaultCard) defaultCard.style.display = '';
      changeBtn.style.display = '';
      resetBtn.style.display = 'none';
      addressInput.style.display = 'none';
      addressInput.value = addressInput.getAttribute('data-default') || '';
    });
  }
})();

// ── Validation côté client ──
LECLIPSE.initFormValidation('.val-form', {
  order_type: 'orderType',
  delivery_address: 'deliveryAddress',
  scheduled_date: 'scheduledDate',
  scheduled_time: 'scheduledTime'
});
</script>

<?php include 'includes/footer.php'; ?>
