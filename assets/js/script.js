// ── Thème light / dark ──────────────────────────────────────
(function () {
  const btn  = document.getElementById('themeToggle');
  const html = document.documentElement;
  const LIGHT_CSS_HREF = '/assets/css/light-mode.css';


/*
(?:^|;\s*) : C'est un groupe non-capturant. Il dit à la Regex : "Cherche soit le tout début de la chaîne (^), soit un point-virgule suivi d'espaces éventuels (;\s*)". Cela garantit qu'on cible le début d'un cookie et pas le milieu d'un nom.

name + '=' : Cherche le nom du cookie suivi du signe égal (ex: "user=").

([^;]*) : C'est un groupe de capture. Il capture tous les caractères qui suivent l'égal tant que ce n'est pas un point-virgule ([^;]*). C'est l'extraction de la valeur du cookie.
*/
  function getCookie(name) {
    const match = document.cookie.match(new RegExp('(?:^|;\\s*)' + name + '=([^;]*)'));
    return match ? decodeURIComponent(match[1]) : null;
  }

  function setCookie(name, value, days) {
    let expires = '';
    if (days) {
      const d = new Date();
      d.setTime(d.getTime() + days * 86400000);
      expires = '; expires=' + d.toUTCString();
    }
    document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/; SameSite=Lax';
  }

  function getLightLink() {
    const links = document.querySelectorAll('link[rel="stylesheet"]');
    for (let i = 0; i < links.length; i++) {
      if (links[i].href.indexOf(LIGHT_CSS_HREF) !== -1) return links[i];
    }
    return null;
  }

  function swapWaves(isLight) {
    document.querySelectorAll('.waves-divider img').forEach(img => {
      img.src = isLight
        ? img.src.replace('/images/waves_dark/', '/images/waves_light/')
        : img.src.replace('/images/waves_light/', '/images/waves_dark/');
    });
  }

  function setTheme(theme) {
    const isLight = theme === 'light';

    if (isLight) {
      if (!getLightLink()) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = LIGHT_CSS_HREF;
        document.head.appendChild(link);
      }
      html.setAttribute('data-theme', 'light');
    } else {
      const link = getLightLink();
      if (link) link.remove();
      html.removeAttribute('data-theme');
    }

    setCookie('theme', theme, 365);
    btn?.classList.toggle('is-light', isLight);
    swapWaves(isLight);
  }

  btn?.addEventListener('click', () => {
    const current = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    setTheme(current);
  });

  // Initialisation : cookie absent ou invalide → mode sombre par défaut
  const savedTheme = getCookie('theme');
  if (savedTheme === 'light' && !getLightLink()) {
    setTheme('light');
  } else if (html.getAttribute('data-theme') === 'light') {
    // CSS déjà injecté par le script inline, ajuster l'UI
    btn?.classList.add('is-light');
    swapWaves(true);
  }
})();
