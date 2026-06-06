(function () {
    const links    = document.querySelectorAll('.cat-nav-link');
    const sections = document.querySelectorAll('[data-spy-service]');

    if (!links.length || !sections.length) return;

    // ── Scroll spy — services uniquement ────────────────────────
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const slug = entry.target.dataset.slug;
            links.forEach(l => l.classList.toggle('is-active', l.getAttribute('href') === '#' + slug));
            history.replaceState(null, '', '#' + slug);
        });
    }, {
        rootMargin: '-80px 0px -40% 0px',
        threshold: 0
    });

    sections.forEach(s => observer.observe(s));

    // ── Click → smooth scroll + active immédiat ─────────────────
    links.forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const href   = link.getAttribute('href');
            const target = document.querySelector(href);
            if (!target) return;
            links.forEach(l => l.classList.remove('is-active'));
            link.classList.add('is-active');
            target.scrollIntoView({ behavior: 'smooth' });
            history.replaceState(null, '', href);
        });
    });

    // ── Ancre dans l'URL au chargement → scroll direct ──────────
    if (window.location.hash) {
        const el = document.querySelector(window.location.hash);
        if (el) requestAnimationFrame(() => el.scrollIntoView());
    }
})();
