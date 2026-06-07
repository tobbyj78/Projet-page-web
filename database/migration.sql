-- ═══════════════════════════════════════════════════════════
-- L'Éclipse — Schéma & données par défaut
-- ═══════════════════════════════════════════════════════════

PRAGMA foreign_keys = ON;

-- ══════════════════════════════════════════════════════════════
-- Utilisateurs
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS users (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    login        TEXT    NOT NULL UNIQUE,
    password     TEXT    NOT NULL,
    role         TEXT    NOT NULL DEFAULT 'client'
                         CHECK(role IN ('client','admin','restaurateur','livreur')),
    first_name   TEXT    NOT NULL,
    last_name    TEXT    NOT NULL,
    nickname     TEXT    NOT NULL UNIQUE,
    phone        TEXT    NOT NULL,
    birthday     TEXT    NOT NULL,
    address      TEXT    NOT NULL,
    address_info TEXT,
    blocked      INTEGER NOT NULL DEFAULT 0,
    created_at   TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login   TEXT
);

-- Utilisateurs de test (mots de passe = login)
INSERT INTO users (login, password, role, first_name, last_name, nickname, phone, birthday, address, address_info) VALUES
('admin1',    '$2y$12$Qy0ifJEA2vIj3AbfLl6ocedOGuod1GIN/krg5tXOJnts7/gLj9Tsq', 'admin',        'Éléonore',  'Dupuis',   'eleonore_admin', '0612345678', '1985-03-12', '15 rue de Rivoli, 75001 Paris',     'Code B2'),
('admin2',    '$2y$12$4RCo0wES8B93Dap9nQWQQuD/fE5lfQLjbzY2O/61lXydKjDCVZH5C', 'admin',        'Gabriel',   'Moreau',   'gabriel_admin',  '0623456789', '1990-07-25', '8 avenue Montaigne, 75008 Paris',  '3e étage'),
('resto1',    '$2y$12$xeIPyjiDBYOkxwKMuNWzJ.vy5GW..tSxYyUQvk0Ae33w4z6rEUDJ6', 'restaurateur', 'Antoine',   'Chevalier','antoine_resto',  '0634567890', '1982-11-08', '22 rue de la Paix, 75002 Paris',    NULL),
('livreur1',  '$2y$12$3ZhnDFjPR9pjvgNshEDyX.BNfSmJblS0QDmQf4tguZ/dg7mxJ.g2W', 'livreur',      'Karim',     'Benali',   'karim_livreur',  '0645678901', '1995-01-30', '45 boulevard Voltaire, 75011 Paris','Digicode 1789'),
('client1',   '$2y$12$0R3bSk0u0onk05wrsGFbFupV15LfkPZbWCLKKpFVRVByOOsjK1mX2', 'client',       'Camille',   'Laurent',  'camille_l',      '0656789012', '1998-06-15', '3 rue des Lilas, 75019 Paris',      'Bâtiment A'),
('client2',   '$2y$12$il3CVKhu.JsgcirYndp6seYoDKu6n/YofyZI01kDEzhuE4d/su9a6', 'client',       'Thomas',    'Petit',    'thomas_p',       '0667890123', '2000-09-22', '78 rue de Belleville, 75020 Paris', NULL),
('client3',   '$2y$12$JypZ6iUbL3wAdQMIY7wE8uoCIPCciLKiXL55TnqrMKgWY5njtc6aS', 'client',       'Inès',      'Roussel',  'ines_r',         '0678901234', '1997-04-03', '12 place de la Bastille, 75011 Paris','Interphone 4'),
('client4',   '$2y$12$lp9Js46luNVc8noKPHMlTeW.bbGEPsFJnAggbBTZQbtwzntqGMsvG', 'client',       'Lucas',     'Fournier', 'lucas_f',        '0689012345', '1993-12-17', '56 avenue des Champs-Élysées, 75008 Paris', NULL),
('client5',   '$2y$12$4ACW4AvjcLTi8VfSghKDFu.6KQHMLvYhl9chkql/D6769wy6lVj/2', 'client',       'Chloé',     'Girard',   'chloe_g',        '0690123456', '2001-08-07', '34 rue Mouffetard, 75005 Paris',    'Escalier C');

