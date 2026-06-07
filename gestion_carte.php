<?php
session_start();
require_once 'database.php';
require_once 'functions.php';

$pdo = getDatabaseConnection();

// ── Auth ─────────────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
$stmt = $pdo->prepare("SELECT first_name, role FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user || $user['role'] !== 'restaurateur') {
    header('Location: index.php');
    exit;
}

// ── Messages flash ──────────────────────────────────────────
$flash = $_SESSION['carte_message'] ?? null;
$flashError = $_SESSION['carte_error'] ?? null;
unset($_SESSION['carte_message'], $_SESSION['carte_error']);

// ── Upload helper ───────────────────────────────────────────
function uploadAndResize(array $file, string $slug, int $targetW, int $targetH, string $placeholderPath): string {
    if ($file['error'] !== UPLOAD_ERR_OK) return '';
    $tmp = $file['tmp_name'];

    $info = getimagesize($tmp);
    if (!$info) return '';
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg': $src = imagecreatefromjpeg($tmp); break;
        case 'image/png':  $src = imagecreatefrompng($tmp); break;
        case 'image/webp': $src = imagecreatefromwebp($tmp); break;
        default: return '';
    }
    if (!$src) return '';

    $srcW = imagesx($src);
    $srcH = imagesy($src);

    // Crop au ratio cible
    $ratio = $targetW / $targetH;
    $srcRatio = $srcW / $srcH;
    if ($srcRatio > $ratio) {
        $cropW = (int)round($srcH * $ratio);
        $cropH = $srcH;
        $cropX = (int)round(($srcW - $cropW) / 2);
        $cropY = 0;
    } else {
        $cropW = $srcW;
        $cropH = (int)round($srcW / $ratio);
        $cropX = 0;
        $cropY = (int)round(($srcH - $cropH) / 2);
    }

    $dst = imagecreatetruecolor($targetW, $targetH);
    imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, $targetW, $targetH, $cropW, $cropH);
    imagedestroy($src);

    $dir = __DIR__ . '/images/catalogue';
    $filename = $slug . '.webp';
    $fullPath = $dir . '/' . $filename;

    imagewebp($dst, $fullPath, 82);
    imagedestroy($dst);

    return '/images/catalogue/' . $filename;
}

// ── Supprimer l'image associée ──────────────────────────────
function deleteImage(string $slug): void {
    $path = __DIR__ . '/images/catalogue/' . $slug . '.webp';
    if (file_exists($path)) unlink($path);
}

