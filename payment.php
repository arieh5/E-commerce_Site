<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'payment.php';
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['cart'])) {
    header('Location: panier.php');
    exit;
}

// Préparer les placeholders pour les requêtes SQL
$placeholders = str_repeat('?,', count($_SESSION['cart']) - 1) . '?';

// Vérifier que les billets sont toujours disponibles avant d'afficher la page
$checkSql = "SELECT COUNT(*) as sold_count FROM Billet WHERE id_billet IN ($placeholders) AND vendu = 1";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param(str_repeat('i', count($_SESSION['cart'])), ...$_SESSION['cart']);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
$soldCount = $checkResult->fetch_assoc()['sold_count'];

if ($soldCount > 0) {
    $_SESSION['payment_error'] = 'Désolé, certains billets de votre panier ont déjà été vendus.';
    header('Location: panier.php');
    exit;
}

// Calculer le total
$sql = "SELECT SUM(prix) as total FROM Billet WHERE id_billet IN ($placeholders)";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('i', count($_SESSION['cart'])), ...$_SESSION['cart']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$subtotal = $row['total'];
$serviceFee = 4.00;
$total = $subtotal + $serviceFee;

if (isset($_POST['payment_method'])) {
    $conn->begin_transaction();
    
    try {
        // Vérifier que les billets existent et sont disponibles
        $checkSql = "SELECT id_billet, vendu FROM Billet WHERE id_billet IN ($placeholders)";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param(str_repeat('i', count($_SESSION['cart'])), ...$_SESSION['cart']);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        // Vérifier si tous les billets existent
        if ($checkResult->num_rows !== count($_SESSION['cart'])) {
            throw new Exception('Certains billets ne sont plus disponibles.');
        }
        
        // Vérifier si des billets ont été vendus entre temps
        $soldTickets = [];
        while ($row = $checkResult->fetch_assoc()) {
            if ($row['vendu']) {
                $soldTickets[] = $row['id_billet'];
            }
        }
        
        if (!empty($soldTickets)) {
            // Retirer les billets vendus du panier
            $_SESSION['cart'] = array_values(array_diff($_SESSION['cart'], $soldTickets));
            
            // Préparer un message d'erreur détaillé
            $ticketCount = count($soldTickets);
            $message = $ticketCount > 1 
                ? "$ticketCount billets ont été vendus pendant votre paiement. Ils ont été retirés de votre panier." 
                : "Un billet a été vendu pendant votre paiement. Il a été retiré de votre panier.";
            
            // Si le panier est maintenant vide
            if (empty($_SESSION['cart'])) {
                $message .= "\nVotre panier est maintenant vide. Veuillez sélectionner d'autres billets.";
            } else {
                $message .= "\nVous pouvez continuer votre achat avec les billets restants.";
            }
            
            throw new Exception($message);
        }
        
        // Marquer les billets comme vendus
        $updateSql = "UPDATE Billet SET vendu = 1 WHERE id_billet IN ($placeholders)";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param(str_repeat('i', count($_SESSION['cart'])), ...$_SESSION['cart']);
        if (!$updateStmt->execute()) {
            throw new Exception('Erreur lors de la mise à jour des billets.');
        }
        
        // Enregistrer la commande
        $insertOrderSql = "INSERT INTO Commande (id_utilisateur, montant_total, frais_service, date_commande) VALUES (?, ?, ?, NOW())";
        $orderStmt = $conn->prepare($insertOrderSql);
        $orderStmt->bind_param("idd", $_SESSION['user_id'], $total, $serviceFee);
        if (!$orderStmt->execute()) {
            throw new Exception('Erreur lors de l\'enregistrement de la commande.');
        }
        $orderId = $conn->insert_id;
        
        // Enregistrer les détails de la commande
        $insertDetailsSql = "INSERT INTO Commande_Billet (id_commande, id_billet) VALUES (?, ?)";
        $detailsStmt = $conn->prepare($insertDetailsSql);
        foreach ($_SESSION['cart'] as $ticketId) {
            $detailsStmt->bind_param("ii", $orderId, $ticketId);
            if (!$detailsStmt->execute()) {
                throw new Exception('Erreur lors de l\'enregistrement des détails de la commande.');
            }
        }
        
        // Valider la transaction
        $conn->commit();
        
        // Sauvegarder l'ID de la commande pour la confirmation
        $_SESSION['last_order_id'] = $orderId;
        
        // Vider le panier
        $_SESSION['cart'] = [];
        
        header('Location: confirmation.php');
        exit;
        
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $conn->rollback();
        $_SESSION['payment_error'] = $e->getMessage();
        header('Location: panier.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - Roland Garros</title>
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
        .payment-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        .payment-method {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-method:hover {
            border-color: #1D5C42;
            transform: translateY(-2px);
        }
        .payment-method.selected {
            border-color: #1D5C42;
            background-color: #f8f9fa;
        }
        .payment-method img {
            height: 40px;
            margin-bottom: 10px;
        }
        .summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        .summary-total {
            border-top: 2px solid #ddd;
            margin-top: 10px;
            padding-top: 10px;
            font-weight: bold;
            font-size: 1.2em;
        }
        .btn-pay {
            width: 100%;
            padding: 15px;
            background: #1D5C42;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .btn-pay:hover {
            background: #164632;
        }
        .secure-info {
            text-align: center;
            margin-top: 20px;
            color: #666;
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

    <div class="payment-container">
        <h2 style="text-align: center; color: #1D5C42; margin-bottom: 30px;">Paiement sécurisé</h2>
        
        <?php if (isset($_SESSION['payment_error'])): ?>
        <div style="background-color: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin-bottom: 30px; text-align: center;">
            <div style="font-size: 24px; margin-bottom: 10px;">⚠️</div>
            <div style="white-space: pre-line;"><?php 
                echo htmlspecialchars($_SESSION['payment_error']); 
                unset($_SESSION['payment_error']);
            ?></div>
            <?php if (empty($_SESSION['cart'])): ?>
            <a href="index.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: #721c24; color: white; text-decoration: none; border-radius: 4px;">
                Retour à la billetterie
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        

        
        <div class="summary">
            <h3 style="margin-top: 0;">Récapitulatif de la commande</h3>
            <div class="summary-row">
                <span>Sous-total</span>
                <span><?php echo number_format($subtotal, 2, ',', ' '); ?> €</span>
            </div>
            <div class="summary-row">
                <span>Frais de service</span>
                <span><?php echo number_format($serviceFee, 2, ',', ' '); ?> €</span>
            </div>
            <div class="summary-row summary-total">
                <span>Total</span>
                <span><?php echo number_format($total, 2, ',', ' '); ?> €</span>
            </div>
        </div>

        <form method="post" id="payment-form">
            <h3>Choisissez votre moyen de paiement</h3>
            <div class="payment-methods">
                <div class="payment-method" onclick="selectPayment('card')">
                    <img src="https://cdn-icons-png.flaticon.com/512/179/179457.png" alt="Carte bancaire">
                    <h4>Carte bancaire</h4>
                    <p>Visa, Mastercard, CB</p>
                </div>
                <div class="payment-method" onclick="selectPayment('paypal')">
                    <img src="https://cdn-icons-png.flaticon.com/512/174/174861.png" alt="PayPal">
                    <h4>PayPal</h4>
                    <p>Paiement sécurisé</p>
                </div>
                <div class="payment-method" onclick="selectPayment('apple')">
                    <img src="https://cdn-icons-png.flaticon.com/512/731/731985.png" alt="Apple Pay">
                    <h4>Apple Pay</h4>
                    <p>Paiement rapide</p>
                </div>
            </div>
            
            <input type="hidden" name="payment_method" id="payment_method" required>
            <button type="submit" class="btn-pay" disabled id="pay-button">
                Sélectionnez un mode de paiement
            </button>
        </form>
        
        <div class="secure-info">
            🔒 Paiement sécurisé • Protection SSL • Données cryptées
        </div>
    </div>

    <script>
    function selectPayment(method) {
        // Réinitialiser tous les boutons
        document.querySelectorAll('.payment-method').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Sélectionner le mode de paiement
        const selectedMethod = document.querySelector(`.payment-method[onclick="selectPayment('${method}')"]`);
        if (selectedMethod) {
            selectedMethod.classList.add('selected');
        }
        
        // Mettre à jour le champ caché
        document.getElementById('payment_method').value = method;
        
        // Mettre à jour le bouton de paiement
        const payButton = document.getElementById('pay-button');
        payButton.disabled = false;
        payButton.innerHTML = `Payer ${method === 'card' ? 'par carte bancaire' : 
                              method === 'paypal' ? 'avec PayPal' : 
                              'avec Apple Pay'} - <?php echo number_format($total, 2, ',', ' '); ?> €`;
    }
    
    // Validation du formulaire avant soumission
    document.getElementById('payment-form').addEventListener('submit', function(e) {
        const method = document.getElementById('payment_method').value;
        if (!method) {
            e.preventDefault();
            alert('Veuillez sélectionner un mode de paiement.');
            return false;
        }
        
        // Désactiver le bouton pour éviter les doubles soumissions
        const payButton = document.getElementById('pay-button');
        payButton.disabled = true;
        payButton.innerHTML = `<span style="display: inline-block; animation: spin 1s infinite linear">↻</span> Traitement en cours...`;
    });
    
    // Animation de chargement
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>
