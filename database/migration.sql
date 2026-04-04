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
(1,  'Salade César',          'Salade romaine, croûtons, parmesan, sauce César maison',           9.50,  'gluten, lait, oeuf',        '350 kcal, 18g protéines, 22g lipides'),
(2,  'Soupe à l''oignon',     'Soupe gratinée traditionnelle avec croûtons et gruyère',           8.00,  'gluten, lait',              '280 kcal, 10g protéines, 12g lipides'),
(3,  'Steak-frites',          'Steak de boeuf 200g, frites maison et sauce au poivre',           18.50,  'moutarde',                  '750 kcal, 42g protéines, 35g lipides'),
(4,  'Saumon grillé',         'Pavé de saumon grillé, riz basmati et légumes de saison',         17.00,  'poisson',                   '520 kcal, 38g protéines, 18g lipides'),
(5,  'Poulet rôti',           'Demi-poulet fermier rôti, pommes de terre grenaille',             15.00,  NULL,                        '620 kcal, 45g protéines, 25g lipides'),
(6,  'Risotto aux champignons','Risotto crémeux aux cèpes et parmesan',                          14.50,  'lait',                      '480 kcal, 12g protéines, 20g lipides'),
(7,  'Tarte Tatin',           'Tarte aux pommes caramélisées, servie tiède avec crème fraîche',  8.50,   'gluten, lait, oeuf',        '420 kcal, 5g protéines, 22g lipides'),
(8,  'Mousse au chocolat',    'Mousse au chocolat noir 70%, légère et onctueuse',                7.50,   'lait, oeuf, soja',          '380 kcal, 6g protéines, 24g lipides'),
(9,  'Crème brûlée',          'Crème brûlée à la vanille de Madagascar',                        8.00,   'lait, oeuf',                '350 kcal, 5g protéines, 20g lipides'),
(10, 'Bruschetta tomates',    'Pain grillé, tomates fraîches, basilic, huile d''olive',          7.00,   'gluten',                    '220 kcal, 6g protéines, 10g lipides');

-- Menus
INSERT INTO menus (id, name, description, total_price, min_people, time_slots) VALUES
(1, 'Menu Classique',    'Entrée + Plat + Dessert au choix',                           25.00, 1, '12h-14h, 19h-22h'),
(2, 'Menu Découverte',   'Une sélection de nos meilleurs plats pour les curieux',      30.00, 1, '19h-22h'),
(3, 'Menu Groupe',       'Formule conviviale pour les tablées, boissons incluses',     22.00, 4, '12h-14h, 19h-21h30');

-- Liaison menus <-> plats
-- Menu Classique : Salade César + Steak-frites + Mousse au chocolat
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (1, 1);
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (1, 3);
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (1, 8);

-- Menu Découverte : Bruschetta + Soupe à l'oignon + Saumon grillé + Risotto + Tarte Tatin + Crème brûlée
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (2, 10);
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (2, 2);
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (2, 4);
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (2, 6);
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (2, 7);
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (2, 9);

-- Menu Groupe : Salade César + Poulet rôti + Crème brûlée
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (3, 1);
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (3, 5);
INSERT INTO menu_dishes (menu_id, dish_id) VALUES (3, 9);

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