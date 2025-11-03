<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$invoice_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

// Vérifier que la facture appartient à l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
$stmt->execute([$invoice_id, $user_id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    redirect('invoices.php?error=not_found');
}

// Envoyer le rappel (simulé)
$stmt = $pdo->prepare("INSERT INTO reminders (user_id, invoice_id, reminder_date) VALUES (?, ?, CURDATE())");
$stmt->execute([$user_id, $invoice_id]);

// Enregistrer l'activité
$stmt = $pdo->prepare("INSERT INTO activities (user_id, description, related_id, related_type) VALUES (?, ?, ?, 'invoice')");
$stmt->execute([$user_id, "Rappel envoyé pour la facture #" . $invoice['invoice_number'], $invoice_id]);

redirect('invoices.php?success=reminder_sent');
?>