<?php
/**
 * Intern Login Page
 * Excellence Classes By Repswal
 */

$currentPage = 'login';
$pageTitle = 'Intern Login';
$pageDescription = 'Login to the Excellence Classes By Repswal Intern Portal to manage your tasks, submissions, progress, and certificates.';

require_once __DIR__ . '/includes/header.php';

// Redirect if already logged in
if (isInternLoggedIn()) {
    redirect(baseUrl('intern/dashboard.php'));
} elseif (isAdminLoggedIn()) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_login'])) {
    if (!$pdo) {
        $errors[] = 'Database connection is currently not established. Please configure your MySQL credentials in the setup wizard or .env file.';
    } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (empty($password)) {
            $errors[] = 'Please enter your password.';
        }

        if (empty($errors)) {
            // Check rate limiting
            if (isLoginRateLimited($pdo, $email, 'intern')) {
                $errors[] = 'Too many failed login attempts. Please wait 15 minutes before trying again.';
            } else {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM interns WHERE email = ? LIMIT 1");
                    $stmt->execute([$email]);
                    $intern = $stmt->fetch();

                    if ($intern && password_verify($password, $intern['password'])) {
                        // Check if account is active
                        if ((int)$intern['is_active'] !== 1) {
                            $errors[] = 'Your account has been deactivated. Please contact administration.';
                            recordLoginAttempt($pdo, $email, 'intern', false);
                        } else {
                            // Successful login
                            recordLoginAttempt($pdo, $email, 'intern', true);

                            // Update last login
                            $updateStmt = $pdo->prepare("UPDATE interns SET last_login = NOW(), login_attempts = 0 WHERE id = ?");
                            $updateStmt->execute([$intern['id']]);

                            loginIntern($intern);

                            setFlash('success', 'Welcome back, ' . htmlspecialchars($intern['full_name']) . '!');
                            redirect(baseUrl('intern/dashboard.php'));
                        }
                    } else {
                        // Failed login
                        recordLoginAttempt($pdo, $email, 'intern', false);
                        $errors[] = 'Invalid email address or password.';
                    }

                } catch (PDOException $e) {
                    $errors[] = 'A server error occurred during authentication. Please try again.';
                }
            }
        }
    }
}
?>

<!-- ═══════════════════ LOGIN SECTION ═══════════════════ -->
<section class="section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                
                <div class="text-center mb-4 reveal">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary mb-3" style="width: 58px; height: 58px; font-size: 1.6rem;">
                        <i class="bi bi-box-arrow-in-right"></i>
                    </div>
                    <h2 class="fw-bold mb-1">Intern Portal Login</h2>
                    <p class="text-secondary small">
                        Access your tasks, progress tracking, and verified certificates.
                    </p>
                </div>

                <?php if (!$pdo): ?>
                    <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-database-exclamation fs-3 text-warning"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Database Connection Required</h6>
                                <p class="small text-muted mb-2">
                                    Unable to connect to MySQL. Configure credentials in <code>.env</code> or run the setup wizard.
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
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <strong>Authentication Notice:</strong>
                        </div>
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="glass-card p-4 p-md-5 shadow-lg reveal">
                    <form action="<?= baseUrl('login.php') ?>" method="POST" class="needs-validation" novalidate>
                        <?= csrfField() ?>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" class="form-control border-start-0" id="email" name="email" value="<?= e($email) ?>" placeholder="registered@email.com" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label mb-0">Password</label>
                                <a href="<?= baseUrl('forgot-password.php') ?>" class="small text-muted text-decoration-none">Forgot Password?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="••••••••" required>
                            </div>
                        </div>

                        <button type="submit" name="submit_login" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Sign In to Dashboard
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <span class="text-secondary small">Don't have an intern account?</span>
                        <a href="<?= baseUrl('register.php') ?>" class="fw-semibold text-primary ms-1 small">
                            Register now
                        </a>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="<?= baseUrl('admin/login.php') ?>" class="text-muted small text-decoration-none">
                        <i class="bi bi-shield-lock me-1"></i> Admin & Staff Login
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