-- ══════════════════════════════════════════════════════════════
-- Services (ex : petit_dejeuner, dejeuner, diner, cave, epicerie)
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS services (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    name           TEXT    NOT NULL UNIQUE,
    label          TEXT    NOT NULL,
    hours          TEXT,
    title_html     TEXT    NOT NULL,
    image_url      TEXT    NOT NULL,
    image_alt      TEXT    NOT NULL,
    gradient       TEXT    NOT NULL DEFAULT 'rgba(22,20,18,.15),rgba(22,20,18,.55)',
    formulas_label TEXT,
    li_class       TEXT    NOT NULL DEFAULT 'nav-item',
    compact        INTEGER NOT NULL DEFAULT 0,
    display_order  INTEGER NOT NULL DEFAULT 0
);

INSERT INTO services (id, name, label, hours, title_html, image_url, image_alt, gradient, formulas_label, li_class, compact, display_order) VALUES
(1, 'petit_dejeuner', 'Petit-Déjeuner', '07h30 — 10h30',
    'Petit-Déjeuner<br><em>Gourmand</em>',
    'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=900&q=80',
    'Petit-déjeuner gourmand',
    'rgba(22,20,18,.15),rgba(22,20,18,.55)',
    'Les Formules', 'nav-item', 0, 1),

(2, 'dejeuner', 'Déjeuner', '12h30 — 15h00',
    'Déjeuner<br><em>Léger</em>',
    'https://images.unsplash.com/photo-1504754524776-8f4f37790ca0?auto=format&fit=crop&w=900&q=80',
    'Plat de saison',
    'rgba(22,20,18,.15),rgba(22,20,18,.55)',
    'Les Formules', 'nav-item', 0, 2),

(3, 'diner', 'Dîner', '19h30 — 23h00',
    '<em>Le Grand</em><br>Dîner',
    'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=900&q=80',
    'Filet de bœuf Rossini',
    'rgba(22,20,18,.15),rgba(22,20,18,.65)',
    'Les Menus Signature', 'nav-item', 0, 3),

(4, 'cave', 'Cave', NULL,
    'Cave &amp;<br><em>Spiritueux</em>',
    'https://images.unsplash.com/photo-1553361371-9b22f78e8b1d?auto=format&fit=crop&w=900&q=80',
    'Cave à vins',
    'rgba(22,20,18,.15),rgba(22,20,18,.65)',
    NULL, 'nav-item divider', 1, 4),

(5, 'epicerie', 'Épicerie', NULL,
    'Épicerie<br><em>fine</em>',
    'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80',
    'Produits d''exception',
    'rgba(22,20,18,.15),rgba(22,20,18,.6)',
    NULL, 'nav-item', 1, 5);

-- ══════════════════════════════════════════════════════════════
-- Catégories (liées aux services)
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS categories (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    name          TEXT    NOT NULL,
    label         TEXT    NOT NULL,
    service_id    INTEGER NOT NULL REFERENCES services(id) ON DELETE CASCADE,
    display_order INTEGER NOT NULL DEFAULT 0,
    UNIQUE(name, service_id)
);

