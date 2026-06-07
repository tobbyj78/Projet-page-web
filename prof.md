Le but de ce projet est de créer le site web d'un restaurant ou d’une
chaîne de restaurants. Le but final est d’avoir une application multi
utilisateurs fonctionnelle pour traiter la chaine complète d’une
commande, depuis le choix du client, jusqu’à la livraison, en passant par
les phases de connexion, paiement, traitement par le restaurateur et le
livreur.
➢ La fonctionnalité de base est celle pour le client qui arrive sur le site et
qui peut choisir de consulter la carte avant d’aller manger sur place ou de
choisir de commander en ligne (à emporter ou en livraison). Les clients
qui commandent peuvent choisir des menus déjà configurés et/ou des
plats à la carte (entrées, plats, desserts, boissons...).
➢ Les clients peuvent s'inscrire pour avoir accès à d’autres fonctionnalités :
 ne pas ressaisir des informations personnelles (état civil, adresse
de livraison, code interphone…)
 retrouver des commandes précédentes
 profiter de remises spécifiques (points bonus, produits offerts,
pourcentage…)
➢ On considèrera 4 profils d’utilisateurs avec des terminaux différents
 Le client (qui commande sur le site)
 L'administrateur (qui peut intervenir sur les profils depuis un
ordinateur, il a accès à toutes les pages du site pour vérifier les
problèmes)
 Le restaurateur (qui configure les menus déjà configurés, prépare
les commandes des clients, il visualise les commandes en cours
sur une tablette tactile)
 Le livreur (qui livre les commandes, il utilise un smartphone avec
un petit écran et avec une connexion Internet dont le débit n'est
pas toujours optimal. En période de froid il possède de gros gants
qui l’empêche de cliquer avec précision sur l’écran).
1 / 12
➢ Le projet sera divisé en plusieurs phases qui seront évaluées au fur et à
mesure de l’avancement du semestre. Ces phases seront des étapes
fonctionnelles qui suivront le rythme des chapitres du cours
d’Informatique 4 (HTML + CSS, PHP, Javascript + DOM, requêtes
asynchrones, bonnes pratiques de développement Web).
➢ La phase #1 va consister à créer la partie graphique côté client (définition
du périmètre du restaurant + nom du site, définition de la charte
graphique, affichage statique de différentes pages du site Internet, ...)
➢ La phase #2 va consister à créer la partie côté serveur (définition du
stockage des données, traitement de l’inscription et de la connexion des
utilisateurs, …)
➢ La phase #3 va consister à ajouter côté client du code Javascript pour
effectuer divers traitements dynamiques sur les pages et utiliser les
requêtes asynchrones pour modifier les pages visibles côté client sans
avoir à les recharger totalement.
➢ La phase #4 permettra de mettre en place (si ce n’est pas encore fait) les
bonnes pratiques de développement Web. Cette phase sera évaluée lors
de la soutenance finale.
➢ Enfin une soutenance orale (présentation fonctionnelle et technique du
site Internet) terminera l’évaluation du semestre pendant laquelle vous
devrez présenter une fonctionnalité innovante non demandée dans ce
cahier des charges. Cette présentation sera ponctuée de scénarios
d’actions à réaliser, de questions-réponses/modifications de votre code.
➢ Les différents critères attendus pour chaque phase vous seront donnés
dans ce document qui sera remis à jour régulièrement. Pensez donc à
consulter la page de cours régulièrement.
2 / 12
PHASE #1 ➢ Cette phase concerne le côté client, avec un affichage de quelques pages
en HTML + CSS
➢ On vous demande tout d’abord de définir le nom et le thème de votre
site web (ex : Bap to Basics, pour un site de cuisine coréenne, Arctic
Table pour un site de cuisine nordique, …). Il faut trouver un critère
spécifique pour chaque groupe de projet. Vous verrez donc à valider ce
choix au plus tôt avec votre chargé(e) de TD.
➢ Ensuite on vous demande de définir la charte graphique, c’est à dire la
liste des couleurs (et leurs valeurs #RRGGBB) que vous allez utiliser, et
pour quelle utilité (couleur des fonds, couleurs des polices, couleurs des
liens, déjà visités ou non, titres, paragraphes, bordures de formulaires,
taille du texte, etc...) .
Vous pouvez vous aider du lien donné dans les ressources utiles de ce
document pour choisir vos couleurs (paletton).
Ces informations seront réunies dans un document de conception de
votre site (au format PDF). Ce document pourra être amené à évoluer,
donc pensez à archiver également le document d’origine (format odt,
docx, …).
➢ Les pages à créer pour cette phase sont les suivantes :
 Page d'accueil par défaut qui reprend le nom du restaurant, une
zone de recherche de plats et une liste de plats (fréquemment
commandés, plat du jour, ...).
 Page de présentation des produits avec la présence d'une barre
de recherche mais aussi d'un menu pour filtrer les produits par
catégorie (types de plats, saveurs, allergènes, …)
 Page d’inscription avec un formulaire (nom, prénom, adresse,
numéro de téléphone, informations complémentaires, …)
 Page de connexion avec un formulaire classique
 Page de profil qui permet d'accéder aux informations de
l'utilisateur mais aussi à ses anciennes commandes et à son
compte fidélité, …). Certaines informations devront pouvoir être
modifiées lorsque l'on clique sur un symbole "Crayon" (pour
cette phase, elles ne sont pas réellement modifiables. La
modification ne sera effective qu'en phase 3).
 Page administrateur avec un accès à la liste des utilisateurs (tous
ou seulement ceux qui ont passé des commandes, ...) avec la
possibilité en phase 2 d'aller sur le profil concerné.
3 / 12
 Page des commandes, accessible uniquement par le restaurateur
