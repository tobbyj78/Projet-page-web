<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../database.php';
    $pdo = getDatabaseConnection();
}
require_once __DIR__ . '/../models/navbar.php';
$navData = getNavbarData($pdo);

// Si l'utilisateur est connecté mais que $user est absent ou incomplet, on le récupère
if (empty($user) && isset($_SESSION['user_id'])) {
    $stmtUser = $pdo->prepare('SELECT first_name, nickname, role, blocked FROM users WHERE id = :id');
    $stmtUser->execute(['id' => $_SESSION['user_id']]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC) ?: null;
} elseif (!empty($user) && !isset($user['nickname']) && isset($_SESSION['user_id'])) {
    $stmtNick = $pdo->prepare('SELECT nickname, blocked FROM users WHERE id = :id');
    $stmtNick->execute(['id' => $_SESSION['user_id']]);
    $rowNick = $stmtNick->fetch(PDO::FETCH_ASSOC);
    if ($rowNick) {
        $user['nickname'] = $rowNick['nickname'];
        $user['blocked'] = $rowNick['blocked'];
    }
}

// Vérification blocage : détruit la session si le compte est bloqué
if (!empty($user) && ($user['blocked'] ?? 0) == 1) {
    session_destroy();
    header('Location: connexion.php?blocked=1');
    exit;
}