INSERT INTO categories (id, name, label, service_id, display_order) VALUES
-- Petit-déjeuner (service_id=1)
( 1, 'viennoiseries',        'Viennoiseries &amp; Douceurs', 1, 1),
( 2, 'fruits',               'Fruits &amp; Céréales',        1, 2),
( 3, 'sale',                 'Salé d''exception',             1, 3),
-- Déjeuner (service_id=2)
( 4, 'entrees',              'Entrées &amp; Froid',           2, 1),
( 5, 'tartes',               'Tartes &amp; Végétal',          2, 2),
( 6, 'chauds',               'Plats chauds',                  2, 3),
-- Dîner (service_id=3)
( 7, 'entrees',              'Les Entrées',                   3, 1),
( 8, 'poissons',             'Poissons &amp; Crustacés',      3, 2),
( 9, 'viandes',              'Viandes d''exception',           3, 3),
(10, 'desserts',             'Fromages &amp; Desserts',        3, 4),
-- Cave (service_id=4)
(11, 'champagnes',           'Champagnes',                    4, 1),
(12, 'rouges',               'Vins Rouges',                   4, 2),
(13, 'blancs',               'Vins Blancs &amp; Sauternes',   4, 3),
(14, 'fortifies',            'Vins Fortifiés &amp; Doux',     4, 4),
(15, 'spiritueux',           'Spiritueux d''exception',        4, 5),
-- Épicerie (service_id=5)
(16, 'caviar',               'Caviar &amp; Fumaisons',         5, 1),
(17, 'truffes',              'Truffes &amp; Produits rares',   5, 2),
(18, 'condiments',           'Huiles &amp; Condiments',        5, 3),
(19, 'charcuterie_conserves','Charcuteries &amp; Conserves',   5, 4),
(20, 'douceurs',             'Douceurs &amp; Infusions',       5, 5);

-- ══════════════════════════════════════════════════════════════
-- Plats
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS dishes (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    name            TEXT    NOT NULL,
    description     TEXT,
    price           REAL    NOT NULL,
    allergens       TEXT,
    nutritional_info TEXT,
    image           TEXT,
    service_id      INTEGER NOT NULL REFERENCES services(id),
    category_id     INTEGER NOT NULL REFERENCES categories(id)
);

-- Petit-Déjeuner / Viennoiseries (service_id=1, category_id=1)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(1,  'Pain au chocolat aux amandes et praliné noisette',                         NULL,  7.50, 'gluten, lait, fruits à coque',       NULL, 1,  1),
(2,  'Croissant revisité à la crème diplomate et vanille de Madagascar',          NULL,  8.00, 'gluten, lait, oeuf',                 NULL, 1,  1),
(3,  'Gaufre liégeoise, compotée de cerises noires et chantilly mascarpone',      NULL, 12.00, 'gluten, lait, oeuf',                 NULL, 1,  1),
(4,  'Brioche perdue au caramel beurre salé et éclats de noix de pécan',         NULL, 11.00, 'gluten, lait, oeuf, fruits à coque', NULL, 1,  1),
(5,  'Pancakes moelleux, sirop d''érable infusé au romarin et myrtilles',        NULL, 13.00, 'gluten, lait, oeuf',                 NULL, 1,  1);

-- Petit-Déjeuner / Fruits (service_id=1, category_id=2)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(6,  'Verrine de chia au lait d''amande, mangue fraîche et citron vert',         NULL,  9.00, 'fruits à coque',                     NULL, 1,  2),
(7,  'Granola maison aux noisettes torréfiées et yaourt grec',                    NULL,  8.00, 'fruits à coque, lait',               NULL, 1,  2);

-- Petit-Déjeuner / Salé (service_id=1, category_id=3)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(8,  'Avocado toast à la grenade et feta AOP',                                    NULL, 14.00, 'gluten, lait',                       NULL, 1,  3),
(9,  'Œufs Bénédicte au bacon de dinde et sauce hollandaise yuzu',               NULL, 16.00, 'oeuf, gluten, lait',                 NULL, 1,  3),
(10, 'Œufs parfaits à la truffe noire sur pain de campagne toasté',              NULL, 22.00, 'oeuf, gluten',                       NULL, 1,  3),
(11, 'Saumon gravlax maison, blinis tièdes et crème crue à la ciboulette',       NULL, 19.00, 'poisson, gluten, lait',              NULL, 1,  3),
(12, 'Assiette de fromages affinés et confiture de figues',                       NULL, 15.00, 'lait',                               NULL, 1,  3);

-- Déjeuner / Entrées (service_id=2, category_id=4)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(13, 'Velouté glacé de petits pois à la menthe fraîche',                          NULL, 14.00, 'lait',                               NULL, 2,  4),
(14, 'Gaspacho de tomates anciennes, éclats de pistache et basilic',              NULL, 13.00, 'fruits à coque',                     NULL, 2,  4),
(15, 'Ceviche de daurade au lait de tigre et agrumes',                            NULL, 18.00, 'poisson',                            NULL, 2,  4),
(16, 'Carpaccio de bœuf, copeaux de parmesan et roquette',                       NULL, 16.00, 'lait, moutarde',                     NULL, 2,  4);

