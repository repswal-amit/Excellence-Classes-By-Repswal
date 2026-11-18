<?php
/**
 * About Us Page
 * Excellence Classes By Repswal
 */

$currentPage = 'about';
$pageTitle = 'About Us';
$pageDescription = 'Learn about Excellence Classes By Repswal, an educational and IT training institute founded by Amit Repswal in Nawalgarh, Rajasthan.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- ═══════════════════ PAGE HEADER ═══════════════════ -->
<section class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= baseUrl() ?>">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">About Us</li>
                    </ol>
                </nav>
                <h1 class="page-title">About Excellence Classes</h1>
                <p class="page-subtitle">
                    Empowering students and aspiring software professionals with practical skills, rigorous training, and structured internships.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end d-none d-lg-block">
                <span class="badge bg-gold-subtle text-gold px-3 py-2 rounded-pill fw-semibold">
                    <i class="bi bi-geo-alt-fill me-1"></i> Nawalgarh, Rajasthan
                </span>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ ABOUT STORY & OVERVIEW ═══════════════════ -->
<section class="section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 reveal">
                <div class="position-relative">
                    <div class="glass-card p-4 p-md-5">
                        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-primary-subtle text-primary fw-semibold small mb-3">
                            <i class="bi bi-mortarboard-fill"></i> Our Foundation
                        </div>
                        <h2 class="section-title mb-3">Dedicated to Practical IT & Software Education</h2>
                        <p class="text-secondary leading-relaxed">
                            <strong>Excellence Classes By Repswal</strong> is a dedicated educational and IT training institute located in Nawalgarh, Jhunjhunu, Rajasthan. Founded by <strong>Amit Repswal</strong> as a Sole Proprietorship, the institute was established with a clear mission: to bridge the gap between academic theory and real-world technology demands.
                        </p>
                        <p class="text-secondary leading-relaxed mb-4">
                            We specialize in modern software engineering disciplines including Full Stack Web Development, Python, Java, App Development, and Database Systems. Our training programs and structured internships prioritize hands-on problem solving, real project architectures, and transparent performance evaluation.
                        </p>
                        
                        <div class="row g-3 pt-2">
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    <span class="fw-medium text-dark">Hands-on Task Practice</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    <span class="fw-medium text-dark">Industry Standard Code</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    <span class="fw-medium text-dark">Direct Mentor Feedback</span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                    <span class="fw-medium text-dark">QR-Verified Certificates</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 reveal">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="metric-card h-100">
                            <div>
                                <div class="metric-icon royal mb-3">
                                    <i class="bi bi-bullseye"></i>
                                </div>
                                <h4 class="fw-bold fs-5 mb-2">Our Mission</h4>
                                <p class="text-secondary small mb-0">
                                    Deliver accessible, high-caliber technical education and structured internships that empower students to build real software with confidence.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="metric-card h-100">
                            <div>
                                <div class="metric-icon gold mb-3">
                                    <i class="bi bi-eye"></i>
                                </div>
                                <h4 class="fw-bold fs-5 mb-2">Our Vision</h4>
                                <p class="text-secondary small mb-0">
                                    To be a trusted regional and national hub for authentic, skill-first technical training where every learner masters production-ready engineering practices.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="metric-card h-100">
                            <div>
                                <div class="metric-icon green mb-3">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <h4 class="fw-bold fs-5 mb-2">Integrity First</h4>
                                <p class="text-secondary small mb-0">
                                    Transparent evaluations with zero false claims. Every completion certificate reflects authentic task submissions and verified milestones.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="metric-card h-100">
                            <div>
                                <div class="metric-icon orange mb-3">
                                    <i class="bi bi-award"></i>
                                </div>
                                <h4 class="fw-bold fs-5 mb-2">Learn. Develop. Grow.</h4>
                                <p class="text-secondary small mb-0">
                                    Our core philosophy guides every lecture, lab, and internship project from the first line of code to portfolio-ready deployment.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ PROPRIETOR & LEADERSHIP ═══════════════════ -->
