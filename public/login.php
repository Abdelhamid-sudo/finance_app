<?php
// login.php

// Connexion à la base de données
include(__DIR__ . '/../includes/db.php');

// Démarrer la session
session_start();

// Définir la page pour le CSS dynamique
$page = 'login'; // Vous pouvez adapter cette valeur si nécessaire
$css_file = "assets/css/style.css";

// Traitement du formulaire de connexion
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Récupérer les valeurs du formulaire de manière sécurisée
    $email = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8');
    $password = $_POST['password'];

    // Utiliser une requête préparée pour sécuriser la base de données
    $query = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();

    // Vérifier si l'utilisateur a été trouvé et si le mot de passe est correct
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Vérification du mot de passe
        if (password_verify($password, $user['password_hash'])) {
            // Authentification réussie
            $_SESSION['user_id'] = $user['id'];

            // Redirection vers le tableau de bord
            header('Location: dashboard.php');
            exit();
        } else {
            $error_message = "Email ou mot de passe incorrect.";
        }
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
    <link rel="stylesheet" href="/finance_app/assets/css/style.css">
    <script src="/finance_app/assets/js/script.js" defer></script>
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

    <?php if (!empty($error_message)) echo "<p style='color: red;'>$error_message</p>"; ?>
</body>
</html>