-- Déjeuner / Tartes & Végétal (service_id=2, category_id=5)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(17, 'Quinoa aux légumes croquants et vinaigrette fruit de la passion',           NULL, 17.00, NULL,                                 NULL, 2,  5),
(18, 'Tarte fine à la courgette violon et moutarde à l''ancienne',               NULL, 15.00, 'gluten, lait, moutarde',             NULL, 2,  5),
(19, 'Mille-feuille de légumes du soleil, coulis de poivron rouge',               NULL, 18.00, NULL,                                 NULL, 2,  5),
(20, 'Tartelette salée aux champignons sauvages et comté affiné',                 NULL, 16.00, 'gluten, lait, oeuf',                 NULL, 2,  5);

-- Déjeuner / Plats chauds (service_id=2, category_id=6)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(21, 'Risotto d''épeautre aux asperges vertes et citron confit',                 NULL, 24.00, 'lait',                               NULL, 2,  6),
(22, 'Suprême de volaille poché, julienne de légumes d''été',                    NULL, 26.00, NULL,                                 NULL, 2,  6),
(23, 'Salade niçoise revisitée au thon mi-cuit',                                  NULL, 22.00, 'poisson',                            NULL, 2,  6),
(24, 'Filet de rouget poêlé, vierge de légumes au fenouil',                      NULL, 28.00, 'poisson',                            NULL, 2,  6);

-- Dîner / Entrées (service_id=3, category_id=7)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(25, 'Foie gras de canard mi-cuit, chutney de figues au porto',                   NULL, 28.00, NULL,                                 NULL, 3,  7),
(26, 'Raviole de langoustine, bouillon léger aux algues kombu',                   NULL, 32.00, 'crustacés, gluten, lait',            NULL, 3,  7),
(27, 'Noix de Saint-Jacques rôties, purée de panais à la vanille',               NULL, 34.00, 'mollusques, lait',                   NULL, 3,  7);

-- Dîner / Poissons (service_id=3, category_id=8)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(28, 'Turbot sauvage rôti sur l''arête, beurre blanc au caviar',                 NULL, 58.00, 'poisson, lait',                      NULL, 3,  8),
(29, 'Homard bleu en deux cuissons, déclinaison de carottes fanes',               NULL, 72.00, 'crustacés, lait',                    NULL, 3,  8);

-- Dîner / Viandes (service_id=3, category_id=9)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(30, 'Carré d''agneau en croûte d''herbes, mousseline d''artichaut',            NULL, 48.00, 'lait',                               NULL, 3,  9),
(31, 'Pigeonneau rôti au sautoir, petits pois à la française',                    NULL, 44.00, NULL,                                 NULL, 3,  9),
(32, 'Ris de veau croustillant, jus corsé et morilles fraîches',                 NULL, 52.00, 'lait',                               NULL, 3,  9),
(33, 'Filet de bœuf Rossini, pommes macaire et jus truffé',                      NULL, 68.00, NULL,                                 NULL, 3,  9);

-- Dîner / Fromages & Desserts (service_id=3, category_id=10)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(34, 'Chariot de fromages affinés par notre maître fromager',                     NULL, 22.00, 'lait',                               NULL, 3, 10),
(35, 'Soufflé chaud au Grand Marnier, sorbet orange sanguine',                    NULL, 18.00, 'oeuf, lait, gluten',                 NULL, 3, 10),
(36, 'Sphère en chocolat noir intense, cœur coulant praliné',                    NULL, 16.00, 'lait, soja, fruits à coque',         NULL, 3, 10);

-- Cave / Champagnes (service_id=4, category_id=11)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(37, 'Grandes Maisons',            'Sélection des maisons champenoises emblématiques',   85.00, 'sulfites', NULL, 4, 11),
(38, 'Cuvées Millésimées',         'Le meilleur des années d''exception',               120.00, 'sulfites', NULL, 4, 11),
(39, 'Rosés de Prestige',          'Champagnes rosés des grandes signatures',            95.00, 'sulfites', NULL, 4, 11),
(40, 'Blanc de Blancs',            'Pureté et minéralité, 100 % Chardonnay',            110.00, 'sulfites', NULL, 4, 11);

