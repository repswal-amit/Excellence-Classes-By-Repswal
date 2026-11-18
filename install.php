<?php
/**
 * Installation & Setup Wizard
 * Excellence Classes By Repswal
 * 
 * Provides an automated web installer to configure database,
 * verify server requirements, import database.sql, and lock installation.
 */

define('ROOT_PATH', __DIR__);
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('LOCK_FILE', STORAGE_PATH . '/installed.lock');

// Check if already installed
$isInstalled = file_exists(LOCK_FILE);

// System Requirements Check
$requirements = [
    'PHP Version >= 8.0' => [
        'status' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'current' => PHP_VERSION,
        'required' => '>= 8.0.0'
    ],
    'PDO Extension' => [
        'status' => extension_loaded('pdo'),
        'current' => extension_loaded('pdo') ? 'Enabled' : 'Missing',
        'required' => 'Enabled'
    ],
    'PDO MySQL Driver' => [
        'status' => extension_loaded('pdo_mysql'),
        'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Missing',
        'required' => 'Enabled'
    ],
    'OpenSSL Extension' => [
        'status' => extension_loaded('openssl'),
        'current' => extension_loaded('openssl') ? 'Enabled' : 'Missing',
        'required' => 'Enabled'
    ],
    'MBString Extension' => [
        'status' => extension_loaded('mbstring'),
        'current' => extension_loaded('mbstring') ? 'Enabled' : 'Missing',
        'required' => 'Enabled'
    ],
    'JSON Extension' => [
        'status' => extension_loaded('json'),
        'current' => extension_loaded('json') ? 'Enabled' : 'Missing',
        'required' => 'Enabled'
    ],
    'GD / Image Extension' => [
        'status' => extension_loaded('gd'),
        'current' => extension_loaded('gd') ? 'Enabled' : 'Optional',
        'required' => 'Recommended'
    ],
    'Storage Directory Writable' => [
        'status' => is_writable(ROOT_PATH . '/storage') || (!file_exists(ROOT_PATH . '/storage') && is_writable(ROOT_PATH)),
        'current' => is_writable(ROOT_PATH . '/storage') ? 'Writable' : 'Not Writable',
        'required' => 'Writable'
    ],
    'Uploads Directory Writable' => [
        'status' => is_writable(ROOT_PATH . '/uploads') || (!file_exists(ROOT_PATH . '/uploads') && is_writable(ROOT_PATH)),
        'current' => is_writable(ROOT_PATH . '/uploads') ? 'Writable' : 'Not Writable',
        'required' => 'Writable'
    ],
];

$allReqsPassed = true;
foreach ($requirements as $name => $req) {
    if (!$req['status'] && $name !== 'GD / Image Extension') {
        $allReqsPassed = false;
    }
}

$error = '';
$success = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;

