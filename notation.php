<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
require_once 'models/users.php';
require_once 'models/orders.php';

$pdo = getDatabaseConnection();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

// Staff interdit
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] !== 'client') {
    header('Location: ' . getRoleRedirect($_SESSION['user_role']));
    exit;
}

$orderId = (int) ($_GET['id'] ?? 0);
if ($orderId <= 0) {
    header('Location: profil.php');
    exit;
}

// Vérifier que la commande appartient au client et est livrée
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id AND user_id = :uid AND status = 'livree'");
$stmt->execute(['id' => $orderId, 'uid' => $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: profil.php');
    exit;
}

// Vérifier que la commande n'est pas déjà notée
$existing = getOrderRating($pdo, $orderId);
if ($existing) {
    $_SESSION['rating_error'] = 'Vous avez déjà noté cette commande.';
    header('Location: profil.php');
    exit;
}

// Récupérer les articles de la commande
$items = getOrderItems($pdo, $orderId);
$total = getOrderTotal($pdo, $orderId);

$itemNames = [];
foreach ($items as $item) {
    $itemNames[] = $item['name'];
}

// Traitement du formulaire de notation
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ratingValue = (int) ($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($ratingValue < 1 || $ratingValue > 5) {
        $error = 'Veuillez sélectionner une note.';
    } else {
        createRating($pdo, $orderId, $_SESSION['user_id'], $ratingValue, $comment !== '' ? $comment : null);
        $_SESSION['rating_success'] = 'Merci pour votre notation !';
        header('Location: profil.php');
        exit;
    }
}
?>

<?php $pageTitle = 'Noter la commande'; $pageCss = 'notation.css'; ?>
<?php include 'includes/header.php'; ?>

<div class="rating-page">

  <div class="rating-card">

    <header class="rating-header">
      <a href="profil.php" class="rating-back">← Retour au profil</a>
      <h1 class="rating-title">Votre<br><em>avis</em></h1>
      <p class="rating-subtitle">Commande n°<?= $orderId ?> — <?= number_format($total, 2, ',', ' ') ?>&nbsp;€</p>
    </header>

    <?php if ($error): ?>
      <div class="rating-error"><?= h($error) ?></div>
    <?php endif; ?>

    <!-- Résumé de la commande -->
    <div class="rating-order-summary">
      <span class="rating-order-label">Contenu de la commande</span>
      <p class="rating-order-items"><?= h(implode(' · ', $itemNames)) ?></p>
      <div class="rating-order-meta">
        <span><?= h($order['order_type'] === 'livraison' ? 'Livraison' : ($order['order_type'] === 'emporter' ? 'À emporter' : 'Sur place')) ?></span>
        <span><?= h($order['created_at']) ?></span>
      </div>
    </div>

    <!-- Formulaire -->
    <form action="notation.php?id=<?= $orderId ?>" method="post">

      <div class="rating-stars-group">
        <span class="rating-stars-label">Note</span>
        <div class="rating-stars">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" name="rating" value="<?= $i ?>" id="star<?= $i ?>"
                   <?= (($_POST['rating'] ?? 0) == $i) ? 'checked' : '' ?>>
            <label for="star<?= $i ?>" aria-label="<?= $i ?> étoiles">★</label>
          <?php endfor; ?>
        </div>
      </div>

      <div class="rating-comment-group">
        <label for="rating-comment">Commentaire <em style="font-style:normal;color:var(--text-muted)">(optionnel)</em></label>
        <textarea name="comment" id="rating-comment"
                  placeholder="Partagez votre expérience…"><?= h($_POST['comment'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="rating-submit">Envoyer ma note</button>

    </form>

  </div>

</div>

<?php include 'includes/footer.php'; ?>
