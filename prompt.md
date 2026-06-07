# PROMPT — Refonte du workflow commandes de L'Éclipse

Tu es un développeur full-stack PHP. Tu travailles sur le site **L'Éclipse**, un site de restauration multi-utilisateurs (clients, restaurateurs, livreurs, admins). Le workflow des commandes est **cassé** et doit être intégralement revu pour coller au cahier des charges ci-dessous.

Lis **tout** ce document avant de coder. Si tu as un doute, **demande**.

---

## 1. Les 4 rôles (rappel)

| Rôle | Terminal | Pages clés | Ce qu'il peut faire |
|------|----------|------------|---------------------|
| **Client** | Web desktop/mobile | `catalogue.php`, `panier.php`, `validation.php`, `paiement.php`, `profil_client.php`, `notation.php` | Commander (sur place / à emporter / livraison), payer, voir son historique, noter une commande livrée |
| **Restaurateur** | Tablette tactile (grand écran, tactile) | `commandes_resto.php`, `detail_commande.php` | Voir les commandes, changer leur statut, assigner un livreur aux commandes livraison |
| **Livreur** | Smartphone (petit écran, connexion parfois lente, utilisation avec des gants = gros boutons) | `livraison.php` | Voir les commandes dispo, prendre une commande, la marquer livrée ou abandonnée, ouvrir l'adresse dans Maps/Waze |
| **Admin** | Ordinateur | `admin.php`, `index_admin.php`, accès à toutes les pages | Gérer les utilisateurs (changer rôles, bloquer/débloquer), voir tout le site |

---

## 2. Les 3 types de commande

Un client peut commander de **trois façons** :

1. **Sur place** (`sur_place`) — le client mange au restaurant. Pas de livraison, pas de livreur.
2. **À emporter** (`emporter`) — le client vient chercher sa commande. Pas de livreur.
3. **Livraison** (`livraison`) — un livreur apporte la commande à l'adresse indiquée.

Pour les 3 types, le client peut choisir :
- **Préparation immédiate** : dès que payé, le resto peut commencer.
- **Préparation programmée** (`scheduled_datetime`) : le client choisit une date/heure future. La commande ne doit **pas** être préparée avant.

---

## 3. LE WORKFLOW COMPLET (à implémenter)

Voici le **graphe d'état** que tu dois coder. Chaque transition est numérotée et décrite (qui la fait, quand, depuis quelle page).

### 3.1 Schéma global

```
                      ┌─────────────┐
                      │  en_attente  │  ← commande créée (validation.php), pas encore payée
                      └──────┬──────┘
                             │ (a) paiement accepté → CY Bank
                             ▼
                      ┌─────────────┐
                      │    payee     │  ← paiement OK, en attente de prise en charge resto
                      └──────┬──────┘
                             │ (b) restaurateur clique "Préparer"
                             ▼
                      ┌─────────────┐
                      │en_preparation│  ← le resto cuisine
                      └──────┬──────┘
                             │ (c) restaurateur clique "Prête"
                             ▼
                      ┌─────────────┐
                      │    prete     │  ← commande prête, à servir / récupérer / livrer
                      └──┬───────┬──┘
                         │       │
            ┌────────────┘       └────────────┐
            │  sur_place / emporter           │  livraison
            ▼                                 ▼
    (d) resto clique "Servie"      ┌───────────────────┐
            │                      │ en_attente_livreur │ ← besoin d'un livreur
            ▼                      └────────┬──────────┘
      ┌─────────┐                          │ (e) resto assigne un livreur
      │  livree  │                          ▼
      └─────────┘                 ┌─────────────┐
                                  │ en_livraison │ ← le livreur a la commande
                                  └──────┬──────┘
                                         │ (f1) livreur clique "Livrée"
                                         │      ┌─────────┐
                                         ├─────►│  livree  │
                                         │      └─────────┘
                                         │ (f2) livreur clique "Abandonnée"
                                         │      ┌────────────┐
                                         └─────►│ abandonnee │
                                                └────────────┘

Chemin de refus (paiement refusé par CY Bank) :
                      ┌─────────────┐
                      │  en_attente  │
                      └──────┬──────┘
                             │ (x) paiement refusé → CY Bank
                             ▼
                      ┌─────────────┐
                      │   refusee    │  ← terminal
                      └─────────────┘
```

