<?php

session_start();

require_once 'database.php';
require_once 'functions.php';
$pdo = getDatabaseConnection();

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, login, password, role FROM users WHERE login = :login");
    $stmt->execute(['login' => $login]);
    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$utilisateur || !password_verify($password, $utilisateur['password'])) {
        $error = "Identifiants invalides.";
    } else {
        // 1) Prévention de la fixation de session
        session_regenerate_id(true);

        $_SESSION['user_id'] = $utilisateur['id'];
        $_SESSION['user_login'] = $utilisateur['login'];
        $_SESSION['user_role'] = $utilisateur['role'];

        $updateLastLogin = $pdo->prepare("UPDATE users SET last_login = datetime('now') WHERE id = :id");
        $updateLastLogin->execute(['id' => $utilisateur['id']]);

        // 2) Redirection pour éviter la soumission multiple via F5
        header('Location: ' . getRoleRedirect($utilisateur['role']));
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

    <div class="auth-header">
      <a href="index.php" class="auth-logo">L'Éclipse</a>
      <h1 class="auth-title">Connexion</h1>
    </div>

    <?php if (!empty($error)): ?>
      <p class="auth-error-global"><?= h($error) ?></p>
    <?php endif; ?>

    <form class="auth-form" action="" method="post">

      <div class="auth-field">
        <label for="login">Identifiant</label>
        <input type="text" name="login" id="login"
               value="<?= h($_POST['login'] ?? '') ?>"
               autocomplete="username">
      </div>

      <div class="auth-field">
        <label for="password">Mot de passe</label>
        <input type="password" name="password" id="password"
               autocomplete="current-password">
      </div>

      <button class="auth-btn" type="submit">Se connecter</button>

    </form>

    <div class="auth-footer">
      <p>Pas encore de compte ? <a href="register.php">Créer un compte</a></p>
    </div>

  </div>
</main>

<?php include 'includes/footer.php'; ?>