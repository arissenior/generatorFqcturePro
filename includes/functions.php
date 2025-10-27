<?php
function formatPrice($price, $currency = '€') {
    return number_format($price, 2, ',', ' ') . ' ' . $currency;
}

function generateInvoiceNumber($pdo, $user_id) {
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM invoices WHERE user_id = ? AND YEAR(created_at) = ?");
    $stmt->execute([$user_id, $year]);
    $count = $stmt->fetch()['count'];
    return "FACT-" . $year . "-" . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

function calculateInvoiceTotal($items, $tax_rate) {
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['quantity'] * $item['unit_price'];
    }
    $tax = $subtotal * ($tax_rate / 100);
    $total = $subtotal + $tax;
    
    return [
        'subtotal' => $subtotal,
        'tax' => $tax,
        'total' => $total
    ];
}

function getInvoiceStatusLabel($status) {
    $statuses = [
        'draft' => 'Brouillon',
        'sent' => 'Envoyée',
        'paid' => 'Payée',
        'cancelled' => 'Annulée'
    ];
    return $statuses[$status] ?? $status;
}
?>