### 3.2 Détail de chaque transition

| # | Qui | Depuis | Vers | Condition | Depuis quelle page |
|---|-----|--------|------|-----------|-------------------|
| **(a)** | Système (retour CY Bank) | `en_attente` | `payee` | Paiement accepté par CY Bank (hash vérifié) | `paiement.php` (retour GET) |
| **(b)** | **Restaurateur** | `payee` | `en_preparation` | Le resto prend en charge la commande. **Si la commande est programmée, il ne peut PAS le faire avant la date prévue.** | `detail_commande.php` (bouton "Préparer") |
| **(c)** | **Restaurateur** | `en_preparation` | `prete` | Cuisine terminée, commande prête | `detail_commande.php` (bouton "Marquer comme prête") |
| **(d)** | **Restaurateur** | `prete` | `livree` | **Uniquement pour `sur_place` et `emporter`.** Le resto sert/remet la commande au client. | `detail_commande.php` (bouton "Servie" / "Remise") |
| **(e)** | **Restaurateur** | `prete` → `en_attente_livreur` | `en_livraison` | **Uniquement pour `livraison`.** Le resto doit d'abord mettre en `en_attente_livreur` (transition auto quand `prete` + `livraison`), puis assigner un livreur → `en_livraison`. | `detail_commande.php` (formulaire assignation livreur) |
| **(f1)** | **Livreur** | `en_livraison` | `livree` | Livraison effectuée. Le livreur a été assigné à cette commande. | `livraison.php` (bouton "Livrée") |
| **(f2)** | **Livreur** | `en_livraison` | `abandonnee` | Livraison impossible (adresse introuvable, client absent…). Le livreur a été assigné à cette commande. | `livraison.php` (bouton "Abandonner") |
| **(x)** | Système (retour CY Bank) | `en_attente` | `refusee` | Paiement refusé ou hash invalide | `paiement.php` (retour GET) |

---

## 4. STATUTS — liste exhaustive

| Statut | Signification | Qui le met | Terminal ? |
|--------|--------------|------------|------------|
| `en_attente` | Commande créée, pas encore payée | Système (validation.php) | Non |
| `payee` | Payée, en attente de prise en charge resto | Système (retour CY Bank) | Non |
| `en_preparation` | En cuisine | Restaurateur | Non |
| `prete` | Cuisinée, prête à servir/livrer | Restaurateur | Non |
| `en_attente_livreur` | Besoin d'un livreur (livraison uniquement) | Système (auto après `prete` si `order_type=livraison`) | Non |
| `en_livraison` | En cours de livraison | Restaurateur (assignation) | Non |
| `livree` | Livrée / servie / remise | Restaurateur (sur place/emporter) **OU** Livreur (livraison) | **Oui** |
| `abandonnee` | Livraison abandonnée | Livreur | **Oui** |
| `refusee` | Paiement refusé | Système (retour CY Bank) | **Oui** |

---

## 5. CE QUI EST DÉJÀ FONCTIONNEL (ne pas casser)

✅ `validation.php` — Création commande en `en_attente`, insertion `order_items`, redirection `paiement.php`  
✅ `paiement.php` — Envoi vers CY Bank, retour GET, vérification hash, création `payments`  
✅ `livraison.php` — Le livreur peut **Prendre** (en_attente_livreur → en_livraison), **Livrée**, **Abandonner**  
✅ `detail_commande.php` — Assignation livreur (`en_attente_livreur` → `en_livraison`)  
✅ `profil_client.php` — Historique, notation sur commande livrée  
✅ `notation.php` — Page de notation (1-5 étoiles + commentaire)  
✅ `admin.php` — Changement de rôle utilisateur  
✅ Modèles : `models/orders.php`, `models/users.php`  
✅ Panier, catalogue, inscription, connexion  

