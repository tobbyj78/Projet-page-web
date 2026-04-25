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
</main>



<?php require __DIR__ . '/includes/footer.php'; ?>