// ═══════════════════════════════════════════════════════════
// GESTION DES ACTIONS POST
// ═══════════════════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── DELETE DISH ─────────────────────────────────────────
    if (isset($_POST['action']) && $_POST['action'] === 'delete_dish') {
        $id = (int)($_POST['dish_id'] ?? 0);
        // Récupérer le nom pour supprimer l'image
        $d = $pdo->prepare('SELECT name FROM dishes WHERE id = ?');
        $d->execute([$id]);
        $dish = $d->fetch();
        if ($dish) {
            $slug = str_replace(' ', '-', str_replace(["'", "\u{2019}"], '', $dish['name']));
            // Vérifier que l'image n'est pas utilisée ailleurs (autre plat avec même slug ?)
            $check = $pdo->prepare('SELECT COUNT(*) FROM dishes WHERE id != ? AND name = ?');
            $check->execute([$id, $dish['name']]);
            if ((int)$check->fetchColumn() === 0) {
                $checkMenu = $pdo->prepare('SELECT COUNT(*) FROM menus WHERE name = ?');
                $checkMenu->execute([$dish['name']]);
                if ((int)$checkMenu->fetchColumn() === 0) {
                    deleteImage($slug);
                }
            }
            $pdo->prepare('DELETE FROM order_items WHERE item_id = ? AND item_type = ?')->execute([$id, 'dish']);
            $pdo->prepare('DELETE FROM menu_dishes WHERE dish_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM dishes WHERE id = ?')->execute([$id]);
        }
        $_SESSION['carte_message'] = 'Plat supprimé.';
        header('Location: gestion_carte.php?tab=dishes');
        exit;
    }

    // ── SAVE DISH (create / update) ─────────────────────────
    if (isset($_POST['action']) && $_POST['action'] === 'save_dish') {
        $dishId      = (int)($_POST['dish_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = (float)($_POST['price'] ?? 0);
        $allergens   = trim($_POST['allergens'] ?? '');
        $nutritional = trim($_POST['nutritional_info'] ?? '');
        $serviceId   = (int)($_POST['service_id'] ?? 0);
        $categoryId  = (int)($_POST['category_id'] ?? 0);

        if ($name === '' || $price <= 0 || $serviceId <= 0 || $categoryId <= 0) {
            $_SESSION['carte_error'] = 'Tous les champs obligatoires doivent être remplis.';
            header('Location: gestion_carte.php?tab=dishes' . ($dishId ? '&edit_dish=' . $dishId : ''));
            exit;
        }

        $newSlug = str_replace(' ', '-', str_replace(["'", "\u{2019}"], '', $name));

        // Si édition, gérer le renommage de l'image
        if ($dishId > 0) {
            $old = $pdo->prepare('SELECT name FROM dishes WHERE id = ?');
            $old->execute([$dishId]);
            $oldDish = $old->fetch();
            if ($oldDish) {
                $oldSlug = str_replace(' ', '-', str_replace(["'", "\u{2019}"], '', $oldDish['name']));
                $oldPath = __DIR__ . '/images/catalogue/' . $oldSlug . '.webp';
                $newPath = __DIR__ . '/images/catalogue/' . $newSlug . '.webp';
                // Renommer l'image si le nom a changé et qu'aucune nouvelle image n'est uploadée
                if ($oldSlug !== $newSlug && file_exists($oldPath) && !(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK)) {
                    // Vérifier qu'aucun autre plat/menu n'utilise l'ancien slug
                    $checkUse = $pdo->prepare('SELECT COUNT(*) FROM dishes WHERE id != ? AND name = ?');
                    $checkUse->execute([$dishId, $oldDish['name']]);
                    if ((int)$checkUse->fetchColumn() === 0) {
                        $checkMenu = $pdo->prepare('SELECT COUNT(*) FROM menus WHERE name = ?');
                        $checkMenu->execute([$oldDish['name']]);
                        if ((int)$checkMenu->fetchColumn() === 0) {
                            rename($oldPath, $newPath);
                        }
                    }
                }
            }
            $pdo->prepare('UPDATE dishes SET name=?, description=?, price=?, allergens=?, nutritional_info=?, service_id=?, category_id=? WHERE id=?')
                ->execute([$name, $description, $price, $allergens, $nutritional, $serviceId, $categoryId, $dishId]);
        } else {
            $pdo->prepare('INSERT INTO dishes (name, description, price, allergens, nutritional_info, service_id, category_id) VALUES (?,?,?,?,?,?,?)')
                ->execute([$name, $description, $price, $allergens, $nutritional, $serviceId, $categoryId]);
        }

        // Upload nouvelle image (écrase l'ancienne si renommée)
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            uploadAndResize($_FILES['image'], $newSlug, 450, 340, '/images/placeholder-dish.svg');
        }

        $_SESSION['carte_message'] = $dishId > 0 ? 'Plat modifié.' : 'Plat créé.';
        header('Location: gestion_carte.php?tab=dishes');
        exit;
    }

    // ── DELETE MENU ─────────────────────────────────────────
    if (isset($_POST['action']) && $_POST['action'] === 'delete_menu') {
        $id = (int)($_POST['menu_id'] ?? 0);
        $m = $pdo->prepare('SELECT name FROM menus WHERE id = ?');
        $m->execute([$id]);
        $menu = $m->fetch();
        if ($menu) {
            $slug = str_replace(' ', '-', str_replace(["'", "\u{2019}"], '', $menu['name']));
            $checkDish = $pdo->prepare('SELECT COUNT(*) FROM dishes WHERE name = ?');
            $checkDish->execute([$menu['name']]);
            if ((int)$checkDish->fetchColumn() === 0) {
                deleteImage($slug);
            }
            $pdo->prepare('DELETE FROM order_items WHERE item_id = ? AND item_type = ?')->execute([$id, 'menu']);
            $pdo->prepare('DELETE FROM menu_dishes WHERE menu_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM menus WHERE id = ?')->execute([$id]);
        }
        $_SESSION['carte_message'] = 'Menu supprimé.';
        header('Location: gestion_carte.php?tab=menus');
        exit;
    }

    // ── SAVE MENU (create / update) ─────────────────────────
    if (isset($_POST['action']) && $_POST['action'] === 'save_menu') {
        $menuId       = (int)($_POST['menu_id'] ?? 0);
        $name         = trim($_POST['name'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $totalPrice   = (float)($_POST['total_price'] ?? 0);
        $minPeople    = (int)($_POST['min_people'] ?? 1);
        $timeSlots    = trim($_POST['time_slots'] ?? '');
        $serviceId    = (int)($_POST['service_id'] ?? 0);
        $dishIds      = $_POST['dish_ids'] ?? [];

        if ($name === '' || $totalPrice <= 0 || $serviceId <= 0) {
            $_SESSION['carte_error'] = 'Tous les champs obligatoires doivent être remplis.';
            header('Location: gestion_carte.php?tab=menus' . ($menuId ? '&edit_menu=' . $menuId : ''));
            exit;
        }

        $newSlug = str_replace(' ', '-', str_replace(["'", "\u{2019}"], '', $name));

        // Si édition, gérer le renommage de l'image
        if ($menuId > 0) {
            $old = $pdo->prepare('SELECT name FROM menus WHERE id = ?');
            $old->execute([$menuId]);
            $oldMenu = $old->fetch();
            if ($oldMenu) {
                $oldSlug = str_replace(' ', '-', str_replace(["'", "\u{2019}"], '', $oldMenu['name']));
                $oldPath = __DIR__ . '/images/catalogue/' . $oldSlug . '.webp';
                $newPath = __DIR__ . '/images/catalogue/' . $newSlug . '.webp';
                if ($oldSlug !== $newSlug && file_exists($oldPath) && !(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK)) {
                    $checkUse = $pdo->prepare('SELECT COUNT(*) FROM menus WHERE id != ? AND name = ?');
                    $checkUse->execute([$menuId, $oldMenu['name']]);
                    if ((int)$checkUse->fetchColumn() === 0) {
                        $checkDish = $pdo->prepare('SELECT COUNT(*) FROM dishes WHERE name = ?');
                        $checkDish->execute([$oldMenu['name']]);
                        if ((int)$checkDish->fetchColumn() === 0) {
                            rename($oldPath, $newPath);
                        }
                    }
                }
            }
            $pdo->prepare('UPDATE menus SET name=?, description=?, total_price=?, min_people=?, time_slots=?, service_id=? WHERE id=?')
                ->execute([$name, $description, $totalPrice, $minPeople, $timeSlots, $serviceId, $menuId]);
            // Mettre à jour les plats liés
            $pdo->prepare('DELETE FROM menu_dishes WHERE menu_id = ?')->execute([$menuId]);
        } else {
            $pdo->prepare('INSERT INTO menus (name, description, total_price, min_people, time_slots, service_id) VALUES (?,?,?,?,?,?)')
                ->execute([$name, $description, $totalPrice, $minPeople, $timeSlots, $serviceId]);
            $menuId = $pdo->lastInsertId();
        }

        // Upload nouvelle image
        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            uploadAndResize($_FILES['image'], $newSlug, 600, 420, '/images/placeholder-menu.svg');
        }

        // Insérer les plats liés
        if (!empty($dishIds)) {
            $insertMd = $pdo->prepare('INSERT INTO menu_dishes (menu_id, dish_id) VALUES (?, ?)');
            foreach ($dishIds as $did) {
                $insertMd->execute([$menuId, (int)$did]);
            }
        }

        $_SESSION['carte_message'] = isset($_POST['menu_id']) && $_POST['menu_id'] > 0 ? 'Menu modifié.' : 'Menu créé.';
        header('Location: gestion_carte.php?tab=menus');
        exit;
    }
}

// ═══════════════════════════════════════════════════════════
// DONNÉES POUR L'AFFICHAGE
// ═══════════════════════════════════════════════════════════

$services = $pdo->query('SELECT * FROM services ORDER BY display_order')->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query('SELECT c.*, s.label as service_label FROM categories c JOIN services s ON s.id = c.service_id ORDER BY s.display_order, c.display_order')->fetchAll(PDO::FETCH_ASSOC);

// Plats groupés par service > catégorie
$allDishes = $pdo->query('SELECT d.*, c.name as cat_name, c.label as cat_label, s.name as svc_name, s.label as svc_label
    FROM dishes d
    JOIN categories c ON c.id = d.category_id
    JOIN services s ON s.id = d.service_id
    ORDER BY s.display_order, c.display_order, d.id')->fetchAll(PDO::FETCH_ASSOC);

$dishesByService = [];
foreach ($allDishes as $d) {
    $svcName = $d['svc_name'];
    if (!isset($dishesByService[$svcName])) {
        $dishesByService[$svcName] = ['label' => $d['svc_label'], 'categories' => []];
    }
    $catName = $d['cat_name'];
    if (!isset($dishesByService[$svcName]['categories'][$catName])) {
        $dishesByService[$svcName]['categories'][$catName] = ['label' => $d['cat_label'], 'dishes' => []];
    }
    $dishesByService[$svcName]['categories'][$catName]['dishes'][] = $d;
}

// Menus groupés par service
$allMenus = $pdo->query('SELECT m.*, s.name as svc_name, s.label as svc_label
    FROM menus m JOIN services s ON s.id = m.service_id
    ORDER BY s.display_order, m.id')->fetchAll(PDO::FETCH_ASSOC);

// Plats des menus
$menuDishesMap = [];
$mdRows = $pdo->query('SELECT md.menu_id, d.id, d.name FROM menu_dishes md JOIN dishes d ON d.id = md.dish_id ORDER BY d.id')->fetchAll(PDO::FETCH_ASSOC);
foreach ($mdRows as $row) {
    $menuDishesMap[(int)$row['menu_id']][] = ['id' => (int)$row['id'], 'name' => $row['name']];
}

// Catégories indexées par service_id pour le formulaire
$catsByService = [];
foreach ($categories as $cat) {
    $catsByService[(int)$cat['service_id']][] = $cat;
}

// Plats indexés par service pour le sélecteur de menu
$dishesForMenuSelect = [];
foreach ($allDishes as $d) {
    $dishesForMenuSelect[(int)$d['service_id']][] = $d;
}

// ═══════════════════════════════════════════════════════════
// Édition ? (pré-remplissage des formulaires)
// ═══════════════════════════════════════════════════════════

$tab = $_GET['tab'] ?? 'dishes';
$editDish = null;
$editMenu = null;
$editMenuDishIds = [];

if (isset($_GET['edit_dish'])) {
    $editId = (int)$_GET['edit_dish'];
    $stmt = $pdo->prepare('SELECT * FROM dishes WHERE id = ?');
    $stmt->execute([$editId]);
    $editDish = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editDish) $tab = 'dishes';
}

if (isset($_GET['edit_menu'])) {
    $editId = (int)$_GET['edit_menu'];
    $stmt = $pdo->prepare('SELECT * FROM menus WHERE id = ?');
    $stmt->execute([$editId]);
    $editMenu = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editMenu) {
        $tab = 'menus';
        $editMenuDishIds = array_column($menuDishesMap[$editId] ?? [], 'id');
    }
}
?>

<?php
$staffRole       = 'restaurateur';
$staffActivePage = 'carte';
$staffPageTitle  = 'Gérer la Carte';
$extraHead = '<link rel="stylesheet" href="/assets/css/catalogue.css">';
include 'includes/staff_header.php';
?>

<!-- ══ CSS embarqué pour cette page ══════════════════════════ -->
<style>
/* ── Tabs ──────────────────────────────── */
.carte-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 40px;
}

