<?php
session_start();

require_once 'database.php';
require_once 'functions.php';

$pdo = getDatabaseConnection();

$user = null;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'quick_login_test') {
    $loginTest = trim($_POST['login_test'] ?? '');
    $passwordTest = $_POST['password_test'] ?? '';

    if ($loginTest === '' || $passwordTest === '') {
        $message = 'Compte de test invalide.';
    } else {
        $stmtQuick = $pdo->prepare("SELECT id, login, password, role FROM users WHERE login = :login LIMIT 1");
        $stmtQuick->execute(['login' => $loginTest]);
        $targetUser = $stmtQuick->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser || !password_verify($passwordTest, $targetUser['password'])) {
            $message = 'Le compte de test est introuvable ou le mot de passe ne correspond pas.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $targetUser['id'];
            $_SESSION['user_login'] = $targetUser['login'];
            $_SESSION['user_role'] = $targetUser['role'];

            $updateLastLogin = $pdo->prepare("UPDATE users SET last_login = datetime('now') WHERE id = :id");
            $updateLastLogin->execute(['id' => $targetUser['id']]);

            header('Location: index.php');
            exit;
        }
    }
}

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT first_name, role FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
</head>

<body>

    <?php if ($user): ?>
        <p>Bonjour <?= h($user['first_name']) ?></p>
    <?php else: ?>
        <p>vous n'êtes pas connecté.</p>
    <?php endif; ?>

    <?php if ($message): ?>
        <p><strong><?= h($message) ?></strong></p>
    <?php endif; ?>

    <?php if (!$user): ?>
        <a href="login.php" class="bouton">Login</a>
        <a href="register.php" class="bouton">Register</a>
    <?php else: ?>
        <a href="logout.php" class="bouton">Logout</a>

        <?php if ($user['role'] === 'client'): ?>
            <a href="catalogue.php" class="bouton">Catalogue</a>
            <a href="panier.php" class="bouton">Panier</a>
            <a href="profil_client.php" class="bouton">Mon profil</a>
        <?php elseif ($user['role'] === 'restaurateur'): ?>
            <a href="commandes_resto.php" class="bouton">Gestion des commandes</a>
        <?php elseif ($user['role'] === 'livreur'): ?>
            <a href="livraison.php" class="bouton">Espace livreur</a>
        <?php elseif ($user['role'] === 'admin'): ?>
            <a href="admin.php" class="bouton">Administration</a>
        <?php endif; ?>
    <?php endif; ?>

    <h2>Connexion rapide aux comptes de test</h2>

    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Login</th>
                <th>Mot de passe</th>
                <th>Prénom</th>
                <th>Type de compte</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>tom.jaffrain@gmail.com</td>
                <td>test</td>
                <td>Tom</td>
                <td>Administrateur</td>
                <td>
                    <form action="index.php" method="post">
                        <input type="hidden" name="action" value="quick_login_test">
                        <input type="hidden" name="login_test" value="tom.jaffrain@gmail.com">
                        <input type="hidden" name="password_test" value="test">
                        <button type="submit">Se connecter</button>
                    </form>
                </td>
            </tr>
            <tr>
                <td>restaurateur@gmail.com</td>
                <td>test1</td>
                <td>Sanji</td>
                <td>Restaurateur</td>
                <td>
                    <form action="index.php" method="post">
                        <input type="hidden" name="action" value="quick_login_test">
                        <input type="hidden" name="login_test" value="restaurateur@gmail.com">
                        <input type="hidden" name="password_test" value="test1">
                        <button type="submit">Se connecter</button>
                    </form>
                </td>
            </tr>
            <tr>
                <td>livreur@gmail.com</td>
                <td>test2</td>
                <td>Simon</td>
                <td>Livreur</td>
                <td>
                    <form action="index.php" method="post">
                        <input type="hidden" name="action" value="quick_login_test">
                        <input type="hidden" name="login_test" value="livreur@gmail.com">
                        <input type="hidden" name="password_test" value="test2">
                        <button type="submit">Se connecter</button>
                    </form>
                </td>
            </tr>
            <tr>
                <td>client@gmail.com</td>
                <td>test3</td>
                <td>Pigeon</td>
                <td>Client</td>
                <td>
                    <form action="index.php" method="post">
                        <input type="hidden" name="action" value="quick_login_test">
                        <input type="hidden" name="login_test" value="client@gmail.com">
                        <input type="hidden" name="password_test" value="test3">
                        <button type="submit">Se connecter</button>
                    </form>
                </td>
            </tr>
        </tbody>
        </div>
</body>

</html>