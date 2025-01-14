<?php
// category_expenses.php

include(__DIR__ . '/../includes/db.php');
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer les dépenses par catégorie
$query = $conn->prepare("SELECT category, SUM(amount) AS total_expenses FROM transactions WHERE user_id = ? AND type = 'expense' GROUP BY category ORDER BY total_expenses DESC");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

$categories = [];
$expenses_data = [];

while ($row = $result->fetch_assoc()) {
    $categories[] = $row['category'];
    $expenses_data[] = $row['total_expenses'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dépenses par catégorie</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <h2>Suivi des Dépenses par Catégorie</h2>

        <!-- Affichage du graphique des dépenses par catégorie -->
        <canvas id="categoryExpensesChart"></canvas>

        <script>
            var ctx = document.getElementById('categoryExpensesChart').getContext('2d');
            var categoryExpensesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($categories); ?>,
                    datasets: [{
                        label: 'Dépenses par Catégorie',
                        data: <?php echo json_encode($expenses_data); ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>
    </div>
</body>
</html>
