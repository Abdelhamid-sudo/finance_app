<?php
// register.php

// Connexion à la base de données
include(__DIR__ . '/../includes/db.php');

// Démarrer la session
session_start();

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);  // Hash du mot de passe

    // Insérer l'utilisateur dans la base de données
    $query = "INSERT INTO users (username, email, password_hash) VALUES ('$username', '$email', '$password')";
    if (mysqli_query($conn, $query)) {
        // Récupérer l'ID de l'utilisateur
        $user_id = mysqli_insert_id($conn);  // Récupère l'ID de l'utilisateur ajouté
        
        // Enregistrer l'ID de l'utilisateur dans la session
        $_SESSION['user_id'] = $user_id;

        // Rediriger vers le dashboard après une inscription réussie
        header('Location: dashboard.php');
        exit();
    } else {
        echo "Erreur : " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link rel="stylesheet" href="/FINANCE_APP/assets/css/style.css">
<script src="/finance_app/assets/js/script.js" defer></script>

</head>
<body>
    <div class="container">
        <h2>Inscription</h2>
        <form action="register.php" method="post">
            <label for="username">Nom d'utilisateur :</label>
            <input type="text" name="username" id="username" required><br>

            <label for="email">Email :</label>
            <input type="email" name="email" id="email" required><br>

            <label for="password">Mot de passe :</label>
            <input type="password" name="password" id="password" required><br>

            <button type="submit">S'inscrire</button>
        </form>
    </div>
</body>
</html>
