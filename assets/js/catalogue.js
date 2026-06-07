// Catalogue — Filtres + scroll-spy navbar + drawer mobile
// L'Éclipse

(function () {
  'use strict';

  // ── DOM refs ──────────────────────────────────────────
  const cards            = document.querySelectorAll('.menu-card, .dish-card');
  const serviceSections  = document.querySelectorAll('.cat-service[data-slug]');
  const categorySections = document.querySelectorAll('.cat-section[id^="cat-"]');

  // Sidebar
  const sidebarSearch   = document.querySelector('[data-filter-search]');
  const sidebarFormulas = document.querySelector('[data-filter-formulas]');
  const sidebarAllergens= document.querySelector('[data-filter-group="allergen"]');
  const sidebarCats     = document.querySelector('[data-filter-group="category"]');
  const sidebarReset    = document.querySelector('[data-filter-reset]');
  const sidebar         = document.querySelector('[data-filter-sidebar]');

  // Drawer
  const drawerSearch   = document.querySelector('[data-filter-search-drawer]');
  const drawerFormulas = document.querySelector('[data-filter-formulas-drawer]');
  const drawerAllergens= document.querySelector('[data-filter-group-drawer="allergen"]');
  const drawerCats     = document.querySelector('[data-filter-group-drawer="category"]');
  const drawerReset    = document.querySelector('[data-filter-reset-drawer]');
  const drawer         = document.querySelector('[data-filter-drawer]');
  const backdrop       = document.querySelector('[data-filter-backdrop]');
  const drawerClose    = document.querySelector('[data-filter-drawer-close]');
  const fab            = document.querySelector('[data-filter-fab]');

  // Navbar
  const navItems = document.querySelectorAll('.nav-item');

  // Empty state
  let emptyMsg = document.querySelector('.filter-empty');
  if (!emptyMsg) {
    emptyMsg = document.createElement('div');
    emptyMsg.className = 'filter-empty';
    emptyMsg.textContent = 'Aucun plat ne correspond à vos critères.';
    const content = document.querySelector('.cat-content');
    if (content) content.appendChild(emptyMsg);
  }

  if (!cards.length) return;

  // ── State ─────────────────────────────────────────────
  let formulasOnly    = false;
  let activeAllergens = new Set();
  let searchQuery     = '';

  function getActiveCategories(container) {
    const cats = new Set();
    const cbs = (container || document).querySelectorAll(
      '[data-filter-group="category"] input[type="checkbox"]:checked, ' +
      '[data-filter-group-drawer="category"] input[type="checkbox"]:checked'
    );
    cbs.forEach(cb => cats.add(cb.value));
    return cats;
  }

  function hasActiveFilters() {
    return formulasOnly ||
           getActiveCategories().size > 0 ||
           activeAllergens.size > 0 ||
           searchQuery.length > 0;
  }

  // ── Sync helpers ──────────────────────────────────────

  function syncSearch(from, to) {
    to.value = from.value;
  }

  function syncPills(fromContainer, toContainer) {
    const fromPills = fromContainer.querySelectorAll('.filter-pill');
    const toPills   = toContainer.querySelectorAll('.filter-pill');
    fromPills.forEach((fp, i) => {
      if (toPills[i]) toPills[i].classList.toggle('is-active', fp.classList.contains('is-active'));
    });
  }

  function syncCheckboxes(fromContainer, toContainer) {
    const fromCBs = fromContainer.querySelectorAll('input[type="checkbox"]');
    const toCBs   = toContainer.querySelectorAll('input[type="checkbox"]');
    fromCBs.forEach((fcb, i) => {
      if (toCBs[i]) toCBs[i].checked = fcb.checked;
    });
  }

  function syncFormulasToggle() {
    const fromBtn = formulasOnly ? sidebarFormulas : drawerFormulas;
    const toBtn   = formulasOnly ? drawerFormulas : sidebarFormulas;
    // On sync l'état : celui qui a changé a déjà la classe is-active,
    // on l'applique à l'autre
    if (sidebarFormulas && drawerFormulas) {
      drawerFormulas.classList.toggle('is-active', sidebarFormulas.classList.contains('is-active'));
    }
    if (drawerFormulas && sidebarFormulas) {
      sidebarFormulas.classList.toggle('is-active', drawerFormulas.classList.contains('is-active'));
    }
  }

  // ── Filter application ────────────────────────────────

  function applyFilters() {
    const activeCategories = getActiveCategories();
    const query = searchQuery.toLowerCase().trim();

    cards.forEach(card => {
      const category  = card.dataset.category;
      const search    = card.dataset.search || '';
      const allergens = card.dataset.allergens || '';
      const isMenu    = card.classList.contains('menu-card');

      let visible = true;

      // Formulas only
      if (formulasOnly && !isMenu) {
        visible = false;
      }

      // Categories
      if (visible && activeCategories.size > 0) {
        if (isMenu) {
          // Un menu est visible s'il contient au moins un plat d'une catégorie sélectionnée
          const menuCategories = (card.dataset.categories || '').split(',').map(s => s.trim()).filter(Boolean);
          if (!menuCategories.some(c => activeCategories.has(c))) {
            visible = false;
          }
        } else {
          if (!activeCategories.has(category)) {
            visible = false;
          }
        }
      }

      // Allergens (exclude dishes containing selected allergens)
      if (visible && activeAllergens.size > 0) {
        const cardAllergens = allergens.split(',').map(s => s.trim()).filter(Boolean);
        for (const a of activeAllergens) {
          if (cardAllergens.some(ca => ca.includes(a) || a.includes(ca))) {
            visible = false;
            break;
          }
        }
      }

      // Search
      if (visible && query && !search.includes(query)) {
        visible = false;
      }

      card.classList.toggle('is-hidden', !visible);
    });

    // Hide/show category subsections
    categorySections.forEach(section => {
      const visibleCards = section.querySelectorAll('.menu-card:not(.is-hidden), .dish-card:not(.is-hidden)');
      section.classList.toggle('is-empty', visibleCards.length === 0);
    });

    // Hide/show service sections
    serviceSections.forEach(section => {
      const visibleCards = section.querySelectorAll('.menu-card:not(.is-hidden), .dish-card:not(.is-hidden)');
      section.style.display = visibleCards.length === 0 ? 'none' : '';
    });

    // Retirer la bordure du premier service visible
    let firstVisible = null;
    serviceSections.forEach(s => {
      if (s.style.display !== 'none' && !firstVisible) firstVisible = s;
    });
    serviceSections.forEach(s => {
      s.style.borderTop = (s === firstVisible) ? 'none' : '';
      s.style.marginTop = (s === firstVisible) ? '0' : '';
      s.style.paddingTop = (s === firstVisible) ? '0' : '';
    });

    // Empty state
    const anyVisible = document.querySelectorAll('.menu-card:not(.is-hidden), .dish-card:not(.is-hidden)').length > 0;
    emptyMsg.classList.toggle('is-visible', !anyVisible);

    // FAB indicator
    if (fab) fab.classList.toggle('has-active-filters', hasActiveFilters());
  }

  // ── Event handlers ────────────────────────────────────

  // Search
  function onSearchInput(e) {
    searchQuery = e.target.value;
    if (e.target === sidebarSearch && drawerSearch) syncSearch(sidebarSearch, drawerSearch);
    if (e.target === drawerSearch && sidebarSearch) syncSearch(drawerSearch, sidebarSearch);
    applyFilters();
  }

  sidebarSearch && sidebarSearch.addEventListener('input', onSearchInput);
  drawerSearch  && drawerSearch.addEventListener('input', onSearchInput);

  // Formulas toggle
  function onFormulasToggle(btn) {
    btn.classList.toggle('is-active');
    formulasOnly = btn.classList.contains('is-active');

    // Sync
    if (btn === sidebarFormulas && drawerFormulas) {
      drawerFormulas.classList.toggle('is-active', formulasOnly);
    }
    if (btn === drawerFormulas && sidebarFormulas) {
      sidebarFormulas.classList.toggle('is-active', formulasOnly);
    }

    applyFilters();
  }

  sidebarFormulas && sidebarFormulas.addEventListener('click', function() { onFormulasToggle(sidebarFormulas); });
  drawerFormulas  && drawerFormulas.addEventListener('click', function() { onFormulasToggle(drawerFormulas); });

  // Category checkboxes
  function onCategoryChange() {
    // Sync both ways
    if (sidebarCats && drawerCats) syncCheckboxes(sidebarCats, drawerCats);
    if (drawerCats && sidebarCats) syncCheckboxes(drawerCats, sidebarCats);
    applyFilters();
  }

  sidebarCats.querySelectorAll('input[type="checkbox"]').forEach(cb => {
    cb.addEventListener('change', onCategoryChange);
  });
  drawerCats.querySelectorAll('input[type="checkbox"]').forEach(cb => {
    cb.addEventListener('change', onCategoryChange);
  });

  // Allergen pills
  function onAllergenPillClick(pill) {
    const value = pill.dataset.filter;

    if (activeAllergens.has(value)) {
      activeAllergens.delete(value);
      pill.classList.remove('is-active');
    } else {
      activeAllergens.add(value);
      pill.classList.add('is-active');
    }

    // Sync
    syncPills(sidebarAllergens, drawerAllergens);
    syncPills(drawerAllergens, sidebarAllergens);

    applyFilters();
  }

  sidebarAllergens.querySelectorAll('.filter-pill').forEach(pill => {
    pill.addEventListener('click', () => onAllergenPillClick(pill));
  });
  drawerAllergens.querySelectorAll('.filter-pill').forEach(pill => {
    pill.addEventListener('click', () => onAllergenPillClick(pill));
  });

  // Reset
  function resetFilters() {
    searchQuery = '';
    formulasOnly = false;
    activeAllergens.clear();

    if (sidebarSearch) sidebarSearch.value = '';
    if (drawerSearch)  drawerSearch.value = '';

    if (sidebarFormulas) sidebarFormulas.classList.remove('is-active');
    if (drawerFormulas)  drawerFormulas.classList.remove('is-active');

    [sidebarAllergens, drawerAllergens].forEach(container => {
      container.querySelectorAll('.filter-pill').forEach(p => {
        p.classList.remove('is-active');
      });
    });

    [sidebarCats, drawerCats].forEach(container => {
      container.querySelectorAll('input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
      });
    });

    applyFilters();
  }

  sidebarReset && sidebarReset.addEventListener('click', resetFilters);
  drawerReset  && drawerReset.addEventListener('click', () => {
    resetFilters();
    closeDrawer();
  });

  // ── Drawer (mobile) ───────────────────────────────────

  function openDrawer() {
    if (!drawer || !backdrop) return;
    drawer.classList.add('is-open');
    drawer.setAttribute('aria-hidden', 'false');
    backdrop.classList.add('is-active');
    document.body.style.overflow = 'hidden';

    setTimeout(() => {
      if (drawerSearch) drawerSearch.focus();
    }, 400);
  }

  function closeDrawer() {
    if (!drawer || !backdrop) return;
    drawer.classList.remove('is-open');
    drawer.setAttribute('aria-hidden', 'true');
    backdrop.classList.remove('is-active');
    document.body.style.overflow = '';
  }

  fab         && fab.addEventListener('click', openDrawer);
  drawerClose && drawerClose.addEventListener('click', closeDrawer);
  backdrop    && backdrop.addEventListener('click', closeDrawer);

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer && drawer.classList.contains('is-open')) {
      closeDrawer();
    }
  });

  // ── Navbar scroll-spy ─────────────────────────────────

  if (navItems.length && serviceSections.length) {
    const navObserver = new IntersectionObserver(entries => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const slug = entry.target.dataset.slug;

        navItems.forEach(item => {
          const link = item.querySelector('.nav-btn');
          if (!link) return;
          const href = link.getAttribute('href') || '';
          const hash = href.split('#')[1] || '';
          item.classList.toggle('is-active', hash === slug);
        });
      });
    }, {
      rootMargin: '-80px 0px -40% 0px',
      threshold: 0
    });

    serviceSections.forEach(s => navObserver.observe(s));
  }

  // ── Navbar click → smooth scroll ──────────────────────

  navItems.forEach(item => {
    const link = item.querySelector('.nav-btn');
    if (!link) return;
    link.addEventListener('click', (e) => {
      const href = link.getAttribute('href') || '';
      if (!href.startsWith('catalogue.php#')) return;
      e.preventDefault();
      const hash = href.split('#')[1];
      if (!hash) return;
      const target = document.getElementById(hash);
      if (!target) return;

      navItems.forEach(ni => ni.classList.remove('is-active'));
      item.classList.add('is-active');
      target.scrollIntoView({ behavior: 'smooth' });
      history.replaceState(null, '', '#' + hash);
    });
  });

  // ── URL hash au chargement ────────────────────────────

  if (window.location.hash) {
    const el = document.querySelector(window.location.hash);
    if (el) {
      requestAnimationFrame(() => el.scrollIntoView());
      const slug = window.location.hash.substring(1);
      navItems.forEach(item => {
        const link = item.querySelector('.nav-btn');
        if (!link) return;
        const href = link.getAttribute('href') || '';
        const hash = href.split('#')[1] || '';
        item.classList.toggle('is-active', hash === slug);
      });
    }
  }

  // ── AJAX Panier (ajout rapide sans rechargement) ──────

  // ── Pastille volante ───────────────────────────────

  function animateFlyDot(fromEl, toEl) {
    var dot = document.createElement('div');
    dot.className = 'fly-dot';
    document.body.appendChild(dot);

    var fromRect = fromEl.getBoundingClientRect();
    var toRect   = toEl.getBoundingClientRect();

    var startX = fromRect.left + fromRect.width  / 2;
    var startY = fromRect.top  + fromRect.height / 2;
    var endX   = toRect.left   + toRect.width   / 2;
    var endY   = toRect.top    + toRect.height  / 2;

    var dx = endX - startX;
    var dy = endY - startY;
    var distance = Math.sqrt(dx * dx + dy * dy);

    // Vitesse constante : 1400 px/s
    var speed    = 1400;
    var duration = distance / speed;

    var startTime = null;

    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      var elapsed = (timestamp - startTime) / 1000;
      var progress = Math.min(elapsed / duration, 1);

      dot.style.left = (startX + dx * progress) + 'px';
      dot.style.top  = (startY + dy * progress) + 'px';

      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        dot.remove();
      }
    }

    requestAnimationFrame(step);
  }

  // ── Mise à jour du badge ───────────────────────────

  function updateCartBadge(count) {
    var orderBtns  = document.querySelectorAll('.order-btn');
    var mobileBtns = document.querySelectorAll('.mobile-bar-btn');

    [].forEach.call(orderBtns, function(btn) {
      var badge = btn.querySelector('.cart-badge');
      if (count > 0) {
        btn.classList.add('has-items');
        btn.href = 'panier.php';
        if (badge) {
          badge.textContent = count;
        } else {
          btn.innerHTML = 'Ma commande <span class="cart-badge">' + count + '</span>';
        }
      } else {
        btn.classList.remove('has-items');
        btn.href = 'catalogue.php';
        btn.textContent = 'Commander';
      }
    });

    [].forEach.call(mobileBtns, function(btn) {
      var badge = btn.querySelector('.cart-badge');
      if (count > 0) {
        btn.classList.add('has-items');
        btn.href = 'panier.php';
        if (badge) {
          badge.textContent = count;
        } else {
          btn.innerHTML = 'Ma commande <span class="cart-badge">' + count + '</span>';
        }
      } else {
        btn.classList.remove('has-items');
        btn.href = 'catalogue.php';
        btn.textContent = 'Commander';
      }
    });
  }

  function quickAdd(itemId, itemType, quantity, sourceEl) {
    var formData = new FormData();
    formData.append('action',    'add');
    formData.append('item_id',   itemId);
    formData.append('item_type', itemType);
    formData.append('quantity',  quantity);

    return fetch('ajax_cart.php', { method: 'POST', body: formData })
      .then(function(resp) { return resp.json(); })
      .then(function(data) {
        if (data.success) {
          updateCartBadge(data.cartCount);
          if (sourceEl) {
            var destEl = document.querySelector('.cart-badge') || document.querySelector('.order-btn') || document.querySelector('.mobile-bar-btn');
            if (destEl) animateFlyDot(sourceEl, destEl);
          }
        }
      })
      .catch(function(e) {
        console.error('Erreur ajout panier:', e);
      });
  }

  // Exposer pour le script inline du popup
  window._cartQuickAdd = quickAdd;

  // Handler pour les boutons "Ajout rapide" (event delegation)
  document.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-quick-add]');
    if (!btn) return;
    e.preventDefault();
    quickAdd(
      parseInt(btn.dataset.itemId, 10),
      btn.dataset.itemType,
      parseInt(btn.dataset.quantity, 10) || 1,
      btn
    );
  });

})();
