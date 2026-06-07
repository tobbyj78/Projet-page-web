<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
require_once 'getapikey.php';

$pdo = getDatabaseConnection();

// ══════════════════════════════════════════════
// Configuration CY Bank
// ══════════════════════════════════════════════
$vendeur = 'MI-3_G';
$api_key = getAPIKey($vendeur);
$cybank_url = 'https://www.plateforme-smc.fr/cybank/index.php';

// URL de retour (construite dynamiquement)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$retour = $protocol . '://' . $host . '/paiement.php';

// ══════════════════════════════════════════════
// CAS 1 : Retour de CY Bank (paramètres GET)
// ══════════════════════════════════════════════
if (isset($_GET['transaction'], $_GET['montant'], $_GET['vendeur'], $_GET['status'], $_GET['control'])) {

    $txn       = $_GET['transaction'];
    $montant   = $_GET['montant'];
    $vnd       = $_GET['vendeur'];
    $statut    = $_GET['status'];
    $control   = $_GET['control'];

    // Vérifier l'intégrité avec le hash MD5
    $expected_control = md5($api_key . "#" . $txn . "#" . $montant . "#" . $vnd . "#" . $statut . "#");

    $payment_valid = false;
    $error_message = '';

    if ($control !== $expected_control) {
        $error_message = "Erreur : le contrôle d'intégrité a échoué.";
        // On marque quand même la commande comme refusée (sauf si c'est un supplément)
        if (preg_match('/^(?:ORD|SUP)(\d+)/', $txn, $matches)) {
            $orderId = (int) $matches[1];
            $isSup = (strpos($txn, 'SUP') === 0);
            if (!$isSup) {
                $stmt = $pdo->prepare("UPDATE orders SET status = 'refusee' WHERE id = :id");
                $stmt->execute(['id' => $orderId]);
            }
        }
    } elseif ($statut === 'accepted') {
        // Paiement accepté — extraire l'order_id de la transaction
        // Supporte les préfixes ORD (paiement normal) et SUP (supplément)
        if (preg_match('/^(?:ORD|SUP)(\d+)/', $txn, $matches)) {
            $orderId = (int) $matches[1];
            $isSupplement = (strpos($txn, 'SUP') === 0);

            if ($isSupplement) {
                // Paiement additionnel accepté → appliquer les modifications à la commande
                $modifyCart = $_SESSION['modify_cart_snapshot'] ?? null;
                $modifyMeta = $_SESSION['modify_order_meta'] ?? null;
                $modifyOid   = $_SESSION['modify_order_id'] ?? null;

                if ($modifyCart && $modifyMeta && $modifyOid) {
                    try {
                        $pdo->beginTransaction();

                        // Mettre à jour les métadonnées
                        $pdo->prepare("
                            UPDATE orders SET order_type = :ot, delivery_address = :da, scheduled_datetime = :sd
                            WHERE id = :id
                        ")->execute([
                            'ot' => $modifyMeta['order_type'],
                            'da' => $modifyMeta['delivery_address'],
                            'sd' => $modifyMeta['scheduled_datetime'],
                            'id' => $modifyOid,
                        ]);

                        // Remplacer les articles
                        $pdo->prepare("DELETE FROM order_items WHERE order_id = :oid")->execute(['oid' => $modifyOid]);

                        $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, item_id, item_type, quantity) VALUES (:order_id, :item_id, :item_type, :quantity)");
                        foreach ($modifyCart as $item) {
                            $stmtItem->execute([
                                'order_id'  => $modifyOid,
                                'item_id'   => $item['item_id'],
                                'item_type' => $item['item_type'],
                                'quantity'  => $item['quantity'],
                            ]);
                        }

                        $pdo->commit();
                    } catch (PDOException $e) {
                        $pdo->rollBack();
                        error_log("Erreur mise à jour commande modifiée : " . $e->getMessage());
                    }
                }

                // Enregistrer le paiement additionnel
                $stmtPay = $pdo->prepare("
                    INSERT INTO payments (order_id, bank_details, transaction_date)
                    VALUES (:order_id, :bank_details, datetime('now'))
                ");
                $stmtPay->execute([
                    'order_id'     => $orderId,
                    'bank_details' => 'CYBank supplément txn: ' . $txn,
                ]);
            } else {
                // Paiement normal : mettre à jour le statut
                $stmt = $pdo->prepare("UPDATE orders SET status = 'payee' WHERE id = :id");
                $stmt->execute(['id' => $orderId]);

                // Enregistrer le paiement
                $stmtPay = $pdo->prepare("
                    INSERT INTO payments (order_id, bank_details, transaction_date)
                    VALUES (:order_id, :bank_details, datetime('now'))
                ");
                $stmtPay->execute([
                    'order_id'     => $orderId,
                    'bank_details' => 'CYBank txn: ' . $txn,
                ]);
            }

            $payment_valid = true;
        } else {
            $error_message = "Erreur : identifiant de transaction invalide.";
        }
    } else {
        // Paiement refusé (denied / declined)
        if (preg_match('/^(?:ORD|SUP)(\d+)/', $txn, $matches)) {
            $orderId = (int) $matches[1];
            $isSup = (strpos($txn, 'SUP') === 0);
            if (!$isSup) {
                // Paiement normal refusé → commande refusée
                $stmt = $pdo->prepare("UPDATE orders SET status = 'refusee' WHERE id = :id");
                $stmt->execute(['id' => $orderId]);
            }
            // Si supplément refusé, on ne change rien (la commande reste payée)
        }
        $error_message = "Le paiement a été refusé par la banque.";
    }

    // Nettoyer la session
    unset(
        $_SESSION['pending_order_id'],
        $_SESSION['supplement_amount'],
        $_SESSION['modify_order_id'],
        $_SESSION['modify_original_total'],
        $_SESSION['modify_cart_snapshot'],
        $_SESSION['modify_order_meta']
    );

    $pageTitle = 'Résultat du paiement';
    $pageCss   = 'paiement.css';
    include 'includes/header.php';
    ?>

    <div class="pay-result-page">

      <div class="pay-result-card">

        <?php if ($payment_valid): ?>

          <!-- Success icon -->
          <div class="pay-result-icon pay-result-icon--success">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>

          <h1 class="pay-result-title">Paiement <em>accepté</em></h1>

          <p class="pay-result-msg">Votre commande n°<?= (int) $orderId ?> a été confirmée avec succès.</p>

          <div class="pay-result-details">
            <div class="pay-result-detail">
              <span class="pay-result-detail-label">Commande</span>
              <span class="pay-result-detail-value">n°<?= (int) $orderId ?></span>
            </div>
            <div class="pay-result-detail">
              <span class="pay-result-detail-label">Transaction</span>
              <span class="pay-result-detail-value"><?= h($txn) ?></span>
            </div>
            <div class="pay-result-detail">
              <span class="pay-result-detail-label">Montant</span>
              <span class="pay-result-detail-value"><?= h($montant) ?> €</span>
            </div>
          </div>

        <?php else: ?>

          <!-- Error icon -->
          <div class="pay-result-icon pay-result-icon--error">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
          </div>

          <h1 class="pay-result-title">Paiement <em>refusé</em></h1>

          <p class="pay-result-msg pay-result-msg--error"><?= h($error_message) ?></p>

        <?php endif; ?>

        <a href="catalogue.php" class="pay-result-cta">Retour au catalogue</a>

      </div>

    </div>

    <?php
    include 'includes/footer.php';
    exit;
}