// Compteur panier : quantité totale d'articles
$cartTotalQty = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartTotalQty += (int) ($item['quantity'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? "L'Éclipse" ?></title>
  <link rel="icon" href="/images/favicon.ico">

  <script>
  // Vérification du cookie de thème AVANT le chargement des CSS → pas de flash
  (function(){var m=document.cookie.match(/(?:^|;\s*)theme=([^;]*)/);var t=m?decodeURIComponent(m[1]):null;if(t==='light'){document.documentElement.setAttribute('data-theme','light');document.write('<link rel="stylesheet" href="/assets/css/light-mode.css">');}})();
  </script>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&family=Cormorant+SC:wght@400;500&family=EB+Garamond:wght@400;500&family=Jost:wght@300;400&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/navbar.css">
  <?php if (!empty($pageCss)): ?>
    <link rel="stylesheet" href="/assets/css/<?= $pageCss ?>">
  <?php endif; ?>
</head>
<body<?= isset($isCataloguePage) && $isCataloguePage ? ' data-catalogue-page' : '' ?>>

<header class="navbar" data-navbar>
  <div class="navbar-inner">

    <a href="/" class="logo" aria-label="L'Éclipse">
      <img class="logo-image" src="/images/logo.webp" alt="" width="38" height="38">
      <span class="logo-text">L'Éclipse</span>
    </a>

    <nav aria-label="Navigation principale">
      <ul class="nav-list">

        <?php foreach ($navData['services'] as $svc):
          $key     = $svc['name'];
          $slug    = str_replace('_', '-', $key);
          $svcData = $navData['data'][$key];
          $cats    = $svcData['categories'];
        ?>
        <li class="<?= $svc['li_class'] ?>">
          <a class="nav-btn" href="catalogue.php#<?= htmlspecialchars($slug) ?>" aria-haspopup="true" aria-expanded="false" data-menu-trigger>
            <?= htmlspecialchars($svc['label']) ?>
          </a>

          <div class="dropdown" aria-hidden="true" data-dropdown>
            <div class="dropdown-inner">
              <div class="dropdown-grid<?= $svc['compact'] ? ' compact' : '' ?>" data-showcase>

                <div class="panel-identity">
                  <div>
                    <?php if ($svc['hours']): ?>
                      <p class="panel-hours"><span aria-hidden="true">✦</span> <?= $svc['hours'] ?></p>
                    <?php endif; ?>
                    <h2 class="panel-title"><?= $svc['title_html'] ?></h2>
                  </div>
                  <div class="panel-image" role="img" aria-label="<?= htmlspecialchars($svc['image_alt']) ?>"
                       style="background-image:linear-gradient(180deg,<?= $svc['gradient'] ?>),url('<?= $svc['image_url'] ?>');"></div>
                </div>

                <?php if (!$svc['compact'] && !empty($svcData['formulas'])): ?>
                <div>
                  <h3 class="col-label"><?= $svc['formulas_label'] ?></h3>
                  <ul class="formulas">
                    <?php foreach ($svcData['formulas'] as $formula): ?>
                    <li><a class="formula" href="catalogue.php#svc-<?= htmlspecialchars($key) ?>--formules">
                      <span class="formula-name"><?= htmlspecialchars($formula['name']) ?></span>
                      <span class="formula-desc"><?= htmlspecialchars($formula['description']) ?></span>
                    </a></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
                <?php endif; ?>

                <div>
                  <h3 class="col-label">La Carte</h3>
                  <ul class="cat-list">
                    <?php $i = 0; foreach ($cats as $catSlug => $catData): ?>
                    <li>
                      <a class="cat-btn<?= $i === 0 ? ' is-active' : '' ?>" data-target="<?= htmlspecialchars($catSlug) ?>" href="catalogue.php#cat-<?= htmlspecialchars($key) ?>--<?= htmlspecialchars($catSlug) ?>">
                        <?= $catData['label'] ?>
                      </a>
                    </li>
                    <?php $i++; endforeach; ?>
                  </ul>
                </div>

                <div class="dishes-stage">
                  <?php $i = 0; foreach ($cats as $catSlug => $catData): ?>
                  <div class="dishes<?= $i === 0 ? ' is-active' : '' ?>"
                       data-panel="<?= htmlspecialchars($catSlug) ?>"
                       aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>">
                    <ul class="dishes-list">
                      <?php foreach ($catData['dishes'] as $dish): ?>
                      <li><a href="catalogue.php#cat-<?= htmlspecialchars($key) ?>--<?= htmlspecialchars($catSlug) ?>"><?= htmlspecialchars($dish['name']) ?></a></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                  <?php $i++; endforeach; ?>
                </div>

                <div class="cta-col">
                  <?php if ($svcData['has_more']): ?>
                  <p class="cta-more">et plus encore&hellip;</p>
                  <?php endif; ?>
                  <a href="catalogue.php#<?= htmlspecialchars($slug) ?>" class="cta-btn">
                    <span>Découvrir</span>
                    <span class="cta-arrow" aria-hidden="true">→</span>
                  </a>
                </div>

              </div>
            </div>
          </div>
        </li>
        <?php endforeach; ?>

      </ul>
    </nav>

    <div class="navbar-utils">

      <div class="profile" data-profile>
        <?php if (empty($user)): ?>
          <a href="connexion.php" class="icon-btn profile-btn" aria-label="Mon compte" data-profile-trigger>
            <span class="profile-icon" aria-hidden="true"></span>
            <span class="profile-dot" aria-hidden="true"></span>
          </a>
          <div class="profile-tooltip" aria-hidden="true" data-profile-tooltip>
            <span>Vous n'êtes pas connecté</span>
          </div>
        <?php else:
            $displayName = $user['nickname'] ?: ($user['first_name'] ?? 'Compte');
            if (mb_strlen($displayName) > 9) {
                $displayName = mb_substr($displayName, 0, 9) . '…';
            }
        ?>
          <a href="profil.php" class="profile-box" aria-label="Mon compte — <?= htmlspecialchars($user['first_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <span class="profile-box-nickname"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="profile-box-icon" aria-hidden="true"></span>
          </a>
        <?php endif; ?>
      </div>

      <button class="icon-btn theme-btn" id="themeToggle" aria-label="Changer de thème">
        <span class="theme-icon" aria-hidden="true"></span>
      </button>

      <?php if ($cartTotalQty === 0): ?>
        <a class="order-btn" href="catalogue.php">Commander</a>
      <?php else: ?>
        <a class="order-btn has-items" href="panier.php">
          Ma commande
          <span class="cart-badge"><?= $cartTotalQty ?></span>
        </a>
      <?php endif; ?>
    </div>

  </div>

  <div class="mobile-bar" data-mobile-bar>
    <?php if ($cartTotalQty === 0): ?>
      <a class="mobile-bar-btn" href="catalogue.php">Commander</a>
    <?php else: ?>
      <a class="mobile-bar-btn has-items" href="panier.php">
        Ma commande
        <span class="cart-badge"><?= $cartTotalQty ?></span>
      </a>
    <?php endif; ?>
  </div>
</header>

<div id="nav-backdrop"></div>
