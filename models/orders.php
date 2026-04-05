<?php

/**
 * Récupère les commandes filtrées par statut (pour le restaurateur).
 */
function getOrdersByStatus(PDO $pdo, ?string $status = null): array {
    if ($status) {
        $stmt = $pdo->prepare("
            SELECT o.*, u.login, u.first_name, u.last_name, u.phone
            FROM orders o
            INNER JOIN users u ON u.id = o.user_id
            WHERE o.status = :status
            ORDER BY o.created_at DESC
        ");
        $stmt->execute(['status' => $status]);
    } else {
        $stmt = $pdo->query("
            SELECT o.*, u.login, u.first_name, u.last_name, u.phone
            FROM orders o
            INNER JOIN users u ON u.id = o.user_id
            ORDER BY o.created_at DESC
        ");
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère une commande par son ID avec les infos client.
 */
function getOrderById(PDO $pdo, int $orderId): ?array {
    $stmt = $pdo->prepare("
        SELECT o.*, u.login, u.first_name, u.last_name, u.phone, u.address, u.address_info
        FROM orders o
        INNER JOIN users u ON u.id = o.user_id
        WHERE o.id = :id
    ");
    $stmt->execute(['id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    return $order ?: null;
}

/**
 * Récupère les articles d'une commande avec les détails des plats/menus.
 */
function getOrderItems(PDO $pdo, int $orderId): array {
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
    $stmt->execute(['order_id' => $orderId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as &$item) {
        if ($item['item_type'] === 'dish') {
            $s = $pdo->prepare("SELECT name, price FROM dishes WHERE id = :id");
        } else {
            $s = $pdo->prepare("SELECT name, total_price AS price FROM menus WHERE id = :id");
        }
        $s->execute(['id' => $item['item_id']]);
        $detail = $s->fetch(PDO::FETCH_ASSOC);
        $item['name']  = $detail['name'] ?? 'Inconnu';
        $item['price'] = $detail['price'] ?? 0;
    }
    unset($item);

    return $items;
}

/**
 * Récupère tous les livreurs.
 */
function getAllLivreurs(PDO $pdo): array {
    return $pdo->query("SELECT id, first_name, last_name FROM users WHERE role = 'livreur' ORDER BY last_name")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère la commande assignée à un livreur.
 */
function getDeliveryOrder(PDO $pdo, int $livreurId): ?array {
    $stmt = $pdo->prepare("
        SELECT o.*, u.first_name, u.last_name, u.phone, u.address, u.address_info
        FROM orders o
        INNER JOIN users u ON u.id = o.user_id
        WHERE o.delivery_person_id = :livreur_id
          AND o.status = 'en_livraison'
        ORDER BY o.created_at DESC
        LIMIT 1
    ");
    $stmt->execute(['livreur_id' => $livreurId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    return $order ?: null;
}
