<?php
session_start();
include 'config.php';

if (isset($_POST['email']) && isset($_POST['password']) && isset($_POST['nom'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nom = $conn->real_escape_string($_POST['nom']);
    
    // Vérifier si l'email existe déjà
    $check = $conn->prepare("SELECT id_utilisateur FROM Utilisateur WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $error = "Cette adresse email est déjà utilisée";
    } else {
        $sql = "INSERT INTO Utilisateur (nom, email, mot_de_passe) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $nom, $email, $password);
        
        if ($stmt->execute()) {
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['user_name'] = $nom;
            
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: " . $redirect);
                exit;
            }
            header("Location: payment.php");
            exit;
        } else {
            $error = "Une erreur est survenue lors de l'inscription";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Roland Garros</title>
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
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        input:focus {
            border-color: #1D5C42;
            outline: none;
        }
        .btn-register {
            width: 100%;
            padding: 14px;
            background: #1D5C42;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            background: #164632;
            transform: translateY(-1px);
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 20px;
            text-align: center;
            padding: 10px;
            background: #ffe5e5;
            border-radius: 4px;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .login-link a {
            color: #1D5C42;
            text-decoration: none;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .benefits {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .benefits h3 {
            color: #1D5C42;
            margin-top: 0;
        }
        .benefits ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .benefits li {
            margin: 10px 0;
            padding-left: 25px;
            position: relative;
        }
        .benefits li:before {
            content: "✓";
            color: #1D5C42;
            position: absolute;
            left: 0;
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

    <div class="register-container">
        <h2 style="text-align: center; color: #1D5C42; margin-bottom: 30px;">Créer un compte</h2>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="nom">Nom complet</label>
                <input type="text" id="nom" name="nom" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
            
            <button type="submit" class="btn-register">Créer mon compte</button>
        </form>
        
        <div class="login-link">
            Déjà un compte ? <a href="login.php">Se connecter</a>
        </div>
        
        <div class="benefits">
            <h3>Avantages de votre compte Roland Garros</h3>
            <ul>
                <li>Accès prioritaire à la billetterie</li>
                <li>Historique de vos commandes</li>
                <li>Alertes pour les matchs importants</li>
                <li>Offres exclusives pour les membres</li>
            </ul>
        </div>
    </div>
</body>
</html>
