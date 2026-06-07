// Navbar — L'Éclipse

(function () {
  'use strict';

  const SCROLL_THRESHOLD  = 100;  // à partir de là on assombrit la navbar
  const MOBILE_THRESHOLD  = 50;   // pour la barre du bas
  const HOVER_DELAY       = 150;  // évite d'ouvrir le menu par accident
  const MOBILE_BREAKPOINT = 1024; // en dessous = mobile

  const navbar   = document.querySelector('[data-navbar]');
  if (!navbar) return;

  // ── Sur la page catalogue, les dropdowns sont désactivés ──
  //     (la navbar devient une barre de navigation simple avec scroll-spy)
  const isCatalogue = document.body.hasAttribute('data-catalogue-page');

  const mobileBar = document.querySelector('[data-mobile-bar]');
  const navItems  = navbar.querySelectorAll('.nav-item');
  const backdrop  = document.getElementById('nav-backdrop'); // l'overlay de flou

  const isDesktop = () => window.innerWidth > MOBILE_BREAKPOINT;


  // 1. scroll

  let lastScroll = window.scrollY;

  function handleScroll() {
    const y = window.scrollY;

    navbar.classList.toggle('is-scrolled', y > SCROLL_THRESHOLD);

    // barre mobile : se cache en scrollant vers le bas, réapparaît en remontant
    if (mobileBar) {
      if (y > lastScroll && y > MOBILE_THRESHOLD) {
        mobileBar.classList.add('is-hidden');
      } else if (y < lastScroll) {
        mobileBar.classList.remove('is-hidden');
      }
    }
    lastScroll = y;
  }

  handleScroll();
  window.addEventListener('scroll', handleScroll, { passive: true });


  // 2. dropdowns

  let activeItem  = null;
  let openTimer   = null;
  let closeTimer  = null;

  function openMenu(item) {
    if (!item || activeItem === item) return;
    if (activeItem) closeMenu(activeItem);

    item.classList.add('is-active');
    navbar.classList.add('menu-hovering', 'menu-open');
    if (backdrop) backdrop.classList.add('is-active'); // on floute la page

    const trigger  = item.querySelector('[data-menu-trigger]');
    const dropdown = item.querySelector('[data-dropdown]');
    if (trigger)  trigger.setAttribute('aria-expanded', 'true');
    if (dropdown) dropdown.setAttribute('aria-hidden', 'false');

    activeItem = item;
  }

  function closeMenu(item) {
    if (!item) return;
    item.classList.remove('is-active');

    const trigger  = item.querySelector('[data-menu-trigger]');
    const dropdown = item.querySelector('[data-dropdown]');
    if (trigger)  trigger.setAttribute('aria-expanded', 'false');
    if (dropdown) dropdown.setAttribute('aria-hidden', 'true');

    if (activeItem === item) activeItem = null;
    if (!activeItem) {
      navbar.classList.remove('menu-hovering', 'menu-open');
      if (backdrop) backdrop.classList.remove('is-active'); // plus aucun menu ouvert → on enlève le flou
    }
  }

  function clearTimers() {
    clearTimeout(openTimer);
    clearTimeout(closeTimer);
  }

  // ── Dropdowns : désactivés sur la page catalogue ──
  if (isCatalogue) {
    // rien à initialiser ; le scroll-spy est géré par catalogue.js
  } else {

  navItems.forEach((item) => {
    const trigger  = item.querySelector('[data-menu-trigger]');
    const dropdown = item.querySelector('[data-dropdown]');

    item.addEventListener('mouseenter', () => {
      if (!isDesktop()) return;
      // Bloqué temporairement après un clic-nav (le temps que la souris quitte la navbar)
      if (navbar.classList.contains('suppress-hover')) return;
      clearTimers();
      openTimer = setTimeout(() => openMenu(item), HOVER_DELAY);
    });

    item.addEventListener('mouseleave', () => {
      if (!isDesktop()) return;
      clearTimers();
      closeTimer = setTimeout(() => closeMenu(item), HOVER_DELAY);
    });

    // si on survole le dropdown lui-même, on annule la fermeture
    if (dropdown) {
      dropdown.addEventListener('mouseenter', () => {
        if (isDesktop()) clearTimers();
      });
    }

    if (trigger) {
      //Le clic sur le <a> natif navigue vers catalogue.php#section
      //Le dropdown s'ouvre au hover et au focus clavier (TAB)

      // Au clic : fermer le dropdown + poser un flag pour qu'il ne se réouvre
      // pas tout seul sur la page suivante (la souris étant encore sur la navbar)
      trigger.addEventListener('click', () => {
        if (activeItem) closeMenu(activeItem);
        sessionStorage.setItem('nav-suppress-hover', '1');
      });

      trigger.addEventListener('focus', () => {
        if (!isDesktop()) return;
        clearTimers();
        openMenu(item);
      });
    }

    item.addEventListener('focusout', (e) => {
      if (!isDesktop()) return;
      if (!item.contains(e.relatedTarget)) {
        clearTimers();
        closeMenu(item);
      }
    });
  });

  
  
  
  document.addEventListener('click', (e) => {
    if (activeItem && !navbar.contains(e.target)) closeMenu(activeItem);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && activeItem) closeMenu(activeItem);
  });

  // redimensionnement vers mobile → on ferme ce qui est ouvert
  window.addEventListener('resize', () => {
    if (!isDesktop() && activeItem) closeMenu(activeItem);
  });


  // 3. catégories → panneau de plats

  navbar.querySelectorAll('[data-showcase]').forEach((block) => {
    const catBtns = block.querySelectorAll('[data-target]');
    const panels  = block.querySelectorAll('[data-panel]');

    catBtns.forEach((btn) => {
      btn.addEventListener('mouseenter', () => {
        const target = btn.dataset.target;

        catBtns.forEach((b) => {
          b.classList.toggle('is-active', b.dataset.target === target);
        });

        panels.forEach((p) => {
          const match = p.dataset.panel === target;
          p.classList.toggle('is-active', match);
          p.setAttribute('aria-hidden', match ? 'false' : 'true');
        });
      });
    });
  });

  } // end if (!isCatalogue)

  // ── 4. Tooltip profil "non connecté" ─────────────────────

  const profile        = navbar.querySelector('[data-profile]');
  const profileTooltip = navbar.querySelector('[data-profile-tooltip]');

  if (profile && profileTooltip) {
    let openTimer  = null;
    let closeTimer = null;

    function showTooltip() {
      profile.classList.add('is-open');
      profileTooltip.setAttribute('aria-hidden', 'false');
    }

    function hideTooltip() {
      profile.classList.remove('is-open');
      profileTooltip.setAttribute('aria-hidden', 'true');
    }

    profile.addEventListener('mouseenter', () => {
      if (!isDesktop()) return;
      if (navbar.classList.contains('suppress-hover')) return;
      clearTimeout(closeTimer);
      openTimer = setTimeout(showTooltip, 400);
    });

    profile.addEventListener('mouseleave', () => {
      if (!isDesktop()) return;
      clearTimeout(openTimer);
      closeTimer = setTimeout(hideTooltip, 200);
    });

    // Clic sur le profil → fermer le tooltip + bloquer la réouverture
    // tant que la souris n'a pas quitté la navbar
    profile.addEventListener('click', () => {
      hideTooltip();
      sessionStorage.setItem('nav-suppress-hover', '1');
      navbar.classList.add('suppress-hover');
    });
  }


  // ── 5. Anti-réouverture après clic-nav ou clic-profil ──────────
  //
  //   Problème 1 : quand on clique sur un lien de la navbar pour aller
  //   vers catalogue.php#section, la souris reste au même endroit.
  //   Au chargement de la nouvelle page, le mouseenter se redéclenche
  //   et le dropdown s'ouvre en couvrant le contenu.
  //
  //   Problème 2 : quand on clique sur l'icône profil, le tooltip
  //   "non connecté" se ferme mais le hover le rouvrirait aussitôt.
  //
  //   Solution : un clic pose un flag sessionStorage + la classe
  //   suppress-hover.  Le flag sert à travers les pages ; la classe
  //   sert dans la page courante.  Tout est nettoyé quand la souris
  //   quitte la navbar.

  (function () {
    // flag posé avant le chargement (clic sur la page précédente)
    if (sessionStorage.getItem('nav-suppress-hover') === '1') {
      navbar.classList.add('suppress-hover');
    }

    // écouteur permanent : nettoie tout au premier mouseleave
    navbar.addEventListener('mouseleave', function unlock() {
      if (!navbar.classList.contains('suppress-hover')) return;
      navbar.classList.remove('suppress-hover');
      sessionStorage.removeItem('nav-suppress-hover');
    });
  })();

})();
