<?php
/**
 * Command-Line Setup Script
 * Excellence Classes By Repswal
 * 
 * Usage:
 *   php setup.php
 *   php setup.php --pass=your_mysql_password
 *   php setup.php --host=localhost --port=3306 --user=root --pass=secret --db=excellence_classes
 */

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line interface.\n");
}

echo "========================================================\n";
echo "   EXCELLENCE CLASSES BY REPSWAL\n";
echo "   Automated CLI Setup & Database Migration Tool\n";
echo "========================================================\n\n";

$options = getopt('', [
    'host::',
    'port::',
    'db::',
    'user::',
    'pass::',
    'admin-email::',
    'admin-pass::',
    'force'
]);

$host = $options['host'] ?? 'localhost';
$port = $options['port'] ?? '3306';
$dbName = $options['db'] ?? 'excellence_classes';
$user = $options['user'] ?? 'root';
$pass = $options['pass'] ?? '';
$adminEmail = $options['admin-email'] ?? 'admin@excellenceclasses.com';
$adminPass = $options['admin-pass'] ?? 'Admin@123';
$force = isset($options['force']);

$rootPath = __DIR__;
$lockFile = $rootPath . '/storage/installed.lock';

if (file_exists($lockFile) && !$force) {
    echo "[INFO] System is already marked as installed (found storage/installed.lock).\n";
    echo "       Use --force to re-run migration.\n";
    exit(0);
}

echo "[1/6] Testing connection to MySQL server on {$host}:{$port}...\n";
try {
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);
    echo "      ✓ MySQL connection successful!\n";
} catch (PDOException $e) {
    echo "      ✗ Connection failed: " . $e->getMessage() . "\n";
    echo "\n[TIP] If your MySQL root user requires a password, run:\n";
    echo "      php setup.php --pass=YOUR_PASSWORD\n";
    echo "      Or if running on a different port (e.g. 3307):\n";
    echo "      php setup.php --port=3307 --pass=YOUR_PASSWORD\n";
    exit(1);
}

echo "[2/6] Verifying/Creating database `{$dbName}`...\n";
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
$pdo->exec("USE `{$dbName}`;");
echo "      ✓ Database `{$dbName}` ready.\n";

echo "[3/6] Importing schema and seed data from database.sql...\n";
$sqlFile = $rootPath . '/database.sql';
if (!file_exists($sqlFile)) {
    echo "      ✗ database.sql not found at {$sqlFile}\n";
    exit(1);
}

$sqlContent = file_get_contents($sqlFile);
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

$lines = explode("\n", $sqlContent);
$query = '';
$queriesRun = 0;

foreach ($lines as $line) {
    $trimmed = trim($line);
    if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*') || empty($trimmed)) {
        continue;
    }
    $query .= $line . "\n";
    if (str_ends_with($trimmed, ';')) {
        if (!stripos($query, 'CREATE DATABASE') && !stripos($query, 'USE `excellence_classes`')) {
            try {
                $pdo->exec($query);
                $queriesRun++;
            } catch (PDOException $e) {
                // table might already exist
            }
        }
        $query = '';
    }
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
echo "      ✓ Executed {$queriesRun} database statements successfully.\n";

echo "[4/6] Configuring Proprietor Administrator account...\n";
$check = $pdo->prepare("SELECT id FROM admins WHERE email = ? LIMIT 1");
$check->execute([$adminEmail]);
$admin = $check->fetch(PDO::FETCH_ASSOC);

$hashedPass = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
if ($admin) {
    $upd = $pdo->prepare("UPDATE admins SET full_name = 'Amit Repswal', password = ?, is_active = 1 WHERE id = ?");
    $upd->execute([$hashedPass, $admin['id']]);
} else {
    $ins = $pdo->prepare("
        INSERT INTO admins (role_id, full_name, email, password, designation, is_active, created_at)
        VALUES (1, 'Amit Repswal', ?, ?, 'Proprietor & Lead Instructor', 1, NOW())
    ");
    $ins->execute([$adminEmail, $hashedPass]);
}
echo "      ✓ Administrator account configured:\n";
echo "        - Name: Amit Repswal (Proprietor)\n";
echo "        - Email: {$adminEmail}\n";
echo "        - Password: {$adminPass}\n";

echo "[5/6] Generating .env configuration file...\n";
$envContent = "# Environment Configuration\n";
$envContent .= "# Excellence Classes By Repswal\n";
$envContent .= "APP_NAME=\"Excellence Classes By Repswal\"\n";
$envContent .= "APP_URL=\"http://localhost/excellence-classes\"\n";
$envContent .= "APP_ENV=\"development\"\n";
$envContent .= "APP_DEBUG=true\n\n";
$envContent .= "# Database\n";
$envContent .= "DB_HOST=\"{$host}\"\n";
$envContent .= "DB_PORT=\"{$port}\"\n";
$envContent .= "DB_NAME=\"{$dbName}\"\n";
$envContent .= "DB_USER=\"{$user}\"\n";
$envContent .= "DB_PASS=\"{$pass}\"\n\n";
$envContent .= "# Mail Configuration\n";
$envContent .= "MAIL_HOST=\"\"\n";
$envContent .= "MAIL_PORT=587\n";
$envContent .= "MAIL_USERNAME=\"\"\n";
$envContent .= "MAIL_PASSWORD=\"\"\n";
$envContent .= "MAIL_FROM_ADDRESS=\"noreply@excellenceclasses.com\"\n";
$envContent .= "MAIL_FROM_NAME=\"Excellence Classes By Repswal\"\n";

file_put_contents($rootPath . '/.env', $envContent);
echo "      ✓ .env created successfully.\n";

echo "[6/6] Ensuring storage directories & creating installation lock...\n";
$dirs = [
    $rootPath . '/storage',
    $rootPath . '/storage/certificates',
    $rootPath . '/storage/documents',
    $rootPath . '/storage/logs',
    $rootPath . '/uploads',
    $rootPath . '/uploads/avatars',
    $rootPath . '/uploads/submissions',
    $rootPath . '/uploads/temp'
];
foreach ($dirs as $d) {
    if (!is_dir($d)) {
        @mkdir($d, 0755, true);
    }
}

file_put_contents($lockFile, json_encode([
    'installed_at' => date('Y-m-d H:i:s'),
    'version' => '1.0.0',
    'method' => 'cli',
    'admin_email' => $adminEmail
], JSON_PRETTY_PRINT));

echo "      ✓ Storage initialized and lock placed at storage/installed.lock.\n\n";
echo "========================================================\n";
echo "   INSTALLATION COMPLETED SUCCESSFULLY!\n";
echo "   Public Website:  http://localhost/excellence-classes/index.php\n";
echo "   Admin Portal:    http://localhost/excellence-classes/admin/login.php\n";
echo "   Admin Login:     {$adminEmail}\n";
echo "   Admin Password:  {$adminPass}\n";
echo "========================================================\n";
