<?php
/**
 * Intern Registration Page
 * Excellence Classes By Repswal
 */

$currentPage = 'register';
$pageTitle = 'Intern Registration';
$pageDescription = 'Create an intern account at Excellence Classes By Repswal to apply for internships, complete tasks, and track your progress.';

require_once __DIR__ . '/includes/header.php';

// If already logged in, redirect to dashboard
if (isInternLoggedIn()) {
    redirect(baseUrl('intern/dashboard.php'));
} elseif (isAdminLoggedIn()) {
    redirect(baseUrl('admin/dashboard.php'));
}

$pdo = null;
$dbError = null;
$programs = [];

try {
    $pdo = getDBConnection();
    $progStmt = $pdo->query("SELECT id, title, slug, duration, mode, application_status FROM internship_programs WHERE is_active = 1 AND is_archived = 0 ORDER BY sort_order ASC");
    $programs = $progStmt->fetchAll();
} catch (PDOException $e) {
    $dbError = $e->getMessage();
    $programs = [
        ['id' => 1, 'title' => 'Full-Stack Web Development Internship', 'slug' => 'web-development', 'duration' => '3 Months', 'mode' => 'Hybrid', 'application_status' => 'Open'],
        ['id' => 2, 'title' => 'Python & Data Analysis Internship', 'slug' => 'python-data-analysis', 'duration' => '2 Months', 'mode' => 'Online', 'application_status' => 'Open'],
        ['id' => 3, 'title' => 'Core & Advanced Java Internship', 'slug' => 'java-programming', 'duration' => '3 Months', 'mode' => 'Hybrid', 'application_status' => 'Open'],
    ];
}

$selectedProgramId = (int)($_GET['program_id'] ?? 0);
if (empty($selectedProgramId) && !empty($_GET['program'])) {
    foreach ($programs as $p) {
        if ($p['slug'] === $_GET['program']) {
            $selectedProgramId = (int)$p['id'];
            break;
        }
    }
}

$errors = [];
$formData = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'college_name' => '',
    'course_degree' => '',
    'current_year' => '',
    'program_id' => $selectedProgramId
];

// Handle Registration POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_register'])) {
    if (!$pdo) {
        $errors[] = 'Database connection is currently unavailable. Please configure MySQL credentials in the setup wizard or .env file.';
    } elseif (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        $formData['full_name'] = trim($_POST['full_name'] ?? '');
        $formData['email'] = strtolower(trim($_POST['email'] ?? ''));
        $formData['phone'] = trim($_POST['phone'] ?? '');
        $formData['college_name'] = trim($_POST['college_name'] ?? '');
        $formData['course_degree'] = trim($_POST['course_degree'] ?? '');
        $formData['current_year'] = trim($_POST['current_year'] ?? '');
        $formData['program_id'] = (int)($_POST['program_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($formData['full_name']) || strlen($formData['full_name']) < 3) {
            $errors[] = 'Full Name must be at least 3 characters.';
        }
        if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (empty($formData['phone']) || !preg_match('/^[0-9+\-\s]{10,15}$/', $formData['phone'])) {
            $errors[] = 'Please enter a valid mobile number (10 to 15 digits).';
        }
        if (empty($formData['college_name'])) {
            $errors[] = 'College / University Name is required.';
        }
        if (empty($formData['course_degree'])) {
            $errors[] = 'Course / Degree is required.';
        }
        if (empty($formData['current_year'])) {
            $errors[] = 'Current Year / Semester is required.';
        }
        if (empty($password) || strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Password confirmation does not match.';
        }

        // Check if email already registered
        if (empty($errors)) {
            try {
                $checkEmail = $pdo->prepare("SELECT id FROM interns WHERE email = ?");
                $checkEmail->execute([$formData['email']]);
                if ($checkEmail->fetch()) {
                    $errors[] = 'An account with this email address already exists. Please log in instead.';
                }
            } catch (PDOException $e) {
                $errors[] = 'Database verification error. Please try again.';
            }
        }

        // Process Registration
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO interns (full_name, email, password, phone, college_name, course_degree, current_year, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $formData['full_name'],
                    $formData['email'],
                    $hashedPassword,
                    $formData['phone'],
                    $formData['college_name'],
                    $formData['course_degree'],
                    $formData['current_year']
                ]);
                $internId = (int)$pdo->lastInsertId();

                // If a program was selected and open, create a pending application
                if ($formData['program_id'] > 0) {
                    $progStmt = $pdo->prepare("SELECT id, title, application_status FROM internship_programs WHERE id = ? AND is_active = 1 AND is_archived = 0");
                    $progStmt->execute([$formData['program_id']]);
                    $prog = $progStmt->fetch();

                    if ($prog && $prog['application_status'] === 'Open') {
                        $appStmt = $pdo->prepare("
                            INSERT INTO internship_applications (intern_id, program_id, application_status, created_at)
                            VALUES (?, ?, 'Pending', NOW())
                        ");
                        $appStmt->execute([$internId, $formData['program_id']]);
                    }
                }

                $pdo->commit();

                // Log the intern in
                loginIntern([
                    'id' => $internId,
                    'full_name' => $formData['full_name'],
                    'email' => $formData['email']
                ]);

                setFlash('success', 'Registration successful! Welcome to Excellence Classes By Repswal.');
                redirect(baseUrl('intern/dashboard.php'));

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Registration failed due to a system error. Please try again.';
            }
        }
    }
}
?>