qui pourra consulter les commandes en attente de prise en
charge (celles à préparer) et celles en cours de livraison. Des
boutons en phase 2/3 permettront de modifier le statut des
commandes (le restaurateur pour indiquer que la commande
passe en livraison, le livreur pourra indiquer que la commande
passe dans l’état livrée)
 Page de livraison, accessible par le livreur depuis son
smartphone et résumant les informations (adresse, code
interphone, étage, commentaires, numéro de téléphone, ...) avec
la possibilité d'ouvrir l'adresse avec une application de
navigation/cartographie (ex: Maps ou Waze). Il aura accès à un
bouton en phase 2/3 pour indiquer que la livraison est terminée.
 Page de notation, le client aura accès (après la livraison) à une
page où il peut noter la livraison et la qualité des produits reçus
dans sa commande.
➢ Chaque page HTML doit être dans un fichier .html séparé.
➢ Le code CSS utilisé par toutes les pages doit être stocké dans un fichier à
part et utiliser la charte graphique définie précédemment.
➢ Vous devez ajouter un fichier au format PDF qui comprendra le planning
réalisé de votre groupe pendant la phase #1 (répartition des tâches,
problèmes humains, organisationnels et techniques rencontrés, solutions
apportées pour les résoudre). Ce document devra être mis à jour à
chaque phase également, donc pensez à archiver le document d’origine
(format odt, docx, …). Ce document sert de rapport de projet : il est
différent du document de conception précédemment cité.
4 / 12
PHASE #2 ➢ Cette phase concerne le côté serveur, avec une gestion des données du
site Internet et une génération dynamique des différentes pages.
➢ Dans cette phase on vous demande en premier lieu de définir avec
précision le format des données que vous allez stocker dans le serveur.
Cette étape est très importante pour vous permettre d’avoir une vision
claire de ce que vous devez faire car il y a une multitude d’informations
que vous allez devoir gérer en fonction des fonctionnalités de votre site
Internet. Pour vous aider dans la conception, voici une liste non
exhaustive des données potentielles à stocker :
 Utilisateurs
• login
• mot de passe
• rôle (client, restaurateur, admin, livreur, ...)
• informations (nom, pseudo, naissance, adresse, ..)
• dates (inscription, dernière connexion, …)
 Menus
• Nom / description
• Prix total (si différent de la somme des plats
• liste des plats
• nombre de personnes minimum
• créneaux limités (menus midi, …)
 Plats
• Nom / description
• Prix
• informations (nutritionnelles, allergènes, …)
 Options
• enlever/remplacer certains ingrédients
• coupons réductions
 Commandes
• liste de menus
• adresse / sur place
• statut du paiement
• date + heure
 Paiement
• coordonnées bancaires
• lien avec le client et la commande
• date de transaction
5 / 12
➢ On demande pour cette phase qu’il y ait au minimum 5 clients
enregistrés, ainsi qu’au moins 2 utilisateurs administrateurs déjà présents
dans les fichiers. Ainsi il sera possible de tester la connexion sans avoir à
s’inscrire.
➢ Il faudra également que les fichiers contiennent au minimum 3 menus et
15 plats différents. Ainsi il sera possible de tester directement l’affichage
des menus, ainsi que les fonctionnalités de commandes.
L’idée est de pouvoir visualiser des menus avec des configurations
multiples et ainsi voir toutes les fonctionnalités du site (et
éventuellement détecter des bugs).
➢ Les différentes fonctionnalités à implémenter sont les suivantes :
 L'inscription d'un utilisateur doit être fonctionnelle
 La connexion doit être fonctionnelle
 L'administrateur peut accéder à la page des utilisateurs et à leur
profil, il aura accès à des boutons pour bloquer/désactiver un
compte ou modifier son statut (Premium, VIP, ...) ou accorder un
niveau de remise (l'action ne sera effective qu'à la phase 3 : ici on
ne souhaite que l’affichage)
 Le restaurateur peut accéder à la liste détaillée des commandes
de son restaurant avec les commandes à préparer, celles qui sont
en cours, celles qui doivent attendre, celles en livraison, …
 Le restaurateur peur aller voir le détail d’une commande, en
changer le statut et l'attribuer à un livreur disponible (l’action ne
sera effective qu’à la phase 3 mais on souhaite avoir l’affichage
complet)
 Le livreur peut accéder aux détails de la commande qui lui a été
