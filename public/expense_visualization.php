<?php
// expense_visualization.php
include '../includes/config.php';
include(__DIR__ . '/../includes/db.php');
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Initialisation des variables pour les filtres
$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
$category = isset($_POST['category']) ? $_POST['category'] : '';

// Requête SQL pour récupérer les dépenses avec les filtres
$query = "SELECT * FROM transactions WHERE user_id = ?";
$filters = [];

if ($start_date && $end_date) {
    $query .= " AND date BETWEEN ? AND ?";
    $filters[] = $start_date;
    $filters[] = $end_date;
}

if ($category) {
    $query .= " AND category = ?";
    $filters[] = $category;
}

$stmt = $conn->prepare($query);
$stmt->bind_param(str_repeat('s', count($filters) + 1), $user_id, ...$filters);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
$categories = [];
$category_totals = [];

// Récupérer les montants et catégories
while ($row = $result->fetch_assoc()) {
    $category_name = $row['category'];
    $amount = $row['amount'];
    if ($category_name == "Initial Balance") {
        continue;
    }

    // Ajouter l'élément au tableau des catégories
    if (!isset($category_totals[$category_name])) {
        $category_totals[$category_name] = 0;
    }
    $category_totals[$category_name] += $amount;
}

// Préparer les données pour le graphique
$labels = array_keys($category_totals); // Catégories des dépenses
$amounts = array_values($category_totals); // Montants totaux par catégorie
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualisation des Dépenses</title>
    
    <!-- Charger la bibliothèque Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            width: 90%;
            max-width: 800px;
            margin: 50px auto;
            background-color: #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: 10px;
        }
        h2 {
            text-align: center;
            color: #4CAF50;
        }
        .form-group {
            margin: 10px 0;
        }
        .btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #45a049;
        }
        canvas {
            max-width: 100%;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Visualisation des Dépenses</h2>
        
        <form method="post">
            <div class="form-group">
                <label for="start_date">Date de début :</label>
                <input type="date" name="start_date" id="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="form-group">
                <label for="end_date">Date de fin :</label>
                <input type="date" name="end_date" id="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="form-group">
                <label for="category">Catégorie :</label>
                <input type="text" name="category" id="category" value="<?php echo $category; ?>">
            </div>
            <button type="submit" class="btn">Filtrer</button>
        </form>

        <!-- Graphique -->
        <canvas id="expenseChart"></canvas>
        
        <script>
            var ctx = document.getElementById('expenseChart').getContext('2d');
            var expenseChart = new Chart(ctx, {
                type: 'bar', // Type de graphique (ici, un graphique à barres)
                data: {
                    labels: <?php echo json_encode($labels); ?>, // Catégories des dépenses
                    datasets: [{
                        label: 'Dépenses par catégorie',
                        data: <?php echo json_encode($amounts); ?>, // Montants totaux des dépenses par catégorie
                        backgroundColor: '#4CAF50', // Couleur des barres
                        borderColor: '#45a049', // Couleur de la bordure
                        borderWidth: 1 // Largeur de la bordure
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true // L'axe Y commence à 0
                        }
                    }
                }
            });
        </script>
    </div>
</body>
</html>
