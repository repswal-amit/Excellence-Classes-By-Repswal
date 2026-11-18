<?php
/**
 * Internship Application Page
 * Excellence Classes By Repswal
 */

$currentPage = 'apply';
$pageTitle = 'Apply for Internship';
$pageDescription = 'Submit your application for a professional internship program at Excellence Classes By Repswal.';

require_once __DIR__ . '/includes/header.php';

$pdo = null;
$dbError = null;
$openPrograms = [];

try {
    $pdo = getDBConnection();
    $progStmt = $pdo->query("SELECT * FROM internship_programs WHERE is_active = 1 AND is_archived = 0 AND application_status = 'Open' ORDER BY sort_order ASC");
    $openPrograms = $progStmt->fetchAll();
} catch (PDOException $e) {
    $dbError = $e->getMessage();
    // Graceful fallback programs for display
    $openPrograms = [
        ['id' => 1, 'title' => 'Full-Stack Web Development Internship', 'slug' => 'web-development', 'mode' => 'Hybrid', 'duration' => '3 Months'],
        ['id' => 2, 'title' => 'Python & Data Analysis Internship', 'slug' => 'python-data-analysis', 'mode' => 'Online', 'duration' => '2 Months'],
        ['id' => 3, 'title' => 'Core & Advanced Java Internship', 'slug' => 'java-programming', 'mode' => 'Hybrid', 'duration' => '3 Months'],
        ['id' => 4, 'title' => 'Frontend UI/UX Design & Development', 'slug' => 'ui-ux-design', 'mode' => 'Online', 'duration' => '2 Months'],
    ];
}

// Check pre-selected program from query param
$selectedProgramSlug = trim($_GET['program'] ?? '');
$selectedProgramId = 0;
if (!empty($selectedProgramSlug)) {
    foreach ($openPrograms as $p) {
        if ($p['slug'] === $selectedProgramSlug) {
            $selectedProgramId = (int)$p['id'];
            break;
        }
    }
}

// Current user state
$isLoggedIn = isInternLoggedIn();
$currentIntern = $isLoggedIn ? getCurrentIntern() : null;

