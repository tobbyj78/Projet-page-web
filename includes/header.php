<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Mon Site', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/script.js" type="text/javascript" defer></script>
</head>

<body>

    <header>
        <nav aria-label="Navigation principale" class="main-nav">
            <button class="btn-mobile-menu" aria-label="Ouvrir le menu">
                ☰
            </button>
            <a href="index.php" class="nav-item nav-logo" aria-label="Accueil">
                <img src="images/logo.webp" alt="logo" height="40">
            </a>
            <a href="catalogue.php" class="nav-item notre-carte">
                Notre Carte
            </a>
            <div class="nav-item choix-type-livraison" role="group" aria-label="Type de commande">
                <button class="livraison">Livraison</button>
                <button class="sur-place">Sur Place</button>
                <button class="a-emporter">À emporter</button>
            </div>
            <div class="nav-item nav-right">
                <a href="profil_client.php" class="profile" aria-label="Profil">
                    <img src="images/utilisateur.png" alt="Profil" width="30" height="30">
                </a>
                <a href="panier.php" class="shopping-cart" aria-label="Panier">
                    <img src="images/carte-de-shopping.png" alt="Votre panier" width="30" height="30">
                </a>
            </div>
        </nav>
    </header>
