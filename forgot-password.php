<?php
/**
 * Forgot Password Page
 * Excellence Classes By Repswal
 */

$currentPage = 'forgot-password';
$pageTitle = 'Forgot Password';
$pageDescription = 'Reset your password for Excellence Classes By Repswal Intern Portal.';

require_once __DIR__ . '/includes/header.php';

$pdo = null;
$dbError = null;
try {
    $pdo = getDBConnection();
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

$errors = [];
$successMessage = '';
$generatedResetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_forgot'])) {
    if (!$pdo) {
        $errors[] = 'Database is currently not connected. Please configure your MySQL credentials in .env or run the setup wizard.';
    } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } else {
            try {
                // Check in interns
                $stmt = $pdo->prepare("SELECT id, full_name, email FROM interns WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $intern = $stmt->fetch();

                if ($intern) {
                    // Generate secure token
                    $token = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    // Store token
                    $insertToken = $pdo->prepare("
                        INSERT INTO password_reset_tokens (email, token, user_type, expires_at, created_at)
                        VALUES (?, ?, 'intern', ?, NOW())
                    ");
                    $insertToken->execute([$email, $token, $expiresAt]);

                    $resetUrl = baseUrl('reset-password.php?token=' . urlencode($token));
                    $generatedResetLink = $resetUrl;

                    $successMessage = 'Password reset instructions have been generated. Follow the link below to set your new password (valid for 1 hour).';
                } else {
                    // For security, show uniform response
                    $successMessage = 'If an account exists with that email address, password reset instructions have been generated.';
                }

            } catch (PDOException $e) {
                $errors[] = 'An error occurred while processing your request. Please try again.';
            }
        }
    }
}
?>

<!-- ═══════════════════ FORGOT PASSWORD SECTION ═══════════════════ -->
<section class="section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                
                <div class="text-center mb-4 reveal">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary mb-3" style="width: 58px; height: 58px; font-size: 1.6rem;">
                        <i class="bi bi-key-fill"></i>
                    </div>
                    <h2 class="fw-bold mb-1">Reset Password</h2>
                    <p class="text-secondary small">
                        Enter your registered email address to receive password reset instructions.
                    </p>
                </div>

                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success shadow-sm border-0 mb-4" role="alert">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                            <strong>Request Processed:</strong>
                        </div>
                        <p class="small mb-2"><?= e($successMessage) ?></p>
                        <?php if (!empty($generatedResetLink)): ?>
                            <div class="p-2 bg-white rounded border small mt-2">
                                <span class="text-muted d-block mb-1">Reset Link:</span>
                                <a href="<?= e($generatedResetLink) ?>" class="text-break fw-semibold text-primary"><?= e($generatedResetLink) ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert">
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="glass-card p-4 p-md-5 shadow-lg reveal">
                    <form action="<?= baseUrl('forgot-password.php') ?>" method="POST" class="needs-validation" novalidate>
                        <?= csrfField() ?>

                        <div class="mb-4">
                            <label for="email" class="form-label">Registered Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" class="form-control border-start-0" id="email" name="email" placeholder="registered@email.com" required autofocus>
                            </div>
                        </div>

                        <button type="submit" name="submit_forgot" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold">
                            <i class="bi bi-arrow-right-circle me-2"></i>Generate Reset Link
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <a href="<?= baseUrl('login.php') ?>" class="small text-muted text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i> Back to Login
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
