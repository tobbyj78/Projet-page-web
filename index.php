<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <a href="login.php" class="bouton">login</a>
    <a href="register.php" class="bouton">register</a>
    <?php if (isset($_SESSION['user_login'])): ?>
        <a href="logout.php" class="bouton">logout</a>
    <?php endif; ?>

    <?php
    if (isset($_SESSION['user_login'])) {
        echo "Bienvenue " . htmlspecialchars($_SESSION['user_login'], ENT_QUOTES, 'UTF-8');
    } else {
        echo "Bienvenue";
    }
    ?>
</body>

</html>