attribuée, indiquer qu'elle est livrée (ou abandonnée si l’adresse
est introuvable, ...)
 Le client peut accéder à l'historique de ses commandes et pour
une commande qui vient d'être livrée de noter la commande.
 Le client peut accéder à sa page de profil, il pourra modifier ses
informations à partir de la phase 3
 Le client peut ajouter des articles (avec des quantités) à son
panier, valider sa commande, la payer et avoir un suivi sur le
statut d'une commande en cours
 Une commande doit pouvoir être validée pour préparation
immédiate ou pour une livraison/récupération plus tard (dans le
cas d’une commande à emporter, le client peut prévoir plusieurs
heures/jours avant, idem pour la livraison : il ne faut donc pas
préparer la commande tout de suite)
 Le paiement de la commande devra utiliser l'API CYBank.
Dans la phase 3 le client pourra ajouter des articles à sa
commande et procéder à un paiement additionnel, mais ici, seul
le paiement d’une commande simple est attendu.
6 / 12
➢ Tous les fichiers HTML doivent être renommés en PHP sauf si il n’y a rien
de dynamique pour ces pages.
➢ Tous les scripts PHP doivent être correctement rangés dans une
arborescence propre (séparer les ‘vues’ des autres scripts de code purs,
créer des bibliothèques de code PHP à inclure dans vos pages, …)
➢ Les fichiers contenant les données doivent être dans une arborescence
distincte des scripts PHP. Le format des fichiers de données est laissé libre
mais, à minima, un format CSV est préconisé, et le mieux reste peut être
d’utiliser le format JSON. Du moment que vous arrivez à gérer vos
données, aucun format n’est imposé.
➢ Pour les groupes qui souhaitent utiliser des bases de données, cela reste
possible mais comme ce chapitre n’est pas abordé explicitement pendant
le semestre, si des problèmes surviennent en lien avec l’utilisation de la
base de données, il faudra assumer le fait que l’application Web ne
fonctionne pas et aucune discussion n’aura lieu concernant ce point.
Si une base de données est utilisée, il faudra fournir dans le document de
conception le schéma des tables, le type de moteur utilisé (MySQL,
PostGreSQL, …), et une démarche simple pour l’installer et la configurer
avant de lancer votre site. Si des erreurs sur ce dernier point apparaissent
cela pourrait compromettre l’évaluation.
➢ Vous devez mettre à jour votre rapport de projet et relivrer au format
PDF la version phase #2. Il ne s’agit pas de créer un nouveau fichier, ni de
remplacer le précédent : il faut ajouter de nouveaux paragraphes à la
suite de ceux existants.
Dans ce rapport apparaitra entre autre le format des données dans vos
fichiers ou votre base de données. Les explications concernant le format
choisi doivent être détaillées et doivent correspondre à ce qui est
réellement codé. Une mauvaise description de votre conception sur ce
point sera très punitif sur l’évaluation.
7 / 12
PHASE #3 ➢ Cette phase met l’accent sur le coté dynamique du front-end, et
notamment sur l’utilisation du langage JavaScript.
➢ La première fonctionnalité est de proposer un changement de charte
graphique (ex : mode clair/sombre, mode contrasté, mode pour
malvoyants avec des tailles de polices plus grandes, …)
Ce changement doit se faire à l’aide d’un bouton disponible sur
l’interface. L’appui sur ce bouton doit générer le chargement du nouveau
fichier CSS sans recharger la page.
Cette charte graphique doit être ajoutée dans le document de projet,
comme la toute première charte lors de la phase #1.
Il faut pouvoir sauvegarder le choix de l’utilisateur concernant le mode
d’affichage dans un cookie. A chaque chargement de page, il faut vérifier
la présence de ce cookie et sa valeur : si il n’existe pas, ou que sa valeur
est incohérente, le mode choisi est le mode par défaut.
➢ Ensuite, pour chaque formulaire existant (inscription, connexion, …) il
faudra vérifier le contenu des champs côté client, et afficher un message
d’erreur le cas échéant, le tout sans recharger la page, ni envoyer le
formulaire côté serveur. La requête HTTP ne doit partir que si l’ensemble
du formulaire est correctement rempli, c’est à dire que la donnée dans le
champ correspond à ce qui est attendu (valeur numérique, chaîne, age,
email, numéro de téléphone, date, ...).
Il faudra également pouvoir proposer de cacher/afficher les champs de
mots de passe (icône ‘œil’ par exemple).
➢ Si vous avez des champs qui sont limités en taille (mots de passe, email,
pseudos, …) il faudra afficher un compteur en temps réel du nombre de
caractères utilisés/restants.
➢ Les nouvelles fonctionnalités demandées sont les suivantes :
 Sur la page de profil, un utilisateur peut cliquer sur un bouton
