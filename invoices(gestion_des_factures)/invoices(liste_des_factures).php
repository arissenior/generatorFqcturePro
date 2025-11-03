<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT i.*, c.name as client_name 
    FROM invoices i 
    LEFT JOIN clients c ON i.client_id = c.id 
    WHERE i.user_id = ? 
    ORDER BY i.created_at DESC
");
$stmt->execute([$user_id]);
$invoices = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes factures - FacturePro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
   <link rel="stylesheet" href="assets/css/invoices.css">
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
                    <a href="invoices.php" class="active"><i class="fas fa-file-invoice"></i> Factures</a>
                    <a href="../clients/clients.php"><i class="fas fa-users"></i> Clients</a>
                    <a href="../profile/profile.php"><i class="fas fa-user"></i> Mon profil</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="section-title">
                <h1>Mes factures</h1>
                <a href="create_invoice.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouvelle facture
                </a>
            </div>
            
            <?php if (count($invoices) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>N° Facture</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Échéance</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                <td><?php echo htmlspecialchars($invoice['client_name'] ?: 'Non spécifié'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($invoice['invoice_date'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?></td>
                                <td>
                                    <span class="status status-<?php echo $invoice['status']; ?>">
                                        <?php 
                                        $status_labels = [
                                            'draft' => 'Brouillon',
                                            'sent' => 'Envoyée',
                                            'paid' => 'Payée'
                                        ];
                                        echo $status_labels[$invoice['status']];
                                        ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="view_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                    <a href="edit_invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm" style="background: #6c757d; color: white;">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-invoice"></i>
                    <h3>Aucune facture</h3>
                    <p>Vous n'avez pas encore créé de factures.</p>
                    <a href="create_invoice.php" class="btn btn-primary" style="margin-top: 20px;">
                        <i class="fas fa-plus"></i> Créer votre première facture
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>