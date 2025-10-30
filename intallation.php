<?php
// Script d'installation automatique
$config = [
    'db_host' => 'localhost',
    'db_name' => 'facture_pro',
    'db_user' => 'root',
    'db_pass' => '',
    'site_url' => 'http://localhost/facturepro',
    'admin_email' => 'admin@facturepro.fr',
    'admin_password' => 'password123'
];

try {
    // Vérifier les dépendances
    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO Extension' => extension_loaded('pdo'),
        'MySQL PDO Driver' => extension_loaded('pdo_mysql'),
        'GD Extension' => extension_loaded('gd'),
        'JSON Extension' => extension_loaded('json'),
        'MBString Extension' => extension_loaded('mbstring'),
    ];

    foreach ($requirements as $requirement => $met) {
        if (!$met) {
            throw new Exception("Requirement not met: $requirement");
        }
    }

    // Créer la base de données
    $pdo = new PDO("mysql:host={$config['db_host']}", $config['db_user'], $config['db_pass']);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$config['db_name']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE {$config['db_name']}");

    // Exécuter le script SQL
    $sql = file_get_contents('sql/database_enhanced.sql');
    $pdo->exec($sql);

    // Créer l'administrateur
    $password_hash = password_hash($config['admin_password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (email, password, company_name) VALUES (?, ?, 'Administrateur')");
    $stmt->execute([$config['admin_email'], $password_hash]);

    // Créer le fichier config.php
    $config_content = "<?php
session_start();

define('DB_HOST', '{$config['db_host']}');
define('DB_NAME', '{$config['db_name']}');
define('DB_USER', '{$config['db_user']}');
define('DB_PASS', '{$config['db_pass']}');
define('SITE_URL', '{$config['site_url']}');
define('UPLOAD_PATH', __DIR__ . '/assets/uploads/');

try {
    \$pdo = new PDO(\"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME, DB_USER, DB_PASS);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->exec(\"SET NAMES utf8mb4\");
} catch(PDOException \$e) {
    die(\"Erreur de connexion: \" . \$e->getMessage());
}

function isLoggedIn() {
    return isset(\$_SESSION['user_id']);
}

function redirect(\$url) {
    header(\"Location: \$url\");
    exit;
}

function getCurrentUser(\$pdo) {
    if (!isLoggedIn()) return null;
    
    \$stmt = \$pdo->prepare(\"SELECT * FROM users WHERE id = ?\");
    \$stmt->execute([\$_SESSION['user_id']]);
    return \$stmt->fetch();
}
?>";

    file_put_contents('config.php', $config_content);

    // Créer les répertoires nécessaires
    $directories = [
        'assets/uploads',
        'exports/pdf',
        'exports/excel',
        'templates',
        'lang',
        'cron',
        'api',
        'includes',
        'invoices',
        ' mobile'
    ];

    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    echo "Installation terminée avec succès!\n";
    echo "URL: {$config['site_url']}\n";
    echo "Email admin: {$config['admin_email']}\n";
    echo "Mot de passe: {$config['admin_password']}\n";

} catch (Exception $e) {
    echo "Erreur lors de l'installation: " . $e->getMessage() . "\n";
}
?>