-- Cave / Vins Rouges (service_id=4, category_id=12)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(41, 'Bordeaux & Grands Crus Classés', 'Les plus grandes appellations bordelaises',    150.00, 'sulfites', NULL, 4, 12),
(42, 'Bourgogne',                  'Pinot noir en terres bourguignonnes',               130.00, 'sulfites', NULL, 4, 12),
(43, 'Vallée du Rhône',            'Grenache, Syrah et assemblages du sud',              75.00, 'sulfites', NULL, 4, 12),
(44, 'Vignobles Italiens',         'Barolo, Amarone et cépages transalpins',             65.00, 'sulfites', NULL, 4, 12),
(45, 'Nouveau Monde',              'Sélection Argentine, Chili, Afrique du Sud',         55.00, 'sulfites', NULL, 4, 12);

-- Cave / Vins Blancs & Sauternes (service_id=4, category_id=13)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(46, 'Bourgogne Blanc',            'Chardonnay d''exception en Côte d''Or',              90.00, 'sulfites', NULL, 4, 13),
(47, 'Loire & Alsace',             'Muscadet, Riesling, Gewurztraminer',                 60.00, 'sulfites', NULL, 4, 13),
(49, 'Sauternes & Vendanges Tardives', 'Liquoreux de grande garde',                    110.00, 'sulfites', NULL, 4, 13);

-- Cave / Vins Fortifiés & Doux (service_id=4, category_id=14)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(48, 'Vins Doux Naturels',         'Muscat, Banyuls et autres trésors doux',             45.00, 'sulfites', NULL, 4, 14),
(50, 'Portos millésimés',          'Vintage Port des grandes maisons',                   80.00, 'sulfites', NULL, 4, 14);

-- Cave / Spiritueux (service_id=4, category_id=15)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(51, 'Cognac & Armagnac',          'Eaux-de-vie de raisin françaises XO et hors d''âge', 25.00, NULL, NULL, 4, 15),
(52, 'Whiskys rares et single malts', 'Distilleries d''Écosse, d''Irlande et du Japon', 35.00, NULL, NULL, 4, 15),
(53, 'Rhums vieux',                'Martinique, Barbade, Jamaïque en vieillissement',    20.00, NULL, NULL, 4, 15),
(54, 'Eaux-de-vie de la Maison',   'Créations exclusives de notre cave',                 18.00, NULL, NULL, 4, 15);

-- Épicerie / Caviar & Fumaisons (service_id=5, category_id=16)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(55, 'Caviar Osciètre et Baeri',   'Par portion de 30 g',                               85.00, 'poisson',  NULL, 5, 16),
(56, 'Saumon fumé sauvage',        'Élevé en mer, fumé à froid — par 100 g',            32.00, 'poisson',  NULL, 5, 16),
(57, 'Truites fumées à la ficelle','Fumage artisanal, la pièce',                         22.00, 'poisson',  NULL, 5, 16),
(58, 'Œufs de hareng et poutargue','Condiments iodés de grande pêche',                  28.00, 'poisson',  NULL, 5, 16);

-- Épicerie / Truffes & Produits rares (service_id=5, category_id=17)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(59, 'Truffes noires fraîches (en saison)', 'Tuber melanosporum — par 10 g',           180.00, NULL,       NULL, 5, 17),
(60, 'Huile de truffe blanche',    'Infusée à froid, le flacon 100 ml',                  45.00, NULL,       NULL, 5, 17),
(61, 'Beurre truffé',              'Beurre d''isigny AOP et copeaux de truffe',          28.00, 'lait',     NULL, 5, 17),
(62, 'Brisures et jus de truffe',  'Concentré de truffe noire, le bocal 100 g',          35.00, NULL,       NULL, 5, 17);

