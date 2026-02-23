<?php
$servername = "localhost";
$username = "root";  // Remplace par ton utilisateur MySQL si nécessaire
$password = "";      // Remplace par ton mot de passe MySQL si nécessaire
$dbname = "mea_tennis";

// Connexion à la base de données
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Échec de la connexion : " . $conn->connect_error);
}
?>
