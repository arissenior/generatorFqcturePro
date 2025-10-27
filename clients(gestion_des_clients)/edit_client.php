<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$client_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ? AND user_id = ?");
$stmt->execute([$client_id, $user_id]);
$client = $stmt->fetch();

if (!$client) {
    redirect('clients.php?error=not_found');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $notes = $_POST['notes'];
    
    $stmt = $pdo->prepare("
        UPDATE clients 
        SET name = ?, email = ?, address = ?, phone = ?, notes = ?
        WHERE id = ? AND user_id = ?
    ");
    
    if ($stmt->execute([$name, $email, $address, $phone, $notes, $client_id, $user_id])) {
        redirect('clients.php?success=updated');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le client - FacturePro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        <?php include 'add_client.php'; ?>
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
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="section-title">
                <i class="fas fa-edit"></i>
                <h1>Modifier le client</h1>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Nom / Entreprise *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($client['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Téléphone</label>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($client['phone']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Adresse</label>
                    <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($client['address']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Informations supplémentaires..."><?php echo htmlspecialchars($client['notes']); ?></textarea>
                </div>
                
                <div class="actions">
                    <a href="clients.php" class="btn" style="background: #6c757d; color: white;">
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