// ══════════════════════════════════════════════
// CAS 2 : Affichage du formulaire de paiement
// ══════════════════════════════════════════════

// Vérifier qu'il y a une commande en attente
if (!isset($_SESSION['pending_order_id'])) {
    header('Location: catalogue.php');
    exit;
}

// Staff interdit (sauf retour CY Bank déjà traité)
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'client') {
    header('Location: ' . getRoleRedirect($_SESSION['user_role']));
    exit;
}

$orderId = $_SESSION['pending_order_id'];

// Vérifier si c'est un paiement additionnel (supplément)
$isSupplement = isset($_SESSION['supplement_amount']) && $_SESSION['supplement_amount'] > 0;
$supplementAmount = $isSupplement ? $_SESSION['supplement_amount'] : null;

// Récupérer la commande
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id");
$stmt->execute(['id' => $orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: catalogue.php');
    exit;
}

// Récupérer les articles de la commande
// Pour un supplément : utiliser le snapshot du panier (les nouveaux articles ne sont pas encore en base)
// Pour un paiement normal : lire depuis la base
if ($isSupplement && isset($_SESSION['modify_cart_snapshot'])) {
    $totalPrice = 0;
    $detailItems = [];
    foreach ($_SESSION['modify_cart_snapshot'] as $key => $item) {
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
            $detailItems[] = [
                'name'     => $row['name'],
                'type'     => $item['item_type'] === 'menu' ? 'Menu' : 'Plat',
                'price'    => $row['price'],
                'quantity' => $item['quantity'],
                'subtotal' => $subtotal,
            ];
        }
    }
} else {
    $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
    $stmtItems->execute(['order_id' => $orderId]);
    $orderItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    $totalPrice = 0;
    $detailItems = [];

    foreach ($orderItems as $oi) {
        if ($oi['item_type'] === 'dish') {
            $stmt = $pdo->prepare("SELECT name, price FROM dishes WHERE id = :id");
        } else {
            $stmt = $pdo->prepare("SELECT name, total_price AS price FROM menus WHERE id = :id");
        }
        $stmt->execute(['id' => $oi['item_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $subtotal = $row['price'] * $oi['quantity'];
            $totalPrice += $subtotal;
            $detailItems[] = [
                'name'     => $row['name'],
                'type'     => $oi['item_type'] === 'menu' ? 'Menu' : 'Plat',
                'price'    => $row['price'],
                'quantity' => $oi['quantity'],
                'subtotal' => $subtotal,
            ];
        }
    }
}

// Générer les paramètres CY Bank
if ($isSupplement) {
    // Paiement additionnel : transaction avec préfixe SUP
    $transaction = 'SUP' . str_pad($orderId, 4, '0', STR_PAD_LEFT) . substr(md5(uniqid(mt_rand(), true)), 0, 10);
    $montant     = number_format($supplementAmount, 2, '.', '');
} else {
    // Paiement normal
    $transaction = 'ORD' . str_pad($orderId, 4, '0', STR_PAD_LEFT) . substr(md5(uniqid(mt_rand(), true)), 0, 10);
    $montant     = number_format($totalPrice, 2, '.', '');
}

// Calculer le hash de contrôle
$control = md5($api_key . "#" . $transaction . "#" . $montant . "#" . $vendeur . "#" . $retour . "#");

$orderTypeLabels = [
    'sur_place' => 'Sur place',
    'emporter'  => 'À emporter',
    'livraison' => 'Livraison',
];
?>

<?php $pageTitle = 'Paiement – CY Bank'; $pageCss = 'paiement.css'; ?>
<?php include 'includes/header.php'; ?>

<div class="payment-page">

  <!-- Progress steps -->
  <div class="pay-steps">
    <div class="pay-step pay-step--done">
      <span class="pay-step-num">1</span>
      <span>Panier</span>
    </div>
    <span class="pay-steps-sep pay-steps-sep--done"></span>
    <div class="pay-step pay-step--done">
      <span class="pay-step-num">2</span>
      <span>Validation</span>
    </div>
    <span class="pay-steps-sep pay-steps-sep--done"></span>
    <div class="pay-step pay-step--active">
      <span class="pay-step-num">3</span>
      <span>Paiement</span>
    </div>
  </div>

  <!-- Header -->
  <header class="pay-header">
    <a href="validation.php" class="pay-back">← Retour à la validation</a>
    <h1 class="pay-title">Paiement</h1>
  </header>

  <!-- Order Summary -->
  <div class="pay-summary-card">
    <div class="pay-summary-header">
      <span class="pay-summary-title">Commande n°<?= (int) $orderId ?></span>
      <span class="pay-summary-count"><?= count($detailItems) ?> article<?= count($detailItems) > 1 ? 's' : '' ?></span>
    </div>
    <div class="pay-summary-items">
      <?php foreach ($detailItems as $di): ?>
        <div class="pay-summary-item">
          <div class="pay-summary-item-info">
            <span class="pay-summary-item-name"><?= h($di['name']) ?></span>
            <span class="pay-summary-item-meta"><?= h($di['type']) ?> × <?= $di['quantity'] ?></span>
          </div>
          <span class="pay-summary-item-price"><?= number_format($di['subtotal'], 2, ',', ' ') ?> €</span>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="pay-summary-total">
      <span class="pay-summary-total-label">Total à payer</span>
      <span class="pay-summary-total-value"><?= number_format($totalPrice, 2, ',', ' ') ?> €</span>
    </div>
  </div>

  <!-- Order Info -->
  <div class="pay-info-card">
    <div class="pay-info-row">
      <span class="pay-info-label">Type</span>
      <span class="pay-info-value"><?= h($orderTypeLabels[$order['order_type']] ?? $order['order_type']) ?></span>
    </div>
    <?php if ($order['order_type'] === 'livraison' && $order['delivery_address']): ?>
      <div class="pay-info-row">
        <span class="pay-info-label">Livraison</span>
        <span class="pay-info-value"><?= h($order['delivery_address']) ?></span>
      </div>
    <?php endif; ?>
    <?php if ($order['scheduled_datetime']): ?>
      <div class="pay-info-row">
        <span class="pay-info-label">Prévue le</span>
        <span class="pay-info-value"><?= h($order['scheduled_datetime']) ?></span>
      </div>
    <?php else: ?>
      <div class="pay-info-row">
        <span class="pay-info-label">Préparation</span>
        <span class="pay-info-value">Immédiate</span>
      </div>
    <?php endif; ?>
  </div>

  <!-- Payment Section -->
  <div class="pay-section">
    <h2 class="pay-section-title">Paiement sécurisé</h2>

    <div class="pay-bank-badge">
      <span class="pay-bank-badge-icon"></span>
      CY Bank
    </div>

    <?php if ($isSupplement): ?>
      <!-- Contexte supplément -->
      <div class="pay-supplement-banner">
        <div class="pay-supplement-banner-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
        </div>
        <div class="pay-supplement-banner-text">
          <span class="pay-supplement-banner-title">Paiement additionnel</span>
          <span class="pay-supplement-banner-hint">Votre commande n°<?= (int)$orderId ?> a déjà été payée. Vous allez régler le supplément lié aux articles ajoutés.</span>
        </div>
      </div>
    <?php endif; ?>

    <p class="pay-section-desc">Vous allez être redirigé vers la plateforme de paiement sécurisée CY Bank pour finaliser votre commande.</p>

    <div class="pay-txn-list">
      <div class="pay-txn-row">
        <span class="pay-txn-label">Transaction</span>
        <span class="pay-txn-value pay-txn-value--mono"><?= h($transaction) ?></span>
      </div>
      <div class="pay-txn-row">
        <span class="pay-txn-label">Montant</span>
        <span class="pay-txn-value"><?= h($montant) ?> €</span>
      </div>
      <div class="pay-txn-row">
        <span class="pay-txn-label">Vendeur</span>
        <span class="pay-txn-value"><?= h($vendeur) ?></span>
      </div>
    </div>
  </div>

  <!-- CY Bank form -->
  <form action="<?= h($cybank_url) ?>" method="post" id="cybank-form">
    <input type="hidden" name="transaction" value="<?= h($transaction) ?>">
    <input type="hidden" name="montant"     value="<?= h($montant) ?>">
    <input type="hidden" name="vendeur"     value="<?= h($vendeur) ?>">
    <input type="hidden" name="retour"      value="<?= h($retour) ?>">
    <input type="hidden" name="control"     value="<?= h($control) ?>">

    <button type="submit" class="pay-submit">Payer maintenant</button>
  </form>

</div>

<?php include 'includes/footer.php'; ?>
