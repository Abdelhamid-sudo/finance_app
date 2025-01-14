<?php
include_once 'db.php';

// Fonction pour inscrire un utilisateur
function registerUser($username, $email, $password) {
    global $conn;

    // Hash du mot de passe
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Préparer la requête SQL
    $sql = "INSERT INTO users (username, email, password_hash) VALUES ('$username', '$email', '$password_hash')";

    if ($conn->query($sql) === TRUE) {
        return "Inscription réussie!";
    } else {
        return "Erreur : " . $conn->error;
    }
}

// Fonction pour vérifier la connexion d'un utilisateur
function loginUser($email, $password) {
    global $conn;

    // Vérifier si l'email existe
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Vérifier le mot de passe
        if (password_verify($password, $user['password_hash'])) {
            return $user['id']; // Retourner l'ID de l'utilisateur
        } else {
            return "Mot de passe incorrect.";
        }
    } else {
        return "Utilisateur non trouvé.";
    }
}

// Fonction pour ajouter un revenu
function addIncome($user_id, $source, $amount) {
    global $conn;
    
    $sql = "INSERT INTO incomes (user_id, source, amount) VALUES ('$user_id', '$source', '$amount')";
    
    if ($conn->query($sql) === TRUE) {
        return "Revenu ajouté avec succès!";
    } else {
        return "Erreur : " . $conn->error;
    }
}

// Fonction pour ajouter une dépense
function addExpense($user_id, $category, $amount) {
    global $conn;
    
    $sql = "INSERT INTO expenses (user_id, category, amount) VALUES ('$user_id', '$category', '$amount')";
    
    if ($conn->query($sql) === TRUE) {
        return "Dépense ajoutée avec succès!";
    } else {
        return "Erreur : " . $conn->error;
    }
}
?>
