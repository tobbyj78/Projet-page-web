<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
$pdo = getDatabaseConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser || $currentUser['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users");
$stmt->execute();

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Administrateur</title>
</head>

<body>
    <h1>Nombre d'utilisateurs enregistrés : <?= count($users) ?></h1>
    <br>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Login/Email</th>
                <th>Role</th>
                <th>Firstname</th>
                <th>Lastname</th>
                <th>Nickname</th>
                <th>Phone Number</th>
                <th>Birthday</th>
                <th>Address</th>
                <th>Address Info</th>
                <th>Created</th>
                <th>Last Seen</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $listedUser): ?>
                <tr>
                    <td><?= h($listedUser['id']) ?></td>
                    <td><?= h($listedUser['login']) ?></td>
                    <td><?= h($listedUser['role']) ?></td>
                    <td><?= h($listedUser['first_name']) ?></td>
                    <td><?= h($listedUser['last_name']) ?></td>
                    <td><?= h($listedUser['nickname']) ?></td>
                    <td><?= h($listedUser['phone']) ?></td>
                    <td><?= h($listedUser['birthday']) ?></td>
                    <td><?= h($listedUser['address']) ?></td>
                    <td><?= h($listedUser['address_info']) ?></td>
                    <td><?= h($listedUser['created_at']) ?></td>
                    <td><?= h($listedUser['last_login']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>

</html>