.carte-tab {
    all: unset;
    cursor: pointer;
    padding: 10px 24px;
    font-family: var(--font-ui);
    font-size: 0.78rem;
    font-weight: 300;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--text-muted);
    border: 1px solid var(--border-subtle);
    border-radius: 4px;
    transition: color 200ms ease, border-color 200ms ease, background 200ms ease;
}

.carte-tab:hover {
    color: var(--text-secondary);
    border-color: var(--accent-line);
}

.carte-tab.is-active {
    color: var(--accent);
    border-color: var(--accent);
    background: rgba(212, 165, 116, 0.06);
}

/* ── Grille services ────────────────────── */
.carte-service-block {
    margin-bottom: 44px;
}

.carte-service-title {
    font-family: var(--font-display);
    font-weight: 300;
    font-size: 1.5rem;
    font-style: italic;
    color: var(--text-primary);
    margin: 0 0 24px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border-subtle);
}

.carte-cat-block {
    margin-bottom: 28px;
}

.carte-cat-title {
    font-family: var(--font-sc);
    font-size: 0.68rem;
    font-weight: 600;
    letter-spacing: 0.32em;
    text-transform: uppercase;
    color: var(--accent);
    margin: 0 0 12px;
}

/* ── Table plats / menus ───────────────── */
.carte-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
}

.carte-table th {
    padding: 10px 14px;
    text-align: left;
    font-family: var(--font-sc);
    font-size: 0.62rem;
    letter-spacing: 0.28em;
    text-transform: uppercase;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border-subtle);
    white-space: nowrap;
}

.carte-table td {
    padding: 10px 14px;
    border-bottom: 1px solid var(--border-subtle);
    color: var(--text-secondary);
    vertical-align: middle;
}

.carte-table tbody tr:hover td {
    background: rgba(255, 255, 255, 0.015);
}

.carte-table tbody tr:last-child td {
    border-bottom: none;
}

.carte-table .col-name {
    font-family: var(--font-body);
    font-size: 0.95rem;
    color: var(--text-primary);
}

.carte-table .col-price {
    font-family: var(--font-display);
    white-space: nowrap;
}

.carte-table .col-dishes {
    font-size: 0.78rem;
    color: var(--text-muted);
    max-width: 260px;
    line-height: 1.4;
}

.carte-table .col-actions {
    white-space: nowrap;
    text-align: right;
}

.carte-img-thumb {
    width: 48px;
    height: 36px;
    border-radius: 4px;
    object-fit: cover;
    border: 1px solid var(--border-subtle);
}

/* ── Header de bloc + bouton ajouter ───── */
.carte-block-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.carte-add-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 16px;
    font-family: var(--font-ui);
    font-size: 0.7rem;
    font-weight: 300;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    cursor: pointer;
    border: 1px solid var(--accent);
    color: var(--accent);
    background: transparent;
    border-radius: 4px;
    transition: background 200ms ease, color 200ms ease;
}

.carte-add-btn:hover {
    background: var(--accent);
    color: var(--surface-dark);
}

.carte-add-btn--sm {
    padding: 5px 12px;
    font-size: 0.65rem;
}

/* ── Boutons d'action inline ───────────── */
.carte-action-btn {
    all: unset;
    cursor: pointer;
    padding: 4px 10px;
    font-family: var(--font-ui);
    font-size: 0.65rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    border: 1px solid var(--border-subtle);
    border-radius: 3px;
    color: var(--text-muted);
    transition: color 200ms ease, border-color 200ms ease;
}

.carte-action-btn:hover {
    color: var(--accent);
    border-color: var(--accent);
}

.carte-action-btn--danger:hover {
    color: #c97a7a;
    border-color: #c97a7a;
}

/* ── Modal overlay ─────────────────────── */
.carte-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 500;
    background: rgba(22, 20, 18, 0.7);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 300ms ease, visibility 0s linear 300ms;
}

.carte-modal-overlay.is-open {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
    transition: opacity 300ms ease, visibility 0s;
}

/* ── Modal ─────────────────────────────── */
.carte-modal {
    background: var(--surface-dark);
    border: 1px solid var(--accent-line);
    border-radius: 16px;
    width: min(920px, 95vw);
    max-height: 88vh;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    scrollbar-width: thin;
    scrollbar-color: var(--accent-line) transparent;
}

.carte-modal::-webkit-scrollbar { width: 4px; }
.carte-modal::-webkit-scrollbar-track { background: transparent; }
.carte-modal::-webkit-scrollbar-thumb { background: var(--accent-line); border-radius: 2px; }

