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

// Récupérer toutes les catégories de la table categories
$query = $conn->prepare("SELECT name FROM categories");
$query->execute();
$result = $query->get_result();
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row['name'];
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
            $query1 = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
            $query1->bind_param("di", $initial_balance, $user_id);
            if ($query1->execute()) {
                header('Location: dashboard.php');
                exit;
            } else {
                $message = "Erreur lors de l'ajout du solde initial.";
            }
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
    <style>
        /* assets/css/styles.css */

/* assets/css/styles.css */

/* Style global */
body {
    font-family: 'Roboto', sans-serif;
    background-color: #f4f7f9;
    color: #333;
    margin: 0;
    padding: 0;
}

/* Diviser la page en sections */
.container {
    width: 85%;
    max-width: 900px;
    margin: 50px auto;
    background-color: #ffffff;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    padding: 30px;
    border-radius: 15px;
}

/* Titres */
h2 {
    font-family: 'Lora', serif;
    font-size: 2.5rem;
    color: #4caf50;
    text-align: center;
    margin-bottom: 25px;
    text-transform: uppercase;
}

h3 {
    font-size: 1.75rem;
    color: #555;
    margin-top: 25px;
    font-family: 'Merriweather', serif;
}

/* Formulaire */
form {
    margin-top: 30px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    background-color: #fafafa;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

label {
    font-size: 1.1rem;
    font-weight: bold;
    color: #666;
    font-family: 'Arial', sans-serif;
}

input[type="number"], select {
    padding: 12px;
    border: 2px solid #ccc;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

input[type="number"]:focus, select:focus {
    border-color: #4caf50;
}

button {
    padding: 12px 20px;
    background-color: #4caf50;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    cursor: pointer;
    transition: background-color 0.3s ease;
    font-family: 'Roboto', sans-serif;
    width: 100%;
    text-align: center;
}

button:hover {
    background-color: #45a049;
}

/* Boutons secondaires */
button[type="submit"] {
    background-color: #3498db;
}

button[type="submit"]:hover {
    background-color: #2980b9;
}

/* Graphiques */
canvas {
    margin-top: 30px;
    max-width: 100%;
    border-radius: 10px;
    background-color: #f9fafc;
    padding: 20px;
}

/* Messages */
p {
    font-size: 1.1rem;
    color: #444;
    margin-top: 15px;
    text-align: center;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 20px;
    }

    h2 {
        font-size: 2rem;
    }

    h3 {
        font-size: 1.5rem;
    }

    input[type="number"], select, button {
        font-size: 1rem;
    }

    form {
        padding: 15px;
    }
}

</style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function toggleCategoryField() {
            const transactionType = document.getElementById('transactionType').value;
            const categoryField = document.getElementById('categoryField');
            if (transactionType === 'income') {
                categoryField.style.display = 'none';
            } else {
                categoryField.style.display = 'block';
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h2>Tableau de bord</h2>
        <p>Bienvenue, utilisateur #<?php echo htmlspecialchars($user_id); ?></p>

        <?php if ($transaction_count == 0): ?>
            <h3>Définir votre solde initial :</h3>
            <form action="dashboard.php" method="POST">
                <label for="initial_balance">Montant initial :</label>
                <input type="number" name="initial_balance" step="0.01" required>
                <button type="submit">Ajouter le solde initial</button>
            </form>
        <?php else: ?>
            <h3>Solde actuel : <?php echo number_format($balance, 2); ?> €</h3>
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
            <h3>Ajouter une transaction :</h3>
            <form action="add_transaction.php" method="POST">
                <label for="amount">Montant :</label>
                <input type="number" name="amount" step="0.01" required>
                <label for="type">Type de transaction :</label>
                <select name="type" id="transactionType" onchange="toggleCategoryField()" required>
                    <option value="income">Revenu</option>
                    <option value="expense">Dépense</option>
                </select>
                <div id="categoryField">
                    <label for="category">Catégorie :</label>
                    <select name="category" required>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Ajouter la transaction</button>
            </form>
            <h3>Historique des transactions :</h3>
            <a href="transaction_history.php"><button>Voir l'historique</button></a>
            <h3>Visualisation des graphiques des dépenses :</h3>
            <a href="expense_visualization.php"><button>Voir les graphiques</button></a>
        <?php endif; ?>
    </div>
</body>
</html>
