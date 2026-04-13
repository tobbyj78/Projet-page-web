<?php
session_start();
require_once 'database.php';
require_once 'functions.php';

$pdo = getDatabaseConnection();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifier que le panier n'est pas vide
if (empty($_SESSION['cart'])) {
    header('Location: panier.php');
    exit;
}

$errors = [];

// Traitement du formulaire de validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $orderType       = $_POST['order_type'] ?? '';
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $scheduling      = $_POST['scheduling'] ?? 'immediate';
    $scheduledDate   = $_POST['scheduled_date'] ?? '';
    $scheduledTime   = $_POST['scheduled_time'] ?? '';

    // Validations
    if (!in_array($orderType, ['sur_place', 'emporter', 'livraison'])) {
        $errors[] = "Veuillez choisir un type de commande.";
    }

    if ($orderType === 'livraison' && $deliveryAddress === '') {
        $errors[] = "L'adresse de livraison est requise.";
    }

    $scheduledDatetime = null;
    if ($scheduling === 'later') {
        if ($scheduledDate === '' || $scheduledTime === '') {
            $errors[] = "Veuillez indiquer la date et l'heure souhaitées.";
        } else {
            $scheduledDatetime = $scheduledDate . ' ' . $scheduledTime;
        }
    }

    // Si pas d'erreur, on crée la commande puis on redirige vers le paiement
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Créer la commande (statut en_attente)
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, order_type, delivery_address, status, scheduled_datetime)
                VALUES (:user_id, :order_type, :delivery_address, :status, :scheduled_datetime)
            ");
            $stmt->execute([
                'user_id'            => $_SESSION['user_id'],
                'order_type'         => $orderType,
                'delivery_address'   => $orderType === 'livraison' ? $deliveryAddress : null,
                'status'             => 'en_attente',
                'scheduled_datetime' => $scheduledDatetime,
            ]);
            $orderId = $pdo->lastInsertId();

            // Insérer les articles
            $stmtItem = $pdo->prepare("
                INSERT INTO order_items (order_id, item_id, item_type, quantity)
                VALUES (:order_id, :item_id, :item_type, :quantity)
            ");
            foreach ($_SESSION['cart'] as $item) {
                $stmtItem->execute([
                    'order_id'  => $orderId,
                    'item_id'   => $item['item_id'],
                    'item_type' => $item['item_type'],
                    'quantity'  => $item['quantity'],
                ]);
            }

            $pdo->commit();

            // Vider le panier
            $_SESSION['cart'] = [];

            // Stocker l'order_id en session pour le paiement
            $_SESSION['pending_order_id'] = $orderId;

            // Rediriger vers la page de paiement
            header('Location: paiement.php');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur commande : " . $e->getMessage());
            $errors[] = "Une erreur technique est survenue.";
        }
    }
}

// Charger le résumé du panier pour l'affichage
$cartItems = [];
$totalPrice = 0;

foreach ($_SESSION['cart'] as $key => $item) {
    if ($item['item_type'] === 'dish') {
        $stmt = $pdo->prepare("SELECT name, price FROM dishes WHERE id = :id");
    } else {
        $stmt = $pdo->prepare("SELECT name, total_price AS price FROM menus WHERE id = :id");
    }
    $stmt->execute(['id' => $item['item_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $subtotal = $row['price'] * $item['quantity'];
        $totalPrice += $subtotal;
        $cartItems[] = [
            'name'     => $row['name'],
            'type'     => $item['item_type'] === 'menu' ? 'Menu' : 'Plat',
            'price'    => $row['price'],
            'quantity' => $item['quantity'],
            'subtotal' => $subtotal,
        ];
    }
}
?>

<?php $pageTitle = 'Validation de commande'; ?>
<?php include 'includes/header.php'; ?>

    <h1>Validation de commande</h1>

    <a href="panier.php">Retour au panier</a>

    <!-- Résumé du panier -->
    <h2>Résumé de votre commande</h2>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Type</th>
                <th>Article</th>
                <th>Prix unitaire</th>
                <th>Quantité</th>
                <th>Sous-total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cartItems as $ci): ?>
                <tr>
                    <td><?= h($ci['type']) ?></td>
                    <td><?= h($ci['name']) ?></td>
                    <td><?= number_format($ci['price'], 2, ',', ' ') ?> €</td>
                    <td><?= $ci['quantity'] ?></td>
                    <td><?= number_format($ci['subtotal'], 2, ',', ' ') ?> €</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p><strong>Total : <?= number_format($totalPrice, 2, ',', ' ') ?> €</strong></p>

    <!-- Erreurs -->
    <?php if (!empty($errors)): ?>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= h($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!-- Formulaire de validation -->
    <form action="validation.php" method="post">

        <fieldset>
            <legend>Type de commande</legend>
            <label>
                <input type="radio" name="order_type" value="sur_place"
                    <?= (($_POST['order_type'] ?? '') === 'sur_place') ? 'checked' : '' ?>>
                Sur place
            </label><br>
            <label>
                <input type="radio" name="order_type" value="emporter"
                    <?= (($_POST['order_type'] ?? '') === 'emporter') ? 'checked' : '' ?>>
                À emporter
            </label><br>
            <label>
                <input type="radio" name="order_type" value="livraison"
                    <?= (($_POST['order_type'] ?? '') === 'livraison') ? 'checked' : '' ?>>
                Livraison
            </label>
        </fieldset>

        <fieldset id="delivery-fieldset">
            <legend>Adresse de livraison</legend>
            <label for="delivery_address">Adresse :</label>
            <input type="text" name="delivery_address" id="delivery_address"
                value="<?= h($_POST['delivery_address'] ?? '') ?>">
        </fieldset>

        <fieldset>
            <legend>Quand souhaitez-vous recevoir votre commande ?</legend>
            <label>
                <input type="radio" name="scheduling" value="immediate"
                    <?= (($_POST['scheduling'] ?? 'immediate') === 'immediate') ? 'checked' : '' ?>>
                Préparation immédiate
            </label><br>
            <label>
                <input type="radio" name="scheduling" value="later"
                    <?= (($_POST['scheduling'] ?? '') === 'later') ? 'checked' : '' ?>>
                Choisir une date et heure
            </label>

            <div id="schedule-fields">
                <br>
                <label for="scheduled_date">Date :</label>
                <input type="date" name="scheduled_date" id="scheduled_date"
                    value="<?= h($_POST['scheduled_date'] ?? '') ?>">
                <label for="scheduled_time">Heure :</label>
                <input type="time" name="scheduled_time" id="scheduled_time"
                    value="<?= h($_POST['scheduled_time'] ?? '') ?>">
            </div>
        </fieldset>

        <br>
        <button type="submit">Procéder au paiement</button>

    </form>

<?php include 'includes/footer.php'; ?>