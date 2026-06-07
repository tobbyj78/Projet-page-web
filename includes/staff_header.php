<?php
/**
 * staff_header.php — Topbar commune pour admin, restaurateur, livreur.
 *
 * Attend les variables suivantes :
 *   $staffRole       : 'admin' | 'restaurateur' | 'livreur'
 *   $staffPageTitle  : titre de la page (optionnel)
 *   $staffCss        : fichier CSS additionnel (optionnel, ex: 'admin.css')
 */

if (!isset($pdo)) {
    require_once __DIR__ . '/../database.php';
    $pdo = getDatabaseConnection();
}
require_once __DIR__ . '/../functions.php';

// Récupérer les infos de l'utilisateur si absent
if (empty($user) && isset($_SESSION['user_id'])) {
    $stmtUser = $pdo->prepare('SELECT first_name, nickname, role, blocked FROM users WHERE id = :id');
    $stmtUser->execute(['id' => $_SESSION['user_id']]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Vérification blocage : détruit la session si le compte est bloqué
if (!empty($user) && ($user['blocked'] ?? 0) == 1) {
    session_destroy();
    header('Location: connexion.php?blocked=1');
    exit;
}

if (!$staffRole && !empty($user['role'])) {
    $staffRole = $user['role'];
}

// Déterminer le dashboard selon le rôle
$dashboardUrl = match ($staffRole) {
    'admin'        => 'admin.php',
    'restaurateur' => 'index_restaurateur.php',
    'livreur'      => 'index_livreur.php',
    default        => 'index.php',
};
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($staffPageTitle ?? 'Espace ' . ucfirst($staffRole ?? 'Staff')) ?> — L'Éclipse</title>
  <link rel="icon" href="/images/favicon.ico">

  <script>
  // Thème (cookie) avant CSS → pas de flash
  (function(){var m=document.cookie.match(/(?:^|;\s*)theme=([^;]*)/);var t=m?decodeURIComponent(m[1]):null;if(t==='light'){document.documentElement.setAttribute('data-theme','light');document.write('<link rel="stylesheet" href="/assets/css/light-mode.css">');}})();
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&family=Cormorant+SC:wght@400;500&family=EB+Garamond:wght@400;500&family=Jost:wght@300;400&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/staff.css">
  <?php if (!empty($staffCss)): ?>
    <link rel="stylesheet" href="/assets/css/<?= $staffCss ?>">
  <?php endif; ?>
</head>
<body>

<header class="staff-topbar">
  <a class="staff-topbar-logo" href="<?= $dashboardUrl ?>">
    <img class="staff-topbar-logo-icon" src="/images/logo.webp" alt="" width="30" height="30">
    L'Éclipse
  </a>

  <nav class="staff-topbar-nav" aria-label="Navigation staff">
    <?php if ($staffRole === 'admin'): ?>
      <a class="staff-nav-link<?= ($staffActivePage ?? '') === 'users' ? ' is-active' : '' ?>" href="admin.php">Utilisateurs</a>
      <a class="staff-nav-link<?= ($staffActivePage ?? '') === 'orders' ? ' is-active' : '' ?>" href="commandes_restaurateur.php">Commandes</a>
      <a class="staff-nav-link<?= ($staffActivePage ?? '') === 'stats' ? ' is-active' : '' ?>" href="statistiques.php">Statistiques</a>
    <?php elseif ($staffRole === 'restaurateur'): ?>
      <a class="staff-nav-link<?= ($staffActivePage ?? '') === 'dashboard' ? ' is-active' : '' ?>" href="index_restaurateur.php">Tableau de bord</a>
      <a class="staff-nav-link<?= ($staffActivePage ?? '') === 'orders' ? ' is-active' : '' ?>" href="commandes_restaurateur.php">Commandes</a>
      <a class="staff-nav-link<?= ($staffActivePage ?? '') === 'carte' ? ' is-active' : '' ?>" href="gestion_carte.php">Carte</a>
    <?php elseif ($staffRole === 'livreur'): ?>
      <a class="staff-nav-link<?= ($staffActivePage ?? '') === 'dashboard' ? ' is-active' : '' ?>" href="index_livreur.php">Tableau de bord</a>
      <a class="staff-nav-link<?= ($staffActivePage ?? '') === 'deliveries' ? ' is-active' : '' ?>" href="livraison.php">Livraisons</a>
    <?php endif; ?>
  </nav>

  <div class="staff-topbar-utils">
    <button class="staff-theme-btn" id="themeToggle" aria-label="Changer de thème">
      <span class="staff-theme-icon" aria-hidden="true"></span>
    </button>
    <a class="staff-logout" href="deconnexion.php">Déconnexion</a>
  </div>
</header>
