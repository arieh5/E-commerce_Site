<?php
session_start();
include 'config.php';

// Vérifier si l'utilisateur vient de passer une commande
if (!isset($_SESSION['last_order_id'])) {
    header('Location: index.php');
    exit;
}

// Récupérer les détails de la commande
$orderId = $_SESSION['last_order_id'];
$sql = "SELECT c.*, u.nom, u.email FROM Commande c 
        JOIN Utilisateur u ON c.id_utilisateur = u.id_utilisateur 
        WHERE c.id_commande = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$orderDetails = $stmt->get_result()->fetch_assoc();

// Récupérer les billets de la commande
$sql = "SELECT b.*, m.date_match, m.heure, m.court, m.tour 
        FROM Commande_Billet cb 
        JOIN Billet b ON cb.id_billet = b.id_billet 
        JOIN Matchs m ON b.id_match = m.id_match 
        WHERE cb.id_commande = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Supprimer l'ID de la commande de la session
unset($_SESSION['last_order_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation - Roland Garros</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .header {
            background-color: white;
            display: flex;
            align-items: center;
            padding: 10px 20px;
            justify-content: space-between;
            border-bottom: 2px solid #1D5C42;
        }
        .logo {
            display: flex;
            align-items: center;
        }
        .logo img {
            height: 40px;
            margin-right: 10px;
        }
        .logo-text {
            color: #1D5C42;
            font-size: 20px;
            font-weight: bold;
            text-decoration: none;
        }
        .confirmation-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .confirmation-icon {
            color: #1D5C42;
            font-size: 48px;
            margin-bottom: 20px;
        }
        h1 {
            color: #1D5C42;
            margin-bottom: 20px;
        }
        .confirmation-message {
            font-size: 18px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .confirmation-details {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 30px;
            text-align: left;
        }
        .order-info {
            background: white;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #eee;
        }
        .order-info p {
            margin: 5px 0;
        }
        .tickets-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .ticket-item {
            background: white;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #eee;
        }
        .ticket-item h4 {
            color: #1D5C42;
            margin: 0 0 10px 0;
        }
        .ticket-item p {
            margin: 5px 0;
        }
        .order-total {
            background: white;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            border: 1px solid #eee;
        }
        .order-total .total {
            font-size: 1.2em;
            color: #1D5C42;
            border-top: 1px solid #eee;
            padding-top: 10px;
            margin-top: 10px;
        }
        .btn-home {
            display: inline-block;
            padding: 12px 30px;
            background: #1D5C42;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        .btn-home:hover {
            background: #164632;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <img src="logo2.jpeg" alt="Roland Garros Logo">
            <a href="index.php" class="logo-text">BILLETTERIE ROLAND GARROS</a>
        </div>
    </div>

    <div class="confirmation-container">
        <div class="confirmation-icon">✓</div>
        <h1>Merci pour votre achat !</h1>
        <div class="confirmation-message">
            <p>Nous sommes ravis de vous accueillir prochainement sur les courts de Roland Garros.</p>
            <p>Vos billets électroniques vous seront envoyés par email dans les prochaines minutes.</p>
        </div>
        <div class="confirmation-details">
            <h3>Détails de votre commande :</h3>
            <div class="order-info">
                <p><strong>Numéro de commande :</strong> #<?php echo str_pad($orderDetails['id_commande'], 6, '0', STR_PAD_LEFT); ?></p>
                <p><strong>Date de commande :</strong> <?php echo date('d/m/Y à H:i', strtotime($orderDetails['date_commande'])); ?></p>
                <p><strong>Client :</strong> <?php echo htmlspecialchars($orderDetails['nom']); ?></p>
                <p><strong>Email :</strong> <?php echo htmlspecialchars($orderDetails['email']); ?></p>
            </div>
            
            <h3>Vos billets :</h3>
            <div class="tickets-list">
                <?php foreach ($tickets as $ticket): ?>
                <div class="ticket-item">
                    <h4>Match du <?php echo date('d/m/Y', strtotime($ticket['date_match'])); ?> à <?php echo $ticket['heure']; ?></h4>
                    <p><strong>Court :</strong> <?php echo $ticket['court']; ?></p>
                    <p><strong>Tour :</strong> <?php echo $ticket['tour']; ?></p>
                    <p><strong>Prix :</strong> <?php echo number_format($ticket['prix'], 2, ',', ' '); ?> €</p>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="order-total">
                <p><strong>Sous-total :</strong> <?php echo number_format($orderDetails['montant_total'] - $orderDetails['frais_service'], 2, ',', ' '); ?> €</p>
                <p><strong>Frais de service :</strong> <?php echo number_format($orderDetails['frais_service'], 2, ',', ' '); ?> €</p>
                <p class="total"><strong>Total payé :</strong> <?php echo number_format($orderDetails['montant_total'], 2, ',', ' '); ?> €</p>
            </div>
            
            <h3>Informations importantes :</h3>
            <ul>
                <li>Présentez-vous au moins 1 heure avant le début du match</li>
                <li>N'oubliez pas une pièce d'identité valide</li>
                <li>Les appareils photos sont autorisés sans flash</li>
                <li>Profitez de l'atmosphère unique de Roland Garros !</li>
            </ul>
        </div>
        <a href="index.php" class="btn-home">Retour à l'accueil</a>
    </div>
</body>
</html>
<?php
// Vider le panier après la confirmation
$_SESSION['cart'] = [];
?>
