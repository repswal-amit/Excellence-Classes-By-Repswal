<?php
/**
 * Homepage
 * Excellence Classes By Repswal
 */

$currentPage = 'index';
$pageTitle = null; // Will use default: APP_NAME — APP_TAGLINE

require_once __DIR__ . '/includes/header.php';

$pdo = null;
try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    $pdo = null;
}

// Fetch active courses
$courses = [];
if ($pdo) {
    try {
        $coursesStmt = $pdo->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 8");
        $courses = $coursesStmt->fetchAll();
    } catch (Exception $e) {
        $courses = [];
    }
}

// Fetch active internship programs
$programs = [];
if ($pdo) {
    try {
        $programsStmt = $pdo->query("SELECT * FROM internship_programs WHERE is_active = 1 AND is_archived = 0 ORDER BY sort_order ASC LIMIT 9");
        $programs = $programsStmt->fetchAll();
    } catch (Exception $e) {
        $programs = [];
    }
}

// Fetch stats (real data)
$totalInterns = 0;
$totalPrograms = count($programs);
$totalCourses = count($courses);
$certIssued = 0;
if ($pdo) {
    try {
        $totalInterns = (int)$pdo->query("SELECT COUNT(*) FROM interns")->fetchColumn();
        $totalPrograms = (int)$pdo->query("SELECT COUNT(*) FROM internship_programs WHERE is_active = 1")->fetchColumn();
        $totalCourses = (int)$pdo->query("SELECT COUNT(*) FROM courses WHERE is_active = 1")->fetchColumn();
        $certIssued = (int)$pdo->query("SELECT COUNT(*) FROM certificates WHERE certificate_status = 'Issued'")->fetchColumn();
    } catch (Exception $e) {
        // Keep defaults
    }
}
?>

<!-- ═══════════════════ HERO SECTION ═══════════════════ -->
<section class="hero-section" id="hero">
    <div class="hero-shapes">
        <div class="hero-shape hero-shape-1"></div>
        <div class="hero-shape hero-shape-2"></div>
        <div class="hero-shape hero-shape-3"></div>
    </div>
    
    <div class="container">
        <div class="row align-items-center">
            <!-- Left Column: Content -->
            <div class="col-lg-6">
                <div class="hero-badge">
                    <i class="bi bi-stars"></i>
                    <?= e(APP_TAGLINE) ?>
                </div>
                
                <h1 class="hero-title">
                    <?= e(getSetting('hero_title', 'Build Your Future in Technology')) ?>
                    <span class="highlight d-block">with Excellence Classes</span>
                </h1>
                
                <p class="hero-text">
                    <?= e(getSetting('hero_subtitle', 'Join our industry-focused internship programs and transform your skills with hands-on experience at Excellence Classes By Repswal.')) ?>
                </p>
                
                <div class="hero-actions">
                    <a href="<?= baseUrl('internships.php') ?>" class="btn btn-primary btn-lg">
                        <i class="bi bi-rocket-takeoff me-2"></i>Explore Internships
                    </a>
                    <a href="<?= baseUrl('courses.php') ?>" class="btn btn-outline-primary btn-lg">
                        <i class="bi bi-book me-2"></i>View Courses
                    </a>
                </div>
                
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-value" data-count="<?= (int)$totalPrograms ?>"><?= (int)$totalPrograms ?></div>
                        <div class="hero-stat-label">Programs</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-value" data-count="<?= (int)$totalCourses ?>"><?= (int)$totalCourses ?></div>
                        <div class="hero-stat-label">Courses</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-value" data-count="<?= (int)$totalInterns ?>"><?= (int)$totalInterns ?></div>
                        <div class="hero-stat-label">Interns</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-value" data-count="<?= (int)$certIssued ?>"><?= (int)$certIssued ?></div>
                        <div class="hero-stat-label">Certificates</div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Visual -->
            <div class="col-lg-6">
                <div class="hero-visual">
                    <div class="hero-card-stack">
                        <!-- Main floating card -->
                        <div class="hero-floating-card">
                            <div class="hfc-header">
                                <div class="hfc-icon">
                                    <i class="bi bi-code-slash"></i>
                                </div>
                                <div>
                                    <div class="hfc-title">Internship Dashboard</div>
                                    <div class="hfc-subtitle">Track your progress in real-time</div>
                                </div>
                            </div>
                            
                            <div class="hfc-tech-tags">
                                <span class="tech-tag">HTML5</span>
                                <span class="tech-tag">CSS3</span>
                                <span class="tech-tag">JavaScript</span>
                                <span class="tech-tag">PHP</span>
                                <span class="tech-tag">MySQL</span>
                                <span class="tech-tag">React</span>
                            </div>
                            
                            <div class="hfc-progress">
                                <div class="hfc-progress-label">
                                    <span>Internship Progress</span>
                                    <span>75%</span>
                                </div>
                                <div class="hfc-progress-bar">
                                    <div class="hfc-progress-fill" style="width: 75%"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Accent cards -->
                        <div class="hero-accent-card card-top">
                            <div class="accent-icon text-success"><i class="bi bi-patch-check-fill"></i></div>
                            <div class="accent-label">Certificates</div>
                            <div class="accent-value">Verified</div>
                        </div>
                        
                        <div class="hero-accent-card card-bottom">
                            <div class="accent-icon text-gold"><i class="bi bi-trophy-fill"></i></div>
                            <div class="accent-label">Tasks</div>
                            <div class="accent-value">Completed</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ ABOUT SECTION ═══════════════════ -->