.carte-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 22px 28px;
    border-bottom: 1px solid var(--border-subtle);
    flex-shrink: 0;
}

.carte-modal-title {
    font-family: var(--font-display);
    font-weight: 400;
    font-style: italic;
    font-size: 1.3rem;
    color: var(--text-primary);
}

.carte-modal-close {
    all: unset;
    cursor: pointer;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-muted);
    border-radius: 50%;
    transition: color 200ms ease, background 200ms ease;
}

.carte-modal-close:hover {
    color: var(--text-primary);
    background: rgba(255, 255, 255, 0.06);
}

/* ── Modal body (form + preview) ──────── */
.carte-modal-body {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 0;
    flex: 1;
    overflow: hidden;
}

@media (max-width: 800px) {
    .carte-modal-body {
        grid-template-columns: 1fr;
    }
    .carte-preview-panel {
        display: none;
    }
}

/* ── Formulaire ────────────────────────── */
.carte-form {
    padding: 24px 28px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    overflow-y: auto;
}

.carte-form-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.carte-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}

.carte-form-label {
    font-family: var(--font-sc);
    font-size: 0.62rem;
    letter-spacing: 0.26em;
    text-transform: uppercase;
    color: var(--text-muted);
}

.carte-form-input,
.carte-form-select,
.carte-form-textarea {
    padding: 10px 14px;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid var(--border-subtle);
    border-radius: 6px;
    font-family: var(--font-ui);
    font-size: 0.85rem;
    color: var(--text-primary);
    outline: none;
    transition: border-color 200ms ease;
}

.carte-form-input:focus,
.carte-form-select:focus,
.carte-form-textarea:focus {
    border-color: rgba(212, 165, 116, 0.5);
}

.carte-form-textarea {
    min-height: 80px;
    resize: vertical;
    font-family: var(--font-body);
    font-size: 0.92rem;
}

.carte-form-select {
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%238a8175' stroke-width='1.2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 36px;
}

.carte-form-select option {
    background: var(--surface-dark);
    color: var(--text-primary);
}

/* ── File upload ───────────────────────── */
.carte-file-area {
    position: relative;
    border: 1px dashed var(--border-subtle);
    border-radius: 8px;
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: border-color 200ms ease, background 200ms ease;
}

.carte-file-area:hover {
    border-color: var(--accent-line);
    background: rgba(255, 255, 255, 0.02);
}

.carte-file-area.has-file {
    border-style: solid;
    border-color: var(--accent);
}

.carte-file-area input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
}

.carte-file-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: var(--text-muted);
    font-family: var(--font-ui);
    font-size: 0.8rem;
}

.carte-file-placeholder svg {
    width: 32px;
    height: 32px;
    opacity: 0.4;
}

.carte-file-preview {
    display: none;
}

.carte-file-area.has-file .carte-file-placeholder {
    display: none;
}

.carte-file-area.has-file .carte-file-preview {
    display: block;
}

.carte-file-preview img {
    max-height: 120px;
    border-radius: 6px;
    object-fit: cover;
}

.carte-file-name {
    font-family: var(--font-ui);
    font-size: 0.75rem;
    color: var(--text-secondary);
    margin-top: 6px;
}

/* ── Checkboxes pour les plats du menu ── */
.carte-dish-checks {
    display: flex;
    flex-direction: column;
    gap: 6px;
    max-height: 200px;
    overflow-y: auto;
    padding: 8px;
    border: 1px solid var(--border-subtle);
    border-radius: 6px;
    scrollbar-width: thin;
    scrollbar-color: var(--accent-line) transparent;
}

.carte-dish-checks::-webkit-scrollbar { width: 4px; }
.carte-dish-checks::-webkit-scrollbar-track { background: transparent; }
.carte-dish-checks::-webkit-scrollbar-thumb { background: var(--accent-line); border-radius: 2px; }

.carte-check-label {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 8px;
    border-radius: 4px;
    cursor: pointer;
    font-family: var(--font-body);
    font-size: 0.9rem;
    color: var(--text-secondary);
    transition: background 150ms ease;
}

.carte-check-label:hover {
    background: rgba(255, 255, 255, 0.02);
}

.carte-check-label input[type="checkbox"] {
    appearance: none;
    -webkit-appearance: none;
    width: 16px;
    height: 16px;
    border: 1px solid var(--accent-line);
    border-radius: 2px;
    background: transparent;
    cursor: pointer;
    flex-shrink: 0;
    position: relative;
}

.carte-check-label input[type="checkbox"]:checked {
    background: var(--accent);
    border-color: var(--accent);
}

.carte-check-label input[type="checkbox"]:checked::after {
    content: '';
    position: absolute;
    left: 4px;
    top: 1px;
    width: 5px;
    height: 9px;
    border: solid var(--surface-dark);
    border-width: 0 1.5px 1.5px 0;
    transform: rotate(45deg);
}

/* ── Panneau de preview ────────────────── */
.carte-preview-panel {
    border-left: 1px solid var(--border-subtle);
    padding: 24px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    overflow-y: auto;
    background: rgba(255, 255, 255, 0.01);
}

.carte-preview-label {
    font-family: var(--font-sc);
    font-size: 0.6rem;
    letter-spacing: 0.3em;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 4px;
}

.carte-preview-card {
    width: 100%;
    max-width: 300px;
    border: 1px solid var(--accent-line);
    border-radius: 4px;
    overflow: hidden;
    background: #161412;
}

.carte-preview-card-img {
    background: #1a1714;
}

.carte-preview-card-img img {
    width: 100%;
    display: block;
    object-fit: cover;
}

.carte-preview-card-body {
    padding: 14px 16px 16px;
}

.carte-preview-card-name {
    font-family: var(--font-display);
    font-weight: 400;
    font-size: 0.95rem;
    color: var(--text-primary);
    margin: 0 0 4px;
    line-height: 1.25;
}

.carte-preview-card-desc {
    font-family: var(--font-body);
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin: 0 0 8px;
    line-height: 1.4;
}

.carte-preview-card-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.carte-preview-card-price {
    font-family: var(--font-display);
    font-size: 0.92rem;
    color: var(--text-primary);
}

.carte-preview-card-dishes {
    font-family: var(--font-ui);
    font-size: 0.6rem;
    letter-spacing: 0.06em;
    color: var(--text-muted);
    margin-top: 8px;
    line-height: 1.5;
}

/* ── Modal footer (boutons) ───────────── */
.carte-modal-footer {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    padding: 16px 28px 24px;
    border-top: 1px solid var(--border-subtle);
}

.carte-btn-cancel {
    all: unset;
    cursor: pointer;
    padding: 10px 20px;
    font-family: var(--font-ui);
    font-size: 0.72rem;
    font-weight: 300;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--text-muted);
    border: 1px solid var(--border-subtle);
    border-radius: 6px;
    transition: color 200ms ease, border-color 200ms ease;
}