---

## 6. CE QU'IL FAUT CORRIGER / IMPLÉMENTER

### 🔴 CRITIQUE — À faire en priorité

#### 6.1 Le retour CY Bank met le mauvais statut
**Fichier : `paiement.php`** (lignes ~48-60)

Actuellement quand le paiement est accepté :
- Si `order_type = livraison` → `en_attente_livreur` (**FAUX**)
- Sinon → `payee` (**OK pour sur_place/emporter, mais incomplet**)

**Ce qu'il faut** : TOUJOURS mettre `payee` après un paiement accepté, quel que soit le type.

```php
// CORRECTION : toujours payee après paiement accepté
$stmt = $pdo->prepare("UPDATE orders SET status = 'payee' WHERE id = :id");
```

Ne plus faire la distinction livraison / pas livraison ici.

#### 6.2 Le restaurateur ne peut PAS changer le statut d'une commande
**Fichier : `detail_commande.php`** — **Fonctionnalité manquante**

Il faut ajouter des boutons visibles par le restaurateur :

| Bouton | Visible quand | Transition |
|--------|--------------|------------|
| "Préparer" | `status = payee` ET (pas de scheduled_datetime OU scheduled_datetime ≤ maintenant) | `payee` → `en_preparation` |
| "Marquer comme prête" | `status = en_preparation` | `en_preparation` → `prete` |
| "Servie" / "Remise au client" | `status = prete` ET `order_type IN (sur_place, emporter)` | `prete` → `livree` |

Pour `livraison` : quand le resto marque `prete`, la commande passe automatiquement en `en_attente_livreur` (ou tu peux faire deux boutons séparés si tu préfères, mais l'auto-transition est plus logique).

**Contrainte commandes programmées** : Si `scheduled_datetime` est dans le futur, le bouton "Préparer" doit être **désactivé ou masqué** avec un message "Préparation prévue le [date]".

#### 6.3 La transition `prete` → `en_attente_livreur` pour les livraisons n'existe pas
**Fichier : `detail_commande.php`**

Quand le resto clique "Marquer comme prête" sur une commande `livraison` :
- Si `order_type = livraison` → `prete` → `en_attente_livreur` (transition auto)
- Si `order_type IN (sur_place, emporter)` → `prete` (reste en prete, le resto peut ensuite cliquer "Servie")

Le code actuel de `detail_commande.php` **n'a aucun bouton de changement de statut** (à part l'assignation livreur). Il faut tout ajouter.

#### 6.4 Assignation livreur : condition à corriger
**Fichier : `detail_commande.php`**

Actuellement l'assignation est possible quand `status = en_attente_livreur`. C'est correct. Mais il faut vérifier que :
- Le livreur assigné n'est pas déjà en train de livrer (`status = en_livraison`)
- La transition assignation met `status = en_livraison` ET `delivery_person_id = livreur_id`

Ça, c'est déjà OK dans le code existant (le check `stmtBusy` existe). ✅

#### 6.5 Le livreur peut "Prendre" une commande en `en_attente_livreur`
**Fichier : `livraison.php`**

Actuellement, le bouton "Prendre" fait `en_attente_livreur` → `en_livraison` ET `delivery_person_id = livreur_id`. 

**Problème** : Normalement c'est le **restaurateur** qui assigne le livreur, pas le livreur lui-même. Mais le prof dit aussi que le livreur "prend une commande". Les deux interprétations sont possibles.

