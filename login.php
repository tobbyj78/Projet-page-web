<?php

session_start();

require_once 'database.php';
require_once 'functions.php';
$pdo = getDatabaseConnection();

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Cohérence : on utilise 'login' partout (HTML, PHP, SQL)
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, login, password, role FROM users WHERE login = :login");
    $stmt->execute(['login' => $login]);
    $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

    // Les conditions de vérification sont regroupées pour plus de lisibilité
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

<?php $pageTitle = 'Connexion'; ?>
<?php include 'includes/header.php'; ?>
    <form action="" method="post">
        <label for="login">Login :</label>
        <input type="text" name="login" id="login" value="<?php echo htmlspecialchars($_POST['login'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <label for="password">Mot de passe :</label>
        <input type="password" name="password" id="password">
        <button type="submit">Se connecter</button>
        <a href="register.php" class="bouton">register</a>
        <?php if (!empty($error)): ?>
            <p><?php echo $error; ?></p>
        <?php endif; ?>
    </form>
<?php include 'includes/footer.php'; ?>