.carte-btn-cancel:hover {
    color: var(--text-secondary);
    border-color: var(--accent-line);
}

.carte-btn-submit {
    all: unset;
    cursor: pointer;
    padding: 10px 24px;
    font-family: var(--font-ui);
    font-size: 0.72rem;
    font-weight: 400;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--surface-dark);
    background: var(--accent-gradient);
    border-radius: 6px;
    transition: opacity 200ms ease, box-shadow 200ms ease;
    box-shadow: 0 4px 12px rgba(212, 165, 116, 0.12);
}

.carte-btn-submit:hover {
    opacity: 0.9;
    box-shadow: 0 6px 20px rgba(212, 165, 116, 0.22);
}

/* ── Message flash ─────────────────────── */
.carte-flash {
    padding: 14px 20px;
    margin-bottom: 24px;
    font-size: 0.85rem;
    letter-spacing: 0.04em;
    border-left: 2px solid var(--accent);
    color: var(--accent);
    background: rgba(212, 165, 116, 0.06);
}

.carte-flash--error {
    border-color: #c97a7a;
    color: #c97a7a;
    background: rgba(201, 122, 122, 0.06);
}

/* ── Pas de données ────────────────────── */
.carte-empty {
    padding: 32px 0;
    text-align: center;
    color: var(--text-muted);
    font-size: 0.9rem;
    font-style: italic;
}

/* ── Formulaire suppression inline ─────── */
.carte-inline-form {
    display: inline;
}
</style>

<main class="staff-page">
  <div class="staff-inner">

    <header class="staff-header">
      <p class="staff-label">Espace restaurateur</p>
      <h1 class="staff-title">Gérer <em>la Carte</em></h1>
    </header>

    <?php if ($flash): ?>
      <div class="carte-flash"><?= h($flash) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
      <div class="carte-flash carte-flash--error"><?= h($flashError) ?></div>
    <?php endif; ?>

    <!-- ══ Tabs ═══════════════════════════════════════════════ -->
    <div class="carte-tabs">
        <a href="gestion_carte.php?tab=dishes" class="carte-tab<?= $tab === 'dishes' ? ' is-active' : '' ?>">Plats</a>
        <a href="gestion_carte.php?tab=menus" class="carte-tab<?= $tab === 'menus' ? ' is-active' : '' ?>">Menus</a>
    </div>

    <?php if ($tab === 'dishes'): ?>
    <!-- ══════════════════════════════════════════════════════ -->
    <!-- PLATS                                                  -->
    <!-- ══════════════════════════════════════════════════════ -->

    <div class="carte-block-header">
        <h2 style="font-family:var(--font-display);font-weight:300;font-size:1.3rem;color:var(--text-primary);margin:0;">
            Tous les plats
        </h2>
        <a href="gestion_carte.php?tab=dishes&new_dish=1" class="carte-add-btn">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><line x1="8" y1="2" x2="8" y2="14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><line x1="2" y1="8" x2="14" y2="8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
            Ajouter un plat
        </a>
    </div>

    <?php if (empty($dishesByService)): ?>
        <div class="carte-empty">Aucun plat pour le moment.</div>
    <?php else: ?>
        <?php foreach ($dishesByService as $svcName => $svcData): ?>
        <div class="carte-service-block">
            <h3 class="carte-service-title"><?= h($svcData['label']) ?></h3>
            <?php foreach ($svcData['categories'] as $catName => $catData): ?>
            <div class="carte-cat-block">
                <h4 class="carte-cat-title"><?= h($catData['label']) ?></h4>
                <table class="carte-table">
                    <thead>
                        <tr>
                            <th style="width:52px"></th>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Prix</th>
                            <th>Allergènes</th>
                            <th style="width:110px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($catData['dishes'] as $dish): ?>
                        <?php $img = catalogue_image($dish['name'], '/images/placeholder-dish.svg'); ?>
                        <tr>
                            <td><img class="carte-img-thumb" src="<?= $img ?>" alt="" loading="lazy"></td>
                            <td class="col-name"><?= h($dish['name']) ?></td>
                            <td style="font-size:0.82rem;max-width:300px;"><?= h($dish['description'] ?? '—') ?></td>
                            <td class="col-price"><?= number_format($dish['price'], 2, ',', ' ') ?> €</td>
                            <td style="font-size:0.75rem;color:var(--text-muted)"><?= h($dish['allergens'] ?? '—') ?></td>
                            <td class="col-actions">
                                <a href="gestion_carte.php?tab=dishes&edit_dish=<?= $dish['id'] ?>" class="carte-action-btn">Modifier</a>
                                <form method="post" class="carte-inline-form" onsubmit="return confirm(<?= json_encode('Supprimer « ' . $dish['name'] . ' » ? Cette action est irréversible.') ?>);">
                                    <input type="hidden" name="action" value="delete_dish">
                                    <input type="hidden" name="dish_id" value="<?= $dish['id'] ?>">
                                    <button type="submit" class="carte-action-btn carte-action-btn--danger">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php else: ?>
    <!-- ══════════════════════════════════════════════════════ -->
    <!-- MENUS                                                  -->
    <!-- ══════════════════════════════════════════════════════ -->

    <div class="carte-block-header">
        <h2 style="font-family:var(--font-display);font-weight:300;font-size:1.3rem;color:var(--text-primary);margin:0;">
            Tous les menus
        </h2>
        <a href="gestion_carte.php?tab=menus&new_menu=1" class="carte-add-btn">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><line x1="8" y1="2" x2="8" y2="14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><line x1="2" y1="8" x2="14" y2="8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
            Ajouter un menu
        </a>
    </div>

    <?php if (empty($allMenus)): ?>
        <div class="carte-empty">Aucun menu pour le moment.</div>
    <?php else: ?>
        <?php $currentSvc = null; ?>
        <?php foreach ($allMenus as $menu): ?>
            <?php if ($currentSvc !== $menu['svc_name']): ?>
                <?php if ($currentSvc !== null): ?></tbody></table></div><?php endif; ?>
                <?php $currentSvc = $menu['svc_name']; ?>
                <div class="carte-service-block">
                    <h3 class="carte-service-title"><?= h($menu['svc_label']) ?></h3>
                    <table class="carte-table">
                        <thead>
                            <tr>
                                <th style="width:52px"></th>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Prix</th>
                                <th>Plats</th>
                                <th style="width:110px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            <?php endif; ?>
            <?php
                $img = catalogue_image($menu['name'], '/images/placeholder-menu.svg');
                $mdDishes = $menuDishesMap[(int)$menu['id']] ?? [];
            ?>
            <tr>
                <td><img class="carte-img-thumb" src="<?= $img ?>" alt="" loading="lazy"></td>
                <td class="col-name"><?= h($menu['name']) ?></td>
                <td style="font-size:0.82rem;max-width:280px;"><?= h($menu['description'] ?? '—') ?></td>
                <td class="col-price"><?= number_format($menu['total_price'], 2, ',', ' ') ?> €</td>
                <td class="col-dishes">
                    <?php if (empty($mdDishes)): ?>
                        —
                    <?php else: ?>
                        <?= h(implode(', ', array_column($mdDishes, 'name'))) ?>
                    <?php endif; ?>
                </td>
                <td class="col-actions">
                    <a href="gestion_carte.php?tab=menus&edit_menu=<?= $menu['id'] ?>" class="carte-action-btn">Modifier</a>
                    <form method="post" class="carte-inline-form" onsubmit="return confirm(<?= json_encode('Supprimer « ' . $menu['name'] . ' » ? Cette action est irréversible.') ?>);">
                        <input type="hidden" name="action" value="delete_menu">
                        <input type="hidden" name="menu_id" value="<?= $menu['id'] ?>">
                        <button type="submit" class="carte-action-btn carte-action-btn--danger">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <?php endif; ?>

    <?php endif; ?>

  </div>
