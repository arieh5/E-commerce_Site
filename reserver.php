<?php
session_start();
include 'config.php';

if (empty($_SESSION['cart'])) {
    header('Location: panier.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Début de la transaction
    $conn->begin_transaction();

    try {
        // Créer la facture
        $client = $conn->real_escape_string($_POST['nom']);
        $sql = "INSERT INTO Facture (client) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $client);
        $stmt->execute();
        $id_facture = $conn->insert_id;

        // Calculer le total
        $total = 0;
        foreach ($_SESSION['cart'] as $id_billet) {
            $sql = "SELECT prix FROM Billet WHERE id_billet = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id_billet);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $total += $row['prix'];
        }

        // Créer les détails de la facture
        $mode_paiement = $conn->real_escape_string($_POST['paiement']);
        $sql = "INSERT INTO Details_facture (billets_achetes, mode_de_paiement, montant_ttc, id_facture) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $nb_billets = count($_SESSION['cart']);
        $stmt->bind_param("isdi", $nb_billets, $mode_paiement, $total, $id_facture);
        $stmt->execute();

        // Valider la transaction
        $conn->commit();

        // Vider le panier
        $_SESSION['cart'] = [];
        
        // Rediriger vers une page de confirmation
        header('Location: index.php?success=1');
        exit;
    } catch (Exception $e) {
        // En cas d'erreur, annuler la transaction
        $conn->rollback();
        $error = "Une erreur est survenue lors de la réservation.";
    }
}

// Calculer le total du panier
$total = 0;
if (!empty($_SESSION['cart'])) {
    $placeholders = str_repeat('?,', count($_SESSION['cart']) - 1) . '?';
    $sql = "SELECT prix FROM Billet WHERE id_billet IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($_SESSION['cart'])), ...$_SESSION['cart']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $total += $row['prix'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réservation - Roland Garros 2025</title>
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
            max-width: 800px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            color: #1D5C42;
            margin-bottom: 30px;
            text-align: center;
            font-size: 28px;
        }
        .form-group {
            margin-bottom: 25px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #1D5C42;
            font-weight: 500;
        }
        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus {
            outline: none;
            border-color: #1D5C42;
        }
        .total {
            font-size: 24px;
            color: #1D5C42;
            text-align: right;
            margin: 30px 0;
            font-weight: bold;
        }
        .btn-submit {
            display: block;
            width: 100%;
            padding: 15px;
            background: #1D5C42;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .btn-submit:hover {
            background: #164632;
        }
        .error {
            color: #dc3545;
            padding: 12px;
            margin-bottom: 25px;
            border: 1px solid #dc3545;
            border-radius: 4px;
            background: #fff8f8;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <img src="images/logo.jpg" alt="Roland Garros Logo">
            <a href="index.php" class="logo-text">BILLETTERIE OFFICIELLE ROLAND-GARROS</a>
        </div>
        <div class="menu">
            <a href="index.php?page=billetterie">Billetterie</a>
            <a href="panier.php">Panier (<?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>)</a>
        </div>
    </div>

    <div class="container">
        <h2>Finaliser votre réservation</h2>
        
        <?php if (isset($error)) { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>

        <form method="post" action="reserver.php">
            <div class="form-group">
                <label for="nom">Nom complet</label>
                <input type="text" id="nom" name="nom" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="paiement">Mode de paiement</label>
                <select id="paiement" name="paiement" required>
                    <option value="">Choisir un mode de paiement</option>
                    <option value="Carte Bancaire">Carte Bancaire</option>
                    <option value="PayPal">PayPal</option>
                </select>
            </div>

            <div class="total">
                Total à payer : <?php echo $total; ?> €
            </div>

            <button type="submit" class="btn-submit">Confirmer la réservation</button>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>
