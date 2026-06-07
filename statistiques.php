<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
$pdo = getDatabaseConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

/* ═══════════════════════════════════════════════════════════
   KPI globaux
   ═══════════════════════════════════════════════════════════ */

// Revenu total (commandes payées et au-delà)
$totalRevenue = $pdo->query("
    SELECT COALESCE(SUM(
        CASE WHEN oi.item_type = 'dish'
             THEN (SELECT price FROM dishes WHERE id = oi.item_id) * oi.quantity
             ELSE (SELECT total_price FROM menus WHERE id = oi.item_id) * oi.quantity
        END
    ), 0)
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.status NOT IN ('en_attente', 'refusee')
")->fetchColumn();

$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$paidOrders   = $pdo->query("SELECT COUNT(*) FROM orders WHERE status NOT IN ('en_attente', 'refusee')")->fetchColumn();
$totalClients = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
$totalRatings = $pdo->query("SELECT COUNT(*) FROM ratings")->fetchColumn();
$avgRating    = $pdo->query("SELECT COALESCE(ROUND(AVG(rating), 1), 0) FROM ratings")->fetchColumn();
$panierMoyen  = $paidOrders > 0 ? round($totalRevenue / $paidOrders, 2) : 0;

/* ═══════════════════════════════════════════════════════════
   Commandes par type
   ═══════════════════════════════════════════════════════════ */
$ordersByType = $pdo->query("
    SELECT order_type, COUNT(*) as cnt FROM orders GROUP BY order_type
")->fetchAll(PDO::FETCH_KEY_PAIR);

$maxTypeQty = max(1, max($ordersByType ?: [0]));

/* ═══════════════════════════════════════════════════════════
   Commandes par statut
   ═══════════════════════════════════════════════════════════ */
$statusLabels = [
    'en_attente'          => 'En attente',
    'payee'               => 'Payée',
    'refusee'             => 'Refusée',
    'en_preparation'      => 'En préparation',
    'prete'               => 'Prête',
    'en_livraison'        => 'En livraison',
    'livree'              => 'Livrée',
    'abandonnee'          => 'Abandonnée',
    'en_attente_livreur'  => 'Attente livreur',
];

$ordersByStatus = $pdo->query("
    SELECT status, COUNT(*) as cnt FROM orders GROUP BY status ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$maxStatusQty = max(1, max($ordersByStatus ?: [0]));

/* ═══════════════════════════════════════════════════════════
   Popularité des plats (top 15)
   ═══════════════════════════════════════════════════════════ */
$popularDishes = $pdo->query("
    SELECT
        oi.item_id,
        oi.item_type,
        CASE WHEN oi.item_type = 'dish' THEN d.name ELSE m.name END AS name,
        SUM(oi.quantity) AS total_qty,
        COUNT(DISTINCT oi.order_id) AS order_count,
        CASE WHEN oi.item_type = 'dish' THEN d.price ELSE m.total_price END AS unit_price,
        CASE WHEN oi.item_type = 'dish' THEN d.service_id ELSE m.service_id END AS service_id,
        CASE WHEN oi.item_type = 'dish' 
             THEN (SELECT label FROM categories WHERE id = d.category_id)
             ELSE 'Formule' END AS category_label
    FROM order_items oi
    LEFT JOIN dishes d ON oi.item_type = 'dish' AND d.id = oi.item_id
    LEFT JOIN menus  m ON oi.item_type = 'menu'  AND m.id = oi.item_id
    GROUP BY oi.item_id, oi.item_type
    ORDER BY total_qty DESC
    LIMIT 15
")->fetchAll(PDO::FETCH_ASSOC);

$maxPopularQty = max(1, $popularDishes ? (int) $popularDishes[0]['total_qty'] : 0);

/* ═══════════════════════════════════════════════════════════
   Produits souvent achetés ensemble (top 12 paires)
   ═══════════════════════════════════════════════════════════ */
$frequentPairs = $pdo->query("
    SELECT
        oi1.item_id   AS item1_id,
        oi1.item_type AS item1_type,
        oi2.item_id   AS item2_id,
        oi2.item_type AS item2_type,
        COUNT(*)      AS times_together
    FROM order_items oi1
    JOIN order_items oi2
        ON oi1.order_id = oi2.order_id
        AND (oi1.item_id < oi2.item_id OR (oi1.item_id = oi2.item_id AND oi1.item_type < oi2.item_type))
    GROUP BY oi1.item_id, oi1.item_type, oi2.item_id, oi2.item_type
    ORDER BY times_together DESC
    LIMIT 12
")->fetchAll(PDO::FETCH_ASSOC);

// Enrichir avec les noms
$pairsWithNames = [];
foreach ($frequentPairs as $pair) {
    $n1 = $pdo->prepare(
        $pair['item1_type'] === 'dish'
            ? "SELECT name FROM dishes WHERE id = ?"
            : "SELECT name FROM menus WHERE id = ?"
    );
    $n1->execute([$pair['item1_id']]);
    $name1 = $n1->fetchColumn() ?: 'Inconnu';

    $n2 = $pdo->prepare(
        $pair['item2_type'] === 'dish'
            ? "SELECT name FROM dishes WHERE id = ?"
            : "SELECT name FROM menus WHERE id = ?"
    );
    $n2->execute([$pair['item2_id']]);
    $name2 = $n2->fetchColumn() ?: 'Inconnu';

    $pairsWithNames[] = [
        'name1'          => $name1,
        'name2'          => $name2,
        'times_together' => $pair['times_together'],
    ];
}

$maxPairCount = max(1, $pairsWithNames ? $pairsWithNames[0]['times_together'] : 0);

/* ═══════════════════════════════════════════════════════════
   Top 5 clients (par dépense)
   ═══════════════════════════════════════════════════════════ */
$topClients = $pdo->query("
    SELECT
        u.id,
        u.first_name,
        u.nickname,
        COUNT(o.id) AS order_cnt,
        COALESCE(SUM(
            (SELECT COALESCE(SUM(
                CASE WHEN oi2.item_type = 'dish'
                     THEN (SELECT price FROM dishes WHERE id = oi2.item_id) * oi2.quantity
                     ELSE (SELECT total_price FROM menus WHERE id = oi2.item_id) * oi2.quantity
                END
            ), 0)
             FROM order_items oi2 WHERE oi2.order_id = o.id)
        ), 0) AS total_spent
    FROM users u
    JOIN orders o ON o.user_id = u.id
    WHERE u.role = 'client' AND o.status NOT IN ('en_attente', 'refusee')
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$maxSpent = max(1, $topClients ? (float) $topClients[0]['total_spent'] : 0);

/* ═══════════════════════════════════════════════════════════
   Évolution des commandes (7 derniers jours)
   ═══════════════════════════════════════════════════════════ */
$dailyOrders = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $joursFr = ['Sun' => 'Dim', 'Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mer', 'Thu' => 'Jeu', 'Fri' => 'Ven', 'Sat' => 'Sam'];
    $dailyOrders[] = [
        'label' => date('d/m', strtotime($date)),
        'day'   => $joursFr[date('D', strtotime($date))] ?? date('D', strtotime($date)),
        'count' => (int) $stmt->fetchColumn(),
    ];
}
$maxDaily = max(1, max(array_column($dailyOrders, 'count')));

/* ═══════════════════════════════════════════════════════════
   Répartition par service (quel service génère le plus)
   ═══════════════════════════════════════════════════════════ */
$revenuePerService = $pdo->query("
    SELECT
        s.label,
        COALESCE(SUM(
            CASE WHEN oi.item_type = 'dish'
                 THEN (SELECT price FROM dishes WHERE id = oi.item_id) * oi.quantity
                 ELSE (SELECT total_price FROM menus WHERE id = oi.item_id) * oi.quantity
            END
        ), 0) AS revenue
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    LEFT JOIN dishes d ON oi.item_type = 'dish' AND d.id = oi.item_id
    LEFT JOIN menus  m ON oi.item_type = 'menu'  AND m.id = oi.item_id
    LEFT JOIN services s ON s.id = COALESCE(d.service_id, m.service_id)
    WHERE o.status NOT IN ('en_attente', 'refusee')
    GROUP BY s.id
    ORDER BY revenue DESC
")->fetchAll(PDO::FETCH_KEY_PAIR);

$maxSvcRevenue = max(1, max($revenuePerService ?: [0]));
?>
<?php
$staffRole = 'admin';
$staffActivePage = 'stats';
$staffPageTitle = 'Statistiques';
$staffCss = 'statistiques.css';
include 'includes/staff_header.php';
?>

<main class="staff-page">
  <div class="staff-inner">

    <header class="staff-header">
      <p class="staff-label">Espace administrateur</p>
      <h1 class="staff-title">Statistiques <em>et analyses</em></h1>
    </header>

    <!-- ═══ KPI Cards ═══ -->
    <div class="kpi-grid">
      <div class="kpi-card kpi-card--revenue">
        <div class="kpi-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </div>
        <span class="kpi-value"><?= number_format($totalRevenue, 2, ',', ' ') ?> €</span>
        <span class="kpi-label">Revenu total</span>
      </div>

      <div class="kpi-card kpi-card--orders">
        <div class="kpi-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        <span class="kpi-value"><?= $totalOrders ?></span>
        <span class="kpi-label">Commandes totales</span>
      </div>

      <div class="kpi-card kpi-card--basket">
        <div class="kpi-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        </div>
        <span class="kpi-value"><?= number_format($panierMoyen, 2, ',', ' ') ?> €</span>
        <span class="kpi-label">Panier moyen</span>
      </div>

      <div class="kpi-card kpi-card--clients">
        <div class="kpi-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <span class="kpi-value"><?= $totalClients ?></span>
        <span class="kpi-label">Clients inscrits</span>
      </div>

      <div class="kpi-card kpi-card--rating">
        <div class="kpi-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </div>
        <span class="kpi-value"><?= $avgRating ?> <span class="kpi-value-sub">/ 5</span></span>
        <span class="kpi-label">Note moyenne (<?= $totalRatings ?> avis)</span>
      </div>
    </div>

    <!-- ═══ Row : Évolution 7j + Types de commande ═══ -->
    <div class="stats-row">
      <!-- Évolution 7 derniers jours -->
      <section class="stats-panel">
        <h2 class="panel-heading">
          <span class="panel-heading-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          </span>
          Commandes — 7 derniers jours
        </h2>
        <div class="bar-chart bar-chart--days">
          <?php foreach ($dailyOrders as $day): ?>
          <div class="bar-col">
            <span class="bar-value"><?= $day['count'] ?></span>
            <div class="bar-track">
              <div class="bar-fill" style="height: <?= round(($day['count'] / $maxDaily) * 100) ?>%"></div>
            </div>
            <span class="bar-label-day"><?= h($day['day']) ?></span>
            <span class="bar-label-date"><?= h($day['label']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Types de commande -->
      <section class="stats-panel">
        <h2 class="panel-heading">
          <span class="panel-heading-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
          </span>
          Types de commande
        </h2>
        <div class="type-bars">
          <?php
          $typeLabels = ['sur_place' => 'Sur place', 'emporter' => 'À emporter', 'livraison' => 'Livraison'];
          $typeIcons  = [
            'sur_place' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3Zm0 0v7"/></svg>',
            'emporter'  => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z"/><path d="M12 22V12"/><path d="m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7"/><path d="m7.5 4.27 9 5.15"/></svg>',
            'livraison' => '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/></svg>',
          ];
          foreach (['sur_place', 'emporter', 'livraison'] as $t):
            $cnt = $ordersByType[$t] ?? 0;
            $pct = $totalOrders > 0 ? round(($cnt / $totalOrders) * 100) : 0;
          ?>
          <div class="type-bar-item">
            <span class="type-icon"><?= $typeIcons[$t] ?></span>
            <div class="type-bar-info">
              <span class="type-bar-label"><?= $typeLabels[$t] ?></span>
              <span class="type-bar-count"><?= $cnt ?> commande<?= $cnt > 1 ? 's' : '' ?></span>
            </div>
            <div class="type-bar-track">
              <div class="type-bar-fill" style="width: <?= round(($cnt / $maxTypeQty) * 100) ?>%"></div>
            </div>
            <span class="type-bar-pct"><?= $pct ?>%</span>
          </div>
          <?php endforeach; ?>
        </div>
      </section>
    </div>

    <!-- ═══ Row : Répartition par statut + Revenu par service ═══ -->
    <div class="stats-row">
      <!-- Statuts -->
      <section class="stats-panel">
        <h2 class="panel-heading">
          <span class="panel-heading-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
          </span>
          Répartition par statut
        </h2>
        <div class="status-grid">
          <?php foreach ($ordersByStatus as $st => $cnt):
            $pct = $totalOrders > 0 ? round(($cnt / $totalOrders) * 100) : 0;
          ?>
          <div class="status-item">
            <div class="status-head">
              <span class="status-dot status-dot--<?= $st ?>"></span>
              <span class="status-name"><?= $statusLabels[$st] ?? $st ?></span>
            </div>
            <div class="status-bar-track">
              <div class="status-bar-fill status-bar-fill--<?= $st ?>" style="width: <?= round(($cnt / $maxStatusQty) * 100) ?>%"></div>
            </div>
            <span class="status-count"><?= $cnt ?> (<?= $pct ?>%)</span>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Revenu par service -->
      <section class="stats-panel">
        <h2 class="panel-heading">
          <span class="panel-heading-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
          </span>
          Revenu par service
        </h2>
        <div class="revenue-list">
          <?php foreach ($revenuePerService as $svcLabel => $rev):
            $svcPct = $totalRevenue > 0 ? round(($rev / $totalRevenue) * 100) : 0;
            $svcBarPct = round(($rev / $maxSvcRevenue) * 100);
          ?>
          <div class="revenue-item">
            <div class="revenue-head">
              <span class="revenue-label"><?= h($svcLabel) ?></span>
              <span class="revenue-amount"><?= number_format($rev, 0, ',', ' ') ?> €</span>
            </div>
            <div class="revenue-bar-row">
              <div class="revenue-track">
                <div class="revenue-fill" style="width: <?= $svcBarPct ?>%"></div>
              </div>
              <span class="revenue-pct"><?= $svcPct ?>%</span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </section>
    </div>

    <!-- ═══ Popularité des plats ═══ -->
    <section class="stats-panel stats-panel--full">
      <h2 class="panel-heading">
        <span class="panel-heading-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </span>
        Plats les plus populaires
        <span class="panel-heading-sub">Top 15 — par quantités commandées</span>
      </h2>

      <?php if (empty($popularDishes)): ?>
        <div class="stats-empty">Aucune donnée disponible pour le moment.</div>
      <?php else: ?>
      <div class="popular-table-wrap">
        <table class="popular-table">
          <thead>
            <tr>
              <th class="col-rank">#</th>
              <th>Plat / Menu</th>
              <th>Catégorie</th>
              <th class="col-num">Qté vendue</th>
              <th class="col-num">Nb commandes</th>
              <th class="col-num">Prix unit.</th>
              <th>Popularité</th>
            </tr>
          </thead>
          <tbody>
            <?php $rank = 0; foreach ($popularDishes as $dish): $rank++; ?>
            <tr class="<?= $rank <= 3 ? 'is-top is-top--' . $rank : '' ?>">
              <td class="col-rank">
                <span class="rank-badge rank-badge--<?= $rank <= 3 ? $rank : 'other' ?>"><?= $rank ?></span>
              </td>
              <td class="col-name"><?= h($dish['name']) ?></td>
              <td class="col-cat"><?= h($dish['category_label']) ?></td>
              <td class="col-num"><strong><?= $dish['total_qty'] ?></strong></td>
              <td class="col-num"><?= $dish['order_count'] ?></td>
              <td class="col-num"><?= number_format($dish['unit_price'], 2, ',', ' ') ?> €</td>
              <td class="col-bar">
                <div class="popular-bar-track">
                  <div class="popular-bar-fill" style="width: <?= round(((int)$dish['total_qty'] / $maxPopularQty) * 100) ?>%"></div>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </section>

    <!-- ═══ Produits souvent achetés ensemble ═══ -->
    <section class="stats-panel stats-panel--full">
      <h2 class="panel-heading">
        <span class="panel-heading-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
        </span>
        Produits souvent achetés ensemble
        <span class="panel-heading-sub">Paires les plus fréquentes dans un même panier</span>
      </h2>

      <?php if (empty($pairsWithNames)): ?>
        <div class="stats-empty">Pas assez de données pour détecter des associations.</div>
      <?php else: ?>
      <div class="pairs-grid">
        <?php foreach ($pairsWithNames as $pair):
          $pairPct = round(($pair['times_together'] / $maxPairCount) * 100);
        ?>
        <div class="pair-card">
          <div class="pair-products">
            <span class="pair-name pair-name--a" title="<?= h($pair['name1']) ?>">
              <?= h(mb_strlen($pair['name1']) > 28 ? mb_substr($pair['name1'], 0, 26) . '…' : $pair['name1']) ?>
            </span>
            <span class="pair-connector" aria-hidden="true">+</span>
            <span class="pair-name pair-name--b" title="<?= h($pair['name2']) ?>">
              <?= h(mb_strlen($pair['name2']) > 28 ? mb_substr($pair['name2'], 0, 26) . '…' : $pair['name2']) ?>
            </span>
          </div>
          <div class="pair-bar-track">
            <div class="pair-bar-fill" style="width: <?= $pairPct ?>%"></div>
          </div>
          <span class="pair-count"><?= $pair['times_together'] ?>× ensemble</span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>

    <!-- ═══ Top 5 clients ═══ -->
    <section class="stats-panel stats-panel--full">
      <h2 class="panel-heading">
        <span class="panel-heading-icon" aria-hidden="true">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M3 21v-2a7 7 0 0 1 7-7h4a7 7 0 0 1 7 7v2"/></svg>
        </span>
        Top 5 clients
        <span class="panel-heading-sub">Par dépenses cumulées</span>
      </h2>

      <?php if (empty($topClients)): ?>
        <div class="stats-empty">Aucune commande payée pour le moment.</div>
      <?php else: ?>
      <div class="clients-grid">
        <?php foreach ($topClients as $i => $client):
          $clientPct = round(((float)$client['total_spent'] / $maxSpent) * 100);
          $medal = ['🥇', '🥈', '🥉', '⭐', '⭐'][$i];
        ?>
        <div class="client-card">
          <div class="client-medal"><?= $medal ?></div>
          <div class="client-info">
            <span class="client-name"><?= h($client['first_name'] ?? $client['nickname']) ?></span>
            <span class="client-nick">@<?= h($client['nickname']) ?></span>
          </div>
          <div class="client-stats">
            <span class="client-spent"><?= number_format($client['total_spent'], 0, ',', ' ') ?> €</span>
            <span class="client-orders"><?= $client['order_cnt'] ?> commande<?= $client['order_cnt'] > 1 ? 's' : '' ?></span>
          </div>
          <div class="client-bar-track">
            <div class="client-bar-fill" style="width: <?= $clientPct ?>%"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>

  </div>
</main>

<?php include 'includes/staff_footer.php'; ?>
