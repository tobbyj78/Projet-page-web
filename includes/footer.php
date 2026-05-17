
    <footer class="site-footer">
        <div class="footer-inner">

            <div class="footer-col footer-col--brand">
                <a href="/" class="footer-logo">L'Éclipse</a>
                <p class="footer-tagline">Une cuisine d'exception,<br>un instant hors du temps.</p>
            </div>

            <div class="footer-col">
                <h3 class="footer-heading">Explorer</h3>
                <ul class="footer-links">
                    <li><a href="catalogue.php">Notre carte</a></li>
                    <li><a href="#">Réservation</a></li>
                    <li><a href="#">L'épicerie fine</a></li>
                    <li><a href="#">Événements privés</a></li>
                    <li><a href="#">Mentions légales</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3 class="footer-heading">Horaires</h3>
                <ul class="footer-hours">
                    <li><span>Petit-déjeuner</span><span>07h30 — 10h30</span></li>
                    <li><span>Déjeuner</span><span>12h30 — 15h00</span></li>
                    <li><span>Dîner</span><span>19h30 — 23h00</span></li>
                    <li><span>Cave & Épicerie</span><span>Toute la journée</span></li>
                </ul>
            </div>

            <div class="footer-col">
                <h3 class="footer-heading">Nous trouver</h3>
                <address class="footer-address">
                    <p>12 rue de la Paix<br>75001 Paris</p>
                    <p><a href="tel:+33142000000">+33 1 42 00 00 00</a></p>
                    <p><a href="mailto:contact@leclipse.fr">contact@leclipse.fr</a></p>
                </address>
            </div>

        </div>

        <div class="footer-bottom">
            <p>© 2025 L'Éclipse — Tous droits réservés</p>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
    <script src="/assets/js/navbar.js"></script>
    <?php if (isset($page_js)): ?>
        <script src="<?= $page_js ?>"></script>
    <?php endif; ?>

</body>

</html>