**Décision à prendre** : Laisse le livreur pouvoir "Prendre" directement (c'est ce que le code actuel fait, et c'est plus simple pour le workflow). Mais garde aussi la possibilité que le resto assigne (déjà existant). Les deux marchent.

---

### 🟡 IMPORTANT — Fonctionnalités à ajouter

#### 6.6 Bloquer la préparation des commandes programmées trop tôt
**Fichier : `detail_commande.php`**

Si `scheduled_datetime` > `NOW`, le bouton "Préparer" doit être inactif. Utilise `strtotime()` pour comparer.

#### 6.7 Afficher le statut "prête" dans les filtres
**Fichier : `commandes_resto.php`**

Ajouter `prete` dans `$validStatuses` et `$statusLabels`. Ajouter un badge filtre pour "Prêtes".

#### 6.8 Afficher le statut "prête" dans profil client
**Fichier : `profil_client.php`**

Ajouter `prete` dans `$statusLabels`.

#### 6.9 Le client doit pouvoir modifier sa commande APRÈS paiement mais AVANT préparation
**Nouvelle fonctionnalité — Phase 3**

Si `status = payee`, le client peut modifier sa commande (ajouter/supprimer des articles). 
- Si le total augmente → nouveau paiement de la différence (2e transaction CY Bank)
- Si le total diminue → libre (pas de remboursement, ou ticket de réduction)

Cette fonctionnalité n'est **pas urgente** mais fait partie du cahier des charges phase 3.

#### 6.10 Admin peut bloquer/débloquer un utilisateur
**Fichier : `admin.php`** — Phase 3

- Ajouter une colonne `blocked` (INTEGER 0/1) dans la table `users`
- Boutons Bloquer/Débloquer en asynchrone (fetch JS)
- Si bloqué → session terminée immédiatement, plus de connexion possible
- Ses commandes déjà payées continuent leur cycle normal

---

### 🟢 NICE TO HAVE — Améliorations

#### 6.11 Lien Maps/Waze pour le livreur
**Fichier : `livraison.php`**

Ajouter un bouton "Ouvrir dans Maps" qui génère un lien `https://www.google.com/maps/dir/?api=1&destination=ADRESSE` ou `https://waze.com/ul?q=ADRESSE`.

#### 6.12 Gros boutons pour le livreur
**Fichiers CSS + `livraison.php`**

Le livreur utilise un smartphone avec des gants → boutons larges (min 48px hauteur, padding généreux), zones tactiles espacées.

#### 6.13 Logs d'incidents
- Mauvais mot de passe
- Connexion à un compte bloqué
- Blocage/déblocage d'un compte

#### 6.14 Restaurateur peut créer/modifier/supprimer des plats/menus
Phase 4 — non prioritaire.

#### 6.15 Statistiques plats populaires
Phase 4 — non prioritaire.

---

## 7. RÉSUMÉ : CE QUE TU DOIS CODER MAINTENANT

### Étape 1 — `paiement.php`
- [ ] Retour CY Bank : toujours mettre `payee` (supprimer la distinction livraison)

### Étape 2 — `detail_commande.php` (le plus gros)
- [ ] Ajouter le traitement POST pour les actions `preparer`, `preter`, `servir`
- [ ] Ajouter les boutons correspondants dans l'interface
- [ ] Gérer le cas `order_type = livraison` : `prete` → `en_attente_livreur` automatiquement
- [ ] Bloquer "Préparer" si commande programmée dans le futur
- [ ] Vérifier que l'assignation livreur ne se déclenche que sur `en_attente_livreur`

### Étape 3 — `commandes_resto.php`
- [ ] Ajouter le statut `prete` dans les filtres et labels

### Étape 4 — `profil_client.php`
- [ ] Ajouter `prete` dans `$statusLabels`

### Étape 5 — Base de données
- [ ] Ajouter la colonne `blocked` dans `users` si pas déjà fait (pour phase 3 admin blocage)

---

## 8. ARCHITECTURE RAPIDEMENT

