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

// ── Traitement du changement de rôle ──
$message = '';
$availableRoles = ['client', 'admin', 'restaurateur', 'livreur'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_role') {
    $targetUserId = (int) ($_POST['user_id'] ?? 0);
    $newRole = $_POST['new_role'] ?? '';

    if ($targetUserId === $_SESSION['user_id']) {
        $message = "Vous ne pouvez pas modifier votre propre rôle.";
    } elseif (!in_array($newRole, $availableRoles)) {
        $message = "Rôle invalide.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
        $stmt->execute(['role' => $newRole, 'id' => $targetUserId]);
        $message = "Rôle mis à jour avec succès.";
    }

    // PRG
    $_SESSION['admin_message'] = $message;
    header('Location: admin.php');
    exit;
}

// Récupérer le message flash
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    unset($_SESSION['admin_message']);
}

$stmt = $pdo->prepare("SELECT * FROM users ORDER BY id");
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
    <h1>Administration</h1>

    <a href="index.php">Accueil</a>

    <p>Nombre d'utilisateurs enregistrés : <strong><?= count($users) ?></strong></p>

    <?php if ($message): ?>
        <p><strong><?= h($message) ?></strong></p>
    <?php endif; ?>

    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>Login</th>
                <th>Rôle actuel</th>
                <th>Changer le rôle</th>
                <th>Prénom</th>
                <th>Nom</th>
                <th>Pseudo</th>
                <th>Téléphone</th>
                <th>Date naiss.</th>
                <th>Adresse</th>
                <th>Complément</th>
                <th>Inscrit le</th>
                <th>Dernière co.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $listedUser): ?>
                <tr>
                    <td><?= h($listedUser['id']) ?></td>
                    <td><?= h($listedUser['login']) ?></td>
                    <td><strong><?= h($listedUser['role']) ?></strong></td>
                    <td>
                        <?php if ($listedUser['id'] != $_SESSION['user_id']): ?>
                            <form action="admin.php" method="post">
                                <input type="hidden" name="action" value="change_role">
                                <input type="hidden" name="user_id" value="<?= $listedUser['id'] ?>">
                                <select name="new_role">
                                    <?php foreach ($availableRoles as $role): ?>
                                        <option value="<?= $role ?>"
                                            <?= ($listedUser['role'] === $role) ? 'selected' : '' ?>>
                                            <?= h($role) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit">Appliquer</button>
                            </form>
                        <?php else: ?>
                            <em>(vous)</em>
                        <?php endif; ?>
                    </td>
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