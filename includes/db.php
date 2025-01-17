<?php
// db.php - Connexion à la base de données
$servername = "localhost";
$username = "root"; // Nom d'utilisateur de votre base de données
$password = ""; // Mot de passe de votre base de données
$dbname = "finance_app"; // Nom de votre base de données

// Créer la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("La connexion a échoué : " . $conn->connect_error);
}
?>