<section class="section about-section" id="about">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 reveal">
                <div class="about-image-wrapper">
                    <div style="background: linear-gradient(135deg, var(--navy-light), var(--navy)); border-radius: var(--radius-xl); padding: 3rem; text-align: center; color: var(--white); min-height: 380px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                        <i class="bi bi-mortarboard-fill" style="font-size: 4rem; color: var(--gold); margin-bottom: 1.5rem;"></i>
                        <h3 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem;">Excellence Classes</h3>
                        <p style="font-size: 1rem; color: var(--gold); font-weight: 600;">By Repswal</p>
                        <p style="font-size: 0.9rem; opacity: 0.7; margin-top: 0.5rem;">Learn. Develop. Grow.</p>
                    </div>
                    <div class="about-highlight-box">
                        <div class="ahb-value">IT Training</div>
                        <div class="ahb-label">& Internship Programs</div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 reveal">
                <span class="section-badge"><i class="bi bi-info-circle"></i> About Us</span>
                <h2 class="section-title">
                    Empowering Students with <span class="text-gradient">Practical Skills</span>
                </h2>
                <p class="mb-3" style="color: var(--text-secondary); line-height: 1.8;">
                    <?= e(getSetting('about_text', 'Excellence Classes By Repswal is an educational and IT training institute based in Nawalgarh, Rajasthan. We provide quality education, practical training, and internship programs to help students develop real-world skills in technology and software development.')) ?>
                </p>
                <p style="color: var(--text-secondary); line-height: 1.8; margin-bottom: 1.5rem;">
                    Our programs are designed to bridge the gap between academic learning and industry requirements, giving students hands-on experience with the latest technologies.
                </p>
                
                <div class="about-features">
                    <div class="about-feature">
                        <div class="about-feature-icon"><i class="bi bi-laptop"></i></div>
                        <span class="about-feature-text">Hands-on IT Training</span>
                    </div>
                    <div class="about-feature">
                        <div class="about-feature-icon"><i class="bi bi-award"></i></div>
                        <span class="about-feature-text">Verified Certificates</span>
                    </div>
                    <div class="about-feature">
                        <div class="about-feature-icon"><i class="bi bi-people"></i></div>
                        <span class="about-feature-text">Expert Mentorship</span>
                    </div>
                    <div class="about-feature">
                        <div class="about-feature-icon"><i class="bi bi-briefcase"></i></div>
                        <span class="about-feature-text">Real-World Projects</span>
                    </div>
                </div>
                
                <a href="<?= baseUrl('about.php') ?>" class="btn btn-primary btn-lg mt-4">
                    Learn More About Us <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ COURSES SECTION ═══════════════════ -->
