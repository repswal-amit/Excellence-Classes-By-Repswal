<?php
/**
 * Internship Programs Directory
 * Excellence Classes By Repswal
 */

$currentPage = 'internships';
$pageTitle = 'Internship Programs';
$pageDescription = 'Explore structured internship programs at Excellence Classes By Repswal. Gain hands-on project experience, task mentorship, and verifiable completion certificates.';

require_once __DIR__ . '/includes/header.php';

// Fetch active programs from database
try {
    $pdo = getDBConnection();
    
    $statusFilter = trim($_GET['status'] ?? '');
    $searchQuery = trim($_GET['q'] ?? '');
    
    $sql = "SELECT * FROM internship_programs WHERE is_active = 1 AND is_archived = 0";
    $params = [];
    
    if (!empty($statusFilter) && in_array($statusFilter, ['Open', 'Closed'])) {
        $sql .= " AND application_status = ?";
        $params[] = $statusFilter;
    }
    
    if (!empty($searchQuery)) {
        $sql .= " AND (title LIKE ? OR short_description LIKE ?)";
        $params[] = "%$searchQuery%";
        $params[] = "%$searchQuery%";
    }
    
    $sql .= " ORDER BY sort_order ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $programs = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $programs = [];
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
                        <li class="breadcrumb-item active" aria-current="page">Internships</li>
                    </ol>
                </nav>
                <h1 class="page-title">Internship Programs</h1>
                <p class="page-subtitle">
                    Structured, industry-relevant internship tracks designed for students and aspiring engineers to build real-world software applications under direct mentorship.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end d-none d-lg-block">
                <a href="<?= baseUrl('apply.php') ?>" class="btn btn-gold btn-lg rounded-pill shadow-sm">
                    <i class="bi bi-send-fill me-2"></i>Apply for Internship
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ FILTER & SEARCH ═══════════════════ -->
<section class="section py-4 border-bottom bg-light">
    <div class="container">
        <form method="GET" action="<?= baseUrl('internships.php') ?>" class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Search domain (e.g. Web, Python, Full Stack)..." value="<?= e($searchQuery ?? '') ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Application Statuses</option>
                    <option value="Open" <?= ($statusFilter ?? '') === 'Open' ? 'selected' : '' ?>>Applications Open</option>
                    <option value="Closed" <?= ($statusFilter ?? '') === 'Closed' ? 'selected' : '' ?>>Applications Closed</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <?php if (!empty($searchQuery) || !empty($statusFilter)): ?>
                    <a href="<?= baseUrl('internships.php') ?>" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</section>

<!-- ═══════════════════ INTERNSHIPS GRID ═══════════════════ -->
<section class="section">
    <div class="container">
        <?php if (empty($programs)): ?>
            <div class="glass-card p-5 text-center my-4">
                <div class="mb-3 text-muted fs-1"><i class="bi bi-briefcase"></i></div>
                <h4 class="fw-bold">No Internship Programs Found</h4>
                <p class="text-secondary max-w-500 mx-auto mb-4">
                    <?= !empty($searchQuery) ? 'No internship programs matched your search filters. Try resetting the filter.' : 'Internship programs are being updated. Please check back shortly.' ?>
                </p>
                <a href="<?= baseUrl('internships.php') ?>" class="btn btn-primary">View All Programs</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($programs as $prog): ?>
                    <?php
                        $isOpen = ($prog['application_status'] === 'Open');
                        $techs = [];
                        if (!empty($prog['technologies'])) {
                            $decoded = json_decode($prog['technologies'], true);
                            if (is_array($decoded)) $techs = $decoded;
                        }
                        $skills = [];
                        if (!empty($prog['skills_covered'])) {
                            $decodedSkills = json_decode($prog['skills_covered'], true);
                            if (is_array($decodedSkills)) $skills = $decodedSkills;
                        }
                        $icon = !empty($prog['icon_class']) ? $prog['icon_class'] : 'bi-code-slash';
                    ?>
                    <div class="col-lg-4 col-md-6 reveal">
                        <div class="program-card h-100 d-flex flex-column">
                            <div class="program-card-header d-flex justify-content-between align-items-start mb-3">
                                <div class="program-icon-box">
                                    <i class="bi <?= e($icon) ?>"></i>
                                </div>
                                <div class="d-flex flex-column align-items-end gap-1">
                                    <?php if ($isOpen): ?>
                                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1">
                                            <i class="bi bi-check-circle me-1"></i>Applications Open
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1">
                                            <i class="bi bi-clock-history me-1"></i>Applications Closed
                                        </span>
                                    <?php endif; ?>
                                    <span class="badge bg-light text-dark border rounded-pill">
                                        <i class="bi bi-calendar3 me-1"></i><?= e($prog['duration']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <h3 class="program-card-title"><?= e($prog['title']) ?></h3>
                            <p class="program-card-desc">
                                <?= e($prog['short_description']) ?>
                            </p>
                            
                            <?php if (!empty($prog['eligibility'])): ?>
                                <div class="small text-muted mb-3">
                                    <i class="bi bi-mortarboard me-1 text-primary"></i>
                                    <strong>Eligibility:</strong> <?= e($prog['eligibility']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($techs)): ?>
                                <div class="program-tech-tags mb-3">
                                    <?php foreach (array_slice($techs, 0, 4) as $t): ?>
                                        <span class="badge bg-light text-secondary border"><?= e($t) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($techs) > 4): ?>
                                        <span class="badge bg-light text-muted border">+<?= count($techs) - 4 ?> more</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>

                            <div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#programModal<?= (int)$prog['id'] ?>">
                                    <i class="bi bi-info-circle me-1"></i>Details
                                </button>

                                <?php if ($isOpen): ?>
                                    <a href="<?= baseUrl('apply.php?program=' . urlencode($prog['slug'])) ?>" class="btn btn-primary btn-sm rounded-pill px-3">
                                        <i class="bi bi-send me-1"></i>Apply Now
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm rounded-pill px-3" disabled title="Applications are currently closed">
                                        <i class="bi bi-lock me-1"></i>Closed
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Program Details Modal -->
                    <div class="modal fade" id="programModal<?= (int)$prog['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= (int)$prog['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content border-0 shadow-lg">
                                <div class="modal-header bg-navy text-white">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi <?= e($icon) ?> fs-4 text-gold"></i>
                                        <h5 class="modal-title fw-bold" id="modalLabel<?= (int)$prog['id'] ?>"><?= e($prog['title']) ?></h5>
                                    </div>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="d-flex flex-wrap gap-2 mb-4">
                                        <span class="badge bg-primary-subtle text-primary"><i class="bi bi-clock me-1"></i>Duration: <?= e($prog['duration']) ?></span>
                                        <span class="badge bg-info-subtle text-info"><i class="bi bi-geo-alt me-1"></i>Mode: <?= e($prog['mode']) ?></span>
                                        <?php if ($isOpen): ?>
                                            <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Status: Open</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-x-circle me-1"></i>Status: Closed</span>
                                        <?php endif; ?>
                                    </div>

                                    <h6 class="fw-bold text-dark">Program Overview</h6>
                                    <p class="text-secondary leading-relaxed mb-4">
                                        <?= nl2br(e($prog['description'] ?? $prog['short_description'])) ?>
                                    </p>

                                    <?php if (!empty($prog['eligibility'])): ?>
                                        <h6 class="fw-bold text-dark">Eligibility & Prerequisites</h6>
                                        <p class="text-secondary mb-4">
                                            <?= e($prog['eligibility']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($techs)): ?>
                                        <h6 class="fw-bold text-dark">Tools & Technologies</h6>
                                        <div class="d-flex flex-wrap gap-2 mb-4">
                                            <?php foreach ($techs as $t): ?>
                                                <span class="badge bg-light text-dark border px-3 py-2"><?= e($t) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($skills)): ?>
                                        <h6 class="fw-bold text-dark">Skills Covered</h6>
                                        <div class="d-flex flex-wrap gap-2 mb-4">
                                            <?php foreach ($skills as $s): ?>
                                                <span class="badge bg-primary-subtle text-primary border px-3 py-2"><?= e($s) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="card bg-light border-0 p-3 rounded-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="bi bi-patch-check-fill text-gold fs-3"></i>
                                            <div>
                                                <h6 class="fw-bold mb-0">Official Completion Certificate</h6>
                                                <small class="text-muted">Issued by Amit Repswal (Proprietor) upon successful task completion and evaluation, complete with unique ID and QR verification.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer bg-light">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <?php if ($isOpen): ?>
                                        <a href="<?= baseUrl('apply.php?program=' . urlencode($prog['slug'])) ?>" class="btn btn-primary">
                                            <i class="bi bi-send me-1"></i>Apply for this Program
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════════════ INTERNSHIP WORKFLOW ═══════════════════ -->
<section class="section bg-light">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5 reveal">
            <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill fw-semibold small mb-2">
                Workflow
            </span>
            <h2 class="section-title">How the Internship Program Works</h2>
            <p class="section-subtitle">
                A transparent, step-by-step process designed to ensure disciplined learning and measurable skill development.
            </p>
        </div>

        <div class="row g-4 reveal">
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm p-4 h-100 position-relative">
                    <div class="badge bg-primary rounded-pill mb-3" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 1rem;">1</div>
                    <h5 class="fw-bold mb-2">Register & Apply</h5>
                    <p class="text-secondary small mb-0">Create an intern account, select your target program domain, and submit your application with educational details.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm p-4 h-100 position-relative">
                    <div class="badge bg-primary rounded-pill mb-3" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 1rem;">2</div>
                    <h5 class="fw-bold mb-2">Review & Offer</h5>
                    <p class="text-secondary small mb-0">The Proprietor reviews your application. Upon approval, your official Offer Letter and start schedule are generated.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm p-4 h-100 position-relative">
                    <div class="badge bg-primary rounded-pill mb-3" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 1rem;">3</div>
                    <h5 class="fw-bold mb-2">Tasks & Reviews</h5>
                    <p class="text-secondary small mb-0">Receive practical coding milestones, submit project repositories, and receive detailed feedback from your mentor.</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm p-4 h-100 position-relative">
                    <div class="badge bg-gold text-dark rounded-pill mb-3" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 700;">4</div>
                    <h5 class="fw-bold mb-2">Verified Certificate</h5>
                    <p class="text-secondary small mb-0">After completion approval, download your official certificate featuring a secure QR code and permanent database verification.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ CTA BANNER ═══════════════════ -->
<section class="section py-5" style="background: linear-gradient(135deg, var(--navy), var(--navy-mid)); color: var(--white);">
    <div class="container text-center reveal">
        <h3 class="fw-bold mb-3">Ready to Build Your Engineering Portfolio?</h3>
        <p class="text-light opacity-75 max-w-600 mx-auto mb-4">
            Take the first step today by submitting your internship application or contacting our desk for program dates.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="<?= baseUrl('apply.php') ?>" class="btn btn-gold btn-lg">
                <i class="bi bi-send-fill me-2"></i>Apply for Internship
            </a>
            <a href="<?= baseUrl('verify-certificate.php') ?>" class="btn btn-outline-light btn-lg">
                <i class="bi bi-qr-code me-2"></i>Verify a Certificate
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
