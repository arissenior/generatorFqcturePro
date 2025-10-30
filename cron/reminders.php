<?php
require_once '../config.php';

// Ce script est conçu pour être exécuté via cron quotidiennement
// Exemple: 0 9 * * * /usr/bin/php /var/www/facturepro/cron/reminders.php

try {
    // Récupérer les factures en retard
    $stmt = $pdo->prepare("
        SELECT i.*, u.email as user_email, u.company_name, c.name as client_name, c.email as client_email
        FROM invoices i
        JOIN users u ON i.user_id = u.id
        LEFT JOIN clients c ON i.client_id = c.id
        WHERE i.status = 'sent' 
        AND i.due_date < CURDATE()
        AND DATEDIFF(CURDATE(), i.due_date) IN (1, 7, 30) -- Rappels à J+1, J+7, J+30
    ");
    $stmt->execute();
    $overdue_invoices = $stmt->fetchAll();

    foreach ($overdue_invoices as $invoice) {
        $days_overdue = date_diff(
            date_create($invoice['due_date']),
            date_create()
        )->days;

        $subject = "Rappel: Facture #{$invoice['invoice_number']} en retard";
        $message = "
            Bonjour {$invoice['client_name']},

            Nous vous rappelons que la facture #{$invoice['invoice_number']} 
            d'un montant de " . number_format(calculateInvoiceTotal(getInvoiceItems($pdo, $invoice['id']), $invoice['tax_rate'])['total'], 2, ',', ' ') . " {$invoice['currency']}
            était due le " . date('d/m/Y', strtotime($invoice['due_date'])) . ".

            Elle est actuellement en retard de {$days_overdue} jour(s).

            Merci de régulariser votre situation au plus vite.

            Cordialement,
            {$invoice['company_name']}
        ";

        // Envoyer l'email (implémentation basique)
        if ($invoice['client_email']) {
            mail($invoice['client_email'], $subject, $message);
        }

        // Enregistrer le rappel
        $stmt = $pdo->prepare("
            INSERT INTO reminders (user_id, invoice_id, reminder_date, type, sent_to) 
            VALUES (?, ?, CURDATE(), 'email', ?)
        ");
        $stmt->execute([$invoice['user_id'], $invoice['id'], $invoice['client_email']]);

        // Mettre à jour le statut de la facture
        $stmt = $pdo->prepare("UPDATE invoices SET status = 'overdue' WHERE id = ?");
        $stmt->execute([$invoice['id']]);
    }

    echo "Rappels envoyés: " . count($overdue_invoices) . "\n";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}

function getInvoiceItems($pdo, $invoice_id) {
    $stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
    $stmt->execute([$invoice_id]);
    return $stmt->fetchAll();
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
?>