<!-- ═══════════════════ REGISTRATION SECTION ═══════════════════ -->
<section class="section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                
                <div class="text-center mb-4 reveal">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary mb-3" style="width: 58px; height: 58px; font-size: 1.6rem;">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <h2 class="fw-bold mb-1">Intern Registration</h2>
                    <p class="text-secondary small">
                        Create your account to apply for internships, track tasks, and earn verifiable certificates.
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
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                            <strong>Registration Failed:</strong>
                        </div>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="glass-card p-4 p-md-5 shadow-lg reveal">
                    <form action="<?= baseUrl('register.php') ?>" method="POST" class="needs-validation" novalidate>
                        <?= csrfField() ?>

                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person-circle text-primary me-2"></i>Personal Details</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="full_name" name="full_name" value="<?= e($formData['full_name']) ?>" placeholder="e.g. Amit Sharma" required autofocus>
                                <div class="form-text small">Your name will appear exactly as typed on certificates.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= e($formData['email']) ?>" placeholder="name@domain.com" required>
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($formData['phone']) ?>" placeholder="10-digit number" required>
                            </div>
                        </div>

                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-mortarboard-fill text-primary me-2"></i>Academic Background</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label for="college_name" class="form-label">College / University Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="college_name" name="college_name" value="<?= e($formData['college_name']) ?>" placeholder="e.g. University of Rajasthan" required>
                            </div>

                            <div class="col-md-6">
                                <label for="course_degree" class="form-label">Course / Degree <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="course_degree" name="course_degree" value="<?= e($formData['course_degree']) ?>" placeholder="e.g. B.Tech CS, BCA, MCA" required>
                            </div>

                            <div class="col-md-6">
                                <label for="current_year" class="form-label">Current Year / Semester <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="current_year" name="current_year" value="<?= e($formData['current_year']) ?>" placeholder="e.g. 3rd Year / 5th Sem" required>
                            </div>
                        </div>

                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-briefcase-fill text-primary me-2"></i>Internship Program Preference</h5>
                        <div class="mb-4">
                            <label for="program_id" class="form-label">Desired Program (Optional at Registration)</label>
                            <select name="program_id" id="program_id" class="form-select">
                                <option value="0">-- Select program now or apply later from dashboard --</option>
                                <?php foreach ($programs as $prog): ?>
                                    <option value="<?= (int)$prog['id'] ?>" <?= ((int)$formData['program_id'] === (int)$prog['id']) ? 'selected' : '' ?>>
                                        <?= e($prog['title']) ?> (<?= e($prog['duration']) ?>) <?= ($prog['application_status'] === 'Open') ? '— [Open]' : '— [Closed]' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small">If an open program is selected, an application will be created in 'Pending' status for review.</div>
                        </div>

                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-lock-fill text-primary me-2"></i>Set Password</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="At least 8 characters" required minlength="8">
                            </div>

                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required minlength="8">
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="termsAgree" required>
                            <label class="form-check-label text-secondary small" for="termsAgree">
                                I agree to the terms and understand that all internship applications and completion certificates require formal review and approval by <strong>Amit Repswal, Proprietor</strong>.
                            </label>
                        </div>

                        <button type="submit" name="submit_register" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold">
                            <i class="bi bi-person-check-fill me-2"></i>Create Intern Account
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <span class="text-secondary small">Already have an intern account?</span>
                        <a href="<?= baseUrl('login.php') ?>" class="fw-semibold text-primary ms-1 small">
                            Login here
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
