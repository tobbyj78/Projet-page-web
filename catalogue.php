<?php
session_start();

require_once 'database.php';
require_once 'functions.php';

$pdo = getDatabaseConnection();

$svcStmt  = $pdo->query('SELECT * FROM services ORDER BY display_order');
$menuStmt = $pdo->prepare('SELECT * FROM menus WHERE service_id = ? ORDER BY id');
$mdStmt   = $pdo->prepare(
    'SELECT d.name FROM dishes d
     JOIN menu_dishes md ON md.dish_id = d.id
     WHERE md.menu_id = ?
     ORDER BY d.id'
);
$catStmt  = $pdo->prepare('SELECT * FROM categories WHERE service_id = ? ORDER BY display_order');
$dishStmt = $pdo->prepare('SELECT * FROM dishes WHERE category_id = ? ORDER BY id');

$catalogue = [];

foreach ($svcStmt->fetchAll(PDO::FETCH_ASSOC) as $svc) {
    $menuStmt->execute([$svc['id']]);
    $menus = $menuStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($menus as &$menu) {
        $mdStmt->execute([$menu['id']]);
        $menu['dish_names'] = array_column($mdStmt->fetchAll(PDO::FETCH_ASSOC), 'name');
    }
    unset($menu);

    $catStmt->execute([$svc['id']]);
    $cats = $catStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cats as &$cat) {
        $dishStmt->execute([$cat['id']]);
        $cat['dishes'] = $dishStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($cat);

    $catalogue[] = array_merge($svc, ['menus' => $menus, 'categories' => $cats]);
}
?>
<?php $pageTitle = "Carte — L'Éclipse"; $pageCss = 'catalogue.css'; ?>
<?php include 'includes/header.php'; ?>

<div class="catalogue-layout">

  <!-- ══ Sidebar ══════════════════════════════════════════════ -->
  <aside class="cat-sidebar">
    <nav class="cat-nav" aria-label="Navigation du catalogue">
      <?php foreach ($catalogue as $svc): ?>
      <div class="cat-nav-service">
        <span class="cat-nav-svc-label"><?= h($svc['label']) ?></span>
        <ul class="cat-nav-list">
          <?php if (!empty($svc['menus'])): ?>
          <li>
            <a href="#svc-<?= h($svc['name']) ?>--formules" class="cat-nav-link">Formules</a>
          </li>
          <?php endif; ?>
          <?php foreach ($svc['categories'] as $cat): ?>
          <?php if (empty($cat['dishes'])) continue; ?>
          <li>
            <a href="#cat-<?= h($svc['name']) ?>--<?= h($cat['name']) ?>" class="cat-nav-link">
              <?= $cat['label'] ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endforeach; ?>
    </nav>
  </aside>

  <!-- ══ Contenu ═══════════════════════════════════════════════ -->
  <main class="cat-content">

    <?php foreach ($catalogue as $svc): ?>
    <section id="svc-<?= h($svc['name']) ?>" class="cat-service">

      <header class="cat-service-header">
        <h2 class="cat-service-title"><?= h($svc['label']) ?></h2>
        <?php if ($svc['hours']): ?>
          <span class="cat-service-hours">✦ <?= h($svc['hours']) ?></span>
        <?php endif; ?>
      </header>

      <!-- Formules -->
      <?php if (!empty($svc['menus'])): ?>
      <section id="svc-<?= h($svc['name']) ?>--formules" class="cat-section" data-spy>
        <h3 class="cat-section-title">Formules</h3>
        <div class="cat-menus-grid">
          <?php foreach ($svc['menus'] as $menu): ?>
          <?php $img = !empty($menu['image']) ? h($menu['image']) : '/images/placeholder-menu.svg'; ?>
          <article class="menu-card">
            <div class="menu-card-img">
              <img src="<?= $img ?>" width="400" height="300" loading="lazy" alt="">
            </div>
            <div class="menu-card-body">
              <h4 class="menu-card-name"><?= h($menu['name']) ?></h4>
              <?php if ($menu['description']): ?>
              <p class="menu-card-desc"><?= h($menu['description']) ?></p>
              <?php endif; ?>
              <?php if (!empty($menu['dish_names'])): ?>
              <p class="menu-card-dishes"><?= h(implode(' · ', $menu['dish_names'])) ?></p>
              <?php endif; ?>
              <?php if ($menu['time_slots']): ?>
              <p class="menu-card-slots"><?= h($menu['time_slots']) ?></p>
              <?php endif; ?>
              <div class="card-footer">
                <span class="price"><?= number_format($menu['total_price'], 2, ',', ' ') ?>&nbsp;€</span>
                <form action="panier.php" method="post">
                  <input type="hidden" name="action"    value="add">
                  <input type="hidden" name="item_id"   value="<?= (int)$menu['id'] ?>">
                  <input type="hidden" name="item_type" value="menu">
                  <input type="hidden" name="quantity"  value="1">
                  <input type="hidden" name="redirect"  value="catalogue.php">
                  <button type="submit" class="add-btn">Ajouter</button>
                </form>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endif; ?>

      <!-- Catégories de plats -->
      <?php foreach ($svc['categories'] as $cat): ?>
      <?php if (empty($cat['dishes'])) continue; ?>
      <section id="cat-<?= h($svc['name']) ?>--<?= h($cat['name']) ?>" class="cat-section" data-spy>
        <h3 class="cat-section-title"><?= $cat['label'] ?></h3>
        <div class="cat-dishes-grid">
          <?php foreach ($cat['dishes'] as $dish): ?>
          <?php $img = !empty($dish['image']) ? h($dish['image']) : '/images/placeholder-dish.svg'; ?>
          <article class="dish-card">
            <div class="dish-card-img">
              <img src="<?= $img ?>" width="250" height="250" loading="lazy" alt="">
            </div>
            <div class="dish-card-body">
              <h4 class="dish-card-name"><?= h($dish['name']) ?></h4>
              <?php if ($dish['description']): ?>
              <p class="dish-card-desc"><?= h($dish['description']) ?></p>
              <?php endif; ?>
              <?php if ($dish['allergens']): ?>
              <p class="dish-card-allergens"><?= h($dish['allergens']) ?></p>
              <?php endif; ?>
              <div class="card-footer">
                <span class="price"><?= number_format($dish['price'], 2, ',', ' ') ?>&nbsp;€</span>
                <form action="panier.php" method="post">
                  <input type="hidden" name="action"    value="add">
                  <input type="hidden" name="item_id"   value="<?= (int)$dish['id'] ?>">
                  <input type="hidden" name="item_type" value="dish">
                  <input type="hidden" name="quantity"  value="1">
                  <input type="hidden" name="redirect"  value="catalogue.php">
                  <button type="submit" class="add-btn">Ajouter</button>
                </form>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endforeach; ?>

    </section>
    <?php endforeach; ?>

  </main>

</div>

<?php $page_js = 'assets/js/catalogue.js'; ?>
<?php include 'includes/footer.php'; ?>
