<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
$pdo = getDatabaseConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT first_name, role FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}
?>

<?php $pageTitle = 'Espace Admin'; ?>
<?php include 'includes/header.php'; ?>

    <h1>Bonjour <?= h($user['first_name']) ?></h1>

    <nav>
        <a href="admin.php" class="bouton">Gestion des utilisateurs</a>
        <a href="logout.php" class="bouton">Déconnexion</a>
    </nav>

<?php include 'includes/footer.php'; ?>
