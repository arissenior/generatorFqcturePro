<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$user = getCurrentUser($pdo);
$user_id = $_SESSION['user_id'];

// Statistiques avancées
$current_month = date('Y-m');
$current_year = date('Y');

// Revenus du mois
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(ii.quantity * ii.unit_price * (1 + i.tax_rate/100)), 0) as revenue
    FROM invoices i 
    JOIN invoice_items ii ON i.id = ii.invoice_id 
    WHERE i.user_id = ? AND i.status = 'paid' 
    AND DATE_FORMAT(i.invoice_date, '%Y-%m') = ?
");
$stmt->execute([$user_id, $current_month]);
$monthly_revenue = $stmt->fetch()['revenue'];

// Revenus de l'année
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(ii.quantity * ii.unit_price * (1 + i.tax_rate/100)), 0) as revenue
    FROM invoices i 
    JOIN invoice_items ii ON i.id = ii.invoice_id 
    WHERE i.user_id = ? AND i.status = 'paid' 
    AND YEAR(i.invoice_date) = ?
");
$stmt->execute([$user_id, $current_year]);
$yearly_revenue = $stmt->fetch()['revenue'];

// Factures impayées
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count, 
           COALESCE(SUM(ii.quantity * ii.unit_price * (1 + i.tax_rate/100)), 0) as amount
    FROM invoices i 
    JOIN invoice_items ii ON i.id = ii.invoice_id 
    WHERE i.user_id = ? AND i.status = 'sent' AND i.due_date < CURDATE()
");
$stmt->execute([$user_id]);
$overdue_data = $stmt->fetch();

// Graphique des revenus mensuels (12 derniers mois)
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(i.invoice_date, '%Y-%m') as month,
           COALESCE(SUM(ii.quantity * ii.unit_price * (1 + i.tax_rate/100)), 0) as revenue
    FROM invoices i 
    JOIN invoice_items ii ON i.id = ii.invoice_id 
    WHERE i.user_id = ? AND i.status = 'paid' 
    AND i.invoice_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(i.invoice_date, '%Y-%m')
    ORDER BY month
");
$stmt->execute([$user_id]);
$monthly_revenues = $stmt->fetchAll();

// Préparer les données pour le graphique
$months = [];
$revenues = [];
foreach ($monthly_revenues as $data) {
    $months[] = date('M Y', strtotime($data['month'] . '-01'));
    $revenues[] = floatval($data['revenue']);
}

