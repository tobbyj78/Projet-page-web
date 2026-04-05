CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    login TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'client',
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    nickname TEXT NOT NULL UNIQUE,
    phone TEXT NOT NULL,
    birthday TEXT NOT NULL,
    address TEXT NOT NULL,
    address_info TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login TEXT
);

-- Table des plats
CREATE TABLE IF NOT EXISTS dishes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    price REAL NOT NULL,
    allergens TEXT,
    nutritional_info TEXT
);

-- Table des menus
CREATE TABLE IF NOT EXISTS menus (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    total_price REAL NOT NULL,
    min_people INTEGER DEFAULT 1,
    time_slots TEXT
);

-- Table de liaison menus <-> plats
CREATE TABLE IF NOT EXISTS menu_dishes (
    menu_id INTEGER NOT NULL,
    dish_id INTEGER NOT NULL,
    PRIMARY KEY (menu_id, dish_id),
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE CASCADE
);

-- ══════════════════════════════════════════════
-- Données par défaut
-- ══════════════════════════════════════════════

-- Plats
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info) VALUES
(1,  'Burger Classique',      'Boeuf, salade, tomate, oignon, cheddar fondu',                     8.50,   'gluten, lait',              '550 kcal, 28g protéines, 30g lipides'),
(2,  'Pomme Rouge',           'Pomme croquante fraîche de saison',                                 1.50,   NULL,                        '85 kcal, 0g protéines, 0g lipides'),
(3,  'Pancake Sirop',         'Pancakes moelleux nappés de sirop d''érable',                       4.20,   'gluten, lait, oeuf',        '380 kcal, 8g protéines, 12g lipides'),
(4,  'Maki Saumon (x6)',      'Six makis au saumon frais et riz vinaigré',                         6.90,   'poisson, soja',             '280 kcal, 18g protéines, 6g lipides'),
(5,  'Gaufre Belge',          'Gaufre croustillante servie avec sucre glace',                      3.80,   'gluten, lait, oeuf',        '320 kcal, 5g protéines, 14g lipides'),
(6,  'Bouteille d''eau',      'Eau minérale naturelle 50cl',                                       2.00,   NULL,                        '0 kcal'),
(7,  'Le Maxi Burger',        'Double steak, bacon, double cheddar, sauce maison',                 12.99,  'gluten, lait, moutarde',    '920 kcal, 52g protéines, 55g lipides'),
(8,  'Pizza Chef',            'Tomate, mozzarella, jambon, champignons, olives, origan',           14.50,  'gluten, lait',              '680 kcal, 28g protéines, 22g lipides'),
(9,  'Frites dorées',         'Frites maison croustillantes et dorées à point',                    4.50,   NULL,                        '365 kcal, 4g protéines, 18g lipides'),
(10, 'Steak Grillé',          'Pièce de boeuf 250g grillée, accompagnée de légumes',              18.00,  'moutarde',                  '650 kcal, 48g protéines, 30g lipides'),
(11, 'Sashimi Saumon',        'Huit tranches fines de saumon frais',                               8.99,   'poisson, soja',             '220 kcal, 26g protéines, 8g lipides'),
(12, 'Nigiri Poulpe',         'Six nigiris au poulpe mariné',                                      7.50,   'poisson, soja, sésame',     '240 kcal, 20g protéines, 4g lipides'),
(13, 'Donut Givré',           'Donut moelleux avec glaçage au sucre',                              3.20,   'gluten, lait, oeuf',        '290 kcal, 4g protéines, 16g lipides'),
(14, 'Glace Sundae',          'Coupe glacée vanille, chocolat, chantilly, topping au choix',       5.00,   'lait, fruits à coque',      '340 kcal, 5g protéines, 18g lipides'),
(15, 'Cupcake Magique',       'Cupcake fourré crème vanille, décor coloré',                        4.00,   'gluten, lait, oeuf',        '310 kcal, 4g protéines, 15g lipides'),
(16, 'Croissant Doré',        'Croissant pur beurre feuilleté et doré',                            2.50,   'gluten, lait, oeuf',        '230 kcal, 4g protéines, 12g lipides'),
(17, 'Hot-Dog New-Yorkais',   'Saucisse de Francfort, moutarde, ketchup, oignons frits',          6.50,   'gluten, moutarde',          '480 kcal, 18g protéines, 24g lipides'),
(18, 'Cuisse de Poulet',      'Cuisse de poulet fermier rôtie, herbes de Provence',               5.50,   NULL,                        '420 kcal, 32g protéines, 20g lipides');

-- Menus
INSERT INTO menus (id, name, description, total_price, min_people, time_slots) VALUES
(1, 'Formule Burger',       'Burger Classique + Frites dorées + Bouteille d''eau',              13.50, 1, '11h30-14h30, 18h30-22h'),
(2, 'Menu Découverte Japon', 'Maki Saumon + Sashimi Saumon + Nigiri Poulpe',                    21.00, 1, '11h30-14h30, 18h30-22h'),
(3, 'Menu Gourmand',         'Steak Grillé + Pizza Chef + Glace Sundae',                        34.00, 2, '19h-22h');

-- Liaison menus <-> plats
-- Formule Burger : Burger Classique + Frites dorées + Bouteille d'eau
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (1, 1);
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (1, 9);
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (1, 6);

-- Menu Découverte Japon : Maki Saumon + Sashimi Saumon + Nigiri Poulpe
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (2, 4);
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (2, 11);
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (2, 12);

-- Menu Gourmand : Steak Grillé + Pizza Chef + Glace Sundae
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (3, 10);
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (3, 8);
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (3, 14);

-- Table des options de modifications
CREATE TABLE IF NOT EXISTS options (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
);

-- Options par défaut
INSERT INTO options (id, name) VALUES
(1,  'Sans sel'),
(2,  'Sans gluten'),
(3,  'Sans lactose'),
(4,  'Remplacer frites par salade'),
(5,  'Remplacer riz par légumes'),
(6,  'Sauce à part'),
(7,  'Cuisson saignant'),
(8,  'Cuisson à point'),
(9,  'Cuisson bien cuit'),
(10, 'Supplément fromage');

-- Table des commandes
CREATE TABLE IF NOT EXISTS orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    order_type TEXT NOT NULL CHECK(order_type IN ('sur_place', 'emporter', 'livraison')),
    delivery_address TEXT,
    status TEXT NOT NULL DEFAULT 'en_attente',
    scheduled_datetime TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des articles de commande
CREATE TABLE IF NOT EXISTS order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    item_id INTEGER NOT NULL,
    item_type TEXT NOT NULL CHECK(item_type IN ('menu', 'dish')),
    quantity INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Table des paiements
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    bank_details TEXT,
    transaction_date TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Colonne livreur assigné sur les commandes
ALTER TABLE orders ADD COLUMN delivery_person_id INTEGER REFERENCES users(id);

-- Table des notations
CREATE TABLE IF NOT EXISTS ratings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL UNIQUE,
    user_id INTEGER NOT NULL,
    rating INTEGER NOT NULL CHECK(rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);