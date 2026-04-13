<?php
session_start();
require_once 'database.php';
require_once 'functions.php';
require_once 'models/users.php';

$pdo = getDatabaseConnection();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = getUserById($pdo, $_SESSION['user_id']);

if (!$user) {
    header('Location: logout.php');
    exit;
}

// Traitement de la notation
$ratingSuccess = false;
$ratingError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rate') {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $ratingValue = (int) ($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($ratingValue < 1 || $ratingValue > 5) {
        $ratingError = "La note doit être entre 1 et 5.";
    } else {
        // Vérifier que la commande appartient au client et est livrée
        $stmt = $pdo->prepare("SELECT id FROM orders WHERE id = :id AND user_id = :uid AND status = 'livree'");
        $stmt->execute(['id' => $orderId, 'uid' => $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $existing = getOrderRating($pdo, $orderId);
            if ($existing) {
                $ratingError = "Vous avez déjà noté cette commande.";
            } else {
                createRating($pdo, $orderId, $_SESSION['user_id'], $ratingValue, $comment !== '' ? $comment : null);
                $ratingSuccess = true;
            }
        } else {
            $ratingError = "Commande invalide.";
        }
    }
}

// Récupérer les commandes du client
$orders = getOrdersByUserId($pdo, $_SESSION['user_id']);

// Pré-charger les notations existantes
$ratings = [];
foreach ($orders as $order) {
    $r = getOrderRating($pdo, $order['id']);
    if ($r) {
        $ratings[$order['id']] = $r;
    }
}

// Calculer les totaux
$orderTotals = [];
foreach ($orders as $order) {
    $orderTotals[$order['id']] = getOrderTotal($pdo, $order['id']);
}

// Labels lisibles
$statusLabels = [
    'en_attente'         => 'En attente',
    'payee'              => 'Payée',
    'en_attente_livreur' => 'En attente d\'un livreur',
    'en_preparation'     => 'En préparation',
    'en_livraison'       => 'En livraison',
    'livree'             => 'Livrée',
    'refusee'            => 'Paiement refusé',
    'abandonnee'         => 'Abandonnée',
];
?>

<?php $pageTitle = 'Mon Profil'; ?>
<?php include 'includes/header.php'; ?>

    <h1>Mon Profil</h1>

    <h2>Mes informations</h2>

    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Login</th>
            <td><?= h($user['login']) ?></td>
        </tr>
        <tr>
            <th>Prénom</th>
            <td><?= h($user['first_name']) ?></td>
        </tr>
        <tr>
            <th>Nom</th>
            <td><?= h($user['last_name']) ?></td>
        </tr>
        <tr>
            <th>Pseudo</th>
            <td><?= h($user['nickname']) ?></td>
        </tr>
        <tr>
            <th>Téléphone</th>
            <td><?= h($user['phone']) ?></td>
        </tr>
        <tr>
            <th>Date de naissance</th>
            <td><?= h($user['birthday']) ?></td>
        </tr>
        <tr>
            <th>Adresse</th>
            <td><?= h($user['address']) ?></td>
        </tr>
        <tr>
            <th>Complément d'adresse</th>
            <td><?= h($user['address_info']) ?></td>
        </tr>
        <tr>
            <th>Inscrit le</th>
            <td><?= h($user['created_at']) ?></td>
        </tr>
    </table>



    <h2>Historique de mes commandes</h2>

    <?php if ($ratingSuccess): ?>
        <p><strong>Merci pour votre notation !</strong></p>
    <?php endif; ?>

    <?php if ($ratingError): ?>
        <p><strong><?= h($ratingError) ?></strong></p>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <p>Vous n'avez pas encore passé de commande.</p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th>Articles</th>
                    <th>Total</th>
                    <th>Notation</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= $order['id'] ?></td>
                        <td><?= h($order['created_at']) ?></td>
                        <td><?= h($order['order_type']) ?></td>
                        <td><?= h($statusLabels[$order['status']] ?? $order['status']) ?></td>
                        <td><?= h($order['items_summary']) ?></td>
                        <td><?= number_format($orderTotals[$order['id']], 2, ',', ' ') ?> €</td>
                        <td>
                            <?php if ($order['status'] === 'livree'): ?>
                                <?php if (isset($ratings[$order['id']])): ?>
                                    <?= $ratings[$order['id']]['rating'] ?>/5
                                    <?php if ($ratings[$order['id']]['comment']): ?>
                                        <br><small><?= h($ratings[$order['id']]['comment']) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <form action="profil_client.php" method="post">
                                        <input type="hidden" name="action" value="rate">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <label>Note :
                                            <select name="rating">
                                                <option value="5">5</option>
                                                <option value="4">4</option>
                                                <option value="3">3</option>
                                                <option value="2">2</option>
                                                <option value="1">1</option>
                                            </select>
                                        </label>
                                        <br>
                                        <input type="text" name="comment" placeholder="Commentaire (optionnel)">
                                        <button type="submit">Noter</button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php include 'includes/footer.php'; ?>