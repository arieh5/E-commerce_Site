<?php
session_start();
include 'config.php';

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM Utilisateur WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id_utilisateur'];
            $_SESSION['user_name'] = $user['nom'];
            
            // Redirection après la connexion
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header("Location: " . $redirect);
                exit;
            } else {
                // Si pas de redirection spécifique, retour à la page précédente
                header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php'));
                exit;
            }
        }
    }
    $error = "Email ou mot de passe incorrect";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Roland Garros</title>
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
        .login-container {
            max-width: 400px;
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
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: #1D5C42;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .btn-login:hover {
            background: #164632;
        }
        .error-message {
            color: #dc3545;
            margin-bottom: 20px;
            text-align: center;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .register-link a {
            color: #1D5C42;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
        .secure-info {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
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

    <div class="login-container">
        <h2 style="text-align: center; color: #1D5C42; margin-bottom: 30px;">Connexion</h2>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Se connecter</button>
        </form>
        
        <div class="register-link">
            Pas encore de compte ? <a href="register.php">Créer un compte</a>
        </div>
        
        <div class="secure-info">
            🔒 Connexion sécurisée • Billetterie officielle Roland Garros
        </div>
    </div>
</body>
</html>
