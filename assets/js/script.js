// ── Thème light / dark ──────────────────────────────────────
(function () {
  const btn  = document.getElementById('themeToggle');
  const html = document.documentElement;

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
      html.setAttribute('data-theme', 'light');
      localStorage.setItem('theme', 'light');
    } else {
      html.removeAttribute('data-theme');
      localStorage.removeItem('theme');
    }
    btn?.classList.toggle('is-light', isLight);
    swapWaves(isLight);
  }

  btn?.addEventListener('click', () => {
    setTheme(html.getAttribute('data-theme') === 'light' ? 'dark' : 'light');
  });

  if (localStorage.getItem('theme') === 'light') setTheme('light');
})();
