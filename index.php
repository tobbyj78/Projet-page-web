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
        <img src="/images/waves.svg" alt="">
    </div>
    <section class="container">
        <figure>
            <img src="/images/dejeuner/dejeuner_1.webp" alt="">
        </figure>
        <div class="about">
            <h2 class="title">Petit-Déjeuner<br><em>Gourmand</em></h2>
            <p class="hours"><span>✦</span> 07h30 — 10h30</p>
            <p>Les viennoiseries sont préparées chaque matin avec des techniques traditionnelles et des ingrédients de saison. Chaque préparation est réalisée avec soin et une attention particulière au goût.</p>
            <a href="">Découvrir notre petit-déjeuner</a>
        </div>
    </section>
    <section class="container">
        
    </section>
    <section class="container">
        
    </section>
    <section class="container">
        
    </section>
    <section class="container">
        
    </section>
</main>



<?php require __DIR__ . '/includes/footer.php'; ?>
