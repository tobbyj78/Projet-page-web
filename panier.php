<?php
session_start();
require_once 'database.php';
require_once 'functions.php';

$pdo = getDatabaseConnection();

// ── Le panier est stocké en session ──
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ── Ajouter un article au panier ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    $action = $_POST['action'];

    if ($action === 'add') {
        $itemId   = (int) ($_POST['item_id'] ?? 0);
        $itemType = $_POST['item_type'] ?? '';
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

        if ($itemId > 0 && in_array($itemType, ['menu', 'dish'])) {
            $key = $itemType . '_' . $itemId;
            if (isset($_SESSION['cart'][$key])) {
                $_SESSION['cart'][$key]['quantity'] += $quantity;
            } else {
                $_SESSION['cart'][$key] = [
                    'item_id'   => $itemId,
                    'item_type' => $itemType,
                    'quantity'  => $quantity,
                ];
            }
        }
    }

    if ($action === 'remove') {
        $key = $_POST['cart_key'] ?? '';
        unset($_SESSION['cart'][$key]);
    }

    if ($action === 'update') {
        $key      = $_POST['cart_key'] ?? '';
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        if (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['quantity'] = $quantity;
        }
    }

    if ($action === 'clear') {
        $_SESSION['cart'] = [];
    }

    // PRG : éviter la resoumission
    $redirect = $_POST['redirect'] ?? 'panier.php';
    // Sécurité : n'accepter que des redirections locales
    if (!in_array($redirect, ['panier.php', 'catalogue.php'])) {
        $redirect = 'panier.php';
    }
    header('Location: ' . $redirect);
    exit;
}

// ── Charger les détails des articles du panier ──
$cartItems = [];
$totalPrice = 0;

foreach ($_SESSION['cart'] as $key => $item) {
    if ($item['item_type'] === 'dish') {
        $stmt = $pdo->prepare("SELECT id, name, price FROM dishes WHERE id = :id");
        $stmt->execute(['id' => $item['item_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->prepare("SELECT id, name, total_price AS price FROM menus WHERE id = :id");
        $stmt->execute(['id' => $item['item_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($row) {
        $subtotal = $row['price'] * $item['quantity'];
        $totalPrice += $subtotal;
        $cartItems[] = [
            'key'       => $key,
            'name'      => $row['name'],
            'type'      => $item['item_type'] === 'menu' ? 'Menu' : 'Plat',
            'price'     => $row['price'],
            'quantity'  => $item['quantity'],
            'subtotal'  => $subtotal,
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier</title>
</head>

<body>

    <h1>Mon Panier</h1>

    <a href="catalogue.php">Retour au catalogue</a>

    <?php if (empty($cartItems)): ?>
        <p>Votre panier est vide.</p>
    <?php else: ?>

        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Article</th>
                    <th>Prix unitaire</th>
                    <th>Quantité</th>
                    <th>Sous-total</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $ci): ?>
                    <tr>
                        <td><?= h($ci['type']) ?></td>
                        <td><?= h($ci['name']) ?></td>
                        <td><?= number_format($ci['price'], 2, ',', ' ') ?> €</td>
                        <td>
                            <form action="panier.php" method="post">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="cart_key" value="<?= h($ci['key']) ?>">
                                <input type="number" name="quantity" value="<?= $ci['quantity'] ?>" min="1">
                                <button type="submit">Modifier</button>
                            </form>
                        </td>
                        <td><?= number_format($ci['subtotal'], 2, ',', ' ') ?> €</td>
                        <td>
                            <form action="panier.php" method="post">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="cart_key" value="<?= h($ci['key']) ?>">
                                <button type="submit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p><strong>Total : <?= number_format($totalPrice, 2, ',', ' ') ?> €</strong></p>

        <form action="panier.php" method="post">
            <input type="hidden" name="action" value="clear">
            <button type="submit">Vider le panier</button>
        </form>

        <br>
        <a href="validation.php">Valider la commande</a>

    <?php endif; ?>

</body>

</html>
