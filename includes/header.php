<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../database.php';
    $pdo = getDatabaseConnection();
}
require_once __DIR__ . '/../models/navbar.php';
$navData = getNavbarData($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? "L'Éclipse" ?></title>
  <link rel="icon" href="/images/favicon.ico">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&family=Cormorant+SC:wght@400;500&family=EB+Garamond:wght@400;500&family=Jost:wght@300;400&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/navbar.css">
  <?php if (!empty($pageCss)): ?>
    <link rel="stylesheet" href="/assets/css/<?= $pageCss ?>">
  <?php endif; ?>
</head>
<body>

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
          $svcData = $navData['data'][$key];
          $cats    = $svcData['categories'];
        ?>
        <li class="<?= $svc['li_class'] ?>">
          <button class="nav-btn" aria-haspopup="true" aria-expanded="false" data-menu-trigger>
            <?= htmlspecialchars($svc['label']) ?>
          </button>

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
                    <?php $i = 0; foreach ($cats as $slug => $catData): ?>
                    <li>
                      <a class="cat-btn<?= $i === 0 ? ' is-active' : '' ?>" data-target="<?= htmlspecialchars($slug) ?>" href="catalogue.php#cat-<?= htmlspecialchars($key) ?>--<?= htmlspecialchars($slug) ?>">
                        <?= $catData['label'] ?>
                      </a>
                    </li>
                    <?php $i++; endforeach; ?>
                  </ul>
                </div>

                <div class="dishes-stage">
                  <?php $i = 0; foreach ($cats as $slug => $catData): ?>
                  <div class="dishes<?= $i === 0 ? ' is-active' : '' ?>"
                       data-panel="<?= htmlspecialchars($slug) ?>"
                       aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>">
                    <ul class="dishes-list">
                      <?php foreach ($catData['dishes'] as $dish): ?>
                      <li><a href="catalogue.php#cat-<?= htmlspecialchars($key) ?>--<?= htmlspecialchars($slug) ?>"><?= htmlspecialchars($dish['name']) ?></a></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                  <?php $i++; endforeach; ?>
                </div>

                <div class="cta-col">
                  <?php if ($svcData['has_more']): ?>
                  <p class="cta-more">et plus encore&hellip;</p>
                  <?php endif; ?>
                  <a href="catalogue.php#svc-<?= htmlspecialchars($key) ?>" class="cta-btn">
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
        <button class="icon-btn" aria-label="Mon compte" aria-haspopup="true" aria-expanded="false" data-profile-trigger>
          <span class="profile-icon" aria-hidden="true"></span>
        </button>

        <div class="profile-dropdown" aria-hidden="true" data-profile-dropdown>
          <?php if (empty($user)): ?>
            <a href="login.php"    class="profile-link">Connexion</a>
            <a href="register.php" class="profile-link">Créer un compte</a>
          <?php else: ?>
            <span class="profile-name"><?= htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') ?></span>
            <a href="catalogue.php"     class="profile-link">Catalogue</a>
            <a href="panier.php"        class="profile-link">Panier</a>
            <a href="profil_client.php" class="profile-link">Mon profil</a>
            <a href="logout.php"        class="profile-link logout">Déconnexion</a>
          <?php endif; ?>
        </div>
      </div>

      <button class="icon-btn theme-btn" id="themeToggle" aria-label="Changer de thème">
        <span class="theme-icon" aria-hidden="true"></span>
      </button>

      <a class="order-btn" href="#commander">Commander</a>
    </div>

  </div>

  <div class="mobile-bar" data-mobile-bar>
    <a class="mobile-bar-btn" href="#commander">Commander</a>
  </div>
</header>

<div id="nav-backdrop"></div>