// Top clients
$stmt = $pdo->prepare("
    SELECT c.name, 
           COUNT(i.id) as invoice_count,
           COALESCE(SUM(ii.quantity * ii.unit_price * (1 + i.tax_rate/100)), 0) as total_spent
    FROM clients c
    LEFT JOIN invoices i ON c.id = i.client_id
    LEFT JOIN invoice_items ii ON i.id = ii.invoice_id
    WHERE c.user_id = ? AND i.status = 'paid'
    GROUP BY c.id
    ORDER BY total_spent DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$top_clients = $stmt->fetchAll();

// Activités récentes
$stmt = $pdo->prepare("
    (SELECT 'invoice' as type, invoice_date as date, invoice_number as title, 
            CONCAT('Facture ', status) as description, id
     FROM invoices 
     WHERE user_id = ? 
     ORDER BY created_at DESC 
     LIMIT 5)
    UNION ALL
    (SELECT 'client' as type, created_at as date, name as title, 
            'Nouveau client ajouté' as description, id
     FROM clients 
     WHERE user_id = ? 
     ORDER BY created_at DESC 
     LIMIT 5)
    ORDER BY date DESC 
    LIMIT 10
");
$stmt->execute([$user_id, $user_id]);
$recent_activities = $stmt->fetchAll();

// Statistiques des statuts
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count
    FROM invoices 
    WHERE user_id = ? 
    GROUP BY status
");
$stmt->execute([$user_id]);
$status_stats = $stmt->fetchAll();

// Préparer les données pour le graphique circulaire
$status_labels = [];
$status_data = [];
$status_colors = [
    'draft' => '#6c757d',
    'sent' => '#17a2b8', 
    'paid' => '#28a745',
    'overdue' => '#dc3545'
];

foreach ($status_stats as $stat) {
    $status_labels[] = ucfirst($stat['status']);
    $status_data[] = $stat['count'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - FacturePro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --dark: #212529;
            --light: #f8f9fa;
            --gray: #6c757d;
            --border-radius: 12px;
            --shadow: 0 4px 20px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: white;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            border-radius: var(--border-radius);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: bold;
            font-size: 1.6rem;
            color: var(--primary);
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-links a:hover, .nav-links a.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            transform: translateY(-2px);
        }
        
        .welcome-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            padding: 30px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }
        
        .welcome-section h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-card.overdue {
            border-left-color: var(--danger);
        }
        
        .stat-card.revenue {
            border-left-color: var(--success);
        }
        
        .stat-card.clients {
            border-left-color: var(--info);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .stat-card.revenue .stat-icon {
            background: rgba(76, 201, 240, 0.2);
            color: var(--success);
        }
        
        .stat-card.overdue .stat-icon {
            background: rgba(247, 37, 133, 0.2);
            color: var(--danger);
        }
        
        .stat-card.clients .stat-icon {
            background: rgba(67, 97, 238, 0.2);
            color: var(--primary);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }
        
        .chart-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .chart-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .activities-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        .activity-card, .clients-card {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }
        
        .activity-icon.invoice {
            background: var(--primary);
        }
        
        .activity-icon.client {
            background: var(--success);
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .activity-description {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .activity-time {
            color: var(--gray);
            font-size: 0.8rem;
        }
        
        .client-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .client-item:last-child {
            border-bottom: none;
        }
        
        .client-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .client-info {
            flex: 1;
        }
        
        .client-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .client-stats {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.4);
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 30px;
        }
        
        .quick-action {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
        }
        
        .quick-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .quick-action i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        @media (max-width: 768px) {
            .charts-grid, .activities-grid {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
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
                    <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
                    <a href="invoices/invoices.php"><i class="fas fa-file-invoice"></i> Factures</a>
                    <a href="quotations/quotations.php"><i class="fas fa-file-contract"></i> Devis</a>
                    <a href="clients/clients.php"><i class="fas fa-users"></i> Clients</a>
                    <a href="expenses/expenses.php"><i class="fas fa-receipt"></i> Dépenses</a>
                    <a href="profile/profile.php"><i class="fas fa-user"></i> Mon profil</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="welcome-section">
            <h1>Bonjour, <?php echo htmlspecialchars($user['company_name'] ?: $user['email']); ?> !</h1>
            <p>Voici votre tableau de bord personnalisé</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card revenue">
                <div class="stat-icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="stat-value"><?php echo number_format($monthly_revenue, 2, ',', ' '); ?> €</div>
                <div class="stat-label">Revenus ce mois</div>
            </div>
            
            <div class="stat-card overdue">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-value"><?php echo $overdue_data['count']; ?></div>
                <div class="stat-label">Factures en retard</div>
                <div class="stat-amount"><?php echo number_format($overdue_data['amount'], 2, ',', ' '); ?> €</div>
            </div>
            
            <div class="stat-card clients">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo count($top_clients); ?></div>
                <div class="stat-label">Clients actifs</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(114, 9, 183, 0.2); color: var(--secondary);">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value"><?php echo number_format($yearly_revenue, 2, ',', ' '); ?> €</div>
                <div class="stat-label">Revenus annuels</div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Revenus Mensuels</h3>
                </div>
                <canvas id="revenueChart" height="250"></canvas>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Statut des Factures</h3>
                </div>
                <canvas id="statusChart" height="250"></canvas>
            </div>
        </div>

        <div class="activities-grid">
            <div class="activity-card">
                <h3 style="margin-bottom: 20px;">Activités Récentes</h3>
                <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon <?php echo $activity['type']; ?>">
                            <i class="fas fa-<?php echo $activity['type'] === 'invoice' ? 'file-invoice' : 'user'; ?>"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                            <div class="activity-description"><?php echo htmlspecialchars($activity['description']); ?></div>
                        </div>
                        <div class="activity-time">
                            <?php echo date('d/m', strtotime($activity['date'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="clients-card">
                <h3 style="margin-bottom: 20px;">Top Clients</h3>
                <?php foreach ($top_clients as $client): ?>
                    <div class="client-item">
                        <div class="client-avatar">
                            <?php echo strtoupper(substr($client['name'], 0, 2)); ?>
                        </div>
                        <div class="client-info">
                            <div class="client-name"><?php echo htmlspecialchars($client['name']); ?></div>
                            <div class="client-stats">
                                <?php echo $client['invoice_count']; ?> factures • 
                                <?php echo number_format($client['total_spent'], 2, ',', ' '); ?> €
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="quick-actions">
            <div class="quick-action" onclick="location.href='invoices/create_invoice.php'">
                <i class="fas fa-plus-circle"></i>
                <div>Nouvelle Facture</div>
            </div>
            <div class="quick-action" onclick="location.href='quotations/create_quotation.php'">
                <i class="fas fa-file-contract"></i>
                <div>Nouveau Devis</div>
            </div>
            <div class="quick-action" onclick="location.href='clients/add_client.php'">
                <i class="fas fa-user-plus"></i>
                <div>Nouveau Client</div>
            </div>
            <div class="quick-action" onclick="location.href='expenses/add_expense.php'">
                <i class="fas fa-receipt"></i>
                <div>Nouvelle Dépense</div>
            </div>
        </div>
    </div>

    <script>
        // Graphique des revenus
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Revenus (€)',
                    data: <?php echo json_encode($revenues); ?>,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Graphique des statuts
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($status_data); ?>,
                    backgroundColor: [
                        '#6c757d',
                        '#17a2b8', 
                        '#28a745',
                        '#dc3545'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '60%'
            }
        });

        // Mise à jour en temps réel (simulée)
        setInterval(() => {
            // Ici on pourrait faire un appel AJAX pour mettre à jour les données
            console.log('Mise à jour des données...');
        }, 30000); // Toutes les 30 secondes
    </script>
</body>
</html>