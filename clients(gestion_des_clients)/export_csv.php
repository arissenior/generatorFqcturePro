<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=factures_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// En-tête du CSV
fputcsv($output, ['Numéro', 'Client', 'Date', 'Échéance', 'Montant HT', 'TVA', 'Montant TTC', 'Statut']);

$stmt = $pdo->prepare("
    SELECT i.*, c.name as client_name,
           SUM(ii.quantity * ii.unit_price) as subtotal,
           SUM(ii.quantity * ii.unit_price) * (i.tax_rate / 100) as tax_amount,
           SUM(ii.quantity * ii.unit_price) * (1 + i.tax_rate / 100) as total
    FROM invoices i 
    LEFT JOIN clients c ON i.client_id = c.id 
    LEFT JOIN invoice_items ii ON i.id = ii.invoice_id 
    WHERE i.user_id = ? 
    GROUP BY i.id
    ORDER BY i.created_at DESC
");
$stmt->execute([$user_id]);
$invoices = $stmt->fetchAll();

foreach ($invoices as $invoice) {
    fputcsv($output, [
        $invoice['invoice_number'],
        $invoice['client_name'] ?: 'Non spécifié',
        $invoice['invoice_date'],
        $invoice['due_date'],
        $invoice['subtotal'],
        $invoice['tax_amount'],
        $invoice['total'],
        $invoice['status']
    ]);
}

fclose($output);
exit;
?>