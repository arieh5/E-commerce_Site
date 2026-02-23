<?php
session_start();
include 'config.php';
include 'cart.php';

// Vérifier si la billetterie doit être affichée
$afficher_billetterie = isset($_GET['page']) && $_GET['page'] == 'billetterie';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billetterie Roland Garros 2025</title>
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
        .banner {
            width: 100%;
            height: 900px;
            background: url('logo.jpg') no-repeat center center/cover;
            margin-bottom: 40px;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: auto;
            padding: 20px;
        }
        h2 {
            color: #1D5C42;
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
        }
        .matches {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .match-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .match-card h3 {
            color: #1D5C42;
            margin: 0 0 15px 0;
            font-size: 18px;
        }
        .match-info {
            color: #666;
            margin: 10px 0;
            font-size: 14px;
        }
        .match-info p {
            margin: 5px 0;
        }
        .price {
            font-size: 20px;
            color: #1D5C42;
            font-weight: bold;
            margin: 15px 0;
        }
        .btn-add-cart {
            display: inline-block;
            padding: 8px 20px;
            background: #1D5C42;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.3s ease;
            font-size: 14px;
            width: 100%;
            box-sizing: border-box;
        }
        .btn-add-cart:hover {
            background: #164632;
        }
        .btn-add-cart:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }
        .cart-count {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            margin-left: 5px;
        }
        .warning-message {
            background-color: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
            border: 1px solid #ffeeba;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            animation: fadeIn 0.5s ease-in-out;
        }
        .warning-message p {
            margin: 0;
            line-height: 1.5;
        }
        .warning-message .warning-icon {
            font-size: 24px;
            margin-bottom: 10px;
            display: block;
        }
        .warning-message .warning-subtitle {
            color: #666;
            font-size: 14px;
            margin-top: 8px;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .timer {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #1D5C42;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            display: none;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
            font-size: 16px;
            font-weight: 500;
        }
        .timer.show {
            display: block;
        }
        .timer.urgent {
            background-color: #dc3545;
            animation: pulse 1s infinite;
        }
        .timer #time {
            font-weight: bold;
            font-size: 18px;
            margin-left: 5px;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .toast {
            position: fixed;
            bottom: 80px;
            right: 20px;
            background-color: #1D5C42;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
            font-size: 16px;
            font-weight: 500;
        }
    </style>
    <script>
        // Vérifier si un timer est déjà en cours au chargement de la page
        window.onload = function() {
            // Vérifier s'il y a des articles dans le panier
            const cartCount = document.querySelector('.menu a:last-child');
            const count = parseInt(cartCount.textContent.match(/\d+/)[0]);
            if (count > 0) {
                startTimer(15 * 60);
            }
        }

        function startTimer(duration) {
            const timer = document.getElementById('timer');
            if (!timer.classList.contains('show')) {
                timer.classList.add('show');
                
                let timeLeft = duration;
                const countdown = setInterval(() => {
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    
                    document.getElementById('time').textContent = 
                        minutes.toString().padStart(2, '0') + ':' + 
                        seconds.toString().padStart(2, '0');
                    
                    // Ajouter l'animation d'urgence quand il reste moins de 5 minutes
                    if (timeLeft <= 300) {
                        timer.classList.add('urgent');
                    }
                    
                    if (--timeLeft < 0) {
                        clearInterval(countdown);
                        timer.classList.remove('show');
                        // Rediriger vers la page du panier pour vider le panier
                        window.location.href = 'cart.php?clear=1';
                    }
                }, 1000);
            }
        }
        
        function addToCart(id_billet, id_match) {
            const button = event.target;
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('id_billet', id_billet);
            
            // Désactiver le bouton immédiatement pour éviter les doubles clics
            button.disabled = true;
            button.textContent = 'Ajout en cours...';
            
            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'affichage du panier
                    const cartCount = document.querySelector('.menu a:last-child');
                    cartCount.textContent = `Panier (${data.count})`;
                    
                    // Mettre à jour le bouton
                    button.textContent = 'Déjà dans le panier';
                    
                    // Démarrer le timer de 15 minutes
                    startTimer(15 * 60);
                    
                    // Afficher un message de confirmation
                    const toast = document.createElement('div');
                    toast.className = 'toast';
                    toast.innerHTML = '✅ Billet ajouté au panier';
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 3000);
                } else {
                    // En cas d'erreur, réactiver le bouton
                    button.disabled = false;
                    button.textContent = 'Ajouter au panier';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                // En cas d'erreur, réactiver le bouton
                button.disabled = false;
                button.textContent = 'Ajouter au panier';
            });
        }
    </script>
