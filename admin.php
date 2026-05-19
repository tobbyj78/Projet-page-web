<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
$pdo = getDatabaseConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$message = '';
$availableRoles = ['client', 'admin', 'restaurateur', 'livreur'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_role') {
    $targetUserId = (int) ($_POST['user_id'] ?? 0);
    $newRole = $_POST['new_role'] ?? '';

    if ($targetUserId === $_SESSION['user_id']) {
        $message = "Vous ne pouvez pas modifier votre propre rôle.";
    } elseif (!in_array($newRole, $availableRoles, true)) {
        $message = "Rôle invalide.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
        $stmt->execute(['role' => $newRole, 'id' => $targetUserId]);
        $message = "Rôle mis à jour avec succès.";
    }

    $_SESSION['admin_message'] = $message;
    header('Location: admin.php');
    exit;
}

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
  <title>Administration — L'Éclipse</title>
  <link rel="icon" href="/images/favicon.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&family=Cormorant+SC:wght@400;500&family=EB+Garamond:wght@400;500&family=Jost:wght@300;400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<div class="admin-topbar">
  <a class="topbar-logo" href="/">L'Éclipse</a>
  <div class="topbar-actions">
    <button class="topbar-theme-btn theme-btn" id="themeToggle" aria-label="Changer de thème">
      <span class="theme-icon" aria-hidden="true"></span>
    </button>
    <a class="topbar-logout" href="logout.php">Déconnexion</a>
  </div>
</div>

<main class="admin-page">
  <div class="admin-inner">

    <header class="admin-header">
      <p class="admin-label">Espace administrateur</p>
      <h1 class="admin-title">Gestion des<br><em>utilisateurs</em></h1>
    </header>

    <div class="admin-stats">
      <div class="stat-card">
        <span class="stat-value"><?= count($users) ?></span>
        <span class="stat-label">Utilisateurs inscrits</span>
      </div>
    </div>

    <?php if ($message): ?>
    <div class="admin-flash <?= str_contains($message, 'succès') ? 'flash-success' : 'flash-error' ?>">
      <?= h($message) ?>
    </div>
    <?php endif; ?>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Login</th>
            <th>Rôle actuel</th>
            <th>Prénom</th>
            <th>Nom</th>
            <th>Pseudo</th>
            <th>Téléphone</th>
            <th>Naissance</th>
            <th>Adresse</th>
            <th>Complément</th>
            <th>Inscrit le</th>
            <th>Dernière co.</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $listedUser): ?>
          <tr>
            <td class="td-id"><?= h($listedUser['id']) ?></td>
            <td class="td-login"><?= h($listedUser['login']) ?></td>
            <td class="td-role">
              <?php if ($listedUser['id'] != $_SESSION['user_id']): ?>
              <div class="role-cell" data-role-cell data-current-role="<?= h($listedUser['role']) ?>">
                <button class="role-badge role-<?= h($listedUser['role']) ?>" data-role-trigger type="button">
                  <?= h($listedUser['role']) ?>
                </button>
                <div class="role-popover">
                  <?php foreach ($availableRoles as $role): ?>
                  <button class="role-pill role-<?= $role ?><?= $listedUser['role'] === $role ? ' is-selected' : '' ?>"
                          data-role-pick="<?= $role ?>"
                          type="button">
                    <?= $role ?>
                  </button>
                  <?php endforeach; ?>
                  <form action="admin.php" method="post">
                    <input type="hidden" name="action" value="change_role">
                    <input type="hidden" name="user_id" value="<?= $listedUser['id'] ?>">
                    <input type="hidden" name="new_role" class="new-role-input" value="<?= h($listedUser['role']) ?>">
                    <button class="role-apply" type="submit">Appliquer</button>
                  </form>
                </div>
              </div>
              <?php else: ?>
              <span class="role-badge role-<?= h($listedUser['role']) ?>"><?= h($listedUser['role']) ?></span>
              <?php endif; ?>
            </td>
            <td><?= h($listedUser['first_name']) ?></td>
            <td><?= h($listedUser['last_name']) ?></td>
            <td><?= h($listedUser['nickname']) ?></td>
            <td><?= h($listedUser['phone']) ?></td>
            <td><?= h($listedUser['birthday']) ?></td>
            <td><?= h($listedUser['address']) ?></td>
            <td><?= h($listedUser['address_info'] ?? '') ?></td>
            <td><?= h($listedUser['created_at']) ?></td>
            <td><?= h($listedUser['last_login'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</main>

<script src="/assets/js/script.js"></script>
<script src="/assets/js/admin.js"></script>
</body>
</html>
