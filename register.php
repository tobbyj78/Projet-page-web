<?php
require_once 'database.php';
require_once 'functions.php';
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

<?php
$pageTitle = 'Inscription';
$pageCss   = 'login.css';
include 'includes/header.php';
?>

<main class="auth-page">
  <div class="auth-card">

    <div class="auth-header">
      <a href="index.php" class="auth-logo">L'Éclipse</a>
      <h1 class="auth-title">Créer un compte</h1>
    </div>

    <?php if (isset($errors['globale'])): ?>
      <p class="auth-error-global"><?= h($errors['globale']) ?></p>
    <?php endif; ?>

    <form class="auth-form" action="" method="post">

      <div class="auth-row">
        <div class="auth-field">
          <label for="login">Identifiant</label>
          <input type="text" name="login" id="login"
                 value="<?= h($_POST['login'] ?? '') ?>"
                 autocomplete="username">
          <?php if (isset($errors['login'])): ?>
            <span class="auth-field-error"><?= h($errors['login']) ?></span>
          <?php endif; ?>
        </div>

        <div class="auth-field">
          <label for="motdepasse">Mot de passe</label>
          <input type="password" name="password" id="motdepasse"
                 autocomplete="new-password">
          <?php if (isset($errors['password'])): ?>
            <span class="auth-field-error"><?= h($errors['password']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="auth-row">
        <div class="auth-field">
          <label for="prenom">Prénom</label>
          <input type="text" name="first_name" id="prenom"
                 value="<?= h($_POST['first_name'] ?? '') ?>">
          <?php if (isset($errors['first_name'])): ?>
            <span class="auth-field-error"><?= h($errors['first_name']) ?></span>
          <?php endif; ?>
        </div>

        <div class="auth-field">
          <label for="nom">Nom</label>
          <input type="text" name="last_name" id="nom"
                 value="<?= h($_POST['last_name'] ?? '') ?>">
          <?php if (isset($errors['last_name'])): ?>
            <span class="auth-field-error"><?= h($errors['last_name']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="auth-row">
        <div class="auth-field">
          <label for="pseudo">Pseudo</label>
          <input type="text" name="nickname" id="pseudo"
                 value="<?= h($_POST['nickname'] ?? '') ?>">
          <?php if (isset($errors['nickname'])): ?>
            <span class="auth-field-error"><?= h($errors['nickname']) ?></span>
          <?php endif; ?>
        </div>

        <div class="auth-field">
          <label for="date_naissance">Date de naissance</label>
          <input type="date" name="birthday" id="date_naissance"
                 value="<?= h($_POST['birthday'] ?? '') ?>">
          <?php if (isset($errors['birthday'])): ?>
            <span class="auth-field-error"><?= h($errors['birthday']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <div class="auth-field">
        <label for="telephone">Téléphone</label>
        <input type="tel" name="phone" id="telephone"
               value="<?= h($_POST['phone'] ?? '') ?>"
               autocomplete="tel">
        <?php if (isset($errors['phone'])): ?>
          <span class="auth-field-error"><?= h($errors['phone']) ?></span>
        <?php endif; ?>
      </div>

      <div class="auth-field">
        <label for="adresse">Adresse</label>
        <input type="text" name="address" id="adresse"
               value="<?= h($_POST['address'] ?? '') ?>"
               autocomplete="street-address">
        <?php if (isset($errors['address'])): ?>
          <span class="auth-field-error"><?= h($errors['address']) ?></span>
        <?php endif; ?>
      </div>

      <div class="auth-field">
        <label for="infos_adresse">Complément d'adresse <em style="font-style:normal;color:var(--text-muted)">(optionnel)</em></label>
        <input type="text" name="address_info" id="infos_adresse"
               value="<?= h($_POST['address_info'] ?? '') ?>"
               placeholder="Bâtiment, étage, code d'accès…">
      </div>

      <button class="auth-btn" type="submit">Créer mon compte</button>

    </form>

    <div class="auth-footer">
      <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
    </div>

  </div>
</main>

<?php include 'includes/footer.php'; ?>