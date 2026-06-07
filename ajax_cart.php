<?php
/**
 * AJAX Cart — Endpoint JSON pour les actions panier.
 * Appelé par catalogue.js pour les ajouts rapides sans rechargement.
 */
session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

header('Content-Type: application/json');

$action   = $_POST['action']   ?? '';
$itemId   = (int) ($_POST['item_id']   ?? 0);
$itemType =       $_POST['item_type'] ?? '';
$quantity = max(1, (int) ($_POST['quantity'] ?? 1));

try {
    if ($action === 'add' && $itemId > 0 && in_array($itemType, ['menu', 'dish'], true)) {
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

    // Total d'articles (somme des quantités)
    $cartCount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += (int) ($item['quantity'] ?? 0);
    }

    echo json_encode(['success' => true, 'cartCount' => $cartCount], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
