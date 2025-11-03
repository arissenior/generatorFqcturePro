<?php
function generateInvoiceHTML($invoice, $items, $subtotal, $tax_amount, $total_amount) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Facture <?php echo $invoice['invoice_number']; ?></title>
         <link rel="stylesheet" href="assets/css/default.css">  
    </head>
    <body>
        <div class="header">
            <div class="company-info">
                <h1 class="invoice-title">FACTURE</h1>
                <h2><?php echo htmlspecialchars($invoice['company_name']); ?></h2>
                <?php if ($invoice['siret']): ?>
                    <p>SIRET: <?php echo htmlspecialchars($invoice['siret']); ?></p>
                <?php endif; ?>
            </div>
            <div class="invoice-info">
                <p><strong>N°:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($invoice['invoice_date'])); ?></p>
                <p><strong>Échéance:</strong> <?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?></p>
            </div>
        </div>

        <div class="parties">
            <div class="party">
                <h3>De:</h3>
                <p><strong><?php echo htmlspecialchars($invoice['company_name']); ?></strong></p>
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
            <div class="party">
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
            <div class="notes">
                <h4>Notes:</h4>
                <p><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
            </div>
        <?php endif; ?>

        <div class="footer">
            <p>Facture générée avec FacturePro - www.facturepro.fr</p>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>