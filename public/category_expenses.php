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

// Récupérer les dépenses par catégorie, en excluant la catégorie "Initial Balance"
$query = $conn->prepare("
    SELECT category, SUM(amount) AS total_expenses 
    FROM transactions 
    WHERE user_id = ? 
    AND type = 'expense' 
    AND category != 'Initial Balance' 
    GROUP BY category 
    ORDER BY total_expenses DESC
");

if ($query === false) {
    die("Erreur dans la préparation de la requête : " . $conn->error);
}

$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

$categories = [];
$expenses_data = [];

if ($result === false) {
    die("Erreur dans l'exécution de la requête : " . $conn->error);
}

while ($row = $result->fetch_assoc()) {
    $categories[] = $row['category'];
    $expenses_data[] = $row['total_expenses'];
}

// Récupérer toutes les transactions pour l'utilisateur (pour la facture)
$invoice_query = $conn->prepare("
    SELECT date, category, amount 
    FROM transactions 
    WHERE user_id = ? 
    ORDER BY date DESC
");

if ($invoice_query === false) {
    die('Erreur dans la préparation de la requête : ' . $conn->error);
}

$invoice_query->bind_param("i", $user_id);
$invoice_query->execute();
$invoice_result = $invoice_query->get_result();

// Vérification si des résultats ont été récupérés
if ($invoice_result === false) {
    die("Erreur dans l'exécution de la requête : " . $conn->error);
}

if ($invoice_result->num_rows > 0) {
    $transactions = [];
    while ($row = $invoice_result->fetch_assoc()) {
        $transactions[] = $row;
    }
} else {
    $transactions = [];
}

// Générer la facture lorsque le formulaire est soumis
if (isset($_POST['generate_invoice'])) {
    // Vérifier s'il y a des transactions avant de générer la facture
    if (empty($transactions)) {
        echo "<p>Aucune transaction trouvée pour cet utilisateur.</p>";
    } else {
        // Générer le contenu de la facture au format HTML
        $invoice_html = '<h2>Facture des Transactions</h2>';
        $invoice_html .= '<table border="1" cellpadding="5">';
        $invoice_html .= '<thead><tr><th>Date</th><th>Catégorie</th><th>Montant</th></tr></thead>';
        $invoice_html .= '<tbody>';

        foreach ($transactions as $transaction) {
            $invoice_html .= '<tr>';
            $invoice_html .= '<td>' . $transaction['date'] . '</td>';
            $invoice_html .= '<td>' . $transaction['category'] . '</td>';
            $invoice_html .= '<td>' . number_format($transaction['amount'], 2, ',', ' ') . ' TND</td>';
            $invoice_html .= '</tr>';
        }

        $invoice_html .= '</tbody></table>';

        // Sauvegarder la facture sous forme de fichier HTML
        $invoice_filename = 'invoice_' . $user_id . '.html';
        file_put_contents($invoice_filename, $invoice_html);

        // Télécharger le fichier
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $invoice_filename . '"');
        readfile($invoice_filename);
        exit;
    }
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

        <!-- Bouton pour afficher la facture -->
        <form method="post" action="category_expenses.php">
            <button type="submit" name="generate_invoice" class="btn btn-primary">Télécharger la Facture</button>
        </form>

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