-- Épicerie / Huiles & Condiments (service_id=5, category_id=18)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(63, 'Huiles d''olive d''exception','Première pression à froid, le flacon 500 ml',      22.00, NULL,       NULL, 5, 18),
(64, 'Vinaigres balsamiques affinés','Modène DOP, vieilli 12 ans minimum',               18.00, 'sulfites', NULL, 5, 18),
(65, 'Moutardes, sels et poivres rares', 'Sélection de condiments d''exception',         15.00, 'moutarde', NULL, 5, 18);

-- Épicerie / Charcuteries & Conserves (service_id=5, category_id=19)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(66, 'Jambons et charcuteries affinées', 'Jambon ibérique Pata Negra et spécialités',   38.00, NULL,       NULL, 5, 19),
(67, 'Foies gras et conserves fines', 'Foie gras d''oie et de canard en bocal',          55.00, NULL,       NULL, 5, 19);

-- Épicerie / Douceurs & Infusions (service_id=5, category_id=20)
INSERT INTO dishes (id, name, description, price, allergens, nutritional_info, service_id, category_id) VALUES
(68, 'Confitures et marmelades artisanales', 'Petit pot 200 g, recettes de la Maison',  12.00, NULL,       NULL, 5, 20),
(69, 'Miels de terroir',           'Acacia, châtaignier, lavande — le pot 250 g',        16.00, NULL,       NULL, 5, 20),
(70, 'Chocolats et pralinés de la Maison', 'Boîte de 12 pièces',                         24.00, 'lait, soja, fruits à coque', NULL, 5, 20),
(71, 'Thés d''exception',          'Darjeeling, oolong et thés rares — la boîte',        18.00, NULL,       NULL, 5, 20),
(72, 'Cafés de grands crus',       'Éthiopie, Jamaïque Blue Mountain — la boîte',        22.00, NULL,       NULL, 5, 20);

-- ══════════════════════════════════════════════════════════════
-- Formules (menus)
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS menus (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT    NOT NULL,
    description TEXT,
    total_price REAL    NOT NULL,
    min_people  INTEGER DEFAULT 1,
    time_slots  TEXT,
    image       TEXT,
    service_id  INTEGER NOT NULL REFERENCES services(id)
);

INSERT INTO menus (id, name, description, total_price, min_people, time_slots, service_id) VALUES
(1, 'L''Éveil Sucré',        'Viennoiseries & douceurs du matin',         22.00, 1, '07h30-10h30', 1),
(2, 'Le Matin Salé-Chic',    'L''audace du salé dès l''aube',             38.00, 1, '07h30-10h30', 1),
(3, 'L''Équilibre Gourmand', 'Sucré, salé & fruits de saison',            30.00, 1, '07h30-10h30', 1),
(4, 'Fraîcheur Végétale',    'Jardin du potager & produits du jour',      38.00, 1, '12h30-15h00', 2),
(5, 'L''Échappée Marine',    'Poissons du littoral & végétal frais',      48.00, 1, '12h30-15h00', 2),
(6, 'Terre & Tradition',     'Une cuisine de caractère',                  52.00, 1, '12h30-15h00', 2),
(7, 'Prestige Océan',        'Grands crus iodés en cinq actes',          175.00, 1, '19h30-23h00', 3),
(8, 'Signature du Chef',     'Carte blanche & accords sommelier',        185.00, 1, '19h30-23h00', 3),
(9, 'Terre d''Exception',    'Hommage aux produits de nos terroirs',     155.00, 1, '19h30-23h00', 3);

-- ══════════════════════════════════════════════════════════════
-- Liaison menus <-> plats
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS menu_dishes (
    menu_id INTEGER NOT NULL,
    dish_id INTEGER NOT NULL,
    PRIMARY KEY (menu_id, dish_id),
    FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE,
    FOREIGN KEY (dish_id) REFERENCES dishes(id) ON DELETE CASCADE
);

