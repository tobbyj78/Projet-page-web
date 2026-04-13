<?php
session_start();
require_once 'database.php';
require_once 'functions.php';

$pdo = getDatabaseConnection();

// Récupérer tous les menus avec leurs plats
$menus = $pdo->query("SELECT * FROM menus ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Pour chaque menu, on récupère les plats associés
$stmtMenuDishes = $pdo->prepare("
    SELECT d.*
    FROM dishes d
    INNER JOIN menu_dishes md ON md.dish_id = d.id
    WHERE md.menu_id = :menu_id
    ORDER BY d.name
");

foreach ($menus as &$menu) {
    $stmtMenuDishes->execute(['menu_id' => $menu['id']]);
    $menu['dishes'] = $stmtMenuDishes->fetchAll(PDO::FETCH_ASSOC);
}
unset($menu); // casser la référence

// Récupérer les plats à la carte (pas dans un menu)
$alaCarte = $pdo->query("
    SELECT * FROM dishes
    WHERE id NOT IN (SELECT dish_id FROM menu_dishes)
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer tous les plats pour la section « Tous les plats »
$allDishes = $pdo->query("SELECT * FROM dishes ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les options de modifications
$options = $pdo->query("SELECT * FROM options ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php $pageTitle = 'Catalogue – Menus & Plats'; ?>
<?php include 'includes/header.php'; ?>

    <h1>Notre Catalogue</h1>

    <!-- ═══════════════════════════════════════════
         SECTION 1 : LES MENUS
         ═══════════════════════════════════════════ -->
    <h2>Nos Menus</h2>

    <?php if (empty($menus)): ?>
        <p>Aucun menu disponible pour le moment.</p>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($menus as $menu): ?>
                <div class="card" id="menu-<?= $menu['id'] ?>">
                    <h3><?= h($menu['name']) ?></h3>
                    <?php if (!empty($menu['description'])): ?>
                        <p><?= h($menu['description']) ?></p>
                    <?php endif; ?>

                    <span class="price"><?= number_format($menu['total_price'], 2, ',', ' ') ?> €</span>

                    <?php if (!empty($menu['min_people']) && $menu['min_people'] > 1): ?>
                        <p class="meta">Minimum <?= (int) $menu['min_people'] ?> personnes</p>
                    <?php endif; ?>

                    <?php if (!empty($menu['time_slots'])): ?>
                        <p class="meta">Créneaux : <?= h($menu['time_slots']) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($menu['dishes'])): ?>
                        <ul class="menu-dishes">
                            <?php foreach ($menu['dishes'] as $dish): ?>
                                <li>
                                    <?= h($dish['name']) ?>
                                    <small>(<?= number_format($dish['price'], 2, ',', ' ') ?> €)</small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <form action="panier.php" method="post">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="item_id" value="<?= $menu['id'] ?>">
                        <input type="hidden" name="item_type" value="menu">
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="redirect" value="catalogue.php">
                        <button type="submit">Ajout rapide</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════
         SECTION 2 : PLATS À LA CARTE
         ═══════════════════════════════════════════ -->
    <h2>Plats à la carte</h2>

    <?php if (empty($allDishes)): ?>
        <p>Aucun plat disponible pour le moment.</p>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($allDishes as $dish): ?>
                <div class="card" id="dish-<?= $dish['id'] ?>">
                    <h3><?= h($dish['name']) ?></h3>
                    <?php if (!empty($dish['description'])): ?>
                        <p><?= h($dish['description']) ?></p>
                    <?php endif; ?>

                    <span class="price"><?= number_format($dish['price'], 2, ',', ' ') ?> €</span>

                    <?php if (!empty($dish['allergens'])): ?>
                        <p class="allergens">
                            <?php foreach (explode(',', $dish['allergens']) as $allergen): ?>
                                <span><?= h(trim($allergen)) ?></span>
                            <?php endforeach; ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($dish['nutritional_info'])): ?>
                        <p class="nutri"><?= h($dish['nutritional_info']) ?></p>
                    <?php endif; ?>

                    <form action="panier.php" method="post">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="item_id" value="<?= $dish['id'] ?>">
                        <input type="hidden" name="item_type" value="dish">
                        <input type="hidden" name="quantity" value="1">
                        <input type="hidden" name="redirect" value="catalogue.php">
                        <button type="submit">Ajout rapide</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════
         SECTION 3 : OPTIONS / MODIFICATIONS
         ═══════════════════════════════════════════ -->
    <div class="options-section">
        <h2>Modifications possibles</h2>
        <p>Vous pouvez demander les adaptations suivantes sur vos plats :</p>
        <ul class="options-list">
            <?php foreach ($options as $option): ?>
                <li id="option-<?= $option['id'] ?>"><?= h($option['name']) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

<?php include 'includes/footer.php'; ?>