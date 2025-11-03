<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];
$user = getCurrentUser($pdo);

$stmt = $pdo->prepare("SELECT * FROM clients WHERE user_id = ? ORDER BY name");
$stmt->execute([$user_id]);
$clients = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM invoices WHERE user_id = ? AND YEAR(created_at) = YEAR(NOW())");
$stmt->execute([$user_id]);
$count = $stmt->fetch()['count'];
$invoice_number = "FACT-" . date('Y') . "-" . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?: NULL;
    $invoice_number = $_POST['invoice_number'];
    $invoice_date = $_POST['invoice_date'];
    $due_date = $_POST['due_date'];
    $tax_rate = $_POST['tax_rate'];
    $currency = $_POST['currency'];
    $notes = $_POST['notes'];
    
    $stmt = $pdo->prepare("
        INSERT INTO invoices (user_id, client_id, invoice_number, invoice_date, due_date, tax_rate, currency, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$user_id, $client_id, $invoice_number, $invoice_date, $due_date, $tax_rate, $currency, $notes])) {
        $invoice_id = $pdo->lastInsertId();
        
        $descriptions = $_POST['item_description'];
        $quantities = $_POST['item_quantity'];
        $prices = $_POST['item_price'];
        
        for ($i = 0; $i < count($descriptions); $i++) {
            if (!empty($descriptions[$i]) && $quantities[$i] > 0 && $prices[$i] >= 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO invoice_items (invoice_id, description, quantity, unit_price) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$invoice_id, $descriptions[$i], $quantities[$i], $prices[$i]]);
            }
        }
        
        redirect("view_invoice.php?id=$invoice_id&success=created");
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle facture - FacturePro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/create_invoice.css">
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
                <i class="fas fa-plus-circle"></i>
                <h1>Nouvelle facture</h1>
            </div>
            
            <form method="POST" action="">
                <div class="grid">
                    <div class="form-group">
                        <label for="invoice_number">Numéro de facture *</label>
                        <input type="text" id="invoice_number" name="invoice_number" value="<?php echo $invoice_number; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="client_id">Client</label>
                        <select id="client_id" name="client_id">
                            <option value="">-- Sélectionner un client --</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>"><?php echo htmlspecialchars($client['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="invoice_date">Date de facturation *</label>
                        <input type="date" id="invoice_date" name="invoice_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="due_date">Date d'échéance *</label>
                        <input type="date" id="due_date" name="due_date" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="tax_rate">Taux de TVA (%)</label>
                        <input type="number" id="tax_rate" name="tax_rate" min="0" max="100" step="0.01" value="20.00">
                    </div>
                    <div class="form-group">
                        <label for="currency">Devise</label>
                        <select id="currency" name="currency">
                            <option value="€">Euro (€)</option>
                            <option value="$">Dollar ($)</option>
                            <option value="£">Livre (£)</option>
                            <option value="CHF">Franc Suisse (CHF)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes (optionnel)</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Notes additionnelles pour la facture..."></textarea>
                </div>
                
                <div class="card">
                    <div class="section-title">
                        <i class="fas fa-shopping-cart"></i>
                        <h2>Articles / Services</h2>
                    </div>
                    
                    <table id="items-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th width="120">Quantité</th>
                                <th width="150">Prix unitaire</th>
                                <th width="120">Total</th>
                                <th width="50">Action</th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            <tr>
                                <td><input type="text" name="item_description[]" placeholder="Description de l'article ou service" class="item-desc"></td>
                                <td><input type="number" name="item_quantity[]" min="1" value="1" class="item-qty"></td>
                                <td><input type="number" name="item_price[]" min="0" step="0.01" value="0.00" class="item-price"></td>
                                <td class="item-total">0.00</td>
                                <td><span class="delete-item" onclick="removeItem(this)"><i class="fas fa-trash"></i></span></td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="3" style="text-align: right;">Sous-total</td>
                                <td id="subtotal">0.00</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3" style="text-align: right;">TVA (<span id="tax-rate-value">20</span>%)</td>
                                <td id="tax-amount">0.00</td>
                                <td></td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="3" style="text-align: right;">Total</td>
                                <td id="total-amount">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    
                    <button type="button" class="btn btn-secondary" id="add-item">
                        <i class="fas fa-plus"></i> Ajouter un article
                    </button>
                </div>
                
                <div class="actions">
                    <a href="../dashboard.php" class="btn" style="background: #6c757d; color: white;">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer la facture
                    </button>
                </div>
            </form>
        </div>
    </div>

   <script src="assets/js/create_invoice.js"></script>
</body>
</html>