-- L'Éveil Sucré : Pain au chocolat (1) + Gaufre liégeoise (3) + Verrine de chia (6)
INSERT INTO menu_dishes VALUES (1, 1), (1, 3), (1, 6);
-- Le Matin Salé-Chic : Avocado toast (8) + Œufs Bénédicte (9) + Saumon gravlax (11)
INSERT INTO menu_dishes VALUES (2, 8), (2, 9), (2, 11);
-- L'Équilibre Gourmand : Pancakes (5) + Granola (7) + Assiette de fromages (12)
INSERT INTO menu_dishes VALUES (3, 5), (3, 7), (3, 12);
-- Fraîcheur Végétale : Gaspacho (14) + Quinoa (17) + Tarte fine courgette (18)
INSERT INTO menu_dishes VALUES (4, 14), (4, 17), (4, 18);
-- L'Échappée Marine : Velouté glacé (13) + Ceviche daurade (15) + Filet de rouget (24)
INSERT INTO menu_dishes VALUES (5, 13), (5, 15), (5, 24);
-- Terre & Tradition : Carpaccio bœuf (16) + Tartelette champignons (20) + Suprême volaille (22)
INSERT INTO menu_dishes VALUES (6, 16), (6, 20), (6, 22);
-- Prestige Océan : Raviole langoustine (26) + Saint-Jacques (27) + Turbot (28) + Homard (29) + Soufflé (35)
INSERT INTO menu_dishes VALUES (7, 26), (7, 27), (7, 28), (7, 29), (7, 35);
-- Signature du Chef : Foie gras (25) + Saint-Jacques (27) + Filet bœuf Rossini (33) + Sphère chocolat (36)
INSERT INTO menu_dishes VALUES (8, 25), (8, 27), (8, 33), (8, 36);
-- Terre d'Exception : Foie gras (25) + Carré d'agneau (30) + Ris de veau (32) + Chariot fromages (34) + Soufflé (35)
INSERT INTO menu_dishes VALUES (9, 25), (9, 30), (9, 32), (9, 34), (9, 35);

-- ══════════════════════════════════════════════════════════════
-- Options de modification
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS options (
    id   INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL
);

INSERT INTO options (id, name) VALUES
(1,  'Sans sel'),
(2,  'Sans gluten'),
(3,  'Sans lactose'),
(4,  'Sauce à part'),
(5,  'Cuisson saignant'),
(6,  'Cuisson à point'),
(7,  'Cuisson bien cuit'),
(8,  'Sans alcool (préparations)'),
(9,  'Allergie aux fruits à coque'),
(10, 'Accompagnement végétal');

-- ══════════════════════════════════════════════════════════════
-- Commandes, paiements, notations
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS orders (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id            INTEGER NOT NULL,
    order_type         TEXT    NOT NULL CHECK(order_type IN ('sur_place', 'emporter', 'livraison')),
    delivery_address   TEXT,
    status             TEXT    NOT NULL DEFAULT 'en_attente'
                               CHECK(status IN ('en_attente','payee','refusee','en_preparation','prete','en_livraison','livree','abandonnee','en_attente_livreur')),
    scheduled_datetime TEXT,
    created_at         TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    delivery_person_id INTEGER REFERENCES users(id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_items (
    id        INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id  INTEGER NOT NULL,
    item_id   INTEGER NOT NULL,
    item_type TEXT    NOT NULL CHECK(item_type IN ('menu', 'dish')),
    quantity  INTEGER NOT NULL DEFAULT 1,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payments (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id         INTEGER NOT NULL,
    bank_details     TEXT,
    transaction_date TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ratings (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id   INTEGER NOT NULL UNIQUE,
    user_id    INTEGER NOT NULL,
    rating     INTEGER NOT NULL CHECK(rating BETWEEN 1 AND 5),
    comment    TEXT,
    created_at TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
);

-- ══════════════════════════════════════════════════════════════
-- Indexes
-- ══════════════════════════════════════════════════════════════

CREATE INDEX idx_orders_user_id        ON orders(user_id);
CREATE INDEX idx_orders_status         ON orders(status);
CREATE INDEX idx_order_items_order_id  ON order_items(order_id);
CREATE INDEX idx_ratings_order_id      ON ratings(order_id);
CREATE INDEX idx_dishes_service_id     ON dishes(service_id);
CREATE INDEX idx_dishes_category_id    ON dishes(category_id);
