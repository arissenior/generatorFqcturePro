<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM clients WHERE user_id = ? ORDER BY name");
$stmt->execute([$user_id]);
$clients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes clients - FacturePro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --dark: #212529;
            --light: #f8f9fa;
            --gray: #6c757d;
            --border-radius: 8px;
            --shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: #f5f7fa;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: white;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: bold;
            font-size: 1.5rem;
            color: var(--primary);
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .nav-links a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: var(--border-radius);
            transition: background 0.3s;
        }
        
        .nav-links a:hover, .nav-links a.active {
            background-color: var(--light);
            color: var(--primary);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 25px;
            margin-bottom: 25px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            border-radius: var(--border-radius);
            overflow: hidden;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: var(--light);
            font-weight: 600;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        .client-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .client-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .client-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
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
                    <a href="../dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
                    <a href="../invoices/invoices.php"><i class="fas fa-file-invoice"></i> Factures</a>
                    <a href="clients.php" class="active"><i class="fas fa-users"></i> Clients</a>
                    <a href="../profile/profile.php"><i class="fas fa-user"></i> Mon profil</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="section-title">
                <h1>Mes clients</h1>
                <a href="add_client.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouveau client
                </a>
            </div>
            
            <?php if (count($clients) > 0): ?>
                <div class="clients-grid">
                    <?php foreach ($clients as $client): ?>
                        <div class="client-card">
                            <div class="client-header">
                                <div class="client-name"><?php echo htmlspecialchars($client['name']); ?></div>
                                <div class="actions">
                                    <a href="edit_client.php?id=<?php echo $client['id']; ?>" class="btn btn-sm" style="background: #6c757d; color: white;">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                    <a href="delete_client.php?id=<?php echo $client['id']; ?>" class="btn btn-sm" style="background: #dc3545; color: white;" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce client ?')">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </a>
                                </div>
                            </div>
                            <?php if ($client['email']): ?>
                                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($client['email']); ?></p>
                            <?php endif; ?>
                            <?php if ($client['phone']): ?>
                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($client['phone']); ?></p>
                            <?php endif; ?>
                            <?php if ($client['address']): ?>
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo nl2br(htmlspecialchars($client['address'])); ?></p>
                            <?php endif; ?>
                            <?php if ($client['notes']): ?>
                                <p><i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($client['notes']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>Aucun client</h3>
                    <p>Vous n'avez pas encore ajouté de clients.</p>
                    <a href="add_client.php" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-plus"></i> Ajouter votre premier client
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>