$errors = [];
$formData = [
    'full_name' => $currentIntern['full_name'] ?? '',
    'email' => $currentIntern['email'] ?? '',
    'phone' => $currentIntern['phone'] ?? '',
    'college_name' => $currentIntern['college_name'] ?? '',
    'course_degree' => $currentIntern['course_degree'] ?? '',
    'current_year' => $currentIntern['current_year'] ?? '',
    'program_id' => $selectedProgramId,
    'cover_letter' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {
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
        $formData['cover_letter'] = trim($_POST['cover_letter'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validations
        if (empty($formData['full_name']) || strlen($formData['full_name']) < 3) {
            $errors[] = 'Please enter your full legal name (minimum 3 characters).';
        }
        if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (empty($formData['phone']) || !preg_match('/^[0-9+\-\s]{10,15}$/', $formData['phone'])) {
            $errors[] = 'Please enter a valid phone number (10-15 digits).';
        }
        if (empty($formData['college_name'])) {
            $errors[] = 'Please enter your college / university name.';
        }
        if (empty($formData['course_degree'])) {
            $errors[] = 'Please specify your degree / course (e.g. B.Tech CS, BCA, MCA).';
        }
        if (empty($formData['current_year'])) {
            $errors[] = 'Please specify your current semester or year.';
        }
        if ($formData['program_id'] <= 0) {
            $errors[] = 'Please select a valid open internship program.';
        } else {
            // Verify program is active and open
            $progCheck = $pdo->prepare("SELECT id, title, application_status FROM internship_programs WHERE id = ? AND is_active = 1 AND is_archived = 0");
            $progCheck->execute([$formData['program_id']]);
            $validProg = $progCheck->fetch();
            if (!$validProg) {
                $errors[] = 'The selected internship program does not exist.';
            } elseif ($validProg['application_status'] !== 'Open') {
                $errors[] = 'Applications for ' . htmlspecialchars($validProg['title']) . ' are currently closed.';
            }
        }

        // If not logged in, require password
        if (!$isLoggedIn) {
            if (empty($password) || strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters long.';
            }
            if ($password !== $confirmPassword) {
                $errors[] = 'Password confirmation does not match.';
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $internId = 0;

                if ($isLoggedIn) {
                    $internId = (int)$currentIntern['id'];
                } else {
                    // Check if email already registered
                    $checkEmail = $pdo->prepare("SELECT id FROM interns WHERE email = ?");
                    $checkEmail->execute([$formData['email']]);
                    $existingIntern = $checkEmail->fetch();

                    if ($existingIntern) {
                        $pdo->rollBack();
                        $errors[] = 'An account with this email already exists. Please log in first to apply.';
                    } else {
                        // Create new intern account
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $insertIntern = $pdo->prepare("
                            INSERT INTO interns (full_name, email, password, phone, college_name, course_degree, current_year, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $insertIntern->execute([
                            $formData['full_name'],
                            $formData['email'],
                            $hashedPassword,
                            $formData['phone'],
                            $formData['college_name'],
                            $formData['course_degree'],
                            $formData['current_year']
                        ]);
                        $internId = (int)$pdo->lastInsertId();

                        // Automatically log in the newly registered intern
                        loginIntern([
                            'id' => $internId,
                            'full_name' => $formData['full_name'],
                            'email' => $formData['email']
                        ]);
                    }
                }

                if (empty($errors) && $internId > 0) {
                    // Check if already applied to this program
                    $checkApp = $pdo->prepare("SELECT id, application_status FROM internship_applications WHERE intern_id = ? AND program_id = ?");
                    $checkApp->execute([$internId, $formData['program_id']]);
                    $existingApp = $checkApp->fetch();

                    if ($existingApp) {
                        $pdo->rollBack();
                        $errors[] = 'You have already submitted an application for this program (Status: ' . htmlspecialchars($existingApp['application_status']) . ').';
                    } else {
                        // Insert application with Pending status
                        $insertApp = $pdo->prepare("
                            INSERT INTO internship_applications (intern_id, program_id, application_status, cover_letter, created_at)
                            VALUES (?, ?, 'Pending', ?, NOW())
                        ");
                        $insertApp->execute([
                            $internId,
                            $formData['program_id'],
                            $formData['cover_letter']
                        ]);

                        $pdo->commit();

                        setFlash('success', 'Your internship application has been submitted successfully! It is currently under review by the Proprietor.');
                        redirect(baseUrl('intern/dashboard.php'));
                    }
                }

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'A database error occurred: ' . $e->getMessage();
            }
        }
    }
}
?>

<!-- ═══════════════════ PAGE HEADER ═══════════════════ -->
<section class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= baseUrl() ?>">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= baseUrl('internships.php') ?>">Internships</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Apply</li>
                    </ol>
                </nav>
                <h1 class="page-title">Internship Application Form</h1>
                <p class="page-subtitle">
                    Complete the application below to join our structured internship program. Every application is personally reviewed by the Proprietor.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end d-none d-lg-block">
                <?php if (!$isLoggedIn): ?>
                    <a href="<?= baseUrl('login.php') ?>" class="btn btn-outline-light rounded-pill">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Already Registered? Login
                    </a>
                <?php else: ?>
                    <a href="<?= baseUrl('intern/dashboard.php') ?>" class="btn btn-gold rounded-pill">
                        <i class="bi bi-speedometer2 me-1"></i> Go to Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ APPLICATION FORM SECTION ═══════════════════ -->
<section class="section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                
                <?php if (!$pdo): ?>
                    <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-database-exclamation fs-3 text-warning"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Database Connection Required</h6>
                                <p class="small text-muted mb-2">
                                    Unable to connect to MySQL server with current credentials.
                                    <?php if ($dbError): ?>
                                        <br><code><?= htmlspecialchars($dbError) ?></code>
                                    <?php endif; ?>
                                </p>
                                <div class="d-flex gap-2">
                                    <a href="<?= baseUrl('install.php') ?>" class="btn btn-warning btn-sm fw-semibold">
                                        <i class="bi bi-magic me-1"></i> Open Setup Wizard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                            <strong>Please fix the following issues:</strong>
                        </div>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (empty($openPrograms)): ?>
                    <div class="glass-card p-5 text-center">
                        <div class="mb-3 text-warning fs-1"><i class="bi bi-hourglass-split"></i></div>
                        <h4 class="fw-bold">Applications are Currently Closed</h4>
                        <p class="text-secondary max-w-500 mx-auto mb-4">
                            There are currently no internship programs with open applications. Please check back when our next batch schedule is announced, or contact us directly.
                        </p>
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="<?= baseUrl('internships.php') ?>" class="btn btn-primary">Browse All Programs</a>
                            <a href="<?= baseUrl('contact.php') ?>" class="btn btn-outline-secondary">Contact Desk</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="glass-card p-4 p-md-5">
                        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                            <div>
                                <h4 class="fw-bold mb-1">Application Details</h4>
                                <p class="text-muted small mb-0">Fields marked with an asterisk (<span class="text-danger">*</span>) are mandatory.</p>
                            </div>
                            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                                <i class="bi bi-shield-lock me-1"></i> Official Portal
                            </span>
                        </div>

                        <form action="<?= baseUrl('apply.php') ?>" method="POST" class="needs-validation" novalidate>
                            <?= csrfField() ?>

                            <!-- Target Program -->
                            <div class="mb-4">
                                <label for="program_id" class="form-label fw-semibold">Target Internship Program <span class="text-danger">*</span></label>
                                <select name="program_id" id="program_id" class="form-select form-select-lg" required>
                                    <option value="">-- Choose an open internship program --</option>
                                    <?php foreach ($openPrograms as $op): ?>
                                        <option value="<?= (int)$op['id'] ?>" <?= ((int)$formData['program_id'] === (int)$op['id']) ? 'selected' : '' ?>>
                                            <?= e($op['title']) ?> (<?= e($op['duration']) ?> — <?= e($op['mode']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Candidate Details -->
                            <h5 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-person-lines-fill text-primary me-2"></i>Personal Information</h5>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="full_name" class="form-label">Full Legal Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($formData['full_name']) ?>" placeholder="e.g. Rahul Sharma" required <?= $isLoggedIn ? 'readonly' : '' ?>>
                                    <div class="form-text small">As you wish it to appear on official certificates.</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= e($formData['email']) ?>" placeholder="name@example.com" required <?= $isLoggedIn ? 'readonly' : '' ?>>
                                    <div class="form-text small">Used for application notifications and login.</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($formData['phone']) ?>" placeholder="e.g. 9876543210" required <?= $isLoggedIn ? 'readonly' : '' ?>>
                                </div>

                                <div class="col-md-6">
                                    <label for="college_name" class="form-label">College / University Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="college_name" name="college_name" value="<?= e($formData['college_name']) ?>" placeholder="e.g. Rajasthan Technical University" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="course_degree" class="form-label">Course / Degree <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="course_degree" name="course_degree" value="<?= e($formData['course_degree']) ?>" placeholder="e.g. B.Tech Computer Science, BCA, MCA" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="current_year" class="form-label">Current Year / Semester <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="current_year" name="current_year" value="<?= e($formData['current_year']) ?>" placeholder="e.g. 3rd Year / 6th Semester" required>
                                </div>
                            </div>

                            <!-- Account Password (if not logged in) -->
                            <?php if (!$isLoggedIn): ?>
                                <h5 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Create Intern Account Password</h5>
                                <p class="text-muted small">Your account will be created so you can access the Intern Dashboard to track applications, complete tasks, and download certificates.</p>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="password" name="password" placeholder="At least 8 characters" required minlength="8">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required minlength="8">
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Statement of Purpose / Motivation -->
                            <h5 class="fw-bold text-dark mt-4 mb-3"><i class="bi bi-chat-square-text-fill text-primary me-2"></i>Motivation & Goals</h5>
                            <div class="mb-4">
                                <label for="cover_letter" class="form-label">Why do you want to join this internship program? (Optional)</label>
                                <textarea name="cover_letter" id="cover_letter" rows="4" class="form-control" placeholder="Briefly mention your learning objectives, programming experience, and what you hope to build during the internship..."><?= e($formData['cover_letter']) ?></textarea>
                            </div>

                            <!-- Institutional Agreement Notice -->
                            <div class="alert alert-secondary small border-0 bg-light p-3 rounded-3 mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="termsCheck" required>
                                    <label class="form-check-label text-secondary" for="termsCheck">
                                        I hereby declare that the information provided is accurate and authentic. I understand that submission of an application does not guarantee automatic approval; all applications are subject to evaluation and approval by <strong>Amit Repswal (Proprietor)</strong> of Excellence Classes By Repswal.
                                    </label>
                                </div>
                            </div>

                            <button type="submit" name="submit_application" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold">
                                <i class="bi bi-send-fill me-2"></i>Submit Internship Application
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
