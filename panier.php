<?php
session_start();
include 'config.php';
include 'cart.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier - MEA Tennis 2025</title>
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
        .menu {
            display: flex;
            gap: 30px;
        }
        .menu a {
            text-decoration: none;
            color: #1D5C42;
            font-weight: 500;
            font-size: 16px;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .menu a:hover {
            background-color: #f0f0f0;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            color: #1D5C42;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .cart-item {
            display: grid;
            grid-template-columns: 2fr 1fr auto;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            gap: 20px;
        }
        .match-info {
            color: #666;
        }
        .match-info h3 {
            margin: 0 0 10px 0;
            color: #1D5C42;
            font-size: 18px;
        }
        .match-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .price {
            font-weight: bold;
            color: #1D5C42;
            font-size: 18px;
            text-align: right;
        }
        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s ease;
            font-size: 14px;
        }
        .btn-remove:hover {
            background: #c82333;
        }
        .cart-total {
            margin-top: 30px;
            text-align: right;
            padding: 30px;
            border-top: 1px solid #eee;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .cart-total h3 {
            color: #1D5C42;
            margin-bottom: 20px;
            font-size: 24px;
        }
        .btn-checkout {
            display: inline-block;
            padding: 12px 25px;
            background: #1D5C42;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-checkout:hover {
            background: #164632;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .btn-checkout:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .empty-cart {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        .empty-cart h3 {
            color: #1D5C42;
            margin-bottom: 15px;
        }
        .empty-cart a {
            display: inline-block;
            margin-top: 20px;
            color: #1D5C42;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 20px;
            border: 2px solid #1D5C42;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .empty-cart a:hover {
            background: #1D5C42;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <img src="logo2.jpeg" alt="MEA Tennis Logo">
            <a href="index.php" class="logo-text">BILLETTERIE ROLAND GARROS </a>
        </div>
        <div class="menu">
            <a href="index.php?page=billetterie">Billetterie</a>
            <a href="panier.php">Panier (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']): 0; ?>)</a>
        </div>
    </div>

    <div class="container">
        <h2>Votre Panier</h2>
        
        <?php 
        // Afficher les messages d'erreur
        if (isset($_SESSION['error']) || isset($_SESSION['payment_error'])): 
            $errorMessage = isset($_SESSION['payment_error']) ? $_SESSION['payment_error'] : $_SESSION['error'];
            unset($_SESSION['error'], $_SESSION['payment_error']);
        ?>
            <div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; justify-content: center;">
                    <span style="font-size: 24px; margin-right: 10px;">⚠️</span>
                    <p style="margin: 0; font-size: 16px;"><?php echo htmlspecialchars($errorMessage); ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php
        // Initialiser les variables
        $subtotal = 0;
        $serviceFee = 4.00;
        
        // Vérifier si des billets dans le panier ont été vendus
        if (!empty($_SESSION['cart'])) {
            $placeholders = str_repeat('?,', count($_SESSION['cart']) - 1) . '?';
            $checkSql = "SELECT id_billet FROM Billet WHERE id_billet IN ($placeholders) AND vendu = 1";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param(str_repeat('i', count($_SESSION['cart'])), ...$_SESSION['cart']);
            $checkStmt->execute();
            $soldTickets = $checkStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            if (!empty($soldTickets)) {
                $soldIds = array_column($soldTickets, 'id_billet');
                $_SESSION['cart'] = array_values(array_diff($_SESSION['cart'], $soldIds));
                
                echo '<div style="background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px;">';
                echo '<div style="display: flex; align-items: center; justify-content: center;">';
                echo '<span style="font-size: 24px; margin-right: 10px;">ℹ️</span>';
                echo '<p style="margin: 0; font-size: 16px;">Certains billets de votre panier ont été vendus et ont été retirés automatiquement.</p>';
                echo '</div></div>';
            }
        }
        
        <?php
        if (!empty($_SESSION['cart'])) {
            // Préparer les placeholders pour les requêtes SQL
            $placeholders = str_repeat('?,', count($_SESSION['cart']) - 1) . '?';
            
            // Récupérer les informations des billets
            $sql = "SELECT b.id_billet, b.prix, b.categorie, m.heure, m.nom_court, m.type_de_match, m.place_dans_le_tournoi,
                    GROUP_CONCAT(CONCAT(j.prenom, ' ', j.nom) SEPARATOR ' vs ') as joueurs
                    FROM Billet b
                    INNER JOIN Matchs m ON b.id_match = m.id_match
                    INNER JOIN Joueur_Match jm ON m.id_match = jm.id_match
                    INNER JOIN Joueurs j ON jm.id_joueur = j.id_joueur
                    WHERE b.id_billet IN ($placeholders)
                    GROUP BY b.id_billet, b.prix, b.categorie, m.heure, m.nom_court, m.type_de_match, m.place_dans_le_tournoi";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(str_repeat('i', count($_SESSION['cart'])), ...$_SESSION['cart']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $subtotal = 0;
            $serviceFee = 4.00; // Frais de service fixes de 4 euros
            
            while ($row = $result->fetch_assoc()) {
                $subtotal += $row['prix'];
                ?>
                <div class="cart-item">
                    <div class="match-info">
                        <h3><?php echo $row['place_dans_le_tournoi']; ?></h3>
                        <p><strong><?php echo $row['joueurs']; ?></strong></p>
                        <p>Court : <?php echo $row['nom_court']; ?></p>
                        <p>Heure : <?php echo $row['heure']; ?></p>
                        <p>Type : <?php echo $row['type_de_match']; ?></p>
                        <p>Catégorie : <?php echo $row['categorie']; ?></p>
                    </div>
                    <div class="price"><?php echo number_format($row['prix'], 2, ',', ' '); ?> €</div>
                    <form method="post" action="cart.php" style="margin: 0;">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="id_billet" value="<?php echo $row['id_billet']; ?>">
                        <button type="submit" class="btn-remove">Supprimer</button>
                    </form>
                </div>
                <?php
            }
            ?>
            
            <div class="cart-total">
                <div style="text-align: right; margin-bottom: 20px;">
                    <div style="margin-bottom: 10px; color: #666;">
                        <span style="display: inline-block; width: 150px; text-align: left;">Sous-total :</span>
                        <span style="font-weight: bold;"><?php echo number_format($subtotal, 2, ',', ' '); ?> €</span>
                    </div>
                    <div style="margin-bottom: 10px; color: #666;">
                        <span style="display: inline-block; width: 150px; text-align: left;">Frais de service :</span>
                        <span style="font-weight: bold;"><?php echo number_format($serviceFee, 2, ',', ' '); ?> €</span>
                    </div>
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                        <span style="display: inline-block; width: 150px; text-align: left; font-size: 18px; color: #1D5C42;">Total :</span>
                        <span style="font-weight: bold; font-size: 24px; color: #1D5C42;"><?php echo number_format($subtotal + $serviceFee, 2, ',', ' '); ?> €</span>
                    </div>
                </div>
                <div style="text-align: center; margin: 20px 0;">
                    <p style="color: #1D5C42; font-size: 16px; font-weight: bold; margin-bottom: 10px;">🟢 Garantie Roland Garros Officiel</p>
                    <p style="color: #666; font-size: 14px; margin: 5px 0;">🔒 Paiement sécurisé • 📧 Billets envoyés par email • 📞 Service client 7j/7</p>
                    <p style="color: #666; font-size: 14px; margin: 5px 0;">✅ Places garanties côte à côte • 🔄 Remboursement garanti si annulation</p>
                    <p style="color: #dc3545; font-size: 14px; margin: 15px 0 5px 0;">⏳ Les billets sont réservés pendant 15 minutes. Finalisez votre commande pour garantir vos places.</p>
                </div>
                <form method="post" action="cart.php" style="display: inline-block;" id="checkout-form">
                    <input type="hidden" name="action" value="checkout">
                    <button type="submit" class="btn-checkout" id="checkout-button" style="font-size: 18px; padding: 15px 30px;">
                        <span style="display: block; font-size: 20px;">Procéder au paiement</span>
                        <span style="display: block; font-size: 14px; margin-top: 5px;">Total : <?php echo number_format($subtotal + $serviceFee, 2, ',', ' '); ?> €</span>
                    </button>
                </form>
                
                <script>
                document.getElementById('checkout-form').addEventListener('submit', function(e) {
                    const button = document.getElementById('checkout-button');
                    button.disabled = true;
                    button.innerHTML = `
                        <span style="display: block; font-size: 20px;">
                            <span style="display: inline-block; animation: spin 1s infinite linear">↻</span> 
                            Redirection vers le paiement...
                        </span>
                        <span style="display: block; font-size: 14px; margin-top: 5px;">
                            Total : <?php echo number_format($subtotal + $serviceFee, 2, ',', ' '); ?> €
                        </span>
                    `;
                });
                
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes spin {
                        from { transform: rotate(0deg); }
                        to { transform: rotate(360deg); }
                    }
                `;
                document.head.appendChild(style);
                </script>
                <button onclick="confirmClearCart()" class="btn-remove" style="margin-left: 10px; vertical-align: top; background: none; border: 2px solid #dc3545; color: #dc3545;">
                    <span style="display: block;">Vider le panier</span>
                    <span style="display: block; font-size: 12px; margin-top: 3px;"><?php echo count($_SESSION['cart']); ?> billets</span>
                </button>

                <script>
                function confirmClearCart() {
                    if (confirm('Êtes-vous sûr de vouloir vider votre panier ? Cette action est irréversible.')) {
                        const button = event.target.closest('button');
                        button.disabled = true;
                        button.innerHTML = `
                            <span style="display: block;">
                                <span style="display: inline-block; animation: spin 1s infinite linear">↻</span>
                                Suppression...
                            </span>
                        `;
                        window.location.href = 'cart.php?clear=1';
                    }
                }
                </script>
            </div>
            
            <?php
        } else {
            ?>
            <div class="empty-cart">
                <h3>Votre panier est vide</h3>
                <p>Découvrez nos matchs disponibles et réservez vos places !</p>
                <a href="index.php?page=billetterie">Voir les matchs disponibles</a>
            </div>
            <?php
        }
        ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>
