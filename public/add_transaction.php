<?php
// Ajouter la connexion à la base de données via mysqli
include(__DIR__ . '/../includes/db.php');

// Démarrer la session
session_start();
$id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification de la présence des clés attendues
    if (!isset($_POST['amount'], $_POST['type'], $_POST['category'])) {
        die("Erreur : Données manquantes.");
    }

    // Récupération des données du formulaire
    $amount = $_POST['amount'];
    $type = $_POST['type'];
    $category = $_POST['category'];

    // Vérification de validité des données
    if (!is_numeric($amount) || $amount <= 0) {
        die('Montant invalide.');
    }

    // Récupérer le solde actuel
    $query = "SELECT balance FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);  // Bind l'ID de l'utilisateur
    $stmt->execute();
    $result = $stmt->get_result();
    $current_balance = $result->fetch_assoc()['balance'];

    if ($current_balance === null) {
        die("Erreur : Impossible de récupérer le solde.");
    }

    // Calcul du nouveau solde
    if ($type === 'income') {
        $new_balance = $current_balance + $amount;
    } elseif ($type === 'expense') {
        if ($current_balance < $amount) {
            die("Erreur : Solde insuffisant pour cette dépense.");
        }
        $new_balance = $current_balance - $amount;
    } else {
        die("Erreur : Type de transaction invalide.");
    }

    // Début de la transaction
    $conn->begin_transaction();

    try {
        // Mise à jour du solde dans la base de données
        $update_query = "UPDATE users SET balance = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("di", $new_balance, $id);
        $update_stmt->execute();

        // Enregistrement de la transaction
        $insert_query = "INSERT INTO transactions (user_id, amount, type, category, date) VALUES (?, ?, ?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("idss", $id, $amount, $type, $category);
        $insert_stmt->execute();

        // Commit de la transaction
        $conn->commit();

        // Redirection après succès
        header("Location: dashboard.php?message=Transaction ajoutée avec succès");
        exit;

    } catch (Exception $e) {
        // Rollback en cas d'erreur
        $conn->rollback();
        die("Erreur : " . $e->getMessage());
    }
} else {
    // Rediriger si la méthode n'est pas POST
    header("Location: dashboard.php?message=Accès non autorisé");
    exit;
}
?>