modifier ses informations. Une fois les modifications terminées, il
peut valider pour transmettre les nouvelles données vers le
serveur en connexion asynchrone obligatoirement.
 Sur la page de présentation des produits, il y aura des filtres et
des tris. Les tris seront effectués sur les données déjà récupérées
lors du chargement de la page, mais à minima, les filtres doivent
utiliser une connexion asynchrone pour récupérer uniquement
les données qui correspondent aux filtres activés
• apéritifs, entrées, plats, fromages, desserts, boissons, …
• végétarien, vegan, halal, sans gluten/lactose/œuf, ...
• salé, sucré, épicé, …
• prix croissant/décroissant, les plus commandés, …
8 / 12
 L’utilisateur peut ajouter/enlever des produits sur une
commande déjà payée mais pas encore en préparation (ce qui
permet de modifier la commande, avant qu’elle ne soit démarrée
en cuisine)
• le montant total se met à jour automatiquement
• si la commande est plus chère, le client doit effectuer un
nouveau paiement de la différence (il y aura donc 2
paiements pour cette même commande au final)
• si la commande est moins chère, la fonctionnalité est laissée
libre : le client est perdant et on ne change rien, ou bien le
client obtient un ‘ticket de réduction’ à utiliser plus tard, …
 Sur la page de commandes du restaurateur, ce dernier peut
changer le statut de la commande de payée à en préparation, et
peut également la passer à l’état prête puis l’assigner à un livreur.
 L’administrateur peut bloquer/débloquer un utilisateur en
utilisant des requêtes asynchrones obligatoirement. Si un
utilisateur est bloqué, sa session courante est terminée sur-le-
champ et ne pourra plus continuer à utiliser le site.
Si une commande a déjà été payée par cet utilisateur, son cycle
sera terminé (préparation, livraison) malgré tout.
 Un livreur peut indiquer qu’une livraison qui lui a été assignée
vient d’être effectuée.
 Un client peut noter (une seule fois) chaque commande qui vient
d’être livrée (pas de notation requise obligatoirement pour les
commandes sur place et à emporter).
9 / 12
PHASE 4 ➢ Cette phase sera évaluée lors de la soutenance orale. Elle est axée sur la
finalisation et la correction des critères non complétés des phases
précédentes.
➢ Votre site web doit fournir des fonctionnalités que l’on s’attend à
retrouver sur un site classique.
➢ L'aspect sécurité doit être évalué : est-ce que des utilisateurs mal
intentionnés peuvent perturber/bloquer le site web, voler des données
d'utilisateurs ou contourner le système de paiement…
➢ L'aspect accessibilité doit être évalué : est-ce que je peux assurer que
tous les utilisateurs peuvent accéder au site…
➢ Des fonctionnalités peuvent être ajoutées à votre site web si vous avez
complété la liste des fonctionnalités obligatoires :
 Créer des logs d'incidents (mauvais mot de passe, connexion à un
compte bloqué, blocage d'un compte, …)
 Le restaurateur peut créer/modifier/supprimer des plats et/ou
des menus
 Il y a des statistiques sur les plats (popularité, produits souvent
achetés ensemble...)
 Les plats ou les menus les plus populaires sont mis en avant sur
la page d'accueil
 Le client pourrait pouvoir choisir un menu aléatoire
 Le client peut utiliser une ancienne commande pour en faire une
nouvelle qui contiendra tous les éléments de la précédente
commande, et qui pourra potentiellement être modifiée avant la
validation et le paiement