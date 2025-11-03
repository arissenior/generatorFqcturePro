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
   <link rel="stylesheet" href="assets/css/profile.css">
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