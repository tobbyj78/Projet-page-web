<?php
require_once 'database.php';
$pdo = getDatabaseConnection();

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Récupération
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $nickname = trim($_POST['nickname'] ?? '');
    $birthday = $_POST['birthday'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $addressInfo = trim($_POST['address_info'] ?? '');

    // Vérifications
    if ($login === '') {
        $errors['login'] = "Le login est requis.";
    }

    if (strlen($password) < 3) {
        $errors['password'] = "Le mot de passe doit contenir au moins 3 caractères.";
    }

    if ($firstName === '') {
        $errors['first_name'] = "Le prénom est requis.";
    }

    if ($lastName === '') {
        $errors['last_name'] = "Le nom est requis.";
    }

    if ($nickname === '') {
        $errors['nickname'] = "Le pseudo est requis.";
    }

    if ($birthday === '') {
        $errors['birthday'] = "La date de naissance est requise.";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday)) {
        $errors['birthday'] = "Le format de date est invalide.";
    }

    if ($phone === '') {
        $errors['phone'] = "Le numéro de téléphone est requis.";
    } elseif (!preg_match('/^[0-9+().\-\s]{6,20}$/', $phone)) {
        $errors['phone'] = "Le numéro de téléphone est invalide.";
    }

    if ($address === '') {
        $errors['address'] = "L'adresse est requise.";
    }

    // Insertion si tout est bon
    if (empty($errors)) {
        try {
            $passwordHache = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (login, password, first_name, last_name, nickname, birthday, phone, address, address_info) VALUES (:login, :password, :first_name, :last_name, :nickname, :birthday, :phone, :address, :address_info)");

            $stmt->execute([
                'login'        => $login,
                'password'     => $passwordHache,
                'first_name'   => $firstName,
                'last_name'    => $lastName,
                'nickname'     => $nickname,
                'birthday'     => $birthday,
                'phone'        => $phone,
                'address'      => $address,
                'address_info' => $addressInfo !== '' ? $addressInfo : null
            ]);

            header("location: login.php");
            exit();
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors['login'] = "Ce login est déjà utilisé.";
            } else {
                error_log("Erreur SQL : " . $e->getMessage());
                $errors['globale'] = "Une erreur technique est survenue.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
</head>

<body>
    <form action="" method="post">

        <label for="login">Login</label>
        <input type="text" name="login" id="login" value="<?php echo htmlspecialchars($_POST['login'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (isset($errors['login'])): ?>
            <p><?php echo $errors['login']; ?></p>
        <?php endif; ?>

        <label for="motdepasse">Mot de passe</label>
        <input type="password" name="password" id="motdepasse">
        <?php if (isset($errors['password'])): ?>
            <p class="A"><?php echo $errors['password']; ?></p>
        <?php endif; ?>

        <label for="prenom">Prénom</label>
        <input type="text" name="first_name" id="prenom" value="<?php echo htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (isset($errors['first_name'])): ?>
            <p><?php echo $errors['first_name']; ?></p>
        <?php endif; ?>

        <label for="nom">Nom</label>
        <input type="text" name="last_name" id="nom" value="<?php echo htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (isset($errors['last_name'])): ?>
            <p><?php echo $errors['last_name']; ?></p>
        <?php endif; ?>

        <label for="pseudo">Pseudo</label>
        <input type="text" name="nickname" id="pseudo" value="<?php echo htmlspecialchars($_POST['nickname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (isset($errors['nickname'])): ?>
            <p><?php echo $errors['nickname']; ?></p>
        <?php endif; ?>

        <label for="date_naissance">Date de Naissance</label>
        <input type="date" name="birthday" id="date_naissance" value="<?php echo htmlspecialchars($_POST['birthday'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (isset($errors['birthday'])): ?>
            <p><?php echo $errors['birthday']; ?></p>
        <?php endif; ?>

        <label for="telephone">Téléphone</label>
        <input type="tel" name="phone" id="telephone" value="<?php echo htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (isset($errors['phone'])): ?>
            <p><?php echo $errors['phone']; ?></p>
        <?php endif; ?>

        <label for="adresse">Adresse</label>
        <input type="text" name="address" id="adresse" value="<?php echo htmlspecialchars($_POST['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (isset($errors['address'])): ?>
            <p><?php echo $errors['address']; ?></p>
        <?php endif; ?>

        <label for="infos_adresse">Informations d'adresse</label>
        <input type="text" name="address_info" id="infos_adresse" value="<?php echo htmlspecialchars($_POST['address_info'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

        <button type="submit">S'inscrire</button>

    </form>
</body>

</html>