</head>
<body>
    <div class="header">
        <div class="logo">
            <img src="logo2.jpeg" alt="Roland Garros Logo">
            <a href="index.php" class="logo-text">BILLETTERIE ROLAND-GARROS</a>
        </div>
        <div class="menu">
            <a href="index.php?page=billetterie">Billetterie</a>
            <a href="panier.php">Panier (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
        </div>
    </div>

    <?php if (!$afficher_billetterie) { ?>
        <div class="banner">
            <div>
            </div>
        </div>
    <?php } else { 
        // Vérifier si l'utilisateur est connecté et a déjà effectué une commande
        $hasOrder = false;
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
            $orderCheck = $conn->prepare("SELECT COUNT(*) as order_count FROM Commande WHERE id_utilisateur = ?");
            $orderCheck->bind_param("i", $userId);
            $orderCheck->execute();
            $result = $orderCheck->get_result();
            $row = $result->fetch_assoc();
            $hasOrder = $row['order_count'] > 0;
        }
    ?>
        <div class="container">
            <?php if ($hasOrder): ?>
            <div class="warning-message" style="background-color: #f8d7da; border-color: #f5c6cb; color: #721c24;">
                <span class="warning-icon">❌</span>
                <p>
                    Vous avez déjà effectué une commande pour ce tournoi.
                </p>
                <p class="warning-subtitle">
                    Pour garantir l'équité d'accès aux matchs, une seule commande est autorisée par utilisateur.
                </p>
            </div>
            <?php else: ?>
            <div class="warning-message">
                <span class="warning-icon">⚠️</span>
                <p>
                    Pour garantir l'équité d'accès aux matchs, une seule commande est autorisée par utilisateur pour l'ensemble du tournoi.
                </p>
                <p class="warning-subtitle">
                    Choisissez vos billets avec soin, vous ne pourrez pas effectuer d'autres achats après votre commande.
                </p>
            </div>
            <div class="warning-message" style="background-color: #cce5ff; border-color: #b8daff; color: #004085;">
                <span class="warning-icon">ℹ️</span>
                <p>
                    Les billets sélectionnés sont réservés pendant 15 minutes dans votre panier.
                </p>
                <p class="warning-subtitle">
                    Après ce délai, ils seront automatiquement remis en vente si le paiement n'est pas effectué.
                </p>
            </div>
            <?php endif; ?>
            <h2>Matchs et Billets Disponibles</h2>
            <div class="matches">
                <?php
                $sql = "SELECT b.id_billet, b.prix, b.categorie, b.vendu, m.heure, m.nom_court, m.type_de_match, m.place_dans_le_tournoi,
                        GROUP_CONCAT(CONCAT(j.prenom, ' ', j.nom) SEPARATOR ' vs ') as joueurs
                        FROM Billet b
                        INNER JOIN Matchs m ON b.id_match = m.id_match
                        INNER JOIN Joueur_Match jm ON m.id_match = jm.id_match
                        INNER JOIN Joueurs j ON jm.id_joueur = j.id_joueur
                        WHERE b.vendu = 0
                        GROUP BY b.id_billet, b.prix, b.categorie, b.vendu, m.heure, m.nom_court, m.type_de_match, m.place_dans_le_tournoi
                        ORDER BY m.place_dans_le_tournoi DESC";
                
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $in_cart = isset($_SESSION['cart']) && in_array($row["id_billet"], $_SESSION['cart']);
                        ?>
                        <div class="match-card">
                            <h3><?php echo $row["place_dans_le_tournoi"]; ?></h3>
                            <div class="match-info">
                                <p><strong><?php echo $row["joueurs"]; ?></strong></p>
                                <p>Court : <?php echo $row["nom_court"]; ?></p>
                                <p>Heure : <?php echo $row["heure"]; ?></p>
                                <p>Type : <?php echo $row["type_de_match"]; ?></p>
                                <p>Catégorie : <?php echo $row["categorie"]; ?></p>
                                <p class="price"><?php echo number_format($row["prix"], 2, ',', ' '); ?> €</p>
                            </div>
                            <?php if ($row['vendu']): ?>
                                <button class="btn-add-cart" disabled style="background-color: #dc3545;">
                                    Billet vendu
                                </button>
                            <?php elseif ($hasOrder): ?>
                                <button class="btn-add-cart" disabled style="background-color: #6c757d;">
                                    Non disponible
                                </button>
                            <?php else: ?>
                                <button class="btn-add-cart" 
                                        onclick="addToCart(<?php echo $row['id_billet']; ?>, <?php echo $row['id_match']; ?>)"
                                        <?php echo $in_cart ? 'disabled' : ''; ?>>
                                    <?php echo $in_cart ? 'Déjà dans le panier' : 'Ajouter au panier'; ?>
                                </button>
                            <?php endif; ?>
                            </div>
                        </div>
                    <?php }
                } else {
                    echo "<p>Aucun match disponible.</p>";
                }
                ?>
            </div>
        </div>
    <?php } ?>

    <script>
    function updateCartCount(count) {
        const cartLinks = document.querySelectorAll('a[href="panier.php"]');
        cartLinks.forEach(link => {
            link.textContent = `Panier (${count})`;
        });
    }

    function addToCart(id_billet, id_match) {
        const button = event.target;
        fetch('cart.php?add=' + id_billet)
            .then(response => response.text())
            .then(data => {
                // Mettre à jour l'affichage du panier
                const cartCount = document.querySelector('.menu a:last-child');
                const currentCount = parseInt(cartCount.textContent.match(/\d+/)[0]);
                cartCount.textContent = `Panier (${currentCount + 1})`;
                
                // Désactiver le bouton
                button.disabled = true;
                button.textContent = 'Déjà dans le panier';
                
                // Démarrer le timer de 15 minutes
                startTimer(15 * 60);
            });
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=add&id_billet=' + id_billet + '&id_match=' + id_match
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartCount(data.count);
                button.disabled = true;
                button.textContent = 'Déjà dans le panier';
            }
        });
    }
    </script>
    <div id="timer" class="timer">
        🕑 Votre réservation expire dans <span id="time">15:00</span>
    </div>
</body>
</html>
<?php $conn->close(); ?>
