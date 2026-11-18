<?php
/**
 * Intern Profile & Account Settings
 * Excellence Classes By Repswal
 */

$internPageTitle = 'My Profile';
$currentInternPage = 'profile';

require_once __DIR__ . '/includes/header.php';

$errors = [];

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid security token.');
        redirect(baseUrl('intern/profile.php'));
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $college = trim($_POST['college_name'] ?? '');
    $degree = trim($_POST['course_degree'] ?? '');
    $year = trim($_POST['current_year'] ?? '');

    if (empty($fullName) || strlen($fullName) < 3) {
        $errors[] = 'Full name must be at least 3 characters.';
    }
    if (empty($phone) || !preg_match('/^[0-9+\-\s]{10,15}$/', $phone)) {
        $errors[] = 'Valid phone number is required.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE interns 
                SET full_name = ?, phone = ?, college_name = ?, course_degree = ?, current_year = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$fullName, $phone, $college, $degree, $year, $internId]);

            // Update session name
            $_SESSION['intern_name'] = $fullName;

            setFlash('success', 'Profile details updated successfully.');
            redirect(baseUrl('intern/profile.php'));

        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid security token.');
        redirect(baseUrl('intern/profile.php'));
    }

    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($currentPass) || empty($newPass)) {
        $errors[] = 'All password fields are required.';
    } elseif (strlen($newPass) < 8) {
        $errors[] = 'New password must be at least 8 characters long.';
    } elseif ($newPass !== $confirmPass) {
        $errors[] = 'New password confirmation does not match.';
    } else {
        try {
            // Verify current password
            $pStmt = $pdo->prepare("SELECT password FROM interns WHERE id = ?");
            $pStmt->execute([$internId]);
            $userRow = $pStmt->fetch();

            if ($userRow && password_verify($currentPass, $userRow['password'])) {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE interns SET password = ?, updated_at = NOW() WHERE id = ?");
                $upd->execute([$newHash, $internId]);

                setFlash('success', 'Password updated successfully.');
                redirect(baseUrl('intern/profile.php'));
            } else {
                $errors[] = 'Current password is incorrect.';
            }

        } catch (PDOException $e) {
            $errors[] = 'Error updating password: ' . $e->getMessage();
        }
    }
}
?>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Profile & Account Settings</h4>
        <p class="text-muted small mb-0">Manage your personal identification, educational records, and login security credentials.</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert">
        <div class="d-flex align-items-center gap-2 mb-1">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Please resolve the following:</strong>
        </div>
        <ul class="mb-0 ps-3 small">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Profile Info Form -->
    <div class="col-lg-7">
        <div class="panel-card h-100">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-person-lines-fill text-primary"></i> Personal Details</h5>
            </div>

            <div class="panel-body">
                <form method="POST" action="<?= baseUrl('intern/profile.php') ?>">
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Legal Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= e($internProfile['full_name']) ?>" required>
                        <div class="form-text small">Your name will be printed on official certificates of internship.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email Address (Read-only)</label>
                            <input type="email" class="form-control bg-light" value="<?= e($internProfile['email']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mobile Number</label>
                            <input type="tel" name="phone" class="form-control" value="<?= e($internProfile['phone']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">College / University Name</label>
                        <input type="text" name="college_name" class="form-control" value="<?= e($internProfile['college_name'] ?? '') ?>" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Course / Degree</label>
                            <input type="text" name="course_degree" class="form-control" value="<?= e($internProfile['course_degree'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Current Year / Semester</label>
                            <input type="text" name="current_year" class="form-control" value="<?= e($internProfile['current_year'] ?? '') ?>" required>
                        </div>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="bi bi-check2-circle me-1"></i>Save Profile Changes
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Password Security -->
    <div class="col-lg-5">
        <div class="panel-card h-100">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-shield-lock text-primary"></i> Change Password</h5>
            </div>

            <div class="panel-body">
                <form method="POST" action="<?= baseUrl('intern/profile.php') ?>">
                    <?= csrfField() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Password</label>
                        <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="At least 8 characters" required minlength="8">
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter new password" required minlength="8">
                    </div>

                    <button type="submit" name="change_password" class="btn btn-outline-primary rounded-pill px-4 fw-bold w-100">
                        <i class="bi bi-key me-1"></i>Update Password
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Passwords are encrypted using PHP <code>password_hash()</code> with bcrypt algorithms.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