```
SiteWeb/
├── database/
│   ├── data.db
│   └── migration.sql        ← schéma DB
├── models/
│   ├── orders.php           ← fonctions getOrdersByStatus(), getOrderById(), etc.
│   └── users.php            ← fonctions getUserById(), getOrdersByUserId(), etc.
├── includes/
│   ├── header.php / footer.php
│   └── staff_header.php / staff_footer.php
├── assets/
│   ├── css/                 ← style.css, light-mode.css, etc.
│   └── js/                  ← script.js, form-validation.js, admin.js, etc.
├── validation.php           ← création commande (en_attente)
├── paiement.php             ← CY Bank + retour → payee
├── commandes_resto.php      ← liste/filtres restaurateur
├── detail_commande.php      ← détail + actions resto (⚠️ à modifier)
├── livraison.php            ← dashboard livreur
├── profil_client.php        ← historique client + notation
├── admin.php                ← gestion utilisateurs
└── ...
```

**Langage** : PHP 8+, SQLite (PDO)  
**Pas de framework** : PHP vanilla  
**CSS** : Variables CSS + thème dark/light existant  
**JS** : Vanilla, module `LECLIPSE` pour la validation  

---

## 9. RÈGLES IMPORTANTES

1. **Ne casse rien de ce qui marche déjà.** Les pages terminées (cf AGENTS.md) sont : `index.php`, `catalogue.php`, `login.php`, `register.php`, `panier.php`, `profil_client.php`, `notation.php`, `admin.php`, `index_admin.php`, `index_restaurateur.php`, `index_livreur.php`.

2. **Les pages à refaire/sans design** : `validation.php`, `paiement.php`, `commandes_resto.php`, `detail_commande.php`, `livraison.php`. Tu peux améliorer leur design, mais le fond fonctionnel prime.

3. **Les modèles `models/orders.php` et `models/users.php`** contiennent les fonctions d'accès DB. Utilise-les, ne duplique pas les requêtes.

4. **Utilise `h()` pour échapper les sorties HTML** (fonction dans `functions.php`).

5. **Teste avec le serveur intégré** : `php -S localhost:8000` depuis `/Users/tom/Code/SiteWeb`.

6. **Utilisateurs de test** : login = mot de passe. `client1`…`client5` (clients), `resto1` (restaurateur), `livreur1` (livreur), `admin1`/`admin2` (admins).

7. **Base SQLite** : `database/data.db`. Pour voir le schéma → `database/migration.sql`.

8. **Si tu hésites entre deux approches, choisis la plus simple** qui satisfait le cahier des charges.

---

## 10. CONTRAINTES DU PROFESSEUR (extraits bruts clés)

> Le restaurateur peut accéder à la liste détaillée des commandes de son restaurant avec les commandes à préparer, celles qui sont en cours, celles qui doivent attendre, celles en livraison…

> Le restaurateur peut aller voir le détail d'une commande, en changer le statut et l'attribuer à un livreur disponible.

> Le livreur peut accéder aux détails de la commande qui lui a été attribuée, indiquer qu'elle est livrée (ou abandonnée si l'adresse est introuvable).

> Une commande doit pouvoir être validée pour préparation immédiate ou pour une livraison/récupération plus tard (dans le cas d'une commande à emporter, le client peut prévoir plusieurs heures/jours avant, idem pour la livraison : il ne faut donc pas préparer la commande tout de suite).

> Sur la page de commandes du restaurateur, ce dernier peut changer le statut de la commande de payée à en préparation, et peut également la passer à l'état prête puis l'assigner à un livreur.

> Un client peut noter (une seule fois) chaque commande qui vient d'être livrée (pas de notation requise obligatoirement pour les commandes sur place et à emporter).

---

Commence par l'étape 1 (`paiement.php`) et l'étape 2 (`detail_commande.php`). Ce sont les deux changements critiques qui débloquent tout le workflow.
