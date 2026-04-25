// Navbar — L'Éclipse

(function () {
  'use strict';

  const SCROLL_THRESHOLD  = 100;  // à partir de là on assombrit la navbar
  const MOBILE_THRESHOLD  = 50;   // pour la barre du bas
  const HOVER_DELAY       = 150;  // évite d'ouvrir le menu par accident
  const MOBILE_BREAKPOINT = 1024; // en dessous = mobile

  const navbar   = document.querySelector('[data-navbar]');
  if (!navbar) return;

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

  navItems.forEach((item) => {
    const trigger  = item.querySelector('[data-menu-trigger]');
    const dropdown = item.querySelector('[data-dropdown]');

    item.addEventListener('mouseenter', () => {
      if (!isDesktop()) return;
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
      trigger.addEventListener('click', () => {
        if (!isDesktop()) return;
        clearTimers();
        if (activeItem === item) closeMenu(item);
        else                     openMenu(item);
      });

      
      //Pour faire en sorte que le dropdown s'ouvre avec le focus de TAB
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


  // 4. dropdown profil

  const profile         = navbar.querySelector('[data-profile]');
  const profileTrigger  = navbar.querySelector('[data-profile-trigger]');
  const profileDropdown = navbar.querySelector('[data-profile-dropdown]');

  if (profile && profileDropdown) {
    let profileOpenTimer  = null;
    let profileCloseTimer = null;

    function openProfile() {
      profile.classList.add('is-open');
      profileDropdown.setAttribute('aria-hidden', 'false');
      if (profileTrigger) profileTrigger.setAttribute('aria-expanded', 'true');
    }

    function closeProfile() {
      profile.classList.remove('is-open');
      profileDropdown.setAttribute('aria-hidden', 'true');
      if (profileTrigger) profileTrigger.setAttribute('aria-expanded', 'false');
    }

    profile.addEventListener('mouseenter', () => {
      if (!isDesktop()) return;
      clearTimeout(profileCloseTimer);
      profileOpenTimer = setTimeout(openProfile, HOVER_DELAY);
    });

    profile.addEventListener('mouseleave', () => {
      if (!isDesktop()) return;
      clearTimeout(profileOpenTimer);
      profileCloseTimer = setTimeout(closeProfile, HOVER_DELAY);
    });

    if (profileTrigger) {
      profileTrigger.addEventListener('click', () => {
        if (profile.classList.contains('is-open')) closeProfile();
        else                                        openProfile();
      });
    }

    document.addEventListener('click', (e) => {
      if (!profile.contains(e.target)) closeProfile();
    });
  }
})();
