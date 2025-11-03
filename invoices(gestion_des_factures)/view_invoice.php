<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$invoice_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT i.*, c.name as client_name, c.email as client_email, c.address as client_address, c.phone as client_phone,
           u.company_name, u.address as company_address, u.phone as company_phone, u.email as company_email, u.siret, u.website
    FROM invoices i 
    LEFT JOIN clients c ON i.client_id = c.id 
    LEFT JOIN users u ON i.user_id = u.id 
    WHERE i.id = ? AND i.user_id = ?
");
$stmt->execute([$invoice_id, $user_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    redirect('invoices.php?error=not_found');
}

$stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$stmt->execute([$invoice_id]);
$items = $stmt->fetchAll();

$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['quantity'] * $item['unit_price'];
}
$tax_amount = $subtotal * ($invoice['tax_rate'] / 100);
$total_amount = $subtotal + $tax_amount;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture <?php echo $invoice['invoice_number']; ?> - FacturePro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/view_invoice.css">
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
                    <a href="invoices.php"><i class="fas fa-file-invoice"></i> Factures</a>
                    <a href="../clients/clients.php"><i class="fas fa-users"></i> Clients</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="card" style="background: #d4edda; color: #155724; margin-bottom: 20px;">
                <p>Facture créée avec succès !</p>
            </div>
        <?php endif; ?>
        
        <div class="invoice-preview">
            <div class="invoice-header">
                <div>
                    <h2 class="invoice-title">FACTURE</h2>
                    <div>
                        <p><strong><?php echo htmlspecialchars($invoice['company_name'] ?: 'Votre entreprise'); ?></strong></p>
                        <?php if ($invoice['siret']): ?>
                            <p>SIRET: <?php echo htmlspecialchars($invoice['siret']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="invoice-details">
                    <p><strong>N°:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($invoice['invoice_date'])); ?></p>
                    <p><strong>Échéance:</strong> <?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?></p>
                </div>
            </div>

            <div class="invoice-body">
                <div class="grid">
                    <div>
                        <h3>De:</h3>
                        <p><strong><?php echo htmlspecialchars($invoice['company_name'] ?: 'Votre entreprise'); ?></strong></p>
                        <?php if ($invoice['company_address']): ?>
                            <p><?php echo nl2br(htmlspecialchars($invoice['company_address'])); ?></p>
                        <?php endif; ?>
                        <?php if ($invoice['company_phone']): ?>
                            <p>Tél: <?php echo htmlspecialchars($invoice['company_phone']); ?></p>
                        <?php endif; ?>
                        <p><?php echo htmlspecialchars($invoice['company_email']); ?></p>
                        <?php if ($invoice['website']): ?>
                            <p><?php echo htmlspecialchars($invoice['website']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3>À:</h3>
                        <?php if ($invoice['client_name']): ?>
                            <p><strong><?php echo htmlspecialchars($invoice['client_name']); ?></strong></p>
                            <?php if ($invoice['client_address']): ?>
                                <p><?php echo nl2br(htmlspecialchars($invoice['client_address'])); ?></p>
                            <?php endif; ?>
                            <?php if ($invoice['client_phone']): ?>
                                <p>Tél: <?php echo htmlspecialchars($invoice['client_phone']); ?></p>
                            <?php endif; ?>
                            <?php if ($invoice['client_email']): ?>
                                <p><?php echo htmlspecialchars($invoice['client_email']); ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>Client non spécifié</p>
                        <?php endif; ?>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Quantité</th>
                            <th>Prix unitaire</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo number_format($item['unit_price'], 2, ',', ' '); ?> <?php echo $invoice['currency']; ?></td>
                                <td><?php echo number_format($item['quantity'] * $item['unit_price'], 2, ',', ' '); ?> <?php echo $invoice['currency']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;">Sous-total</td>
                            <td><?php echo number_format($subtotal, 2, ',', ' '); ?> <?php echo $invoice['currency']; ?></td>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align: right;">TVA (<?php echo $invoice['tax_rate']; ?>%)</td>
                            <td><?php echo number_format($tax_amount, 2, ',', ' '); ?> <?php echo $invoice['currency']; ?></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;">Total</td>
                            <td><?php echo number_format($total_amount, 2, ',', ' '); ?> <?php echo $invoice['currency']; ?></td>
                        </tr>
                    </tfoot>
                </table>
                
                <?php if ($invoice['notes']): ?>
                    <div style="margin-top: 30px;">
                        <h3>Notes</h3>
                        <p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="invoice-footer">
                <p>Facture générée avec FacturePro - www.facturepro.fr</p>
            </div>
        </div>

        <div class="actions">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Imprimer
            </button>
            <a href="edit_invoice.php?id=<?php echo $invoice_id; ?>" class="btn" style="background: #6c757d; color: white;">
                <i class="fas fa-edit"></i> Modifier
            </a>
            <a href="invoices.php" class="btn" style="background: var(--gray); color: white;">
                <i class="fas fa-arrow-left"></i> Retour aux factures
            </a>
        </div>
    </div>
</body>
</html>