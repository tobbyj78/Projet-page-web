(function () {
    const links    = document.querySelectorAll('.cat-nav-link');
    const sections = document.querySelectorAll('[data-spy]');

    if (!links.length || !sections.length) return;

    // ── Scroll spy ──────────────────────────────────────────────
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const href = '#' + entry.target.id;
            links.forEach(l => l.classList.toggle('is-active', l.getAttribute('href') === href));
            history.replaceState(null, '', href);
        });
    }, {
        rootMargin: '-80px 0px -65% 0px',
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
        const target = document.querySelector(window.location.hash);
        if (target) requestAnimationFrame(() => target.scrollIntoView());
    }
})();
