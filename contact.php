<?php
/**
 * Contact Us Page
 * Excellence Classes By Repswal
 */

$currentPage = 'contact';
$pageTitle = 'Contact Us';
$pageDescription = 'Get in touch with Excellence Classes By Repswal in Nawalgarh, Rajasthan for course inquiries, internship admissions, and educational services.';

require_once __DIR__ . '/includes/header.php';

$errors = [];
$successMessage = '';

$prefillSubject = trim($_GET['subject'] ?? '');
if (empty($prefillSubject) && !empty($_GET['course'])) {
    $prefillSubject = 'Inquiry about ' . trim($_GET['course']);
}

$formData = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'subject' => $prefillSubject,
    'message' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['submit_contact']) || isset($_POST['submit_message']))) {
    // CSRF verification
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $formData['name'] = trim($_POST['name'] ?? '');
        $formData['email'] = strtolower(trim($_POST['email'] ?? ''));
        $formData['phone'] = trim($_POST['phone'] ?? '');
        $formData['subject'] = trim($_POST['subject'] ?? '');
        $formData['message'] = trim($_POST['message'] ?? '');

        // Validation
        if (empty($formData['name']) || strlen($formData['name']) < 2) {
            $errors[] = 'Please enter your name (at least 2 characters).';
        }
        if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }
        if (empty($formData['subject']) || strlen($formData['subject']) < 3) {
            $errors[] = 'Please provide a subject for your message (at least 3 characters).';
        }
        if (empty($formData['message']) || strlen($formData['message']) < 10) {
            $errors[] = 'Please enter a message of at least 10 characters.';
        }

        if (empty($errors)) {
            try {
                $pdo = getDBConnection();
                $ipAddress = getClientIP();

                $stmt = $pdo->prepare("
                    INSERT INTO contact_messages (name, email, phone, subject, message, ip_address, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $formData['name'],
                    $formData['email'],
                    $formData['phone'],
                    $formData['subject'],
                    $formData['message'],
                    $ipAddress
                ]);

                $successMessage = 'Thank you for reaching out! Your message has been received. Amit Repswal and our admissions team will respond to your query shortly.';
                // Reset form
                $formData = [
                    'name' => '',
                    'email' => '',
                    'phone' => '',
                    'subject' => '',
                    'message' => ''
                ];

            } catch (PDOException $e) {
                $errors[] = 'An error occurred while saving your message. Please try again later.';
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
                        <li class="breadcrumb-item active" aria-current="page">Contact</li>
                    </ol>
                </nav>
                <h1 class="page-title">Contact Excellence Classes</h1>
                <p class="page-subtitle">
                    Have questions about our training courses, internship eligibility, or certification verification? Reach out to us directly.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end d-none d-lg-block">
                <span class="badge bg-gold-subtle text-gold px-3 py-2 rounded-pill fw-semibold">
                    <i class="bi bi-clock-history me-1"></i> Quick Response Desk
                </span>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ CONTACT CARDS & FORM ═══════════════════ -->
<section class="section">
    <div class="container">
        <div class="row g-5">
            <!-- Left Column: Institute Details & Cards -->
            <div class="col-lg-5 reveal">
                <div class="d-flex flex-column gap-4">
                    
                    <!-- Institute Profile Card -->
                    <div class="glass-card p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="metric-icon royal" style="width: 48px; height: 48px;">
                                <i class="bi bi-mortarboard-fill"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Excellence Classes By Repswal</h5>
                                <small class="text-gold fw-semibold">Learn. Develop. Grow.</small>
                            </div>
                        </div>
                        <p class="text-secondary small mb-3">
                            Educational services and IT training institute focused on software engineering competence, project development, and verified internships.
                        </p>
                        <div class="p-3 bg-light rounded-3 small">
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Legal Structure:</span>
                                <span class="fw-semibold text-dark">Sole Proprietorship</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Proprietor:</span>
                                <span class="fw-semibold text-dark">Amit Repswal</span>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">Enterprise Category:</span>
                                <span class="fw-semibold text-dark">Micro Enterprise</span>
                            </div>
                        </div>
                    </div>

                    <!-- Direct Contact Channels -->
                    <div class="card border-0 shadow-sm p-4">
                        <h6 class="fw-bold mb-3 text-dark">Direct Channels</h6>
                        
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="metric-icon green" style="width: 40px; height: 40px; font-size: 1.1rem;">
                                <i class="bi bi-geo-alt-fill"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-dark small">Campus Location</div>
                                <div class="text-secondary small">
                                    <?= e(getSetting('site_address', 'Nawalgarh, Jhunjhunu, Rajasthan, India - 333308')) ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3 mb-3">
                            <div class="metric-icon royal" style="width: 40px; height: 40px; font-size: 1.1rem;">
                                <i class="bi bi-envelope-fill"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-dark small">Official Email</div>
                                <div class="text-secondary small">
                                    <a href="mailto:<?= e(getSetting('site_email', 'info@excellenceclasses.com')) ?>" class="text-decoration-none">
                                        <?= e(getSetting('site_email', 'info@excellenceclasses.com')) ?>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <?php if ($phone = getSetting('site_phone')): ?>
                        <div class="d-flex align-items-start gap-3">
                            <div class="metric-icon gold" style="width: 40px; height: 40px; font-size: 1.1rem;">
                                <i class="bi bi-telephone-fill"></i>
                            </div>
                            <div>
                                <div class="fw-semibold text-dark small">Phone Number</div>
                                <div class="text-secondary small">
                                    <a href="tel:<?= e($phone) ?>" class="text-decoration-none"><?= e($phone) ?></a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Verification Helper Card -->
                    <div class="card border-0 shadow-sm p-4 bg-primary text-white">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-qr-code fs-1 text-gold"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Verify a Certificate?</h6>
                                <p class="small text-white-50 mb-2">Employers and academic institutions can verify certificates instantly.</p>
                                <a href="<?= baseUrl('verify-certificate.php') ?>" class="btn btn-gold btn-sm rounded-pill px-3">
                                    Verification Portal <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Right Column: Interactive Contact Form -->
            <div class="col-lg-7 reveal">
                <div class="glass-card p-4 p-md-5">
                    <h3 class="fw-bold mb-2">Send Us a Message</h3>
                    <p class="text-secondary small mb-4">
                        Please fill out the form below. We usually respond within 24 business hours.
                    </p>

                    <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success d-flex align-items-center gap-3 p-3 mb-4 shadow-sm border-0" role="alert">
                            <i class="bi bi-check-circle-fill fs-3 text-success"></i>
                            <div>
                                <strong>Message Sent Successfully!</strong>
                                <div class="small mt-1"><?= e($successMessage) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                                <strong>Please check the following errors:</strong>
                            </div>
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="<?= baseUrl('contact.php') ?>" method="POST" class="needs-validation" novalidate>
                        <?= csrfField() ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="name" name="name" value="<?= e($formData['name']) ?>" placeholder="Your name" required>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" value="<?= e($formData['email']) ?>" placeholder="your.email@example.com" required>
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number (Optional)</label>
                                <input type="tel" class="form-control form-control-lg" id="phone" name="phone" value="<?= e($formData['phone']) ?>" placeholder="e.g. 9876543210">
                            </div>

                            <div class="col-md-6">
                                <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="subject" name="subject" value="<?= e($formData['subject']) ?>" placeholder="Reason for contact" required>
                            </div>

                            <div class="col-12">
                                <label for="message" class="form-label">Message Details <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="message" name="message" rows="5" placeholder="Please elaborate on your inquiry or questions..." required><?= e($formData['message']) ?></textarea>
                            </div>

                            <div class="col-12 pt-2">
                                <button type="submit" name="submit_contact" class="btn btn-primary btn-lg w-100 py-3 rounded-pill fw-bold">
                                    <i class="bi bi-send-fill me-2"></i>Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
