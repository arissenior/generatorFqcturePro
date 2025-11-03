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
    <link rel="stylesheet" href="assets/css/client.css">
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