</main>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL ÉDITION PLAT (ouvert si edit_dish ou new_dish)       -->
<!-- ══════════════════════════════════════════════════════════ -->

<?php $openDishModal = isset($_GET['edit_dish']) || isset($_GET['new_dish']); ?>
<div class="carte-modal-overlay<?= $openDishModal ? ' is-open' : '' ?>" id="dishModalOverlay">
  <div class="carte-modal" id="dishModal">
    <div class="carte-modal-header">
      <h3 class="carte-modal-title"><?= $editDish ? 'Modifier le plat' : 'Nouveau plat' ?></h3>
      <a href="gestion_carte.php?tab=dishes" class="carte-modal-close" aria-label="Fermer">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
          <line x1="5" y1="5" x2="15" y2="15"/><line x1="15" y1="5" x2="5" y2="15"/>
        </svg>
      </a>
    </div>

    <form method="post" enctype="multipart/form-data" id="dishForm">
      <input type="hidden" name="action" value="save_dish">
      <input type="hidden" name="dish_id" value="<?= $editDish['id'] ?? '' ?>">

      <div class="carte-modal-body">
        <!-- Colonne formulaire -->
        <div class="carte-form">
          <div class="carte-form-row">
            <div class="carte-form-group">
              <label class="carte-form-label" for="dish_service">Service *</label>
              <select class="carte-form-select" name="service_id" id="dish_service" required>
                <option value="">— Choisir —</option>
                <?php foreach ($services as $svc): ?>
                  <option value="<?= $svc['id'] ?>" <?= ($editDish && $editDish['service_id'] == $svc['id']) ? 'selected' : '' ?>><?= h($svc['label']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="carte-form-group">
              <label class="carte-form-label" for="dish_category">Catégorie *</label>
              <select class="carte-form-select" name="category_id" id="dish_category" required>
                <option value="">— Choisir un service d'abord —</option>
                <?php if ($editDish): ?>
                  <?php foreach ($categories as $cat): ?>
                    <?php if ($cat['service_id'] == $editDish['service_id']): ?>
                      <option value="<?= $cat['id'] ?>" <?= $editDish['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= h($cat['label']) ?></option>
                    <?php endif; ?>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
          </div>

          <div class="carte-form-group">
            <label class="carte-form-label" for="dish_name">Nom du plat *</label>
            <input type="text" class="carte-form-input" name="name" id="dish_name"
                   value="<?= h($editDish['name'] ?? '') ?>" required
                   placeholder="Ex : Filet de bœuf Rossini">
          </div>

          <div class="carte-form-group">
            <label class="carte-form-label" for="dish_desc">Description</label>
            <textarea class="carte-form-textarea" name="description" id="dish_desc"
                      placeholder="Texte de description…"><?= h($editDish['description'] ?? '') ?></textarea>
          </div>

          <div class="carte-form-row">
            <div class="carte-form-group">
              <label class="carte-form-label" for="dish_price">Prix (€) *</label>
              <input type="number" step="0.01" min="0.01" class="carte-form-input" name="price" id="dish_price"
                     value="<?= $editDish ? number_format($editDish['price'], 2, '.', '') : '' ?>" required>
            </div>
            <div class="carte-form-group">
              <label class="carte-form-label" for="dish_allergens">Allergènes</label>
              <input type="text" class="carte-form-input" name="allergens" id="dish_allergens"
                     value="<?= h($editDish['allergens'] ?? '') ?>"
                     placeholder="Ex : gluten, lait, oeuf">
            </div>
          </div>

          <div class="carte-form-group">
            <label class="carte-form-label" for="dish_nutrition">Infos nutritionnelles</label>
            <input type="text" class="carte-form-input" name="nutritional_info" id="dish_nutrition"
                   value="<?= h($editDish['nutritional_info'] ?? '') ?>"
                   placeholder="Ex : 320 kcal / portion">
          </div>

          <div class="carte-form-group">
            <label class="carte-form-label">Image du plat</label>
            <div class="carte-file-area<?= ($editDish && !empty($editDish['image'])) ? ' has-file' : '' ?>" id="dishFileArea">
              <input type="file" name="image" id="dish_image" accept="image/jpeg,image/png,image/webp">
              <div class="carte-file-placeholder">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                <span>Cliquer ou glisser une image<br><small>JPEG, PNG ou WebP — recadrée en 450×340</small></span>
              </div>
              <div class="carte-file-preview">
                <img id="dishFilePreviewImg" src="<?= ($editDish ? catalogue_image($editDish['name'], '/images/placeholder-dish.svg') : '') ?>" alt="Aperçu">
                <div class="carte-file-name" id="dishFileName"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Colonne preview -->
        <div class="carte-preview-panel" id="dishPreviewPanel">
          <span class="carte-preview-label">Aperçu sur la carte</span>
          <div class="carte-preview-card" style="max-width:260px;">
            <div class="carte-preview-card-img">
              <img id="dishPreviewImg" src="<?= ($editDish ? catalogue_image($editDish['name'], '/images/placeholder-dish.svg') : '/images/placeholder-dish.svg') ?>"
                   style="height:170px;" alt="">
            </div>
            <div class="carte-preview-card-body">
              <h4 class="carte-preview-card-name" id="dishPreviewName"><?= h($editDish['name'] ?? 'Nom du plat') ?></h4>
              <p class="carte-preview-card-desc" id="dishPreviewDesc"><?= h($editDish['description'] ?? '') ?></p>
              <div class="carte-preview-card-meta">
                <span class="carte-preview-card-price" id="dishPreviewPrice"><?= $editDish ? number_format($editDish['price'], 2, ',', ' ') . ' €' : '0,00 €' ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="carte-modal-footer">
        <a href="gestion_carte.php?tab=dishes" class="carte-btn-cancel">Annuler</a>
        <button type="submit" class="carte-btn-submit"><?= $editDish ? 'Enregistrer' : 'Créer le plat' ?></button>
      </div>
    </form>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL ÉDITION MENU (ouvert si edit_menu ou new_menu)       -->
<!-- ══════════════════════════════════════════════════════════ -->

<?php $openMenuModal = isset($_GET['edit_menu']) || isset($_GET['new_menu']); ?>
<div class="carte-modal-overlay<?= $openMenuModal ? ' is-open' : '' ?>" id="menuModalOverlay">
  <div class="carte-modal" id="menuModal">
    <div class="carte-modal-header">
      <h3 class="carte-modal-title"><?= $editMenu ? 'Modifier le menu' : 'Nouveau menu' ?></h3>
      <a href="gestion_carte.php?tab=menus" class="carte-modal-close" aria-label="Fermer">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round">
          <line x1="5" y1="5" x2="15" y2="15"/><line x1="15" y1="5" x2="5" y2="15"/>
        </svg>
      </a>
    </div>

    <form method="post" enctype="multipart/form-data" id="menuForm">
      <input type="hidden" name="action" value="save_menu">
      <input type="hidden" name="menu_id" value="<?= $editMenu['id'] ?? '' ?>">

      <div class="carte-modal-body">
        <!-- Colonne formulaire -->
        <div class="carte-form">
          <div class="carte-form-group">
            <label class="carte-form-label" for="menu_service">Service *</label>
            <select class="carte-form-select" name="service_id" id="menu_service" required>
              <option value="">— Choisir —</option>
              <?php foreach ($services as $svc): ?>
                <option value="<?= $svc['id'] ?>" <?= ($editMenu && $editMenu['service_id'] == $svc['id']) ? 'selected' : '' ?>><?= h($svc['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="carte-form-group">
            <label class="carte-form-label" for="menu_name">Nom du menu *</label>
            <input type="text" class="carte-form-input" name="name" id="menu_name"
                   value="<?= h($editMenu['name'] ?? '') ?>" required
                   placeholder="Ex : Signature du Chef">
          </div>

          <div class="carte-form-group">
            <label class="carte-form-label" for="menu_desc">Description</label>
            <textarea class="carte-form-textarea" name="description" id="menu_desc"
                      placeholder="Texte de description…"><?= h($editMenu['description'] ?? '') ?></textarea>
          </div>

          <div class="carte-form-row">
            <div class="carte-form-group">
              <label class="carte-form-label" for="menu_price">Prix total (€) *</label>
              <input type="number" step="0.01" min="0.01" class="carte-form-input" name="total_price" id="menu_price"
                     value="<?= $editMenu ? number_format($editMenu['total_price'], 2, '.', '') : '' ?>" required>
            </div>
            <div class="carte-form-group">
              <label class="carte-form-label" for="menu_min_people">Min. personnes</label>
              <input type="number" min="1" class="carte-form-input" name="min_people" id="menu_min_people"
                     value="<?= $editMenu ? (int)$editMenu['min_people'] : '1' ?>">
            </div>
          </div>

          <div class="carte-form-group">
            <label class="carte-form-label" for="menu_slots">Créneau horaire</label>
            <input type="text" class="carte-form-input" name="time_slots" id="menu_slots"
                   value="<?= h($editMenu['time_slots'] ?? '') ?>"
                   placeholder="Ex : 19h30-23h00">
          </div>

          <div class="carte-form-group">
            <label class="carte-form-label">Plats du menu</label>
            <div class="carte-dish-checks" id="menuDishChecks">
              <span style="color:var(--text-muted);font-size:0.8rem;font-style:italic;">Choisissez d'abord un service</span>
            </div>
          </div>

          <div class="carte-form-group">
            <label class="carte-form-label">Image du menu</label>
            <div class="carte-file-area<?= ($editMenu && !empty($editMenu['image'])) ? ' has-file' : '' ?>" id="menuFileArea">
              <input type="file" name="image" id="menu_image" accept="image/jpeg,image/png,image/webp">
              <div class="carte-file-placeholder">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                <span>Cliquer ou glisser une image<br><small>JPEG, PNG ou WebP — recadrée en 600×420</small></span>
              </div>
              <div class="carte-file-preview">
                <img id="menuFilePreviewImg" src="<?= ($editMenu ? catalogue_image($editMenu['name'], '/images/placeholder-menu.svg') : '') ?>" alt="Aperçu">
                <div class="carte-file-name" id="menuFileName"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Colonne preview -->
        <div class="carte-preview-panel" id="menuPreviewPanel">
          <span class="carte-preview-label">Aperçu sur la carte</span>
          <div class="carte-preview-card" style="max-width:280px;">
            <div class="carte-preview-card-img">
              <img id="menuPreviewImg" src="<?= ($editMenu ? catalogue_image($editMenu['name'], '/images/placeholder-menu.svg') : '/images/placeholder-menu.svg') ?>"
                   style="height:210px;" alt="">
            </div>
            <div class="carte-preview-card-body">
              <h4 class="carte-preview-card-name" id="menuPreviewName"><?= h($editMenu['name'] ?? 'Nom du menu') ?></h4>
              <p class="carte-preview-card-desc" id="menuPreviewDesc"><?= h($editMenu['description'] ?? '') ?></p>
              <div class="carte-preview-card-meta">
                <span class="carte-preview-card-price" id="menuPreviewPrice"><?= $editMenu ? number_format($editMenu['total_price'], 2, ',', ' ') . ' €' : '0,00 €' ?></span>
              </div>
              <p class="carte-preview-card-dishes" id="menuPreviewDishes"></p>
            </div>
          </div>
        </div>
      </div>

      <div class="carte-modal-footer">
        <a href="gestion_carte.php?tab=menus" class="carte-btn-cancel">Annuler</a>
        <button type="submit" class="carte-btn-submit"><?= $editMenu ? 'Enregistrer' : 'Créer le menu' ?></button>
      </div>
    </form>
  </div>
</div>

<!-- ══ JavaScript ═════════════════════════════════════════════ -->
<script>
(function() {
    'use strict';

    // ══════════════════════════════════════════════════════════
    // Données pour le JS
    // ══════════════════════════════════════════════════════════

    var catsByService = <?= json_encode($catsByService, JSON_UNESCAPED_UNICODE) ?>;
    var dishesByService = <?= json_encode($dishesForMenuSelect, JSON_UNESCAPED_UNICODE) ?>;

    <?php if ($editMenu): ?>
    var editMenuDishIds = <?= json_encode($editMenuDishIds) ?>;
    <?php else: ?>
    var editMenuDishIds = [];
    <?php endif; ?>

    // ══════════════════════════════════════════════════════════
    // DÉPENDANCE SERVICE → CATÉGORIE (formulaire plat)
    // ══════════════════════════════════════════════════════════

    var dishServiceSelect = document.getElementById('dish_service');
    var dishCategorySelect = document.getElementById('dish_category');

    if (dishServiceSelect && dishCategorySelect) {
        dishServiceSelect.addEventListener('change', function() {
            var svcId = parseInt(this.value, 10);
            dishCategorySelect.innerHTML = '<option value="">— Choisir —</option>';
            if (svcId && catsByService[svcId]) {
                catsByService[svcId].forEach(function(cat) {
                    var opt = document.createElement('option');
                    opt.value = cat.id;
                    opt.textContent = cat.label;
                    dishCategorySelect.appendChild(opt);
                });
            }
        });
    }

    // ══════════════════════════════════════════════════════════
    // DÉPENDANCE SERVICE → PLATS (formulaire menu)
    // ══════════════════════════════════════════════════════════

    var menuServiceSelect = document.getElementById('menu_service');
    var menuDishChecks = document.getElementById('menuDishChecks');

    function updateMenuDishChecks(svcId) {
        menuDishChecks.innerHTML = '';
        if (!svcId || !dishesByService[svcId]) {
            menuDishChecks.innerHTML = '<span style="color:var(--text-muted);font-size:0.8rem;font-style:italic;">Choisissez d\'abord un service</span>';
            return;
        }
        var dishes = dishesByService[svcId];
        if (!dishes.length) {
            menuDishChecks.innerHTML = '<span style="color:var(--text-muted);font-size:0.8rem;font-style:italic;">Aucun plat dans ce service</span>';
            return;
        }
        dishes.forEach(function(d) {
            var label = document.createElement('label');
            label.className = 'carte-check-label';
            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.name = 'dish_ids[]';
            cb.value = d.id;
            if (editMenuDishIds.indexOf(d.id) !== -1) cb.checked = true;
            label.appendChild(cb);
            label.appendChild(document.createTextNode(d.name + ' (' + parseFloat(d.price).toFixed(2).replace('.', ',') + ' €)'));
            menuDishChecks.appendChild(label);
        });
    }

    if (menuServiceSelect && menuDishChecks) {
        menuServiceSelect.addEventListener('change', function() {
            updateMenuDishChecks(parseInt(this.value, 10));
        });
        // Initialiser si édition
        <?php if ($editMenu): ?>
        updateMenuDishChecks(<?= (int)$editMenu['service_id'] ?>);
        <?php endif; ?>
    }

    // ══════════════════════════════════════════════════════════
    // PREVIEW LIVE — PLAT
    // ══════════════════════════════════════════════════════════

    var dishNameInput = document.getElementById('dish_name');
    var dishDescInput = document.getElementById('dish_desc');
    var dishPriceInput = document.getElementById('dish_price');
    var dishImageInput = document.getElementById('dish_image');
    var dishFileArea = document.getElementById('dishFileArea');

    var dishPreviewName = document.getElementById('dishPreviewName');
    var dishPreviewDesc = document.getElementById('dishPreviewDesc');
    var dishPreviewPrice = document.getElementById('dishPreviewPrice');
    var dishPreviewImg = document.getElementById('dishPreviewImg');
    var dishFilePreviewImg = document.getElementById('dishFilePreviewImg');
    var dishFileName = document.getElementById('dishFileName');

    if (dishNameInput) {
        dishNameInput.addEventListener('input', function() {
            dishPreviewName.textContent = this.value || 'Nom du plat';
        });
    }
    if (dishDescInput) {
        dishDescInput.addEventListener('input', function() {
            dishPreviewDesc.textContent = this.value || '';
        });
    }
    if (dishPriceInput) {
        dishPriceInput.addEventListener('input', function() {
            var v = parseFloat(this.value);
            dishPreviewPrice.textContent = isNaN(v) ? '0,00 €' : v.toFixed(2).replace('.', ',') + ' €';
        });
    }
    if (dishImageInput && dishFileArea) {
        dishImageInput.addEventListener('change', function() {
            var file = this.files[0];
            if (file) {
                dishFileArea.classList.add('has-file');
                dishFileName.textContent = file.name;
                var reader = new FileReader();
                reader.onload = function(e) {
                    dishPreviewImg.src = e.target.result;
                    dishFilePreviewImg.src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                dishFileArea.classList.remove('has-file');
            }
        });
    }

    // ══════════════════════════════════════════════════════════
    // PREVIEW LIVE — MENU
    // ══════════════════════════════════════════════════════════

    var menuNameInput = document.getElementById('menu_name');
    var menuDescInput = document.getElementById('menu_desc');
    var menuPriceInput = document.getElementById('menu_price');
    var menuImageInput = document.getElementById('menu_image');
    var menuFileArea = document.getElementById('menuFileArea');

    var menuPreviewName = document.getElementById('menuPreviewName');
    var menuPreviewDesc = document.getElementById('menuPreviewDesc');
    var menuPreviewPrice = document.getElementById('menuPreviewPrice');
    var menuPreviewImg = document.getElementById('menuPreviewImg');
    var menuPreviewDishes = document.getElementById('menuPreviewDishes');
    var menuFilePreviewImg = document.getElementById('menuFilePreviewImg');
    var menuFileName = document.getElementById('menuFileName');

    if (menuNameInput) {
        menuNameInput.addEventListener('input', function() {
            menuPreviewName.textContent = this.value || 'Nom du menu';
        });
    }
    if (menuDescInput) {
        menuDescInput.addEventListener('input', function() {
            menuPreviewDesc.textContent = this.value || '';
        });
    }
    if (menuPriceInput) {
        menuPriceInput.addEventListener('input', function() {
            var v = parseFloat(this.value);
            menuPreviewPrice.textContent = isNaN(v) ? '0,00 €' : v.toFixed(2).replace('.', ',') + ' €';
        });
    }
    if (menuImageInput && menuFileArea) {
        menuImageInput.addEventListener('change', function() {
            var file = this.files[0];
            if (file) {
                menuFileArea.classList.add('has-file');
                menuFileName.textContent = file.name;
                var reader = new FileReader();
                reader.onload = function(e) {
                    menuPreviewImg.src = e.target.result;
                    menuFilePreviewImg.src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                menuFileArea.classList.remove('has-file');
            }
        });
    }

    // Preview des plats cochés pour le menu
    if (menuDishChecks) {
        menuDishChecks.addEventListener('change', function() {
            var checked = menuDishChecks.querySelectorAll('input[type="checkbox"]:checked');
            var names = [];
            checked.forEach(function(cb) {
                var text = cb.parentNode.textContent.trim();
                // Enlever le prix entre parenthèses
                text = text.replace(/\s*\(\d+,\d{2}\s*€\)\s*$/, '');
                names.push(text);
            });
            menuPreviewDishes.textContent = names.length ? names.join(' · ') : '';
        });
    }

})();
</script>

<?php include 'includes/staff_footer.php'; ?>
