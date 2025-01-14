<?php
// login.php

// Connexion à la base de données
include(__DIR__ . '/../includes/db.php');

// Démarrer la session
session_start();

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Vérifier si l'utilisateur existe dans la base de données
    $query = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $query);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password_hash'])) {
        // Authentification réussie
        // Enregistrer l'ID de l'utilisateur dans la session
        $_SESSION['user_id'] = $user['id']; // Assurez-vous que la colonne 'id' existe dans la table 'users'

        // Rediriger vers le dashboard après la connexion
        header('Location: dashboard.php');
        exit();
    } else {
        $error_message = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <h2>Connexion</h2>
    <form action="login.php" method="post">
        <label for="email">Email :</label>
        <input type="email" name="email" required><br>

        <label for="password">Mot de passe :</label>
        <input type="password" name="password" required><br>

        <button type="submit">Se connecter</button>
    </form>
    <?php if (isset($error_message)) echo "<p>$error_message</p>"; ?>
</body>
</html>