<section class="section section-alt" id="courses">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge"><i class="bi bi-book"></i> Our Courses</span>
            <h2 class="section-title">Explore Our <span class="text-gradient">Training Programs</span></h2>
            <p class="section-subtitle">Build strong foundations with our structured courses designed for practical learning.</p>
        </div>
        
        <div class="row g-4">
            <?php foreach ($courses as $index => $course): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 reveal">
                <div class="program-card">
                    <div class="program-icon">
                        <i class="bi <?= e($course['icon_class'] ?: 'bi-book') ?>"></i>
                    </div>
                    <h3 class="program-title"><?= e($course['title']) ?></h3>
                    <p class="program-desc"><?= e($course['short_description'] ?? '') ?></p>
                    <div class="program-meta">
                        <span class="program-meta-item">
                            <i class="bi bi-clock"></i> <?= e($course['duration'] ?? 'Flexible') ?>
                        </span>
                        <span class="program-meta-item">
                            <i class="bi bi-signal"></i> <?= e($course['level'] ?? 'All Levels') ?>
                        </span>
                        <span class="program-meta-item">
                            <i class="bi bi-laptop"></i> <?= e($course['mode'] ?? 'Online') ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-4 reveal">
            <a href="<?= baseUrl('courses.php') ?>" class="btn btn-outline-primary btn-lg">
                View All Courses <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════════ INTERNSHIP PROGRAMS SECTION ═══════════════════ -->
<section class="section" id="internships">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge"><i class="bi bi-briefcase"></i> Internship Programs</span>
            <h2 class="section-title">Launch Your Career with <span class="text-gradient">Our Internships</span></h2>
            <p class="section-subtitle">Gain real-world experience with structured internship programs and earn verified certificates.</p>
        </div>
        
        <div class="row g-4">
            <?php foreach ($programs as $program): ?>
            <div class="col-lg-4 col-md-6 reveal">
                <div class="program-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="program-icon mb-0">
                            <i class="bi <?= e($program['icon_class'] ?: 'bi-code-slash') ?>"></i>
                        </div>
                        <span class="program-status <?= $program['application_status'] === 'Open' ? 'status-open' : 'status-closed' ?>">
                            <i class="bi <?= $program['application_status'] === 'Open' ? 'bi-circle-fill' : 'bi-x-circle' ?>" style="font-size: 0.5rem;"></i>
                            <?= e($program['application_status']) ?>
                        </span>
                    </div>
                    <h3 class="program-title"><?= e($program['title']) ?></h3>
                    <p class="program-desc"><?= e($program['short_description'] ?? '') ?></p>
                    <div class="program-meta">
                        <span class="program-meta-item">
                            <i class="bi bi-clock"></i> <?= e($program['duration']) ?>
                        </span>
                        <span class="program-meta-item">
                            <i class="bi bi-laptop"></i> <?= e($program['mode']) ?>
                        </span>
                        <?php if ($program['total_seats']): ?>
                        <span class="program-meta-item">
                            <i class="bi bi-people"></i> <?= (int)($program['total_seats'] - $program['filled_seats']) ?> seats left
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-4 reveal">
            <a href="<?= baseUrl('internships.php') ?>" class="btn btn-primary btn-lg">
                View All Internships <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════════ WHY CHOOSE US ═══════════════════ -->
<section class="section section-alt" id="why-us">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge"><i class="bi bi-shield-check"></i> Why Choose Us</span>
            <h2 class="section-title">Why <span class="text-gradient">Excellence Classes?</span></h2>
            <p class="section-subtitle">We are committed to providing a quality learning experience that prepares students for the industry.</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-3 col-md-6 reveal">
                <div class="premium-card">
                    <div class="why-choose-card">
                        <div class="card-icon"><i class="bi bi-code-slash"></i></div>
                        <h4 class="card-title-text">Practical Learning</h4>
                        <p class="card-desc">Work on real projects and develop practical skills that employers value.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 reveal">
                <div class="premium-card">
                    <div class="why-choose-card">
                        <div class="card-icon"><i class="bi bi-person-workspace"></i></div>
                        <h4 class="card-title-text">Expert Guidance</h4>
                        <p class="card-desc">Learn from experienced mentors who guide you through every step of your journey.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 reveal">
                <div class="premium-card">
                    <div class="why-choose-card">
                        <div class="card-icon"><i class="bi bi-patch-check"></i></div>
                        <h4 class="card-title-text">Verified Certificates</h4>
                        <p class="card-desc">Receive QR-verified internship certificates upon successful completion.</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 reveal">
                <div class="premium-card">
                    <div class="why-choose-card">
                        <div class="card-icon"><i class="bi bi-calendar-check"></i></div>
                        <h4 class="card-title-text">Flexible Schedule</h4>
                        <p class="card-desc">Online programs that fit your schedule and allow you to learn at your own pace.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ TECHNOLOGIES WE TEACH ═══════════════════ -->
