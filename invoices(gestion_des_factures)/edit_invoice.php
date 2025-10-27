<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$invoice_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Récupérer la facture existante
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
$stmt->execute([$invoice_id, $user_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    redirect('invoices.php?error=not_found');
}

// Récupérer les clients
$stmt = $pdo->prepare("SELECT * FROM clients WHERE user_id = ? ORDER BY name");
$stmt->execute([$user_id]);
$clients = $stmt->fetchAll();

// Récupérer les articles
$stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$stmt->execute([$invoice_id]);
$items = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?: NULL;
    $invoice_number = $_POST['invoice_number'];
    $invoice_date = $_POST['invoice_date'];
    $due_date = $_POST['due_date'];
    $tax_rate = $_POST['tax_rate'];
    $currency = $_POST['currency'];
    $notes = $_POST['notes'];
    
    // Mettre à jour la facture
    $stmt = $pdo->prepare("
        UPDATE invoices 
        SET client_id = ?, invoice_number = ?, invoice_date = ?, due_date = ?, tax_rate = ?, currency = ?, notes = ?
        WHERE id = ? AND user_id = ?
    ");
    
    if ($stmt->execute([$client_id, $invoice_number, $invoice_date, $due_date, $tax_rate, $currency, $notes, $invoice_id, $user_id])) {
        // Supprimer les anciens articles
        $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
        $stmt->execute([$invoice_id]);
        
        // Ajouter les nouveaux articles
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
        
        redirect("view_invoice.php?id=$invoice_id&success=updated");
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la facture - FacturePro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        <?php include 'create_invoice.php'; ?>
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
                <i class="fas fa-edit"></i>
                <h1>Modifier la facture</h1>
            </div>
            
            <form method="POST" action="">
                <div class="grid">
                    <div class="form-group">
                        <label for="invoice_number">Numéro de facture *</label>
                        <input type="text" id="invoice_number" name="invoice_number" value="<?php echo htmlspecialchars($invoice['invoice_number']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="client_id">Client</label>
                        <select id="client_id" name="client_id">
                            <option value="">-- Sélectionner un client --</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>" <?php echo $invoice['client_id'] == $client['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($client['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="invoice_date">Date de facturation *</label>
                        <input type="date" id="invoice_date" name="invoice_date" value="<?php echo $invoice['invoice_date']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="due_date">Date d'échéance *</label>
                        <input type="date" id="due_date" name="due_date" value="<?php echo $invoice['due_date']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="tax_rate">Taux de TVA (%)</label>
                        <input type="number" id="tax_rate" name="tax_rate" min="0" max="100" step="0.01" value="<?php echo $invoice['tax_rate']; ?>">
                    </div>
                    <div class="form-group">
                        <label for="currency">Devise</label>
                        <select id="currency" name="currency">
                            <option value="€" <?php echo $invoice['currency'] == '€' ? 'selected' : ''; ?>>Euro (€)</option>
                            <option value="$" <?php echo $invoice['currency'] == '$' ? 'selected' : ''; ?>>Dollar ($)</option>
                            <option value="£" <?php echo $invoice['currency'] == '£' ? 'selected' : ''; ?>>Livre (£)</option>
                            <option value="CHF" <?php echo $invoice['currency'] == 'CHF' ? 'selected' : ''; ?>>Franc Suisse (CHF)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes (optionnel)</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Notes additionnelles pour la facture..."><?php echo htmlspecialchars($invoice['notes']); ?></textarea>
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
                            <?php if (count($items) > 0): ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><input type="text" name="item_description[]" value="<?php echo htmlspecialchars($item['description']); ?>" class="item-desc"></td>
                                        <td><input type="number" name="item_quantity[]" min="1" value="<?php echo $item['quantity']; ?>" class="item-qty"></td>
                                        <td><input type="number" name="item_price[]" min="0" step="0.01" value="<?php echo $item['unit_price']; ?>" class="item-price"></td>
                                        <td class="item-total"><?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                                        <td><span class="delete-item" onclick="removeItem(this)"><i class="fas fa-trash"></i></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td><input type="text" name="item_description[]" placeholder="Description de l'article ou service" class="item-desc"></td>
                                    <td><input type="number" name="item_quantity[]" min="1" value="1" class="item-qty"></td>
                                    <td><input type="number" name="item_price[]" min="0" step="0.01" value="0.00" class="item-price"></td>
                                    <td class="item-total">0.00</td>
                                    <td><span class="delete-item" onclick="removeItem(this)"><i class="fas fa-trash"></i></span></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="3" style="text-align: right;">Sous-total</td>
                                <td id="subtotal">0.00</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="3" style="text-align: right;">TVA (<span id="tax-rate-value"><?php echo $invoice['tax_rate']; ?></span>%)</td>
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
                    <a href="view_invoice.php?id=<?php echo $invoice_id; ?>" class="btn" style="background: #6c757d; color: white;">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Mettre à jour
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