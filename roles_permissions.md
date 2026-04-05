# Rôles et Permissions du Site

Ce document décrit les différents rôles disponibles sur le site de commande de restauration, ainsi que les actions que chaque rôle peut effectuer.

## 👥 1. Client (Rôle par défaut)
C'est le rôle attribué automatiquement à toute personne qui s'inscrit sur le site. Il permet d'utiliser le service en tant que consommateur.

**Pages spécifiques accessibles :**
- `catalogue.php` (Catalogue)
- `panier.php` (Panier)
- `validation.php` (Validation de commande)
- `paiement.php` (Paiement)
- `profil_client.php` (Mon profil)

**Actions possibles :**
- Parcourir le catalogue et voir le détail des menus et des plats.
- Ajouter des articles à son panier, modifier les quantités ou vider le panier.
- Commander en choisissant l'option (sur place, à emporter, livraison) et l'horaire (immédiat ou programmé).
- Payer sa commande de manière sécurisée via l'API CY Bank.
- Consulter ses informations personnelles sur son profil.
- Voir l'historique complet de ses commandes avec leurs statuts en temps réel.
- **Noter et commenter** (de 1 à 5 étoiles) une commande, uniquement lorsque celle-ci a atteint le statut "livrée".

---

## 👨‍🍳 2. Restaurateur
C'est le rôle destiné aux cuisiniers ou gérants du restaurant pour gérer le flux des commandes.

**Pages spécifiques accessibles :**
- `commandes_resto.php` (Gestion des commandes)
- `detail_commande.php` (Détail d'une commande)

**Actions possibles :**
- Voir la liste de toutes les commandes passées par les clients du site.
- Filtrer cette liste par statuts (ex: voir uniquement les commandes "À préparer" ou "En livraison").
- Accéder au détail complet d'une commande (ce que le client a commandé, ses coordonnées).
- **Changer le statut** d'une commande (ex: faire passer une commande de "Payée" à "En préparation", puis à "En livraison"). *(Boutons actuellement visuels)*.
- **Assigner un livreur** parmi la liste des livreurs disponibles pour les commandes de type "livraison". *(Boutons actuellement visuels)*.

---

## 🛵 3. Livreur
C'est le rôle destiné aux personnes chargées d'acheminer les commandes jusqu'au domicile des clients.

**Pages spécifiques accessibles :**
- `livraison.php` (Espace Livreur)

**Actions possibles :**
- Voir uniquement la commande qui lui a été **assignée** par le restaurateur.
- Accéder aux informations critiques pour la livraison : nom du client, numéro de téléphone, adresse exacte, et informations complémentaires (code interphone, bâtiment, etc.).
- Marquer la commande comme **"Livrée"** une fois la remise effectuée au client. *(Boutons actuellement visuels)*.
- Marquer la commande comme **"Abandonnée"** s'il est impossible de livrer le client (adresse introuvable, client injoignable, etc.). *(Boutons actuellement visuels)*.

---

## 👑 4. Administrateur (Admin)
C'est le rôle de supervision complète de la base de données utilisateurs.

**Pages spécifiques accessibles :**
- `admin.php` (Administration)

**Actions possibles :**
- Consulter le nombre total d'utilisateurs inscrits sur le site.
- Voir un tableau listant tous les utilisateurs avec leurs informations complètes (ID, nom, coordonnées, date de dernière connexion, etc.).
- **Modifier le rôle** de n'importe quel utilisateur via un menu déroulant pour lui donner accès aux outils correspondants (transformer un `client` en `restaurateur` ou en `livreur`).
- *Note de sécurité :* Un administrateur ne peut pas auto-rétrograder son propre rôle pour éviter de perdre l'accès à la page d'administration.