<section class="section" id="technologies">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge"><i class="bi bi-cpu"></i> Technologies</span>
            <h2 class="section-title">Technologies <span class="text-gradient">We Teach</span></h2>
            <p class="section-subtitle">Stay ahead with the latest technologies and programming languages.</p>
        </div>
        
        <div class="tech-grid reveal">
            <span class="tech-pill"><i class="bi bi-filetype-html"></i> HTML5</span>
            <span class="tech-pill"><i class="bi bi-filetype-css"></i> CSS3</span>
            <span class="tech-pill"><i class="bi bi-filetype-js"></i> JavaScript</span>
            <span class="tech-pill"><i class="bi bi-filetype-php"></i> PHP</span>
            <span class="tech-pill"><i class="bi bi-database"></i> MySQL</span>
            <span class="tech-pill"><i class="bi bi-filetype-py"></i> Python</span>
            <span class="tech-pill"><i class="bi bi-cup-hot"></i> Java</span>
            <span class="tech-pill"><i class="bi bi-braces"></i> C/C++</span>
            <span class="tech-pill"><i class="bi bi-bootstrap"></i> Bootstrap</span>
            <span class="tech-pill"><i class="bi bi-filetype-jsx"></i> React</span>
            <span class="tech-pill"><i class="bi bi-node-plus"></i> Node.js</span>
            <span class="tech-pill"><i class="bi bi-phone"></i> Flutter</span>
            <span class="tech-pill"><i class="bi bi-git"></i> Git</span>
            <span class="tech-pill"><i class="bi bi-github"></i> GitHub</span>
            <span class="tech-pill"><i class="bi bi-terminal"></i> VS Code</span>
            <span class="tech-pill"><i class="bi bi-server"></i> REST APIs</span>
        </div>
    </div>
</section>

<!-- ═══════════════════ LEARNING PROCESS ═══════════════════ -->
<section class="section section-alt" id="process">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge"><i class="bi bi-arrow-repeat"></i> How It Works</span>
            <h2 class="section-title">Your <span class="text-gradient">Learning Journey</span></h2>
            <p class="section-subtitle">A structured approach to ensure you get the most out of your internship experience.</p>
        </div>
        
        <div class="row g-4 reveal">
            <div class="col-lg-3 col-md-6">
                <div class="process-step">
                    <div class="step-number">1</div>
                    <h4 class="step-title">Register & Apply</h4>
                    <p class="step-desc">Create your account and apply for the internship program that matches your goals.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="process-step">
                    <div class="step-number">2</div>
                    <h4 class="step-title">Get Approved</h4>
                    <p class="step-desc">Your application is reviewed by our team. Approved interns receive their offer letter.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="process-step">
                    <div class="step-number">3</div>
                    <h4 class="step-title">Complete Tasks</h4>
                    <p class="step-desc">Work on assigned tasks, submit projects, and track your progress through the dashboard.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="process-step">
                    <div class="step-number">4</div>
                    <h4 class="step-title">Get Certified</h4>
                    <p class="step-desc">Upon successful completion, receive a QR-verified internship certificate.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ FAQ SECTION ═══════════════════ -->
<section class="section faq-section" id="faq">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-5 reveal">
                    <span class="section-badge"><i class="bi bi-question-circle"></i> FAQ</span>
                    <h2 class="section-title">Frequently Asked <span class="text-gradient">Questions</span></h2>
                    <p class="section-subtitle">Find answers to common questions about our programs and internships.</p>
                </div>
                
                <div class="accordion reveal" id="faqAccordion">
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" aria-expanded="true" aria-controls="faq1">
                                What is Excellence Classes By Repswal?
                            </button>
                        </h3>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Excellence Classes By Repswal is an educational and IT training institute based in Nawalgarh, Jhunjhunu, Rajasthan. It is a sole proprietorship run by Amit Repswal, offering IT training courses and internship programs to help students build practical skills in software development and related fields.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" aria-expanded="false" aria-controls="faq2">
                                How can I apply for an internship?
                            </button>
                        </h3>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                To apply for an internship, first register an account on our portal. Then browse the available internship programs and apply for the one that interests you. Your application will be reviewed by our team, and you will be notified of the decision.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" aria-expanded="false" aria-controls="faq3">
                                Are the internships online or offline?
                            </button>
                        </h3>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Our internship programs are primarily offered online, allowing students from anywhere to participate. Each program listing specifies its mode (Online, Offline, or Hybrid).
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4" aria-expanded="false" aria-controls="faq4">
                                Will I receive a certificate after the internship?
                            </button>
                        </h3>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, upon successful completion of your internship and all assigned tasks, you will receive a QR-verified Internship Completion Certificate. The certificate is issued by the Proprietor and can be verified online through our certificate verification portal.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5" aria-expanded="false" aria-controls="faq5">
                                How can I verify a certificate?
                            </button>
                        </h3>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Certificates can be verified by visiting our <a href="<?= baseUrl('verify-certificate.php') ?>">Certificate Verification</a> page. Enter the Certificate ID or scan the QR code printed on the certificate. The system will verify it against our database and display the certificate details.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6" aria-expanded="false" aria-controls="faq6">
                                Who can apply for the internship programs?
                            </button>
                        </h3>
                        <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Students currently pursuing or who have completed B.Tech, BCA, MCA, B.Sc(CS/IT), or equivalent IT/CS courses can apply. Each program may have specific eligibility criteria mentioned in its description.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ CONTACT SECTION ═══════════════════ -->
