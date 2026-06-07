<?php
session_start();

require_once 'database.php';
require_once 'functions.php';

$pdo = getDatabaseConnection();

$svcStmt  = $pdo->query('SELECT * FROM services ORDER BY display_order');
$menuStmt = $pdo->prepare('SELECT * FROM menus WHERE service_id = ? ORDER BY id');
$mdStmt   = $pdo->prepare(
    'SELECT d.name, d.allergens, c.name as category_name FROM dishes d
     JOIN menu_dishes md ON md.dish_id = d.id
     LEFT JOIN categories c ON c.id = d.category_id
     WHERE md.menu_id = ?
     ORDER BY d.id'
);
$catStmt  = $pdo->prepare('SELECT * FROM categories WHERE service_id = ? ORDER BY display_order');
$dishStmt = $pdo->prepare('SELECT * FROM dishes WHERE category_id = ? ORDER BY id');

$catalogue = [];

foreach ($svcStmt->fetchAll(PDO::FETCH_ASSOC) as $svc) {
    $svc['url_slug'] = str_replace('_', '-', $svc['name']);
    $menuStmt->execute([$svc['id']]);
    $menus = $menuStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($menus as &$menu) {
        $mdStmt->execute([$menu['id']]);
        $dishes = $mdStmt->fetchAll(PDO::FETCH_ASSOC);
        $menu['dish_names'] = array_column($dishes, 'name');
        // Agréger les allergènes et catégories de tous les plats du menu
        $menuAllergens = [];
        $menuCategories = [];
        foreach ($dishes as $d) {
            if (!empty($d['allergens'])) {
                foreach (explode(',', $d['allergens']) as $a) {
                    $a = trim($a);
                    if ($a !== '') $menuAllergens[$a] = true;
                }
            }
            if (!empty($d['category_name'])) {
                $menuCategories[$d['category_name']] = true;
            }
        }
        $menu['allergens'] = implode(', ', array_keys($menuAllergens));
        $menu['categories'] = implode(',', array_keys($menuCategories));
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
<?php
$pageTitle = "Carte — L'Éclipse";
$pageCss = 'catalogue.css';
$isCataloguePage = true;

// ── Données pour le popup info produit ──────────────────
$popupDishes  = $pdo->query('SELECT id, name, description, price, allergens, nutritional_info FROM dishes')->fetchAll(PDO::FETCH_ASSOC);
$popupMenus   = $pdo->query('SELECT id, name, description, total_price, time_slots FROM menus')->fetchAll(PDO::FETCH_ASSOC);
$popupMenuDishes = $pdo->query('
    SELECT md.menu_id, d.id, d.name, d.description, d.price, d.allergens, d.nutritional_info
    FROM menu_dishes md JOIN dishes d ON d.id = md.dish_id ORDER BY md.menu_id, d.id
')->fetchAll(PDO::FETCH_ASSOC);

$popupData = ['dishes' => [], 'menus' => []];

foreach ($popupDishes as $d) {
    $img = catalogue_image($d['name'], '/images/placeholder-dish.svg');
    $popupData['dishes'][(int)$d['id']] = [
        'name'      => $d['name'],
        'desc'      => $d['description'] ?: '',
        'price'     => (float)$d['price'],
        'allergens' => $d['allergens'] ?: '',
        'nutrition' => $d['nutritional_info'] ?: '',
        'image'     => $img,
    ];
}

foreach ($popupMenus as $m) {
    $img = catalogue_image($m['name'], '/images/placeholder-menu.svg');
    $popupData['menus'][(int)$m['id']] = [
        'name'    => $m['name'],
        'desc'    => $m['description'] ?: '',
        'price'   => (float)$m['total_price'],
        'slots'   => $m['time_slots'] ?: '',
        'image'   => $img,
        'dishes'  => [],
    ];
}

foreach ($popupMenuDishes as $md) {
    $mid = (int)$md['menu_id'];
    if (isset($popupData['menus'][$mid])) {
        $img = catalogue_image($md['name'], '/images/placeholder-dish.svg');
        $popupData['menus'][$mid]['dishes'][] = [
            'id'        => (int)$md['id'],
            'name'      => $md['name'],
            'desc'      => $md['description'] ?: '',
            'price'     => (float)$md['price'],
            'allergens' => $md['allergens'] ?: '',
            'nutrition' => $md['nutritional_info'] ?: '',
            'image'     => $img,
        ];
    }
}
?>
<?php include 'includes/header.php'; ?>

<div class="catalogue-layout">

  <!-- ══ Sidebar — Filtres ═══════════════════════════════════ -->
  <aside class="cat-sidebar" data-filter-sidebar>

    <!-- Barre de recherche -->
    <div class="filter-search">
      <svg class="filter-search-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
        <circle cx="8.5" cy="8.5" r="5.5" stroke="currentColor" stroke-width="1.4"/>
        <line x1="12.5" y1="12.5" x2="17" y2="17" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
      </svg>
      <input type="text" class="filter-search-input"
             placeholder="Rechercher un plat…"
             data-filter-search
             autocomplete="off">
    </div>

    <!-- Toggle Formules uniquement -->
    <button class="filter-formulas-toggle" data-filter-formulas>
      <span class="filter-formulas-toggle-icon" aria-hidden="true">
        <svg viewBox="0 0 16 16" fill="none">
          <rect x="1" y="1" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.2"/>
          <line x1="1" y1="5.5" x2="15" y2="5.5" stroke="currentColor" stroke-width="1"/>
          <line x1="5.5" y1="5.5" x2="5.5" y2="15" stroke="currentColor" stroke-width="1"/>
        </svg>
      </span>
      <span class="filter-formulas-toggle-label">afficher les formules uniquement</span>
    </button>

    <!-- Allergènes -->
    <div class="filter-group">
      <h3 class="filter-label">Allergènes <span class="filter-label-hint">À EXCLURE</span></h3>
      <div class="filter-pills" data-filter-group="allergen">
        <button class="filter-pill" data-filter="gluten">Gluten</button>
        <button class="filter-pill" data-filter="lait">Lait</button>
        <button class="filter-pill" data-filter="oeuf">Œuf</button>
        <button class="filter-pill" data-filter="fruits à coque">Fruits à coque</button>
        <button class="filter-pill" data-filter="poisson">Poisson</button>
        <button class="filter-pill" data-filter="crustacés">Crustacés</button>
        <button class="filter-pill" data-filter="mollusques">Mollusques</button>
        <button class="filter-pill" data-filter="moutarde">Moutarde</button>
        <button class="filter-pill" data-filter="soja">Soja</button>
        <button class="filter-pill" data-filter="sulfites">Sulfites</button>
      </div>
    </div>

    <!-- Catégories -->
    <div class="filter-group">
      <h3 class="filter-label">Catégories</h3>
      <div class="filter-checkboxes" data-filter-group="category">
        <?php foreach ($catalogue as $svc): ?>
          <?php foreach ($svc['categories'] as $cat): ?>
          <?php if (empty($cat['dishes'])) continue; ?>
          <label class="filter-checkbox">
            <input type="checkbox" value="<?= h($cat['name']) ?>">
            <span class="filter-checkbox-label"><?= h(html_entity_decode($cat['label'], ENT_QUOTES, 'UTF-8')) ?></span>
          </label>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Réinitialiser -->
    <button class="filter-reset" data-filter-reset>
      <svg viewBox="0 0 16 16" fill="none" aria-hidden="true" class="filter-reset-icon">
        <path d="M2 8a6 6 0 0 1 10.47-3.72M14 8a6 6 0 0 1-10.47 3.72"
              stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
        <polyline points="12.5,2.3 12.5,6 8.8,6" stroke="currentColor" stroke-width="1.2"
                  stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Réinitialiser les filtres
    </button>

  </aside>

  <!-- ══ Contenu ═══════════════════════════════════════════════ -->
  <main class="cat-content">

    <?php foreach ($catalogue as $svc): ?>
    <section id="<?= h($svc['url_slug']) ?>" class="cat-service" data-spy-service data-slug="<?= h($svc['url_slug']) ?>">

      <header class="cat-service-header">
        <h2 class="cat-service-title"><?= h($svc['label']) ?></h2>
        <?php if ($svc['hours']): ?>
          <span class="cat-service-hours">✦ <?= h($svc['hours']) ?></span>
        <?php endif; ?>
      </header>

      <!-- Formules -->
      <?php if (!empty($svc['menus'])): ?>
      <section id="svc-<?= h($svc['name']) ?>--formules" class="cat-section">
        <h3 class="cat-section-title">Formules</h3>
        <div class="cat-menus-grid">
          <?php foreach ($svc['menus'] as $menu): ?>
          <?php $img = catalogue_image($menu['name'], '/images/placeholder-menu.svg'); ?>
          <article class="menu-card"
                   data-item-id="<?= (int)$menu['id'] ?>"
                   data-item-type="menu"
                   data-service="<?= h($svc['name']) ?>"
                   data-category="formules"
                   data-categories="<?= h($menu['categories'] ?? '') ?>"
                   data-search="<?= h(strtolower($menu['name'] . ' ' . $menu['description'] . ' ' . implode(' ', $menu['dish_names']))) ?>"
                   data-allergens="<?= h(strtolower($menu['allergens'] ?? '')) ?>">
            <div class="menu-card-img">
              <img src="<?= $img ?>" width="600" height="420" loading="lazy" alt="">
            </div>
            <div class="menu-card-body">
              <h4 class="menu-card-name"><?= h($menu['name']) ?></h4>
              <div class="card-footer">
                <span class="price"><?= number_format($menu['total_price'], 2, ',', ' ') ?>&nbsp;€</span>
                <button type="button" class="add-btn add-btn--quick"
                        data-quick-add
                        data-item-id="<?= (int)$menu['id'] ?>"
                        data-item-type="menu"
                        data-quantity="1">Ajout rapide</button>
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
      <section id="cat-<?= h($svc['name']) ?>--<?= h($cat['name']) ?>" class="cat-section">
        <h3 class="cat-section-title"><?= $cat['label'] ?></h3>
        <div class="cat-dishes-grid">
          <?php foreach ($cat['dishes'] as $dish): ?>
          <?php $img = catalogue_image($dish['name'], '/images/placeholder-dish.svg'); ?>
          <article class="dish-card"
                   data-item-id="<?= (int)$dish['id'] ?>"
                   data-item-type="dish"
                   data-service="<?= h($svc['name']) ?>"
                   data-category="<?= h($cat['name']) ?>"
                   data-search="<?= h(strtolower($dish['name'] . ' ' . ($dish['description'] ?? ''))) ?>"
                   data-allergens="<?= h(strtolower($dish['allergens'] ?? '')) ?>">
            <div class="dish-card-img">
              <img src="<?= $img ?>" width="450" height="340" loading="lazy" alt="">
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
                <button type="button" class="add-btn add-btn--quick"
                        data-quick-add
                        data-item-id="<?= (int)$dish['id'] ?>"
                        data-item-type="dish"
                        data-quantity="1">Ajout rapide</button>
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

<!-- ══ Drawer mobile (filtres) ══════════════════════════════ -->
<div class="filter-backdrop" data-filter-backdrop></div>

<aside class="filter-drawer" data-filter-drawer aria-hidden="true">
  <div class="filter-drawer-header">
    <h2 class="filter-drawer-title">Filtres</h2>
    <button class="filter-drawer-close" data-filter-drawer-close aria-label="Fermer les filtres">
      <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
        <line x1="5" y1="5" x2="15" y2="15" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
        <line x1="15" y1="5" x2="5" y2="15" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
      </svg>
    </button>
  </div>

  <div class="filter-drawer-body">

    <!-- Barre de recherche -->
    <div class="filter-search">
      <svg class="filter-search-icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
        <circle cx="8.5" cy="8.5" r="5.5" stroke="currentColor" stroke-width="1.4"/>
        <line x1="12.5" y1="12.5" x2="17" y2="17" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
      </svg>
      <input type="text" class="filter-search-input"
             placeholder="Rechercher un plat…"
             data-filter-search-drawer
             autocomplete="off">
    </div>

    <!-- Toggle Formules uniquement -->
    <button class="filter-formulas-toggle" data-filter-formulas-drawer>
      <span class="filter-formulas-toggle-icon" aria-hidden="true">
        <svg viewBox="0 0 16 16" fill="none">
          <rect x="1" y="1" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.2"/>
          <line x1="1" y1="5.5" x2="15" y2="5.5" stroke="currentColor" stroke-width="1"/>
          <line x1="5.5" y1="5.5" x2="5.5" y2="15" stroke="currentColor" stroke-width="1"/>
        </svg>
      </span>
      <span class="filter-formulas-toggle-label">afficher les formules uniquement</span>
    </button>

    <!-- Allergènes -->
    <div class="filter-group">
      <h3 class="filter-label">Allergènes <span class="filter-label-hint">À EXCLURE</span></h3>
      <div class="filter-pills" data-filter-group-drawer="allergen">
        <button class="filter-pill" data-filter="gluten">Gluten</button>
        <button class="filter-pill" data-filter="lait">Lait</button>
        <button class="filter-pill" data-filter="oeuf">Œuf</button>
        <button class="filter-pill" data-filter="fruits à coque">Fruits à coque</button>
        <button class="filter-pill" data-filter="poisson">Poisson</button>
        <button class="filter-pill" data-filter="crustacés">Crustacés</button>
        <button class="filter-pill" data-filter="mollusques">Mollusques</button>
        <button class="filter-pill" data-filter="moutarde">Moutarde</button>
        <button class="filter-pill" data-filter="soja">Soja</button>
        <button class="filter-pill" data-filter="sulfites">Sulfites</button>
      </div>
    </div>

    <!-- Catégories -->
    <div class="filter-group">
      <h3 class="filter-label">Catégories</h3>
      <div class="filter-checkboxes" data-filter-group-drawer="category">
        <?php foreach ($catalogue as $svc): ?>
          <?php foreach ($svc['categories'] as $cat): ?>
          <?php if (empty($cat['dishes'])) continue; ?>
          <label class="filter-checkbox">
            <input type="checkbox" value="<?= h($cat['name']) ?>">
            <span class="filter-checkbox-label"><?= h(html_entity_decode($cat['label'], ENT_QUOTES, 'UTF-8')) ?></span>
          </label>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Réinitialiser -->
    <button class="filter-reset" data-filter-reset-drawer>
      <svg viewBox="0 0 16 16" fill="none" aria-hidden="true" class="filter-reset-icon">
        <path d="M2 8a6 6 0 0 1 10.47-3.72M14 8a6 6 0 0 1-10.47 3.72"
              stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/>
        <polyline points="12.5,2.3 12.5,6 8.8,6" stroke="currentColor" stroke-width="1.2"
                  stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      Réinitialiser les filtres
    </button>

  </div>
</aside>

<!-- FAB mobile -->
<button class="filter-fab" data-filter-fab aria-label="Filtres">
  <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
    <line x1="4" y1="6" x2="16" y2="6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
    <line x1="7" y1="10" x2="16" y2="10" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
    <line x1="10" y1="14" x2="16" y2="14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
    <circle cx="4.5" cy="10" r="1.5" fill="currentColor"/>
    <circle cx="7.5" cy="14" r="1.5" fill="currentColor"/>
  </svg>
</button>

<?php $page_js = 'assets/js/catalogue.js'; ?>

<!-- ══ Popup Info Produit ═══════════════════════════════════ -->
<div class="item-modal-backdrop" data-item-modal-backdrop></div>

<dialog class="item-modal" data-item-modal>
  <div class="item-modal-inner" data-item-modal-inner>
    <!-- Rempli par JS -->
  </div>
  <button class="item-modal-close" data-item-modal-close aria-label="Fermer">
    <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
      <line x1="5" y1="5" x2="15" y2="15"/><line x1="15" y1="5" x2="5" y2="15"/>
    </svg>
  </button>
</dialog>

<!-- ══ Données popup (JSON) ══════════════════════════════════ -->
<script id="catalogue-popup-data" type="application/json"><?= json_encode($popupData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<!-- ══ Popup + Card click (JS) ══════════════════════════════ -->
<script>
(function() {
  'use strict';

  /* ── Popup data ────────────────────────────────────── */
  var dataScript = document.getElementById('catalogue-popup-data');
  if (!dataScript) return;

  var popupData = JSON.parse(dataScript.textContent);
  var backdrop  = document.querySelector('[data-item-modal-backdrop]');
  var dialog    = document.querySelector('[data-item-modal]');
  var inner     = document.querySelector('[data-item-modal-inner]');
  var closeBtn  = document.querySelector('[data-item-modal-close]');

  if (!dialog || !backdrop) return;

  var currentItem = null; // { id, type }

  function closeModal() {
    dialog.removeAttribute('open');
    backdrop.classList.remove('is-active');
    document.body.style.overflow = '';
    currentItem = null;
  }

  function openModal() {
    dialog.setAttribute('open', '');
    backdrop.classList.add('is-active');
    document.body.style.overflow = 'hidden';
  }

  closeBtn && closeBtn.addEventListener('click', closeModal);
  backdrop  && backdrop.addEventListener('click', closeModal);

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && dialog.hasAttribute('open')) closeModal();
  });

  /* ── Render helpers ─────────────────────────────────── */

  function priceStr(p) {
    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(p).replace(/\u202F/g, ' ') + ' €';
  }

  function renderAllergens(a) {
    if (!a) return '';
    var tags = a.split(',').map(function(s) { return s.trim(); }).filter(Boolean);
    if (!tags.length) return '';
    return '<div class="im-allergens">' +
      '<span class="im-label">Allergènes</span>' +
      '<div class="im-pills">' + tags.map(function(t) {
        return '<span class="im-pill">' + t + '</span>';
      }).join('') + '</div></div>';
  }

  /* ── Qty stepper HTML ───────────────────────────────── */

  function renderStepper() {
    return '<div class="im-stepper">' +
      '<button type="button" class="im-stepper-btn" data-stepper="minus" aria-label="Réduire">−</button>' +
      '<span class="im-stepper-val">1</span>' +
      '<button type="button" class="im-stepper-btn" data-stepper="plus" aria-label="Augmenter">+</button>' +
    '</div>';
  }

  function renderAddForm(id, type) {
    return '<form action="panier.php" method="post" class="im-add-form">' +
      '<input type="hidden" name="action" value="add">' +
      '<input type="hidden" name="item_id" value="' + id + '">' +
      '<input type="hidden" name="item_type" value="' + type + '">' +
      '<input type="hidden" name="quantity" value="1" class="im-qty-input">' +
      '<input type="hidden" name="redirect" value="catalogue.php">' +
      renderStepper() +
      '<button type="submit" class="im-add-btn">Ajouter au panier</button>' +
    '</form>';
  }

  /* ── Popup "Ajouter au panier" via AJAX ─────────────── */

  inner.addEventListener('submit', function(e) {
    var form = e.target.closest('.im-add-form');
    if (!form) return;
    e.preventDefault();

    var itemId   = parseInt(form.querySelector('[name="item_id"]').value, 10);
    var itemType = form.querySelector('[name="item_type"]').value;
    var quantity = parseInt(form.querySelector('[name="quantity"]').value, 10) || 1;

    // Chercher le bouton Ajout rapide correspondant sur la carte
    var cardBtn = document.querySelector('[data-item-id="' + itemId + '"][data-item-type="' + itemType + '"] [data-quick-add]');
    var sourceEl = (cardBtn && cardBtn.offsetParent !== null) ? cardBtn : form.querySelector('.im-add-btn');

    window._cartQuickAdd(itemId, itemType, quantity, sourceEl).then(function() {
      closeModal();
    });
  });

  /* ── Stepper logic (delegated) ──────────────────────── */

  inner.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-stepper]');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    var dir = btn.dataset.stepper;
    var container = btn.closest('.im-add-form');
    if (!container) return;
    var valEl = container.querySelector('.im-stepper-val');
    var input = container.querySelector('.im-qty-input');
    var v = parseInt(valEl.textContent, 10);
    v = dir === 'plus' ? v + 1 : v - 1;
    v = Math.max(1, Math.min(v, 20));
    valEl.textContent = v;
    if (input) input.value = v;
  });

  /* ── Dish card inside formula popup ─────────────────── */

  function renderDishCard(dish, showInfoBtn) {
    var descHtml = dish.desc ? '<span class="im-dish-card-desc">' + dish.desc + '</span>' : '';
    var infoBtnHtml = showInfoBtn
      ? '<button type="button" class="im-dish-info-btn" data-dish-id="' + dish.id + '" aria-label="Infos"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg></button>'
      : '';
    return '<div class="im-dish-card">' +
      '<img class="im-dish-card-img" src="' + dish.image + '" alt="" loading="lazy">' +
      '<div class="im-dish-card-body">' +
        '<span class="im-dish-card-name">' + dish.name + '</span>' +
        '<span class="im-dish-card-price">' + priceStr(dish.price) + '</span>' +
        descHtml +
      '</div>' +
      infoBtnHtml +
    '</div>';
  }

  /* ── Show dish popup ────────────────────────────────── */

  function showDish(dishId) {
    var d = popupData.dishes[dishId];
    if (!d) return;
    currentItem = { id: dishId, type: 'dish' };

    var html = '<div class="im-header">' +
      '<img class="im-hero" src="' + d.image + '" alt="" loading="lazy">' +
      '<div class="im-hero-info">' +
        '<h2 class="im-title">' + d.name + '</h2>' +
        '<span class="im-price">' + priceStr(d.price) + '</span>' +
      '</div>' +
    '</div>';

    if (d.desc) html += '<p class="im-desc">' + d.desc + '</p>';
    html += renderAllergens(d.allergens);
    html += '<div class="im-bottom">' + renderAddForm(dishId, 'dish') + '</div>';

    inner.innerHTML = html;
    openModal();
    dialog.scrollTop = 0;
  }

  /* ── Show menu popup ────────────────────────────────── */

  function showMenu(menuId) {
    var m = popupData.menus[menuId];
    if (!m) return;
    currentItem = { id: menuId, type: 'menu' };

    var html = '<div class="im-header">' +
      '<img class="im-hero" src="' + m.image + '" alt="" loading="lazy">' +
      '<div class="im-hero-info">' +
        '<h2 class="im-title">' + m.name + '</h2>' +
        '<span class="im-price">' + priceStr(m.price) + '</span>' +
      '</div>' +
    '</div>';

    if (m.desc) html += '<p class="im-desc">' + m.desc + '</p>';
    if (m.slots) html += '<p class="im-slots">' + m.slots + '</p>';

    if (m.dishes && m.dishes.length) {
      html += '<div class="im-section">' +
        '<h3 class="im-section-title">Les plats de la formule</h3>' +
        '<div class="im-dishes-list">' +
          m.dishes.map(function(d) { return renderDishCard(d, true); }).join('') +
        '</div>' +
      '</div>';
    }

    html += '<div class="im-bottom">' + renderAddForm(menuId, 'menu') + '</div>';

    inner.innerHTML = html;
    openModal();
    dialog.scrollTop = 0;

    inner.querySelectorAll('.im-dish-info-btn').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        var did = parseInt(btn.dataset.dishId, 10);
        if (did) showDish(did);
      });
    });
  }

  /* ── Card click → open popup ────────────────────────── */

  document.addEventListener('click', function(e) {
    var card = e.target.closest('.menu-card, .dish-card');
    if (!card) return;

    // Ne pas ouvrir si le clic était sur un bouton d'ajout rapide
    if (e.target.closest('button[type="submit"], [data-quick-add]')) return;

    e.preventDefault();
    var id   = parseInt(card.dataset.itemId, 10);
    var type = card.dataset.itemType;
    if (type === 'menu') showMenu(id);
    else if (type === 'dish') showDish(id);
  });

})();
</script>
<?php include 'includes/footer.php'; ?>
