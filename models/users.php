<?php

/**
 * Récupère un utilisateur par son ID.
 */
function getUserById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

/**
 * Récupère les commandes d'un utilisateur avec le total recalculé.
 */
function getOrdersByUserId(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("
        SELECT o.*,
            (SELECT GROUP_CONCAT(
                CASE oi.item_type
                    WHEN 'dish' THEN (SELECT d.name FROM dishes d WHERE d.id = oi.item_id)
                    WHEN 'menu' THEN (SELECT m.name FROM menus m WHERE m.id = oi.item_id)
                END, ', ')
            FROM order_items oi WHERE oi.order_id = o.id
            ) AS items_summary
        FROM orders o
        WHERE o.user_id = :user_id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Calcule le montant total d'une commande.
 */
function getOrderTotal(PDO $pdo, int $orderId): float {
    $stmt = $pdo->prepare("
        SELECT SUM(
            CASE oi.item_type
                WHEN 'dish' THEN (SELECT d.price FROM dishes d WHERE d.id = oi.item_id) * oi.quantity
                WHEN 'menu' THEN (SELECT m.total_price FROM menus m WHERE m.id = oi.item_id) * oi.quantity
            END
        ) AS total
        FROM order_items oi
        WHERE oi.order_id = :order_id
    ");
    $stmt->execute(['order_id' => $orderId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return (float) ($row['total'] ?? 0);
}

/**
 * Vérifie si une commande a déjà été notée.
 */
function getOrderRating(PDO $pdo, int $orderId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM ratings WHERE order_id = :order_id");
    $stmt->execute(['order_id' => $orderId]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}

/**
 * Enregistre une notation pour une commande.
 */
function createRating(PDO $pdo, int $orderId, int $userId, int $rating, ?string $comment): bool {
    $stmt = $pdo->prepare("
        INSERT INTO ratings (order_id, user_id, rating, comment)
        VALUES (:order_id, :user_id, :rating, :comment)
    ");
    return $stmt->execute([
        'order_id' => $orderId,
        'user_id'  => $userId,
        'rating'   => $rating,
        'comment'  => $comment,
    ]);
}
