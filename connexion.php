<?php

session_start();

require_once 'database.php';
require_once 'functions.php';
$pdo = getDatabaseConnection();

$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT first_name, role FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$error = '';
$redirect = $_GET['redirect'] ?? '';

// Validation de la cible de redirection (sécurité : uniquement chemins relatifs)
if (!empty($redirect) && (str_starts_with($redirect, 'http') || str_starts_with($redirect, '//'))) {
    $redirect = '';
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? '';

    $stmt = $pdo->prepare("SELECT id, login, password, role FROM users WHERE login = :login");
    $stmt->execute(['login' => $login]);
    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$utilisateur || !password_verify($password, $utilisateur['password'])) {
        $error = "Identifiants invalides.";
    } else {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $utilisateur['id'];
        $_SESSION['user_login'] = $utilisateur['login'];
        $_SESSION['user_role'] = $utilisateur['role'];

        $updateLastLogin = $pdo->prepare("UPDATE users SET last_login = datetime('now') WHERE id = :id");
        $updateLastLogin->execute(['id' => $utilisateur['id']]);

        header('Location: ' . (!empty($redirect) ? $redirect : getRoleRedirect($utilisateur['role'])));
        exit;
    }
}

?>

<?php
$pageTitle = 'Connexion';
$pageCss   = 'login.css';
include 'includes/header.php';
?>

<main class="auth-page">
  <div class="auth-card">

    <div class="auth-tabs" role="tablist">
      <span class="auth-tab is-active" role="tab" aria-selected="true">Connexion</span>
      <a  class="auth-tab" href="inscription.php" role="tab" aria-selected="false">Inscription</a>
    </div>

    <?php if (isset($_GET['registered'])): ?>
      <p class="auth-success">✅ Compte créé avec succès&nbsp;! Connectez-vous ci-dessous.</p>
    <?php endif; ?>

    <?php if (isset($_GET['blocked'])): ?>
      <p class="auth-error-global">⛔ Votre compte a été bloqué par un administrateur.</p>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <p class="auth-error-global"><?= h($error) ?></p>
    <?php endif; ?>

    <form class="auth-form" action="" method="post" novalidate>

      <?php if (!empty($redirect)): ?>
      <input type="hidden" name="redirect" value="<?= h($redirect) ?>">
      <?php endif; ?>

      <div class="auth-field">
        <label for="login">Identifiant <span class="required" aria-hidden="true">*</span></label>
        <input type="text" name="login" id="login"
               value="<?= h($_POST['login'] ?? '') ?>"
               autocomplete="username" maxlength="30">
      </div>

      <div class="auth-field">
        <label for="password">Mot de passe <span class="required" aria-hidden="true">*</span></label>
        <input type="password" name="password" id="password"
               autocomplete="current-password" maxlength="128">
      </div>

      <div class="auth-forgot">
        <a href="#">Mot de passe oublié&nbsp;?</a>
      </div>

      <button class="auth-btn" type="submit">Se connecter</button>

    </form>

    <div class="auth-footer">
      <p>Pas encore de compte&nbsp;? <a href="inscription.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>">Créer un compte</a></p>
    </div>

    <a href="catalogue.php" class="auth-skip">Accéder au catalogue</a>

  </div>
</main>

<script src="/assets/js/form-validation.js"></script>
<script>
LECLIPSE.setupPasswordToggle('password');
LECLIPSE.setupCharCounter('login', 30);
LECLIPSE.setupCharCounter('password', 128);
LECLIPSE.initFormValidation('.auth-form', {
  login: 'required',
  password: 'required'
});
</script>

<?php include 'includes/footer.php'; ?>
