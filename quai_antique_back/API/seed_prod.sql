-- Données de départ pour la base de production (à exécuter UNE SEULE FOIS, sur une base vide).
-- Reprend le contenu actuel du site : restaurant, galerie, catégories, plats et formules de la carte.
-- Exemple : mysql -h mysql-<compte>.alwaysdata.net -u <utilisateur> -p <base> < seed_prod.sql
SET NAMES utf8mb4;
SET @now = NOW();

INSERT INTO restaurant (name, description, am_opening_time, pm_opening_time, max_guest, created_at)
SELECT 'Quai Antique', 'Restaurant du chef Arnaud Michant : cuisine de saison, poissons de nos pêcheurs et produits de la région.', '["12:00","14:00"]', '["18:00","23:00"]', 40, @now FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM restaurant);
SET @restaurant = (SELECT id FROM restaurant ORDER BY id LIMIT 1);

INSERT INTO picture (restaurant_id, title, slug, created_at) VALUES
(@restaurant, 'Nos saumons', 'saumon.png', @now),
(@restaurant, 'Nos plats', 'plats.png', @now),
(@restaurant, 'Notre fondant au chocolat', 'fondant.png', @now),
(@restaurant, 'La salle le jour', 'salle-jour.png', @now),
(@restaurant, 'La salle le soir', 'salle-nuit.png', @now);

INSERT INTO category (title, created_at) VALUES ('Entrées', @now);
SET @c_entrees = LAST_INSERT_ID();
INSERT INTO category (title, created_at) VALUES ('Plats', @now);
SET @c_plats = LAST_INSERT_ID();
INSERT INTO category (title, created_at) VALUES ('Desserts', @now);
SET @c_desserts = LAST_INSERT_ID();
INSERT INTO category (title, created_at) VALUES ('Boissons', @now);
SET @c_boissons = LAST_INSERT_ID();

INSERT INTO food (title, description, price, created_at) VALUES ('Velouté de saison', 'Légumes du marché, crème fraîche et éclats de noisettes torréfiées', 9, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_entrees);
INSERT INTO food (title, description, price, created_at) VALUES ('Tartare de saumon fumé maison', 'Saumon frais de Chambéry, aneth, citron vert et pain toasté', 13, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_entrees);
INSERT INTO food (title, description, price, created_at) VALUES ('Terrine du chef', 'Terrine de campagne, chutney d''oignons rouges et pain brioché', 11, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_entrees);
INSERT INTO food (title, description, price, created_at) VALUES ('Salade Quai Antique', 'Mesclun, magret fumé, copeaux de parmesan et vinaigrette au miel', 12, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_entrees);

INSERT INTO food (title, description, price, created_at) VALUES ('Pavé de saumon rôti', 'Saumon de nos pêcheurs, purée de panais et beurre blanc citronné', 24, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_plats);
INSERT INTO food (title, description, price, created_at) VALUES ('Suprême de volaille fermière', 'Jus corsé au thym, gratin dauphinois et légumes de saison', 22, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_plats);
INSERT INTO food (title, description, price, created_at) VALUES ('Filet de bœuf sauce au poivre', 'Frites maison et sauce au poivre concassé', 28, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_plats);
INSERT INTO food (title, description, price, created_at) VALUES ('Risotto crémeux aux champignons', 'Champignons de saison, parmesan affiné et huile de truffe', 19, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_plats);

INSERT INTO food (title, description, price, created_at) VALUES ('Fondant au chocolat', 'Cœur coulant, glace vanille de Madagascar', 10, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_desserts);
INSERT INTO food (title, description, price, created_at) VALUES ('Tarte fine aux pommes', 'Pommes caramélisées et sorbet poire', 9, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_desserts);
INSERT INTO food (title, description, price, created_at) VALUES ('Crème brûlée à la vanille', 'Vanille bourbon et sucre caramélisé', 8, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_desserts);
INSERT INTO food (title, description, price, created_at) VALUES ('Assiette de fromages affinés', 'Sélection de fromages de la région, pain aux noix', 11, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_desserts);

INSERT INTO food (title, description, price, created_at) VALUES ('Verre de vin rouge (Savoie)', '', 6, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_boissons);
INSERT INTO food (title, description, price, created_at) VALUES ('Verre de vin blanc (Chignin)', '', 6, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_boissons);
INSERT INTO food (title, description, price, created_at) VALUES ('Bouteille de vin rosé de Provence', '', 28, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_boissons);
INSERT INTO food (title, description, price, created_at) VALUES ('Eau minérale (50 cl)', '', 4, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_boissons);
INSERT INTO food (title, description, price, created_at) VALUES ('Soda / Jus de fruits', '', 5, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_boissons);
INSERT INTO food (title, description, price, created_at) VALUES ('Café gourmand', '', 7, @now);
INSERT INTO food_category (food_id, category_id) VALUES (LAST_INSERT_ID(), @c_boissons);

INSERT INTO menu (restaurant_id, title, description, price, created_at) VALUES (@restaurant, 'Formule Midi', 'Entrée + Plat ou Plat + Dessert, au choix parmi une sélection dédiée.', 22, @now);
SET @m = LAST_INSERT_ID();
INSERT INTO menu_category (menu_id, category_id) VALUES (@m, @c_entrees), (@m, @c_plats), (@m, @c_desserts);
INSERT INTO menu (restaurant_id, title, description, price, created_at) VALUES (@restaurant, 'Formule Découverte', 'Entrée + Plat + Dessert, parmi l''ensemble de notre carte.', 34, @now);
SET @m = LAST_INSERT_ID();
INSERT INTO menu_category (menu_id, category_id) VALUES (@m, @c_entrees), (@m, @c_plats), (@m, @c_desserts);
INSERT INTO menu (restaurant_id, title, description, price, created_at) VALUES (@restaurant, 'Formule Prestige', 'Entrée + Plat + Dessert + accord mets-vins choisi par notre sommelier.', 49, @now);
SET @m = LAST_INSERT_ID();
INSERT INTO menu_category (menu_id, category_id) VALUES (@m, @c_entrees), (@m, @c_plats), (@m, @c_desserts);

