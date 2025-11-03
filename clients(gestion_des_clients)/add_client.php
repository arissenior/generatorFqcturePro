<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $notes = $_POST['notes'];
    
    $stmt = $pdo->prepare("
        INSERT INTO clients (user_id, name, email, address, phone, notes) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$user_id, $name, $email, $address, $phone, $notes])) {
        redirect('clients.php?success=created');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau client - FacturePro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="assets/css/add_client.css">
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
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="section-title">
                <i class="fas fa-user-plus"></i>
                <h1>Nouveau client</h1>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Nom / Entreprise *</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email">
                </div>
                
                <div class="form-group">
                    <label for="phone">Téléphone</label>
                    <input type="text" id="phone" name="phone">
                </div>
                
                <div class="form-group">
                    <label for="address">Adresse</label>
                    <textarea id="address" name="address" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Informations supplémentaires..."></textarea>
                </div>
                
                <div class="actions">
                    <a href="clients.php" class="btn" style="background: #6c757d; color: white;">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer le client
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>