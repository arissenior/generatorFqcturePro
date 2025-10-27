<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$user = getCurrentUser($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil - FacturePro</title>
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
            max-width: 800px;
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
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
        }
        
        .profile-info h1 {
            margin-bottom: 5px;
        }
        
        .profile-info p {
            color: var(--gray);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .info-value {
            color: var(--gray);
        }
        
        .actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
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
                    <a href="../clients/clients.php"><i class="fas fa-users"></i> Clients</a>
                    <a href="profile.php" class="active"><i class="fas fa-user"></i> Mon profil</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="profile-info">
                    <h1><?php echo htmlspecialchars($user['company_name'] ?: $user['email']); ?></h1>
                    <p>Profil utilisateur</p>
                </div>
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Nom de l'entreprise</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['company_name'] ?: 'Non spécifié'); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">SIRET</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['siret'] ?: 'Non spécifié'); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Téléphone</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?: 'Non spécifié'); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Site web</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['website'] ?: 'Non spécifié'); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Date d'inscription</div>
                    <div class="info-value"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></div>
                </div>
            </div>
            
            <?php if ($user['address']): ?>
                <div class="info-item" style="grid-column: 1 / -1;">
                    <div class="info-label">Adresse</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($user['address'])); ?></div>
                </div>
            <?php endif; ?>
            
            <div class="actions">
                <a href="update_profile.php" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Modifier le profil
                </a>
            </div>
        </div>
    </div>
</body>
</html>