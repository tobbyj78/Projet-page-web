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
</main>



<?php require __DIR__ . '/includes/footer.php'; ?>
