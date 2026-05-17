<?php
session_start();

require_once "database.php";
require_once "functions.php";

$pdo = getDatabaseConnection();

$user = null;

if (isset($_SESSION["user_id"])) {
    $stmt = $pdo->prepare("SELECT first_name, role FROM users WHERE id = :id");
    $stmt->execute(["id" => $_SESSION["user_id"]]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

    if ($user && $user["role"] !== "client") {
        header("Location: " . getRoleRedirect($user["role"]));
        exit();
    }
}
?>

<?php
$pageTitle = "L'Éclipse";
$pageCss   = "index.css";
$page_js = 'assets/js/index.js';
require __DIR__ . '/includes/header.php';
?>

<main>
    <section class="hero">
        <h1 class="hero-title"><span class="tw-line"></span><span class="tw-line"></span></h1>
    </section>
    <div class="waves-divider">
        <img src="/images/waves/waves<?= rand(1, 5) ?>.svg" alt="">
    </div>
    
    
    <section class="container">
        <figure class="crossfade">
            <img src="/images/petit-dejeuner/petit-dejeuner_1.webp" alt="">
            <img src="/images/petit-dejeuner/petit-dejeuner_2.webp" alt="">
        </figure>
        <div class="about">
            <h2 class="title">Petit-Déjeuner<br><em>Gourmand</em></h2>
            <p class="hours"><span>✦</span> 07h30 — 10h30</p>
            <p>Les viennoiseries sont préparées chaque matin avec des techniques traditionnelles et des ingrédients de saison. Chaque préparation est réalisée avec soin et une attention particulière au goût.</p>
            <a href=""><span>Découvrir notre petit-déjeuner</span><span class="cta-arrow" aria-hidden="true">→</span></a>
        </div>
    </section>
    <section class="container">
        <div class="about order">
            <h2 class="title">Déjeuner<br><em>Léger</em></h2>
            <p class="hours"><span>✦</span> 12h30 — 15h00</p>
            <p>Les viennoiseries sont préparées chaque matin avec des techniques traditionnelles et des ingrédients de saison. Chaque préparation est réalisée avec soin et une attention particulière au goût.</p>
            <a href=""><span>Découvrir notre déjeuner</span><span class="cta-arrow" aria-hidden="true">→</span></a>
        </div>
        <figure class="crossfade">
            <img src="/images/dejeuner/dejeuner_1.webp" alt="">
            <img src="/images/dejeuner/dejeuner_2.webp" alt="">
        </figure>
    </section>
    <section class="container">
        <figure class="crossfade">
            <img src="/images/diner/diner_1.webp" alt="">
            <img src="/images/diner/diner_2.webp" alt="">
        </figure>
        <div class="about">
            <h2 class="title"><em>Le Grand</em><br>Dîner</h2>
            <p class="hours"><span>✦</span> 19h30 — 23h00</p>
            <p>Les viennoiseries sont préparées chaque matin avec des techniques traditionnelles et des ingrédients de saison. Chaque préparation est réalisée avec soin et une attention particulière au goût.</p>
            <a href=""><span>Découvrir le Grand Dîner</span><span class="cta-arrow" aria-hidden="true">→</span></a>
        </div>
    </section>
    <section class="container">
        <div class="about order">
            <h2 class="title">La Cave</h2>
            <p class="hours"><span>✦</span> Servi toute la journée</p>
            <p>Les viennoiseries sont préparées chaque matin avec des techniques traditionnelles et des ingrédients de saison. Chaque préparation est réalisée avec soin et une attention particulière au goût.</p>
            <a href=""><span>Découvrir notre sélection de vins</span><span class="cta-arrow" aria-hidden="true">→</span></a>
        </div>
        <figure class="crossfade">
            <img src="/images/cave/cave_1.webp" alt="">
            <img src="/images/cave/cave_2.webp" alt="">
        </figure>
    </section>
    <section class="container">
        <figure class="crossfade">
            <img src="/images/epicerie/epicerie_1.webp" alt="">
            <img src="/images/epicerie/epicerie_2.webp" alt="">
        </figure>
        <div class="about">
            <h2 class="title">Epicerie</h2>
            <p class="hours"><span>✦</span> Servi toute la journée</p>
            <p>Les viennoiseries sont préparées chaque matin avec des techniques traditionnelles et des ingrédients de saison. Chaque préparation est réalisée avec soin et une attention particulière au goût.</p>
            <a href=""><span>Découvrir le Grand Dîner</span><span class="cta-arrow" aria-hidden="true">→</span></a>
        </div>
    </section>
    
    <section class="reviews-section">
        <div class="section-sep"><span class="section-sep-ornament">✦</span></div>
        <div class="reviews-inner">

            <div class="reviews-header">
                <p class="reviews-label">Témoignages</p>
                <h2 class="reviews-title">Ce que nos hôtes<br><em>nous confient</em></h2>
            </div>

            <div class="reviews-carousel-wrap">

                <button class="reviews-arrow reviews-prev" aria-label="Précédent">&#8249;</button>

                <div class="reviews-viewport">
                    <div class="reviews-track" id="reviewsTrack">

                        <div class="review-card">
                            <span class="review-quote" aria-hidden="true"></span>
                            <p class="review-text">Une expérience absolument remarquable. Chaque plat est une déclaration d'amour à la gastronomie française. Je reviendrai sans hésiter.</p>
                            <div class="review-author">
                                <img class="review-avatar" src="/images/avis/femme1.webp" alt="">
                                <div>
                                    <h5 class="review-name">Sophie M.</h5>
                                    <span class="review-role">Hôte régulière</span>
                                </div>
                            </div>
                        </div>

                        <div class="review-card">
                            <span class="review-quote" aria-hidden="true"></span>
                            <p class="review-text">Le Grand Dîner m'a laissé sans voix. Service impeccable, cave à vins exceptionnelle, et une ambiance qui transporte dès l'entrée.</p>
                            <div class="review-author">
                                <img class="review-avatar" src="/images/avis/homme1.webp" alt="">
                                <div>
                                    <h5 class="review-name">Alexandre D.</h5>
                                    <span class="review-role">Amateur de grands crus</span>
                                </div>
                            </div>
                        </div>

                        <div class="review-card">
                            <span class="review-quote" aria-hidden="true"></span>
                            <p class="review-text">Le petit-déjeuner gourmand est tout simplement divin. Les viennoiseries fondent en bouche et l'accueil est d'une chaleur rare à Paris.</p>
                            <div class="review-author">
                                <img class="review-avatar" src="/images/avis/femme2.webp" alt="">
                                <div>
                                    <h5 class="review-name">Camille R.</h5>
                                    <span class="review-role">Chroniqueuse gastronomique</span>
                                </div>
                            </div>
                        </div>

                        <div class="review-card">
                            <span class="review-quote" aria-hidden="true"></span>
                            <p class="review-text">L'épicerie fine est une merveille. J'ai ramené des produits introuvables ailleurs. La sélection de truffes et de champagnes est particulièrement soignée.</p>
                            <div class="review-author">
                                <img class="review-avatar" src="/images/avis/homme2.webp" alt="">
                                <div>
                                    <h5 class="review-name">Thomas B.</h5>
                                    <span class="review-role">Chef cuisinier</span>
                                </div>
                            </div>
                        </div>

                        <div class="review-card">
                            <span class="review-quote" aria-hidden="true"></span>
                            <p class="review-text">Un lieu hors du temps. Le menu Signature du Chef est une carte blanche qui mérite pleinement son nom. Ici, chaque assiette est une œuvre.</p>
                            <div class="review-author">
                                <img class="review-avatar" src="/images/avis/homme3.webp" alt="">
                                <div>
                                    <h5 class="review-name">Laurent V.</h5>
                                    <span class="review-role">Habitué depuis 2019</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <button class="reviews-arrow reviews-next" aria-label="Suivant">&#8250;</button>

            </div>

            <div class="reviews-dots" id="reviewsDots"></div>

        </div>
    </section>
</main>



<?php require __DIR__ . '/includes/footer.php'; ?>