// Handle Installation POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isInstalled) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'install') {
        $dbHost = trim($_POST['db_host'] ?? 'localhost');
        $dbPort = trim($_POST['db_port'] ?? '3306');
        $dbName = trim($_POST['db_name'] ?? 'excellence_classes');
        $dbUser = trim($_POST['db_user'] ?? 'root');
        $dbPass = $_POST['db_pass'] ?? '';
        
        $adminName = trim($_POST['admin_name'] ?? 'Amit Repswal');
        $adminEmail = trim($_POST['admin_email'] ?? 'admin@excellenceclasses.com');
        $adminPass = $_POST['admin_password'] ?? 'Admin@123';
        
        if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
            $error = 'Please fill in all required database connection details.';
        } elseif (strlen($adminPass) < 8) {
            $error = 'Admin password must be at least 8 characters long.';
        } else {
            try {
                // 1. Test MySQL server connection without DB first
                $dsnWithoutDb = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
                $pdo = new PDO($dsnWithoutDb, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5
                ]);
                
                // 2. Create database if it does not exist
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                $pdo->exec("USE `{$dbName}`;");
                
                // 3. Read and execute database.sql
                $sqlFile = ROOT_PATH . '/database.sql';
                if (!file_exists($sqlFile)) {
                    throw new Exception("database.sql file not found at " . $sqlFile);
                }
                
                $sqlContent = file_get_contents($sqlFile);
                
                // Split queries properly
                // Temporarily disable foreign keys during mass import
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
                
                // Remove comment blocks and split by semicolon
                $lines = explode("\n", $sqlContent);
                $query = '';
                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if (str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*') || empty($trimmed)) {
                        continue;
                    }
                    $query .= $line . "\n";
                    if (str_ends_with($trimmed, ';')) {
                        // Avoid executing duplicate CREATE DATABASE / USE statements
                        if (!stripos($query, 'CREATE DATABASE') && !stripos($query, 'USE `excellence_classes`')) {
                            try {
                                $pdo->exec($query);
                            } catch (PDOException $e) {
                                // Ignore table already exists warnings
                            }
                        }
                        $query = '';
                    }
                }
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
                
                // 4. Update / create administrator account
                $checkAdmin = $pdo->prepare("SELECT id FROM admins WHERE email = ? LIMIT 1");
                $checkAdmin->execute([$adminEmail]);
                $existingAdmin = $checkAdmin->fetch(PDO::FETCH_ASSOC);
                
                $hashedPassword = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
                
                if ($existingAdmin) {
                    $updAdmin = $pdo->prepare("UPDATE admins SET full_name = ?, password = ?, is_active = 1 WHERE id = ?");
                    $updAdmin->execute([$adminName, $hashedPassword, $existingAdmin['id']]);
                } else {
                    $insAdmin = $pdo->prepare("
                        INSERT INTO admins (role_id, full_name, email, password, designation, is_active, created_at)
                        VALUES (1, ?, ?, ?, 'Proprietor & Lead Instructor', 1, NOW())
                    ");
                    $insAdmin->execute([$adminName, $adminEmail, $hashedPassword]);
                }
                
                // 5. Generate / Update .env file
                $envContent = "# Environment Configuration\n";
                $envContent .= "# Excellence Classes By Repswal\n";
                $envContent .= "# Generated automatically by Setup Wizard\n\n";
                $envContent .= "APP_NAME=\"Excellence Classes By Repswal\"\n";
                $envContent .= "APP_URL=\"" . (isset($_SERVER['HTTP_HOST']) ? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\')) : 'http://localhost/excellence-classes') . "\"\n";
                $envContent .= "APP_ENV=\"production\"\n";
                $envContent .= "APP_DEBUG=false\n\n";
                $envContent .= "# Database\n";
                $envContent .= "DB_HOST=\"{$dbHost}\"\n";
                $envContent .= "DB_PORT=\"{$dbPort}\"\n";
                $envContent .= "DB_NAME=\"{$dbName}\"\n";
                $envContent .= "DB_USER=\"{$dbUser}\"\n";
                $envContent .= "DB_PASS=\"{$dbPass}\"\n\n";
                $envContent .= "# Mail Configuration\n";
                $envContent .= "MAIL_HOST=\"\"\n";
                $envContent .= "MAIL_PORT=587\n";
                $envContent .= "MAIL_USERNAME=\"\"\n";
                $envContent .= "MAIL_PASSWORD=\"\"\n";
                $envContent .= "MAIL_FROM_ADDRESS=\"noreply@excellenceclasses.com\"\n";
                $envContent .= "MAIL_FROM_NAME=\"Excellence Classes By Repswal\"\n";
                
                file_put_contents(ROOT_PATH . '/.env', $envContent);
                
                // 6. Ensure storage directories exist
                $dirs = [
                    STORAGE_PATH,
                    STORAGE_PATH . '/certificates',
                    STORAGE_PATH . '/documents',
                    STORAGE_PATH . '/logs',
                    ROOT_PATH . '/uploads',
                    ROOT_PATH . '/uploads/avatars',
                    ROOT_PATH . '/uploads/submissions',
                    ROOT_PATH . '/uploads/temp'
                ];
                foreach ($dirs as $d) {
                    if (!is_dir($d)) {
                        @mkdir($d, 0755, true);
                    }
                }
                
                // 7. Write lock file
                file_put_contents(LOCK_FILE, json_encode([
                    'installed_at' => date('Y-m-d H:i:s'),
                    'version' => '1.0.0',
                    'admin_email' => $adminEmail,
                    'app_url' => isset($_SERVER['HTTP_HOST']) ? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\')) : 'http://localhost/excellence-classes'
                ], JSON_PRETTY_PRINT));
                
                $isInstalled = true;
                $success = 'Installation successfully completed! All database tables, seed data, and administrator account have been configured.';
                $step = 3;
            } catch (Exception $e) {
                $error = 'Installation Error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Wizard - Excellence Classes By Repswal</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Main Style -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .installer-container {
            max-width: 860px;
            margin: 40px auto;
        }
        .step-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            border-radius: 50px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 600;
        }
        .step-pill.active {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.4);
            color: #818cf8;
        }
        .step-pill.completed {
            background: rgba(16, 185, 129, 0.15);
            border-color: rgba(16, 185, 129, 0.4);
            color: #34d399;
        }
        .step-num {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            font-size: 0.8rem;
        }
        .step-pill.active .step-num {
            background: #6366f1;
            color: #fff;
        }
        .step-pill.completed .step-num {
            background: #10b981;
            color: #fff;
        }
        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
        }
    </style>
