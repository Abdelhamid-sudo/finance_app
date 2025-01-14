<?php
// dashboard.php

include(__DIR__ . '/../includes/db.php');
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer toutes les catégories pour la liste déroulante
$query = $conn->prepare("SELECT DISTINCT category FROM transactions WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row['category'];
}

// Vérifier si une transaction existe pour l'utilisateur
$query = $conn->prepare("SELECT COUNT(*) AS count FROM transactions WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$row = $result->fetch_assoc();
$transaction_count = $row['count'];

if ($transaction_count == 0) {
    // Si aucune transaction n'est trouvée
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['initial_balance'])) {
        $initial_balance = $_POST['initial_balance'];
        $date = date('Y-m-d');
        
        $query = $conn->prepare("INSERT INTO transactions (user_id, amount, type, category, date) VALUES (?, ?, 'income', 'Initial Balance', ?)");
        $query->bind_param("ids", $user_id, $initial_balance, $date);
        if ($query->execute()) {
            header('Location: dashboard.php');
            exit;
        } else {
            $message = "Erreur lors de l'ajout du solde initial.";
        }
    }
} else {
    // Calculer le solde actuel
    $query = $conn->prepare("SELECT SUM(amount) AS total_income FROM transactions WHERE user_id = ? AND type = 'income'");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_assoc();
    $total_income = $row['total_income'];

    $query = $conn->prepare("SELECT SUM(amount) AS total_expenses FROM transactions WHERE user_id = ? AND type = 'expense'");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_assoc();
    $total_expenses = $row['total_expenses'];

    $balance = $total_income - $total_expenses;

    // Récupérer les données pour les graphiques
    $query = $conn->prepare("
        SELECT DATE_FORMAT(date, '%Y-%m') AS month, category, SUM(amount) AS total 
        FROM transactions 
        WHERE user_id = ? 
        GROUP BY month, category 
        ORDER BY month DESC
    ");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();

    $months = [];
    $categories_graph = [];
    $graph_data = [];

    while ($row = $result->fetch_assoc()) {
        $months[$row['month']] = $row['month'];
        $categories_graph[$row['category']] = $row['category'];
        $graph_data[$row['month']][$row['category']] = $row['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <h2>Tableau de bord</h2>
        <p>Bienvenue, utilisateur #<?php echo htmlspecialchars($user_id); ?></p>

        <?php if ($transaction_count == 0): ?>
            <h3>Définir votre solde initial :</h3>
            <form action="dashboard.php" method="POST">
                <label for="initial_balance">Montant initial :</label>
                <input type="number" name="initial_balance" step="0.01" required><br><br>
                <button type="submit">Ajouter le solde initial</button>
            </form>
        <?php else: ?>
            <h3>Solde actuel : <?php echo number_format($balance, 2); ?> €</h3>

            <!-- Graphique -->
            <h3>Évolution du solde par catégorie</h3>
            <canvas id="balanceChart"></canvas>
            <script>
                const ctx = document.getElementById('balanceChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode(array_values($months)); ?>,
                        datasets: <?php 
                            $datasets = [];
                            foreach ($categories_graph as $category) {
                                $datasets[] = [
                                    'label' => $category,
                                    'data' => array_map(function ($month) use ($graph_data, $category) {
                                        return $graph_data[$month][$category] ?? 0;
                                    }, array_keys($months)),
                                    'borderColor' => '#' . substr(md5($category), 0, 6),
                                    'fill' => false,
                                ];
                            }
                            echo json_encode($datasets);
                        ?>
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            </script>

            <!-- Formulaire -->
            <h3>Ajouter une transaction :</h3>
            <form action="add_transaction.php" method="POST">
                <label for="amount">Montant :</label>
                <input type="number" name="amount" step="0.01" required><br><br>

                <label for="type">Type de transaction :</label>
                <select name="type" required>
                    <option value="income">Revenu</option>
                    <option value="expense">Dépense</option>
                </select><br><br>

                <label for="category">Catégorie :</label>
                <select name="category" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                    <?php endforeach; ?>
                </select><br><br>

                <button type="submit">Ajouter la transaction</button>
            </form>

            <!-- Bouton vers la page des graphiques -->
            <form action="category_expenses.php" method="GET">
                <button type="submit">Voir les graphiques détaillés</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