<section class="section section-alt" id="contact">
    <div class="container">
        <div class="text-center mb-5 reveal">
            <span class="section-badge"><i class="bi bi-envelope"></i> Contact</span>
            <h2 class="section-title">Get In <span class="text-gradient">Touch</span></h2>
            <p class="section-subtitle">Have questions? Reach out to us. We're here to help!</p>
        </div>
        
        <div class="row g-4 justify-content-center">
            <div class="col-lg-4 col-md-6 reveal">
                <div class="contact-card h-100">
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <div class="contact-info-label">Address</div>
                            <div class="contact-info-value"><?= e(getSetting('site_address', 'Nawalgarh, Jhunjhunu, Rajasthan, India - 333308')) ?></div>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="bi bi-envelope-fill"></i></div>
                        <div>
                            <div class="contact-info-label">Email</div>
                            <div class="contact-info-value">
                                <a href="mailto:<?= e(getSetting('site_email', 'info@excellenceclasses.com')) ?>">
                                    <?= e(getSetting('site_email', 'info@excellenceclasses.com')) ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($phone = getSetting('site_phone')): ?>
                    <div class="contact-info-item">
                        <div class="contact-info-icon"><i class="bi bi-telephone-fill"></i></div>
                        <div>
                            <div class="contact-info-label">Phone</div>
                            <div class="contact-info-value">
                                <a href="tel:<?= e($phone) ?>"><?= e($phone) ?></a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="contact-info-item mb-0">
                        <div class="contact-info-icon"><i class="bi bi-building"></i></div>
                        <div>
                            <div class="contact-info-label">Organization</div>
                            <div class="contact-info-value">Excellence Classes By Repswal<br><small class="text-muted">Sole Proprietorship</small></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 col-md-6 reveal">
                <div class="contact-card h-100">
                    <h4 class="mb-3" style="font-weight: 700;">Send us a Message</h4>
                    <form action="<?= baseUrl('contact.php') ?>" method="POST" class="needs-validation" novalidate>
                        <?= csrfField() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="contact_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="contact_name" name="name" placeholder="Your name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="contact_email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="contact_email" name="email" placeholder="your@email.com" required>
                            </div>
                            <div class="col-12">
                                <label for="contact_subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="contact_subject" name="subject" placeholder="What is this about?" required>
                            </div>
                            <div class="col-12">
                                <label for="contact_message" class="form-label">Message</label>
                                <textarea class="form-control" id="contact_message" name="message" rows="4" placeholder="Write your message here..." required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="submit_contact" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-send me-2"></i>Send Message
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ CTA SECTION ═══════════════════ -->
<section class="section" style="background: linear-gradient(135deg, var(--navy), var(--navy-mid)); color: var(--white);">
    <div class="container text-center reveal">
        <h2 style="font-size: 2.2rem; font-weight: 800; margin-bottom: 1rem;">Ready to Start Your Journey?</h2>
        <p style="font-size: 1.1rem; opacity: 0.8; max-width: 550px; margin: 0 auto 2rem;">
            Register now and take the first step towards building your career in technology.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="<?= baseUrl('register.php') ?>" class="btn btn-gold btn-lg">
                <i class="bi bi-person-plus me-2"></i>Register Now
            </a>
            <a href="<?= baseUrl('verify-certificate.php') ?>" class="btn btn-outline-light btn-lg" style="border-radius: 50px;">
                <i class="bi bi-qr-code me-2"></i>Verify Certificate
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
