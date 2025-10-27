<?php
require_once 'config.php';

if ($_POST['action'] === 'register') {
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $company_name = $_POST['company_name'] ?? '';
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        redirect('index.php?error=email_exists');
    }
    
    $stmt = $pdo->prepare("INSERT INTO users (email, password, company_name) VALUES (?, ?, ?)");
    
    if ($stmt->execute([$email, $password, $company_name])) {
        $user_id = $pdo->lastInsertId();
        $_SESSION['user_id'] = $user_id;
        redirect('dashboard.php');
    } else {
        redirect('index.php?error=registration_failed');
    }
} elseif ($_POST['action'] === 'login') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        redirect('dashboard.php');
    } else {
        redirect('index.php?error=invalid_credentials');
    }
}
?>