<?php

function getNavbarData($pdo) {
    $services = $pdo->query(
        'SELECT * FROM services ORDER BY display_order'
    )->fetchAll(PDO::FETCH_ASSOC);

    $formulaStmt = $pdo->prepare(
        'SELECT id, name, description FROM menus WHERE service_id = ? ORDER BY id LIMIT 4'
    );
    $catStmt = $pdo->prepare(
        'SELECT id, name, label FROM categories WHERE service_id = ? ORDER BY display_order LIMIT 5'
    );
    $dishStmt = $pdo->prepare(
        'SELECT id, name FROM dishes WHERE service_id = ? AND category_id = ? ORDER BY id LIMIT 6'
    );

    $data = [];

    foreach ($services as $svc) {
        $has_more = false;

        $formulaStmt->execute([$svc['id']]);
        $allFormulas = $formulaStmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($allFormulas) > 3) $has_more = true;
        $formulas = array_slice($allFormulas, 0, 3);

        $catStmt->execute([$svc['id']]);
        $allCats = $catStmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($allCats) > 4) $has_more = true;
        $cats = array_slice($allCats, 0, 4);

        $categories = [];
        foreach ($cats as $cat) {
            $dishStmt->execute([$svc['id'], $cat['id']]);
            $allDishes = $dishStmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($allDishes) > 5) $has_more = true;
            $categories[$cat['name']] = [
                'label'  => $cat['label'],
                'dishes' => array_slice($allDishes, 0, 5),
            ];
        }

        $data[$svc['name']] = [
            'formulas'   => $formulas,
            'categories' => $categories,
            'has_more'   => $has_more,
        ];
    }

    return [
        'services' => $services,
        'data'     => $data,
    ];
}
