<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $expense_date = $_POST['expense_date'];
    $category = $_POST['category'];
    
    $stmt = $pdo->prepare("
        INSERT INTO expenses (user_id, amount, description, expense_date, category) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute([$user_id, $amount, $description, $expense_date, $category])) {
        // Enregistrer l'activité
        $stmt = $pdo->prepare("INSERT INTO activities (user_id, description, related_id, related_type) VALUES (?, ?, ?, 'expense')");
        $stmt->execute([$user_id, "Dépense ajoutée: $description", $pdo->lastInsertId()]);
        
        redirect('expenses.php?success=created');
    }
}
?>

<!-- Formulaire d'ajout de dépense -->  
<form action="" method="post">
    <div>
        <label for="amount">Montant:</label>
        <input type="number" name="amount" id="amount" required>
    </div>
    <div>
        <label for="description">Description:</label>
        <input type="text" name="description" id="description" required>
    </div>
    <div>
        <label for="expense_date">Date de la dépense:</label>
        <input type="date" name="expense_date" id="expense_date" required>
    </div>
    <div>
        <label for="category">Catégorie:</label>
        <input type="text" name="category" id="category" required>
    </div>
    <button type="submit">Ajouter la dépense</button>
</form>