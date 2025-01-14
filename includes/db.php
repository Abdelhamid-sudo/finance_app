<?php
$servername = "localhost"; // ou l'adresse de ton serveur MySQL
$username = "root"; // ton nom d'utilisateur MySQL
$password = ""; // ton mot de passe MySQL (si nécessaire)
$dbname = "finance_app"; // le nom de ta base de données

// Créer la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connexion échouée : " . $conn->connect_error);
}
?>
