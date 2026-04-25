<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? "L'Éclipse" ?></title>
  <link rel="icon" href="/images/favicon.ico">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&family=Cormorant+SC:wght@400;500&family=EB+Garamond:wght@400;500&family=Jost:wght@300;400&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="/assets/css/navbar.css">
  <?php if (!empty($pageCss)): ?>
    <link rel="stylesheet" href="/assets/css/<?= $pageCss ?>">
  <?php endif; ?>
</head>
<body>

<header class="navbar" data-navbar>
  <div class="navbar-inner">

    <!-- Logo -->
    <a href="/" class="logo" aria-label="L'Éclipse">
      <img class="logo-image" src="/images/logo.webp" alt="" width="38" height="38">
      <span class="logo-text">L'Éclipse</span>
    </a>

    <!-- Menu principal -->
    <nav aria-label="Navigation principale">
      <ul class="nav-list">

        <!-- Petit-Déjeuner -->
        <li class="nav-item">
          <button class="nav-btn" aria-haspopup="true" aria-expanded="false" data-menu-trigger>
            Petit-Déjeuner
          </button>

          <div class="dropdown" aria-hidden="true" data-dropdown>
            <div class="dropdown-inner">
              <div class="dropdown-grid" data-showcase>

                <!-- col. image + titre -->
                <div class="panel-identity">
                  <div>
                    <p class="panel-hours"><span aria-hidden="true">✦</span> 07h30 — 10h30</p>
                    <h2 class="panel-title">Petit-Déjeuner<br><em>Gourmand</em></h2>
                  </div>
                  <div class="panel-image" role="img" aria-label="Petit-déjeuner gourmand"
                       style="background-image:linear-gradient(180deg,rgba(22,20,18,.15),rgba(22,20,18,.55)),url('https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=900&q=80');"></div>
                </div>

                <!-- col. formules -->
                <div>
                  <h3 class="col-label">Les Formules</h3>
                  <ul class="formulas">
                    <li><a class="formula" href="#">
                      <span class="formula-name">L'Éveil Sucré</span>
                      <span class="formula-desc">Viennoiseries &amp; douceurs du matin</span>
                    </a></li>
                    <li><a class="formula" href="#">
                      <span class="formula-name">Le Matin Salé-Chic</span>
                      <span class="formula-desc">L'audace du salé dès l'aube</span>
                    </a></li>
                    <li><a class="formula" href="#">
                      <span class="formula-name">L'Équilibre Gourmand</span>
                      <span class="formula-desc">Sucré, salé &amp; fruits de saison</span>
                    </a></li>
                  </ul>
                </div>

                <!-- col. catégories -->
                <div>
                  <h3 class="col-label">La Carte</h3>
                  <ul class="cat-list">
                    <li><button class="cat-btn is-active" data-target="viennoiseries">Viennoiseries &amp; Douceurs</button></li>
                    <li><button class="cat-btn"           data-target="fruits">Fruits &amp; Céréales</button></li>
                    <li><button class="cat-btn"           data-target="sale">Salé d'exception</button></li>
                  </ul>
                </div>

                <!-- col. plats -->
                <div class="dishes-stage">
                  <div class="dishes is-active" data-panel="viennoiseries" aria-hidden="false">
                    <ul class="dishes-list">
                      <li><a href="#">Pain au chocolat aux amandes et praliné noisette</a></li>
                      <li><a href="#">Croissant revisité à la crème diplomate et vanille de Madagascar</a></li>
                      <li><a href="#">Gaufre liégeoise, compotée de cerises noires et chantilly mascarpone</a></li>
                      <li><a href="#">Brioche perdue au caramel beurre salé et éclats de noix de pécan</a></li>
                      <li><a href="#">Pancakes moelleux, sirop d'érable infusé au romarin et myrtilles</a></li>
                    </ul>
                  </div>
                  <div class="dishes" data-panel="fruits" aria-hidden="true">
                    <ul class="dishes-list">
                      <li><a href="#">Verrine de chia au lait d'amande, mangue fraîche et citron vert</a></li>
                      <li><a href="#">Granola maison aux noisettes torréfiées et yaourt grec</a></li>
                    </ul>
                  </div>
                  <div class="dishes" data-panel="sale" aria-hidden="true">
                    <ul class="dishes-list">
                      <li><a href="#">Avocado toast à la grenade et feta AOP</a></li>
                      <li><a href="#">Œufs Bénédicte au bacon de dinde et sauce hollandaise yuzu</a></li>
                      <li><a href="#">Œufs parfaits à la truffe noire sur pain de campagne toasté</a></li>
                      <li><a href="#">Saumon gravlax maison, blinis tièdes et crème crue à la ciboulette</a></li>
                      <li><a href="#">Assiette de fromages affinés et confiture de figues</a></li>
                    </ul>
                  </div>
                </div>

                <!-- col. lien "Découvrir" -->
                <div class="cta-col">
                  <a href="#" class="cta-btn">
                    <span>Découvrir</span>
                    <span class="cta-arrow" aria-hidden="true">→</span>
                  </a>
                </div>

              </div>
            </div>
          </div>
        </li>

        <!-- Déjeuner -->
        <li class="nav-item">
          <button class="nav-btn" aria-haspopup="true" aria-expanded="false" data-menu-trigger>
            Déjeuner
          </button>

          <div class="dropdown" aria-hidden="true" data-dropdown>
            <div class="dropdown-inner">
              <div class="dropdown-grid" data-showcase>

                <div class="panel-identity">
                  <div>
                    <p class="panel-hours"><span aria-hidden="true">✦</span> 12h30 — 15h00</p>
                    <h2 class="panel-title">Déjeuner<br><em>Léger</em></h2>
                  </div>
                  <div class="panel-image" role="img" aria-label="Plat de saison"
                       style="background-image:linear-gradient(180deg,rgba(22,20,18,.15),rgba(22,20,18,.55)),url('https://images.unsplash.com/photo-1504754524776-8f4f37790ca0?auto=format&fit=crop&w=900&q=80');"></div>
                </div>

                <div>
                  <h3 class="col-label">Les Formules</h3>
                  <ul class="formulas">
                    <li><a class="formula" href="#">
                      <span class="formula-name">Fraîcheur Végétale</span>
                      <span class="formula-desc">Jardin du potager &amp; produits du jour</span>
                    </a></li>
                    <li><a class="formula" href="#">
                      <span class="formula-name">L'Échappée Marine</span>
                      <span class="formula-desc">Poissons du littoral &amp; végétal frais</span>
                    </a></li>
                    <li><a class="formula" href="#">
                      <span class="formula-name">Terre &amp; Tradition</span>
                      <span class="formula-desc">Une cuisine de caractère</span>
                    </a></li>
                  </ul>
                </div>

                <div>
                  <h3 class="col-label">La Carte</h3>
                  <ul class="cat-list">
                    <li><button class="cat-btn is-active" data-target="entrees">Entrées &amp; Froid</button></li>
                    <li><button class="cat-btn"           data-target="tartes">Tartes &amp; Végétal</button></li>
                    <li><button class="cat-btn"           data-target="chauds">Plats chauds</button></li>
                  </ul>
                </div>

                <div class="dishes-stage">
                  <div class="dishes is-active" data-panel="entrees" aria-hidden="false">
                    <ul class="dishes-list">
                      <li><a href="#">Velouté glacé de petits pois à la menthe fraîche</a></li>
                      <li><a href="#">Gaspacho de tomates anciennes, éclats de pistache et basilic</a></li>
                      <li><a href="#">Ceviche de daurade au lait de tigre et agrumes</a></li>
                      <li><a href="#">Carpaccio de bœuf, copeaux de parmesan et roquette</a></li>
                    </ul>
                  </div>
                  <div class="dishes" data-panel="tartes" aria-hidden="true">
                    <ul class="dishes-list">
                      <li><a href="#">Quinoa aux légumes croquants et vinaigrette fruit de la passion</a></li>
                      <li><a href="#">Tarte fine à la courgette violon et moutarde à l'ancienne</a></li>
                      <li><a href="#">Mille-feuille de légumes du soleil, coulis de poivron rouge</a></li>
                      <li><a href="#">Tartelette salée aux champignons sauvages et comté affiné</a></li>
                    </ul>
                  </div>
                  <div class="dishes" data-panel="chauds" aria-hidden="true">
                    <ul class="dishes-list">
                      <li><a href="#">Risotto d'épeautre aux asperges vertes et citron confit</a></li>
                      <li><a href="#">Suprême de volaille poché, julienne de légumes d'été</a></li>
                      <li><a href="#">Salade niçoise revisitée au thon mi-cuit</a></li>
                      <li><a href="#">Filet de rouget poêlé, vierge de légumes au fenouil</a></li>
                    </ul>
                  </div>
                </div>

                <div class="cta-col">
                  <a href="#" class="cta-btn">
                    <span>Découvrir</span>
                    <span class="cta-arrow" aria-hidden="true">→</span>
                  </a>
                </div>

              </div>
            </div>
          </div>
        </li>

        <!-- Dîner -->
        <li class="nav-item">
          <button class="nav-btn" aria-haspopup="true" aria-expanded="false" data-menu-trigger>
            Dîner
          </button>

          <div class="dropdown" aria-hidden="true" data-dropdown>
            <div class="dropdown-inner">
              <div class="dropdown-grid" data-showcase>

                <div class="panel-identity">
                  <div>
                    <p class="panel-hours"><span aria-hidden="true">✦</span> 19h30 — 23h00</p>
                    <h2 class="panel-title"><em>Le Grand</em><br>Dîner</h2>
                  </div>
                  <div class="panel-image" role="img" aria-label="Filet de bœuf Rossini"
                       style="background-image:linear-gradient(180deg,rgba(22,20,18,.15),rgba(22,20,18,.65)),url('https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=900&q=80');"></div>
                </div>

                <div>
                  <h3 class="col-label">Les Menus Signature</h3>
                  <ul class="formulas">
                    <li><a class="formula" href="#">
                      <span class="formula-name">Prestige Océan</span>
                      <span class="formula-desc">Grands crus iodés en cinq actes</span>
                    </a></li>
                    <li><a class="formula" href="#">
                      <span class="formula-name">Signature du Chef</span>
                      <span class="formula-desc">Carte blanche &amp; accords sommelier</span>
                    </a></li>
                    <li><a class="formula" href="#">
                      <span class="formula-name">Terre d'Exception</span>
                      <span class="formula-desc">Hommage aux produits de nos terroirs</span>
                    </a></li>
                  </ul>
                </div>

                <div>
                  <h3 class="col-label">La Carte</h3>
                  <ul class="cat-list">
                    <li><button class="cat-btn is-active" data-target="entrees">Les Entrées</button></li>
                    <li><button class="cat-btn"           data-target="poissons">Poissons &amp; Crustacés</button></li>
                    <li><button class="cat-btn"           data-target="viandes">Viandes d'exception</button></li>
                    <li><button class="cat-btn"           data-target="desserts">Fromages &amp; Desserts</button></li>
                  </ul>
                </div>

                <div class="dishes-stage">
                  <div class="dishes is-active" data-panel="entrees" aria-hidden="false">
                    <ul class="dishes-list">
                      <li><a href="#">Foie gras de canard mi-cuit, chutney de figues au porto</a></li>
                      <li><a href="#">Raviole de langoustine, bouillon léger aux algues kombu</a></li>
                      <li><a href="#">Noix de Saint-Jacques rôties, purée de panais à la vanille</a></li>
                    </ul>
                  </div>
                  <div class="dishes" data-panel="poissons" aria-hidden="true">
                    <ul class="dishes-list">
                      <li><a href="#">Turbot sauvage rôti sur l'arête, beurre blanc au caviar</a></li>
                      <li><a href="#">Homard bleu en deux cuissons, déclinaison de carottes fanes</a></li>
                    </ul>
                  </div>
                  <div class="dishes" data-panel="viandes" aria-hidden="true">
                    <ul class="dishes-list">
                      <li><a href="#">Carré d'agneau en croûte d'herbes, mousseline d'artichaut</a></li>
                      <li><a href="#">Pigeonneau rôti au sautoir, petits pois à la française</a></li>
                      <li><a href="#">Ris de veau croustillant, jus corsé et morilles fraîches</a></li>
                      <li><a href="#">Filet de bœuf Rossini, pommes macaire et jus truffé</a></li>
                    </ul>
                  </div>
                  <div class="dishes" data-panel="desserts" aria-hidden="true">
                    <ul class="dishes-list">
                      <li><a href="#">Chariot de fromages affinés par notre maître fromager</a></li>
                      <li><a href="#">Soufflé chaud au Grand Marnier, sorbet orange sanguine</a></li>
                      <li><a href="#">Sphère en chocolat noir intense, cœur coulant praliné</a></li>
                    </ul>
                  </div>
                </div>

                <div class="cta-col">
                  <a href="#" class="cta-btn">
                    <span>Découvrir</span>
                    <span class="cta-arrow" aria-hidden="true">→</span>
                  </a>
                </div>

              </div>
            </div>
          </div>
        </li>

        <!-- Cave (sans formules) -->
        <li class="nav-item divider">
          <button class="nav-btn" aria-haspopup="true" aria-expanded="false" data-menu-trigger>
            Cave
          </button>

          <div class="dropdown" aria-hidden="true" data-dropdown>
            <div class="dropdown-inner">
              <div class="dropdown-grid compact" data-showcase>

                <div class="panel-identity">
                  <div>
                    <h2 class="panel-title">Cave &amp;<br><em>Spiritueux</em></h2>
                  </div>
                  <div class="panel-image" role="img" aria-label="Cave à vins"
                       style="background-image:linear-gradient(180deg,rgba(22,20,18,.15),rgba(22,20,18,.65)),url('https://images.unsplash.com/photo-1553361371-9b22f78e8b1d?auto=format&fit=crop&w=900&q=80');"></div>
                </div>

                <div>
                  <h3 class="col-label">La Carte</h3>
                  <ul class="cat-list">
                    <li><button class="cat-btn is-active" data-target="champagnes">Champagnes</button></li>
                    <li><button class="cat-btn"           data-target="rouges">Vins Rouges</button></li>
                    <li><button class="cat-btn"           data-target="blancs">Vins Blancs &amp; Liquoreux</button></li>
                    <li><button class="cat-btn"           data-target="spiritueux">Spiritueux d'exception</button></li>
                  </ul>
                </div>

                <div class="dishes-stage">
                  <div class="dishes is-active" data-panel="champagnes" aria-hidden="false">
                    <ul class="dishes-list">
                      <li><a href="#">Grandes Maisons</a></li>
                      <li><a href="#">Cuvées Millésimées</a></li>
                      <li><a href="#">Rosés de Prestige</a></li>
                      <li><a href="#">Blanc de Blancs</a></li>
                    </ul>
                  </div>
                  <div class="dishes" data-panel="rouges" aria-hidden="true">
                    <ul class="dishes-list">
                      <li><a href="#">Bordeaux &amp; Grands Crus Classés</a></li>
                      <li><a href="#">Bourgogne</a></li>
                      <li><a href="#">Vallée du Rhône</a></li>
                      <li><a href="#">Vignobles Italiens</a></li>
                      <li><a href="#">Nouveau Monde</a></li>
                    </ul>
                  </div>
                  <div class="dishes" data-panel="blancs" aria-hidden="true">
                    <ul class="dishes-list">
                      <li><a href="#">Bourgogne Blanc</a></li>
                      <li><a href="#">Loire &amp; Alsace</a></li>
                      <li><a href="#">Vins Doux Naturels</a></li>
                      <li><a href="#">Sauternes &amp; Vendanges Tardives</a></li>
                      <li><a href="#">Portos millésimés</a></li>
                    </ul>
                  </div>
                  <div class="dishes" data-panel="spiritueux" aria-hidden="true">
                    <ul class="dishes-list">
                      <li><a href="#">Cognac &amp; Armagnac</a></li>
                      <li><a href="#">Whiskys rares et single malts</a></li>
                      <li><a href="#">Rhums vieux</a></li>
                      <li><a href="#">Eaux-de-vie de la Maison</a></li>
                    </ul>
                  </div>
                </div>

                <div class="cta-col">
                  <a href="#" class="cta-btn">
                    <span>Découvrir</span>
                    <span class="cta-arrow" aria-hidden="true">→</span>
                  </a>
                </div>

              </div>
            </div>
          </div>
        </li>

        <!-- Épicerie (sans formules) -->
        <li class="nav-item">
          <button class="nav-btn" aria-haspopup="true" aria-expanded="false" data-menu-trigger>
            Épicerie
          </button>

          <div class="dropdown" aria-hidden="true" data-dropdown>
            <div class="dropdown-inner">
              <div class="dropdown-grid compact" data-showcase>

                <div class="panel-identity">
                  <div>
                    <h2 class="panel-title">Épicerie<br><em>fine</em></h2>
                  </div>
                  <div class="panel-image" role="img" aria-label="Produits d'exception"
                       style="background-image:linear-gradient(180deg,rgba(22,20,18,.15),rgba(22,20,18,.6)),url('https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=900&q=80');"></div>
                </div>

                <div>
                  <h3 class="col-label">La Carte</h3>
                  <ul class="cat-list">
                    <li><button class="cat-btn is-active" data-target="caviar">Caviar &amp; Fumaisons</button></li>
                    <li><button class="cat-btn"           data-target="truffes">Truffes &amp; Produits rares</button></li>
                    <li><button class="cat-btn"           data-target="huiles">Huiles &amp; Condiments</button></li>
                    <li><button class="cat-btn"           data-target="douceurs">Douceurs &amp; Infusions</button></li>
                  </ul>
                </div>

                <div class="dishes-stage">
                  <div class="dishes is-active" data-panel="caviar" aria-hidden="false">
                    <ul class="dishes-list">
                      <li><a href="#">Caviar Osciètre et Baeri</a></li>
                      <li><a href="#">Saumon fumé sauvage</a></li>
                      <li><a href="#">Truites fumées à la ficelle</a></li>
                      <li><a href="#">Œufs de hareng et poutargue</a></li>
                    </ul>
                  </div>
                  <div class="dishes" data-panel="truffes" aria-hidden="true">
                    <ul class="dishes-list">
                      <li><a href="#">Truffes noires fraîches (en saison)</a></li>
                      <li><a href="#">Huile de truffe blanche</a></li>
                      <li><a href="#">Beurre truffé</a></li>
                      <li><a href="#">Brisures et jus de truffe</a></li>
                    </ul>
                  </div>
                  <div class="dishes" data-panel="huiles" aria-hidden="true">
                    <ul class="dishes-list">
                      <li><a href="#">Huiles d'olive d'exception</a></li>
                      <li><a href="#">Vinaigres balsamiques affinés</a></li>
                      <li><a href="#">Moutardes, sels et poivres rares</a></li>
                      <li><a href="#">Jambons et charcuteries affinées</a></li>
                      <li><a href="#">Foies gras et conserves fines</a></li>
                    </ul>
                  </div>
                  <div class="dishes" data-panel="douceurs" aria-hidden="true">
                    <ul class="dishes-list">
                      <li><a href="#">Confitures et marmelades artisanales</a></li>
                      <li><a href="#">Miels de terroir</a></li>
                      <li><a href="#">Chocolats et pralinés de la Maison</a></li>
                      <li><a href="#">Thés d'exception</a></li>
                      <li><a href="#">Cafés de grands crus</a></li>
                    </ul>
                  </div>
                </div>

                <div class="cta-col">
                  <a href="#" class="cta-btn">
                    <span>Découvrir</span>
                    <span class="cta-arrow" aria-hidden="true">→</span>
                  </a>
                </div>

              </div>
            </div>
          </div>
        </li>

      </ul>
    </nav>

    <!-- profil + bouton commander -->
    <div class="navbar-utils">

      <div class="profile" data-profile>
        <button class="icon-btn" aria-label="Mon compte" aria-haspopup="true" aria-expanded="false" data-profile-trigger>
          <span class="profile-icon" aria-hidden="true"></span>
        </button>

        <div class="profile-dropdown" aria-hidden="true" data-profile-dropdown>
          <?php if (empty($user)): ?>
            <a href="login.php"    class="profile-link">Connexion</a>
            <a href="register.php" class="profile-link">Créer un compte</a>
          <?php else: ?>
            <span class="profile-name"><?= htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') ?></span>
            <a href="catalogue.php"     class="profile-link">Catalogue</a>
            <a href="panier.php"        class="profile-link">Panier</a>
            <a href="profil_client.php" class="profile-link">Mon profil</a>
            <a href="logout.php"        class="profile-link logout">Déconnexion</a>
          <?php endif; ?>
        </div>
      </div>

      <a class="order-btn" href="#commander">Commander</a>
    </div>

  </div>

  <!-- barre fixée en bas, visible uniquement sur mobile -->
  <div class="mobile-bar" data-mobile-bar>
    <a class="mobile-bar-btn" href="#commander">Commander</a>
  </div>
</header>

<!-- flou de fond quand le menu est ouvert — animé via navbar.js -->
<div id="nav-backdrop"></div>
