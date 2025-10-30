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

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM quotations WHERE user_id = ? AND YEAR(created_at) = YEAR(NOW())");
$stmt->execute([$user_id]);
$count = $stmt->fetch()['count'];
$quotation_number = "DEV-" . date('Y') . "-" . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?: NULL;
    $quotation_number = $_POST['quotation_number'];
    $quotation_date = $_POST['quotation_date'];
    $valid_until = $_POST['valid_until'];
    $tax_rate = $_POST['tax_rate'];
    $currency = $_POST['currency'];
    $notes = $_POST['notes'];
    $template = $_POST['template'];
    
    $stmt = $pdo->prepare("
        INSERT INTO quotations (user_id, client_id, quotation_number, quotation_date, valid_until, tax_rate, currency, notes, template) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$user_id, $client_id, $quotation_number, $quotation_date, $valid_until, $tax_rate, $currency, $notes, $template])) {
        $quotation_id = $pdo->lastInsertId();
        
        $descriptions = $_POST['item_description'];
        $quantities = $_POST['item_quantity'];
        $prices = $_POST['item_price'];
        
        for ($i = 0; $i < count($descriptions); $i++) {
            if (!empty($descriptions[$i]) && $quantities[$i] > 0 && $prices[$i] >= 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO quotation_items (quotation_id, description, quantity, unit_price) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$quotation_id, $descriptions[$i], $quantities[$i], $prices[$i]]);
            }
        }
        
        // Convertir en facture si demandé
        if (isset($_POST['convert_to_invoice']) && $_POST['convert_to_invoice'] === '1') {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM invoices WHERE user_id = ? AND YEAR(created_at) = YEAR(NOW())");
            $stmt->execute([$user_id]);
            $invoice_count = $stmt->fetch()['count'];
            $invoice_number = "FACT-" . date('Y') . "-" . str_pad($invoice_count + 1, 3, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO invoices (user_id, client_id, invoice_number, invoice_date, due_date, tax_rate, currency, notes, template) 
                VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $client_id, $invoice_number, $tax_rate, $currency, $notes, $template]);
            $invoice_id = $pdo->lastInsertId();
            
            // Copier les articles
            $stmt = $pdo->prepare("
                INSERT INTO invoice_items (invoice_id, description, quantity, unit_price)
                SELECT ?, description, quantity, unit_price 
                FROM quotation_items 
                WHERE quotation_id = ?
            ");
            $stmt->execute([$invoice_id, $quotation_id]);
            
            redirect("../invoices/view_invoice.php?id=$invoice_id&success=created_from_quotation");
        } else {
            redirect("view_quotation.php?id=$quotation_id&success=created");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau Devis - FacturePro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        <?php include '../invoices/create_invoice.php'; ?>
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
                    <a href="quotations.php" class="active"><i class="fas fa-file-contract"></i> Devis</a>
                    <a href="../clients/clients.php"><i class="fas fa-users"></i> Clients</a>
                    <a href="../expenses/expenses.php"><i class="fas fa-receipt"></i> Dépenses</a>
                    <a href="../profile/profile.php"><i class="fas fa-user"></i> Mon profil</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="section-title">
                <i class="fas fa-file-contract"></i>
                <h1>Nouveau Devis</h1>
            </div>
            
            <form method="POST" action="">
                <div class="grid">
                    <div class="form-group">
                        <label for="quotation_number">Numéro de devis *</label>
                        <input type="text" id="quotation_number" name="quotation_number" value="<?php echo $quotation_number; ?>" required>
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
                        <label for="quotation_date">Date du devis *</label>
                        <input type="date" id="quotation_date" name="quotation_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="valid_until">Valide jusqu'au *</label>
                        <input type="date" id="valid_until" name="valid_until" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
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
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="template">Template</label>
                        <select id="template" name="template">
                            <option value="default">Par défaut</option>
                            <option value="modern">Moderne</option>
                            <option value="classic">Classique</option>
                            <option value="minimal">Minimaliste</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes (optionnel)</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Notes additionnelles pour le devis..."></textarea>
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
                
                <div class="form-group">
                    <label style="display: inline-flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="convert_to_invoice" value="1">
                        Convertir directement en facture après création
                    </label>
                </div>
                
                <div class="actions">
                    <a href="quotations.php" class="btn" style="background: #6c757d; color: white;">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer le devis
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('add-item').addEventListener('click', addItemRow);
            document.getElementById('items-body').addEventListener('input', calculateTotals);
            document.getElementById('tax_rate').addEventListener('input', calculateTotals);
            calculateTotals();
        });
        
        function addItemRow() {
            const tbody = document.getElementById('items-body');
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="item_description[]" placeholder="Description de l'article ou service" class="item-desc"></td>
                <td><input type="number" name="item_quantity[]" min="1" value="1" class="item-qty"></td>
                <td><input type="number" name="item_price[]" min="0" step="0.01" value="0.00" class="item-price"></td>
                <td class="item-total">0.00</td>
                <td><span class="delete-item" onclick="removeItem(this)"><i class="fas fa-trash"></i></span></td>
            `;
            tbody.appendChild(row);
            calculateTotals();
        }
        
        function removeItem(element) {
            const row = element.closest('tr');
            if (document.querySelectorAll('#items-body tr').length > 1) {
                row.remove();
            } else {
                row.querySelector('.item-desc').value = '';
                row.querySelector('.item-qty').value = 1;
                row.querySelector('.item-price').value = 0.00;
                row.querySelector('.item-total').textContent = '0.00';
            }
            calculateTotals();
        }
        
        function calculateTotals() {
            const rows = document.querySelectorAll('#items-body tr');
            let subtotal = 0;
            
            rows.forEach(row => {
                const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                const total = qty * price;
                
                row.querySelector('.item-total').textContent = total.toFixed(2);
                subtotal += total;
            });
            
            const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
            const taxAmount = subtotal * (taxRate / 100);
            const totalAmount = subtotal + taxAmount;
            
            document.getElementById('subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('tax-amount').textContent = taxAmount.toFixed(2);
            document.getElementById('total-amount').textContent = totalAmount.toFixed(2);
            document.getElementById('tax-rate-value').textContent = taxRate;
        }
    </script>
</body>
</html>