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

// AJAX : bloquer / débloquer un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_block') {
    header('Content-Type: application/json');
    $targetUserId = (int) ($_POST['user_id'] ?? 0);
    $newBlocked   = (int) ($_POST['blocked'] ?? 0);

    if ($targetUserId === $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'error' => 'Vous ne pouvez pas bloquer votre propre compte.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET blocked = :blocked WHERE id = :id");
    $stmt->execute(['blocked' => $newBlocked, 'id' => $targetUserId]);
    echo json_encode(['success' => true, 'blocked' => $newBlocked]);
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
<?php
$staffRole = 'admin';
$staffActivePage = 'users';
$staffPageTitle = 'Administration';
$staffCss = 'admin.css';
$staffJs  = 'admin.js';
include 'includes/staff_header.php';
?>

<main class="staff-page">
  <div class="staff-inner">

    <header class="staff-header">
      <p class="staff-label">Espace administrateur</p>
      <h1 class="staff-title">Gestion des <em>utilisateurs</em></h1>
    </header>

    <?php
    // Stats
    $statsUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $statsOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $statsPending = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'en_attente'")->fetchColumn();
    $statsLivreurs = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'en_livraison'")->fetchColumn();
    ?>
    <div class="staff-stats">
      <div class="staff-stat-card">
        <span class="staff-stat-value"><?= $statsUsers ?></span>
        <span class="staff-stat-label">Utilisateurs</span>
      </div>
      <div class="staff-stat-card">
        <span class="staff-stat-value"><?= $statsOrders ?></span>
        <span class="staff-stat-label">Commandes totales</span>
      </div>
      <div class="staff-stat-card">
        <span class="staff-stat-value"><?= $statsPending ?></span>
        <span class="staff-stat-label">En attente</span>
      </div>
      <div class="staff-stat-card">
        <span class="staff-stat-value"><?= $statsLivreurs ?></span>
        <span class="staff-stat-label">En livraison</span>
      </div>
    </div>

    <?php if ($message): ?>
    <div class="staff-flash <?= str_contains($message, 'succès') ? 'staff-flash--success' : 'staff-flash--error' ?>">
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
            <th>Statut</th>
            <th>Prénom</th>
            <th>Nom</th>
            <th>Pseudo</th>
            <th>Téléphone</th>
            <th>Naissance</th>
            <th>Adresse</th>
            <th>Complément</th>
            <th>Inscrit le</th>
            <th>Dernière co.</th>
            <th></th>
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
            <td class="td-status">
              <?php if ($listedUser['id'] != $_SESSION['user_id']): ?>
              <button class="block-toggle<?= $listedUser['blocked'] ? ' is-blocked' : '' ?>"
                      data-block-toggle
                      data-user-id="<?= $listedUser['id'] ?>"
                      data-blocked="<?= $listedUser['blocked'] ?>"
                      type="button"
                      aria-label="<?= $listedUser['blocked'] ? 'Débloquer' : 'Bloquer' ?> <?= h($listedUser['login']) ?>">
                <span class="block-toggle-track">
                  <span class="block-toggle-thumb"></span>
                </span>
                <span class="block-toggle-label"><?= $listedUser['blocked'] ? 'Bloqué' : 'Actif' ?></span>
              </button>
              <?php else: ?>
              <span class="block-status is-self"><?= $listedUser['blocked'] ? 'Bloqué' : 'Actif' ?></span>
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
            <td class="td-action">
              <a href="profil.php?id=<?= $listedUser['id'] ?>" class="admin-profil-btn" title="Voir le profil de <?= h($listedUser['login']) ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M3 21v-2a7 7 0 0 1 7-7h4a7 7 0 0 1 7 7v2"/></svg>
                Profil
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</main>

<?php include 'includes/staff_footer.php'; ?>
