<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if (isset($_GET['error'])) {
    $errors = [
        'invalid_credentials' => 'Email ou mot de passe incorrect',
        'email_exists' => 'Cet email est déjà utilisé',
        'registration_failed' => 'Erreur lors de l\'inscription',
        'required_fields' => 'Veuillez remplir tous les champs'
    ];
    $error = $errors[$_GET['error']] ?? 'Une erreur est survenue';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FacturePro - Connexion</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <i class="fas fa-file-invoice-dollar"></i>
                <h1>FacturePro</h1>
            </div>
            
            <div class="tabs">
                <div class="tab active" data-tab="login">Connexion</div>
                <div class="tab" data-tab="register">Inscription</div>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="tab-content active" id="login-tab">
                <form action="auth.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn">Se connecter</button>
                </form>
            </div>
            
            <div class="tab-content" id="register-tab">
                <form action="auth.php" method="POST">
                    <input type="hidden" name="action" value="register">
                    <div class="form-group">
                        <label for="reg_email">Email</label>
                        <input type="email" id="reg_email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_password">Mot de passe</label>
                        <input type="password" id="reg_password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="company_name">Nom de l'entreprise (optionnel)</label>
                        <input type="text" id="company_name" name="company_name">
                    </div>
                    <button type="submit" class="btn">S'inscrire</button>
                </form>
            </div>
        </div>
    </div>

   <script src="assets/js/index.js"></script>
</body>
</html>