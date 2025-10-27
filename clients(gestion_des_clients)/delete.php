<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$user = getCurrentUser($pdo);
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = $_POST['company_name'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $siret = $_POST['siret'];
    $website = $_POST['website'];
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET company_name = ?, address = ?, phone = ?, siret = ?, website = ?
        WHERE id = ?
    ");
    
    if ($stmt->execute([$company_name, $address, $phone, $siret, $website, $user_id])) {
        redirect('profile.php?success=updated');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le profil - FacturePro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        <?php include 'profile.php'; ?>
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
            <div class="section-title">
                <i class="fas fa-edit"></i>
                <h1>Modifier le profil</h1>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="company_name">Nom de l'entreprise</label>
                    <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($user['company_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="siret">SIRET</label>
                    <input type="text" id="siret" name="siret" value="<?php echo htmlspecialchars($user['siret']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Adresse</label>
                    <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="phone">Téléphone</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="website">Site web</label>
                    <input type="text" id="website" name="website" value="<?php echo htmlspecialchars($user['website']); ?>">
                </div>
                
                <div class="actions">
                    <a href="profile.php" class="btn" style="background: #6c757d; color: white;">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>