<section class="section bg-light">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5 reveal">
            <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill fw-semibold small mb-2">
                Leadership
            </span>
            <h2 class="section-title">Meet the Proprietor</h2>
            <p class="section-subtitle">
                Guiding students with dedicated mentorship, software engineering expertise, and educational commitment.
            </p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8 reveal">
                <div class="glass-card p-4 p-md-5">
                    <div class="row align-items-center g-4">
                        <div class="col-md-4 text-center">
                            <div class="avatar-holder mx-auto mb-3" style="width: 140px; height: 140px; border-radius: 50%; background: linear-gradient(135deg, var(--navy), var(--royal)); display: flex; align-items: center; justify-content: center; color: var(--gold); font-size: 3.5rem; border: 4px solid var(--white); box-shadow: 0 10px 25px rgba(10,22,40,0.15);">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <h4 class="fw-bold mb-1">Amit Repswal</h4>
                            <p class="text-gold fw-semibold small mb-2">Proprietor & Lead Instructor</p>
                            <span class="badge bg-secondary-subtle text-secondary small">
                                Nawalgarh, Rajasthan
                            </span>
                        </div>
                        <div class="col-md-8">
                            <h5 class="fw-bold text-dark mb-3">Message from the Proprietor</h5>
                            <blockquote class="blockquote fs-6 text-secondary fst-italic mb-3">
                                "Technology is mastered through disciplined practice, building real applications, and understanding core engineering principles. At Excellence Classes By Repswal, our promise is sincere guidance, rigorous curriculum, and an authentic internship platform where your efforts translate directly into verifiable skills."
                            </blockquote>
                            <div class="border-top pt-3 d-flex flex-wrap gap-4 text-muted small">
                                <div><i class="bi bi-building me-1 text-primary"></i> Sole Proprietorship</div>
                                <div><i class="bi bi-mortarboard me-1 text-primary"></i> Educational Services</div>
                                <div><i class="bi bi-geo-alt me-1 text-primary"></i> PIN: 333308</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ INSTITUTIONAL IDENTITY ═══════════════════ -->
<section class="section">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5 reveal">
            <span class="badge bg-gold-subtle text-gold px-3 py-1 rounded-pill fw-semibold small mb-2">
                Transparency
            </span>
            <h2 class="section-title">Institutional Overview</h2>
            <p class="section-subtitle">
                Clear, honest, and verifiable institutional details in full compliance with regulatory standards.
            </p>
        </div>

        <div class="row g-4 reveal">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="metric-icon royal" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi bi-briefcase-fill"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Legal Structure</h6>
                            <small class="text-muted">Entity Information</small>
                        </div>
                    </div>
                    <ul class="list-unstyled text-secondary small mb-0">
                        <li class="py-1"><strong>Organization:</strong> Excellence Classes By Repswal</li>
                        <li class="py-1"><strong>Structure:</strong> Sole Proprietorship</li>
                        <li class="py-1"><strong>Proprietor:</strong> Amit Repswal</li>
                        <li class="py-1"><strong>Category:</strong> Micro Enterprise (Educational Services)</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="metric-icon green" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi bi-geo-fill"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Location & Reach</h6>
                            <small class="text-muted">Regional Hub</small>
                        </div>
                    </div>
                    <ul class="list-unstyled text-secondary small mb-0">
                        <li class="py-1"><strong>Town:</strong> Nawalgarh</li>
                        <li class="py-1"><strong>District:</strong> Jhunjhunu</li>
                        <li class="py-1"><strong>State:</strong> Rajasthan, India</li>
                        <li class="py-1"><strong>Postal Code:</strong> 333308</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="metric-icon gold" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Credential Verification</h6>
                            <small class="text-muted">Tamper-Proof Records</small>
                        </div>
                    </div>
                    <ul class="list-unstyled text-secondary small mb-0">
                        <li class="py-1"><strong>Verification:</strong> Instant QR & Online ID</li>
                        <li class="py-1"><strong>Audit Trail:</strong> Full cryptographic issuance logs</li>
                        <li class="py-1"><strong>Signatory:</strong> Authorized Proprietor Signature</li>
                        <li class="py-1"><strong>Privacy:</strong> Zero exposure of private data</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ CTA BANNER ═══════════════════ -->
<section class="section py-5" style="background: linear-gradient(135deg, var(--navy), var(--navy-mid)); color: var(--white);">
    <div class="container text-center reveal">
        <h3 class="fw-bold mb-3">Join Excellence Classes By Repswal Today</h3>
        <p class="text-light opacity-75 max-w-600 mx-auto mb-4">
            Browse our open internship domains or contact our admissions desk for training schedules and syllabus details.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="<?= baseUrl('internships.php') ?>" class="btn btn-gold btn-lg">
                <i class="bi bi-mortarboard me-2"></i>Explore Internships
            </a>
            <a href="<?= baseUrl('contact.php') ?>" class="btn btn-outline-light btn-lg">
                <i class="bi bi-chat-dots me-2"></i>Contact Us
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
