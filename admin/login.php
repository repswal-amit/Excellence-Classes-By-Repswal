<?php
/**
 * Admin Login Page
 * Excellence Classes By Repswal
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';

initSession();

// If already logged in as admin, redirect to admin dashboard
if (isAdminLoggedIn()) {
    redirect(baseUrl('admin/dashboard.php'));
}

$pdo = null;
$dbError = null;
try {
    $pdo = getDBConnection();
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

$errors = [];
$email = trim($_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_admin_login'])) {
    if (!$pdo) {
        $errors[] = 'Database connection is currently not established. Please configure MySQL credentials in the setup wizard or .env file.';
    } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid administrative email.';
        }
        if (empty($password)) {
            $errors[] = 'Please enter your administrative password.';
        }

        if (empty($errors)) {
            // Check rate limiting
            if (isLoginRateLimited($pdo, $email, 'admin')) {
                $errors[] = 'Too many failed administrative attempts. Account temporarily locked for 15 minutes.';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        SELECT a.*, r.role_name, r.role_slug, r.permissions 
                        FROM admins a
                        JOIN admin_roles r ON a.role_id = r.id
                        WHERE a.email = ? LIMIT 1
                    ");
                    $stmt->execute([$email]);
                    $admin = $stmt->fetch();

                    if ($admin && password_verify($password, $admin['password'])) {
                        if ((int)$admin['is_active'] !== 1) {
                            $errors[] = 'Your administrative access has been suspended. Please contact the Proprietor.';
                            recordLoginAttempt($pdo, $email, 'admin', false);
                        } else {
                            recordLoginAttempt($pdo, $email, 'admin', true);

                            // Reset failed attempts & update last login
                            $update = $pdo->prepare("UPDATE admins SET last_login = NOW(), login_attempts = 0 WHERE id = ?");
                            $update->execute([$admin['id']]);

                            $permissions = [];
                            if (!empty($admin['permissions'])) {
                                $decoded = json_decode($admin['permissions'], true);
                                if (is_array($decoded)) $permissions = $decoded;
                            }

                            loginAdmin($admin, $permissions);

                            // Audit log
                            logAdminAction($pdo, $admin['id'], 'Login', 'Admin', $admin['id'], null, ['ip' => getUserIP()]);

                            setFlash('success', 'Welcome, ' . htmlspecialchars($admin['full_name']) . '! Administrative session active.');
                            redirect(baseUrl('admin/dashboard.php'));
                        }
                    } else {
                        recordLoginAttempt($pdo, $email, 'admin', false);
                        $errors[] = 'Invalid administrative credentials.';
                    }

                } catch (PDOException $e) {
                    $errors[] = 'Server error during authentication. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Login | Excellence Classes By Repswal</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= baseUrl('assets/css/style.css') ?>" rel="stylesheet">
    <link href="<?= baseUrl('assets/css/dashboard.css') ?>" rel="stylesheet">
</head>
<body style="min-height: 100vh; background: radial-gradient(circle at 50% 20%, #162d50 0%, #0a1628 100%); display: flex; align-items: center; justify-content: center; padding: 2rem 1rem;">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-4 col-md-6">
            
            <div class="text-center mb-4">
                <a href="<?= baseUrl() ?>" class="d-inline-flex align-items-center gap-2 text-decoration-none text-white mb-3">
                    <div class="sidebar-brand-icon mx-auto" style="width: 52px; height: 52px; font-size: 1.6rem;">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                </a>
                <h3 class="fw-bold text-white mb-1">Administrative Portal</h3>
                <p class="text-white-50 small">Excellence Classes By Repswal</p>
            </div>

            <?php if (!$pdo): ?>
                <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
                    <div class="d-flex align-items-start gap-3">
                        <i class="bi bi-database-exclamation fs-3 text-warning"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Database Setup Required</h6>
                            <p class="small text-muted mb-2">
                                Unable to connect to MySQL. Configure credentials in <code>.env</code> or run the setup wizard.
                                <?php if (!empty($dbError)): ?>
                                    <br><span class="text-danger small font-monospace">Details: <?= e($dbError) ?></span>
                                <?php endif; ?>
                            </p>
                            <a href="<?= baseUrl('install.php') ?>" class="btn btn-warning btn-sm fw-semibold">
                                <i class="bi bi-magic me-1"></i> Open Setup Wizard
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-shield-x"></i>
                        <strong>Access Denied:</strong>
                    </div>
                    <ul class="mb-0 ps-3 small">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-lg p-4 p-md-5" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(16px); border-radius: 20px;">
                <form action="<?= baseUrl('admin/login.php') ?>" method="POST" class="needs-validation" novalidate>
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label for="email" class="form-label text-dark fw-semibold small">Admin Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-shield-lock text-muted"></i></span>
                            <input type="email" class="form-control border-start-0" id="email" name="email" value="<?= e($email) ?>" placeholder="admin@excellenceclasses.com" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label text-dark fw-semibold small">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-key text-muted"></i></span>
                            <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" name="submit_admin_login" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold shadow">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Authenticate Admin
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <a href="<?= baseUrl() ?>" class="text-muted small text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i> Return to Public Website
                    </a>
                </div>
            </div>

            <div class="text-center mt-4 text-white-50 small">
                Authorized Personnel Only • IP Logged
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
