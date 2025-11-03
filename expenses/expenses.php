<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

// Filtres
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');
$category = $_GET['category'] ?? '';

$where_conditions = ["user_id = ?"];
$params = [$user_id];

if ($year) {
    $where_conditions[] = "YEAR(expense_date) = ?";
    $params[] = $year;
}

if ($month) {
    $where_conditions[] = "MONTH(expense_date) = ?";
    $params[] = $month;
}

if ($category) {
    $where_conditions[] = "category = ?";
    $params[] = $category;
}

$where_sql = implode(" AND ", $where_conditions);

$stmt = $pdo->prepare("
    SELECT * FROM expenses 
    WHERE $where_sql 
    ORDER BY expense_date DESC
");
$stmt->execute($params);
$expenses = $stmt->fetchAll();

// Statistiques
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(amount), 0) as total,
        COUNT(*) as count,
        category
    FROM expenses 
    WHERE user_id = ? AND YEAR(expense_date) = ?
    GROUP BY category
    ORDER BY total DESC
");
$stmt->execute([$user_id, $year]);
$expense_stats = $stmt->fetchAll();

// Années disponibles
$stmt = $pdo->prepare("SELECT DISTINCT YEAR(expense_date) as year FROM expenses WHERE user_id = ? ORDER BY year DESC");
$stmt->execute([$user_id]);
$available_years = $stmt->fetchAll();

// Catégories disponibles
$stmt = $pdo->prepare("SELECT DISTINCT category FROM expenses WHERE user_id = ? AND category IS NOT NULL ORDER BY category");
$stmt->execute([$user_id]);
$available_categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Dépenses - FacturePro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="assets/css/expenses.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>FacturePro</span>
                </div>
                <div class="nav-links">
                    <a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
                    <a href="../invoices/invoices.php"><i class="fas fa-file-invoice"></i> Factures</a>
                    <a href="../quotations/quotations.php"><i class="fas fa-file-contract"></i> Devis</a>
                    <a href="../clients/clients.php"><i class="fas fa-users"></i> Clients</a>
                    <a href="expenses.php" class="active"><i class="fas fa-receipt"></i> Dépenses</a>
                    <a href="../profile/profile.php"><i class="fas fa-user"></i> Mon profil</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="section-title">
                <h1>Gestion des Dépenses</h1>
                <a href="add_expense.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouvelle dépense
                </a>
            </div>
            
            <div class="filters">
                <div class="filter-group">
                    <label for="year">Année</label>
                    <select id="year" onchange="updateFilters()">
                        <?php foreach ($available_years as $y): ?>
                            <option value="<?php echo $y['year']; ?>" <?php echo $y['year'] == $year ? 'selected' : ''; ?>>
                                <?php echo $y['year']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="month">Mois</label>
                    <select id="month" onchange="updateFilters()">
                        <option value="">Tous les mois</option>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>" <?php echo $i == $month ? 'selected' : ''; ?>>
                                <?php echo DateTime::createFromFormat('!m', $i)->format('F'); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="category">Catégorie</label>
                    <select id="category" onchange="updateFilters()">
                        <option value="">Toutes les catégories</option>
                        <?php foreach ($available_categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $cat['category'] == $category ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <?php if (count($expense_stats) > 0): ?>
                <div class="stats-grid">
                    <?php 
                    $total_expenses = 0;
                    foreach ($expense_stats as $stat) {
                        $total_expenses += $stat['total'];
                    }
                    ?>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format($total_expenses, 2, ',', ' '); ?> €</div>
                        <div class="stat-label">Total des dépenses</div>
                    </div>
                    
                    <?php foreach ($expense_stats as $stat): ?>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo number_format($stat['total'], 2, ',', ' '); ?> €</div>
                            <div class="stat-label"><?php echo htmlspecialchars($stat['category'] ?: 'Non catégorisé'); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (count($expenses) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Catégorie</th>
                            <th>Montant</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($expense['expense_date'])); ?></td>
                                <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                <td>
                                    <?php if ($expense['category']): ?>
                                        <span class="category-badge"><?php echo htmlspecialchars($expense['category']); ?></span>
                                    <?php else: ?>
                                        <span class="category-badge">Non catégorisé</span>
                                    <?php endif; ?>
                                </td>
                                <td class="expense-amount"><?php echo number_format($expense['amount'], 2, ',', ' '); ?> €</td>
                                <td class="actions">
                                    <a href="edit_expense.php?id=<?php echo $expense['id']; ?>" class="btn btn-sm" style="background: #6c757d; color: white;">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                    <a href="delete_expense.php?id=<?php echo $expense['id']; ?>" class="btn btn-sm" style="background: #dc3545; color: white;" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette dépense ?')">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <h3>Aucune dépense</h3>
                    <p>Vous n'avez pas encore enregistré de dépenses.</p>
                    <a href="add_expense.php" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-plus"></i> Ajouter votre première dépense
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateFilters() {
            const year = document.getElementById('year').value;
            const month = document.getElementById('month').value;
            const category = document.getElementById('category').value;
            
            let url = 'expenses.php?';
            const params = [];
            
            if (year) params.push(`year=${year}`);
            if (month) params.push(`month=${month}`);
            if (category) params.push(`category=${encodeURIComponent(category)}`);
            
            window.location.href = url + params.join('&');
        }
    </script>
</body>
</html>