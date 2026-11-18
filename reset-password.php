<?php
/**
 * Reset Password Handler
 * Excellence Classes By Repswal
 */

$currentPage = 'reset-password';
$pageTitle = 'Set New Password';
$pageDescription = 'Set a new password for your Excellence Classes By Repswal account.';

require_once __DIR__ . '/includes/header.php';

$pdo = null;
$dbError = null;
try {
    $pdo = getDBConnection();
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

$token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$errors = [];
$isValidToken = false;
$tokenRecord = null;

if (!empty($token) && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM password_reset_tokens 
            WHERE token = ? AND used = 0 AND expires_at > NOW() 
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $tokenRecord = $stmt->fetch();
        if ($tokenRecord) {
            $isValidToken = true;
        } else {
            $errors[] = 'This password reset link is invalid or has expired. Please request a new one.';
        }
    } catch (PDOException $e) {
        $errors[] = 'A server error occurred while validating the token.';
    }
} else {
    $errors[] = 'No reset token was supplied.';
}

// Process new password submission
if ($isValidToken && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reset'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($newPassword) || strlen($newPassword) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Password confirmation does not match.';
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

                if ($tokenRecord['user_type'] === 'admin') {
                    $update = $pdo->prepare("UPDATE admins SET password = ?, updated_at = NOW() WHERE email = ?");
                    $update->execute([$newHash, $tokenRecord['email']]);
                } else {
                    $update = $pdo->prepare("UPDATE interns SET password = ?, updated_at = NOW() WHERE email = ?");
                    $update->execute([$newHash, $tokenRecord['email']]);
                }

                // Mark token as used
                $markUsed = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE id = ?");
                $markUsed->execute([$tokenRecord['id']]);

                $pdo->commit();

                setFlash('success', 'Your password has been reset successfully! You can now log in.');
                redirect(baseUrl('login.php'));

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Failed to update password. Please try again.';
            }
        }
    }
}
?>

<!-- ═══════════════════ SET NEW PASSWORD SECTION ═══════════════════ -->
<section class="section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                
                <div class="text-center mb-4 reveal">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary mb-3" style="width: 58px; height: 58px; font-size: 1.6rem;">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <h2 class="fw-bold mb-1">Set New Password</h2>
                    <p class="text-secondary small">
                        Choose a strong, secure password for your account.
                    </p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert">
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($isValidToken): ?>
                    <div class="glass-card p-4 p-md-5 shadow-lg reveal">
                        <form action="<?= baseUrl('reset-password.php') ?>" method="POST" class="needs-validation" novalidate>
                            <?= csrfField() ?>
                            <input type="hidden" name="token" value="<?= e($token) ?>">

                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                    <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="At least 8 characters" required minlength="8" autofocus>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-lock-fill text-muted"></i></span>
                                    <input type="password" class="form-control border-start-0" id="confirm_password" name="confirm_password" placeholder="Re-enter new password" required minlength="8">
                                </div>
                            </div>

                            <button type="submit" name="submit_reset" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold">
                                <i class="bi bi-check-circle-fill me-2"></i>Update Password
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="glass-card p-4 text-center">
                        <a href="<?= baseUrl('forgot-password.php') ?>" class="btn btn-primary rounded-pill">
                            <i class="bi bi-arrow-repeat me-1"></i> Request New Reset Link
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
