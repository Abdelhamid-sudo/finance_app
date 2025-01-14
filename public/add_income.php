<?php
session_start();
include_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo "Vous devez être connecté pour ajouter un revenu.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $source = $_POST['source'];
    $amount = $_POST['amount'];

    $message = addIncome($user_id, $source, $amount);
    echo $message;
}
?>

<form method="post" action="add_income.php">
    <input type="text" name="source" placeholder="Source de revenu" required>
    <input type="number" name="amount" placeholder="Montant" required>
    <button type="submit">Ajouter un revenu</button>
</form>