</head>
<body class="bg-dark text-light">
    <div class="container installer-container">
        
        <!-- Header -->
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle p-3 mb-3 border border-primary border-opacity-25 shadow-sm">
                <i class="bi bi-mortarboard-fill fs-2"></i>
            </div>
            <h2 class="fw-bold mb-1">Excellence Classes By Repswal</h2>
            <p class="text-muted mb-0">System Installation & Environment Setup Wizard</p>
            <div class="mt-2">
                <span class="badge bg-secondary bg-opacity-25 text-info border border-secondary border-opacity-25 px-3 py-1">
                    <i class="bi bi-shield-check me-1"></i> Sole Proprietorship &bull; Nawalgarh, Rajasthan
                </span>
            </div>
        </div>

        <?php if ($isInstalled && $step !== 3): ?>
            <!-- Already Installed Alert -->
            <div class="glass-panel text-center py-5">
                <div class="mb-3 text-warning">
                    <i class="bi bi-lock-fill fs-1"></i>
                </div>
                <h4 class="fw-bold text-white mb-2">System Already Installed</h4>
                <p class="text-muted mb-4 max-w-md mx-auto">
                    The portal has already been configured and locked for security. To re-run this setup wizard, please manually delete the lock file at <code>storage/installed.lock</code>.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="index.php" class="btn btn-outline-light px-4 py-2">
                        <i class="bi bi-house me-2"></i> Visit Homepage
                    </a>
                    <a href="admin/login.php" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-shield-lock me-2"></i> Admin Login
                    </a>
                </div>
            </div>
        <?php else: ?>

            <!-- Wizard Progress Steps -->
            <div class="d-flex justify-content-between mb-4 flex-wrap gap-2">
                <div class="step-pill <?= $step === 1 ? 'active' : ($step > 1 ? 'completed' : '') ?>">
                    <span class="step-num"><?= $step > 1 ? '<i class="bi bi-check"></i>' : '1' ?></span>
                    <span>1. Requirements</span>
                </div>
                <div class="step-pill <?= $step === 2 ? 'active' : ($step > 2 ? 'completed' : '') ?>">
                    <span class="step-num"><?= $step > 2 ? '<i class="bi bi-check"></i>' : '2' ?></span>
                    <span>2. Database & Admin</span>
                </div>
                <div class="step-pill <?= $step === 3 ? 'active completed' : '' ?>">
                    <span class="step-num">3</span>
                    <span>3. Completed</span>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <!-- STEP 1: Requirements Check -->
                <div class="glass-panel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-bold mb-0 text-white">
                            <i class="bi bi-cpu text-primary me-2"></i> System Environment Check
                        </h4>
                        <span class="badge <?= $allReqsPassed ? 'bg-success' : 'bg-warning text-dark' ?> px-3 py-2">
                            <?= $allReqsPassed ? 'All Requirements Met' : 'Action Required' ?>
                        </span>
                    </div>
                    <p class="text-muted mb-4">
                        Please review the server prerequisites below before continuing with the automated installation.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0 border-secondary">
                            <thead>
                                <tr class="text-muted text-uppercase fs-xs">
                                    <th>Prerequisite</th>
                                    <th>Required</th>
                                    <th>Detected</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requirements as $name => $req): ?>
                                    <tr>
                                        <td class="fw-semibold text-white">
                                            <?= htmlspecialchars($name) ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?= htmlspecialchars($req['required']) ?>
                                        </td>
                                        <td class="text-muted small">
                                            <?= htmlspecialchars($req['current']) ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($req['status']): ?>
                                                <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 px-2 py-1">
                                                    <i class="bi bi-check-circle me-1"></i> Passed
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 px-2 py-1">
                                                    <i class="bi bi-x-circle me-1"></i> Failed
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                        <a href="install.php?step=2" class="btn btn-primary px-4 <?= $allReqsPassed ? '' : 'disabled' ?>">
                            Proceed to Database Setup <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

            <?php elseif ($step === 2): ?>
                <!-- STEP 2: Database Configuration & Admin -->
                <div class="glass-panel">
                    <h4 class="fw-bold mb-2 text-white">
                        <i class="bi bi-database-check text-primary me-2"></i> Database & Administrator Setup
                    </h4>
                    <p class="text-muted mb-4">
                        Enter your MySQL database credentials. The installer will automatically verify the connection, create the <code>excellence_classes</code> database, import all tables and seed data, and configure the Administrator profile.
                    </p>

                    <form method="POST" action="install.php?step=2" id="installerForm">
                        <input type="hidden" name="action" value="install">
                        
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <h6 class="text-primary text-uppercase fw-bold fs-xs tracking-wider border-bottom border-secondary border-opacity-25 pb-2 mb-3">
                                    <i class="bi bi-hdd-network me-1"></i> MySQL Connection
                                </h6>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label text-muted small">Database Host</label>
                                <input type="text" name="db_host" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                                <div class="form-text text-muted">Usually <code>localhost</code> or <code>127.0.0.1</code></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted small">Database Port</label>
                                <input type="number" name="db_port" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>" required>
                                <div class="form-text text-muted">Default MySQL port is <code>3306</code> (or <code>3307</code>)</div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label text-muted small">Database Name</label>
                                <input type="text" name="db_name" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($_POST['db_name'] ?? 'excellence_classes') ?>" required>
                                <div class="form-text text-muted">Will be created automatically if it doesn't already exist.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Database Username</label>
                                <input type="text" name="db_user" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Database Password</label>
                                <input type="password" name="db_pass" class="form-control bg-dark text-white border-secondary" placeholder="Leave blank if no password" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <h6 class="text-primary text-uppercase fw-bold fs-xs tracking-wider border-bottom border-secondary border-opacity-25 pb-2 mb-3">
                                    <i class="bi bi-person-badge me-1"></i> Proprietor Admin Account
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Administrator Name</label>
                                <input type="text" name="admin_name" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($_POST['admin_name'] ?? 'Amit Repswal') ?>" required>
                                <div class="form-text text-muted">Sole Proprietor authority</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted small">Admin Email Address</label>
                                <input type="email" name="admin_email" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($_POST['admin_email'] ?? 'admin@excellenceclasses.com') ?>" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label text-muted small">Admin Password</label>
                                <input type="password" name="admin_password" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($_POST['admin_password'] ?? 'Admin@123') ?>" minlength="8" required>
                                <div class="form-text text-muted">Minimum 8 characters. Default is <code>Admin@123</code>.</div>
                            </div>
                        </div>

                        <div class="pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                            <a href="install.php?step=1" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary px-4 py-2" id="submitBtn">
                                <span class="spinner-border spinner-border-sm me-2 d-none" id="btnSpinner"></span>
                                <i class="bi bi-lightning-charge-fill me-1" id="btnIcon"></i> Run Installation & Migrate
                            </button>
                        </div>
                    </form>
                </div>

            <?php elseif ($step === 3): ?>
                <!-- STEP 3: Completed Successfully -->
                <div class="glass-panel text-center py-5">
                    <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-25 text-success rounded-circle p-4 mb-3 border border-success border-opacity-25">
                        <i class="bi bi-check-circle-fill display-5"></i>
                    </div>
                    <h3 class="fw-bold text-white mb-2">Installation Complete!</h3>
                    <p class="text-muted mb-4 max-w-lg mx-auto">
                        Excellence Classes By Repswal Internship & Certification Portal is now fully deployed and operational. The database has been seeded, the <code>.env</code> file is written, and the installation has been securely locked.
                    </p>

                    <div class="bg-dark bg-opacity-50 border border-secondary border-opacity-25 rounded-3 p-3 max-w-md mx-auto mb-4 text-start small">
                        <div class="d-flex justify-content-between py-1 border-bottom border-secondary border-opacity-25">
                            <span class="text-muted">Administrator:</span>
                            <span class="text-white fw-semibold">Amit Repswal (Proprietor)</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom border-secondary border-opacity-25">
                            <span class="text-muted">Admin Email:</span>
                            <span class="text-info fw-semibold"><?= htmlspecialchars($_POST['admin_email'] ?? 'admin@excellenceclasses.com') ?></span>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Installation Lock:</span>
                            <span class="badge bg-success bg-opacity-25 text-success">Active (Protected)</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-3">
                        <a href="index.php" class="btn btn-outline-light px-4 py-2">
                            <i class="bi bi-house me-2"></i> Public Website
                        </a>
                        <a href="admin/login.php" class="btn btn-primary px-4 py-2 shadow-sm">
                            <i class="bi bi-speedometer2 me-2"></i> Go to Admin Dashboard
                        </a>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <!-- Footer -->
        <div class="text-center text-muted small mt-4 pt-3">
            &copy; <?= date('Y') ?> Excellence Classes By Repswal &bull; Learn. Develop. Grow.
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const installerForm = document.getElementById('installerForm');
        if (installerForm) {
            installerForm.addEventListener('submit', function() {
                const btn = document.getElementById('submitBtn');
                const spinner = document.getElementById('btnSpinner');
                const icon = document.getElementById('btnIcon');
                if (btn) {
                    btn.disabled = true;
                    if (spinner) spinner.classList.remove('d-none');
                    if (icon) icon.classList.add('d-none');
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Installing & Migrating...';
                }
            });
        }
    </script>
</body>
</html>
