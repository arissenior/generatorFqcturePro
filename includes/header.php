<?php
if (!isset($page_title)) {
    $page_title = 'FacturePro';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>FacturePro</span>
                </div>
                <nav class="nav-links">
                    <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i> Tableau de bord
                    </a>
                    <a href="invoices/invoices.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'invoices/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice"></i> Factures
                    </a>
                    <a href="clients/clients.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'clients/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i> Clients
                    </a>
                    <a href="profile/profile.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'profile/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-user"></i> Mon profil
                    </a>
                    <a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </nav>
            </div>
        </div>
    </header>
    <?php endif; ?>
    <main class="container">