<?php
/**
 * Courses & IT Training Programs
 * Excellence Classes By Repswal
 */

$currentPage = 'courses';
$pageTitle = 'Our Courses & IT Training Programs';
$pageDescription = 'Explore professional IT training courses offered by Excellence Classes By Repswal in Nawalgarh, Rajasthan including Web Development, Python, Java, and App Development.';

require_once __DIR__ . '/includes/header.php';

// Fetch courses from database
try {
    $pdo = getDBConnection();
    
    // Filter parameters
    $selectedLevel = trim($_GET['level'] ?? '');
    $searchQuery = trim($_GET['q'] ?? '');
    
    $sql = "SELECT * FROM courses WHERE is_active = 1";
    $params = [];
    
    if (!empty($selectedLevel) && in_array($selectedLevel, ['Beginner', 'Intermediate', 'Advanced', 'All Levels'])) {
        $sql .= " AND (level = ? OR level = 'All Levels')";
        $params[] = $selectedLevel;
    }
    
    if (!empty($searchQuery)) {
        $sql .= " AND (title LIKE ? OR short_description LIKE ?)";
        $params[] = "%$searchQuery%";
        $params[] = "%$searchQuery%";
    }
    
    $sql .= " ORDER BY sort_order ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $courses = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $courses = [];
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
                        <li class="breadcrumb-item active" aria-current="page">Courses</li>
                    </ol>
                </nav>
                <h1 class="page-title">Courses & IT Training Programs</h1>
                <p class="page-subtitle">
                    Comprehensive, hands-on technical curriculum crafted to provide practical software engineering competence and foundational problem-solving skills.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end d-none d-lg-block">
                <a href="<?= baseUrl('internships.php') ?>" class="btn btn-gold rounded-pill px-4 py-2">
                    <i class="bi bi-briefcase me-1"></i> Looking for Internships?
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ FILTER & SEARCH ═══════════════════ -->
<section class="section py-4 border-bottom bg-light">
    <div class="container">
        <form method="GET" action="<?= baseUrl('courses.php') ?>" class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-start-0" placeholder="Search courses (e.g. Python, Web, Java)..." value="<?= e($searchQuery ?? '') ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="level" class="form-select" onchange="this.form.submit()">
                    <option value="">All Skill Levels</option>
                    <option value="Beginner" <?= ($selectedLevel ?? '') === 'Beginner' ? 'selected' : '' ?>>Beginner Level</option>
                    <option value="Intermediate" <?= ($selectedLevel ?? '') === 'Intermediate' ? 'selected' : '' ?>>Intermediate Level</option>
                    <option value="Advanced" <?= ($selectedLevel ?? '') === 'Advanced' ? 'selected' : '' ?>>Advanced Level</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <?php if (!empty($searchQuery) || !empty($selectedLevel)): ?>
                    <a href="<?= baseUrl('courses.php') ?>" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</section>

<!-- ═══════════════════ COURSES GRID ═══════════════════ -->
<section class="section">
    <div class="container">
        <?php if (empty($courses)): ?>
            <div class="glass-card p-5 text-center my-4">
                <div class="mb-3 text-muted fs-1"><i class="bi bi-journal-x"></i></div>
                <h4 class="fw-bold">No Courses Found</h4>
                <p class="text-secondary max-w-500 mx-auto mb-4">
                    <?= !empty($searchQuery) ? 'No courses matched your search query. Try broadening your terms or reset the filters.' : 'Courses are currently being updated. Please check back shortly or get in touch with us.' ?>
                </p>
                <a href="<?= baseUrl('courses.php') ?>" class="btn btn-primary">View All Courses</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($courses as $c): ?>
                    <?php
                        $techs = [];
                        if (!empty($c['technologies'])) {
                            $decoded = json_decode($c['technologies'], true);
                            if (is_array($decoded)) {
                                $techs = $decoded;
                            }
                        }
                        $icon = !empty($c['icon_class']) ? $c['icon_class'] : 'bi-laptop';
                    ?>
                    <div class="col-lg-4 col-md-6 reveal">
                        <div class="course-card h-100 d-flex flex-direction-column">
                            <div class="course-card-top d-flex justify-content-between align-items-start mb-3">
                                <div class="course-icon-box">
                                    <i class="bi <?= e($icon) ?>"></i>
                                </div>
                                <div class="d-flex flex-column align-items-end gap-1">
                                    <span class="badge bg-primary-subtle text-primary rounded-pill small">
                                        <?= e($c['level'] ?? 'All Levels') ?>
                                    </span>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill small">
                                        <i class="bi bi-clock me-1"></i><?= e($c['duration'] ?? 'Self-paced') ?>
                                    </span>
                                </div>
                            </div>
                            
                            <h3 class="course-card-title"><?= e($c['title']) ?></h3>
                            <p class="course-card-desc flex-grow-1">
                                <?= e($c['short_description'] ?? 'In-depth practical curriculum covering concepts, design, and code implementation.') ?>
                            </p>
                            
                            <?php if (!empty($techs)): ?>
                                <div class="course-card-techs mb-3">
                                    <?php foreach (array_slice($techs, 0, 4) as $tech): ?>
                                        <span class="badge bg-light text-dark border"><?= e($tech) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="course-card-footer border-top pt-3 d-flex justify-content-between align-items-center">
                                <span class="badge bg-light text-muted">
                                    <i class="bi bi-broadcast me-1"></i><?= e($c['mode'] ?? 'Online') ?>
                                </span>
                                <a href="<?= baseUrl('contact.php?subject=' . urlencode('Inquiry regarding ' . $c['title'])) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                    Inquire Now <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════════════ TRAINING HIGHLIGHTS ═══════════════════ -->
<section class="section bg-light">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5 reveal">
            <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill fw-semibold small mb-2">
                Features
            </span>
            <h2 class="section-title">Why Learn at Excellence Classes?</h2>
            <p class="section-subtitle">
                Quality education with structured mentorship tailored for college students, graduates, and aspiring developers.
            </p>
        </div>

        <div class="row g-4 reveal">
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm p-4 text-center h-100">
                    <div class="metric-icon royal mx-auto mb-3" style="width: 50px; height: 50px;">
                        <i class="bi bi-laptop"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Practical Labs</h5>
                    <p class="text-secondary small mb-0">Learn by writing clean, working code from your very first session.</p>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm p-4 text-center h-100">
                    <div class="metric-icon gold mx-auto mb-3" style="width: 50px; height: 50px;">
                        <i class="bi bi-person-video3"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Dedicated Mentor</h5>
                    <p class="text-secondary small mb-0">Direct interaction and query resolution with instructor Amit Repswal.</p>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm p-4 text-center h-100">
                    <div class="metric-icon green mx-auto mb-3" style="width: 50px; height: 50px;">
                        <i class="bi bi-code-square"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Real Projects</h5>
                    <p class="text-secondary small mb-0">Build portfolio-worthy applications and database-driven solutions.</p>
                </div>
            </div>

            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm p-4 text-center h-100">
                    <div class="metric-icon cyan mx-auto mb-3" style="width: 50px; height: 50px;">
                        <i class="bi bi-patch-check"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Verified Completion</h5>
                    <p class="text-secondary small mb-0">Earn verifiable completion certificates backed by transparent records.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ CTA ═══════════════════ -->
<section class="section py-5" style="background: linear-gradient(135deg, var(--navy), var(--navy-mid)); color: var(--white);">
    <div class="container text-center reveal">
        <h3 class="fw-bold mb-3">Looking to Apply for an Internship Instead?</h3>
        <p class="text-light opacity-75 max-w-600 mx-auto mb-4">
            Our structured internship programs offer real task deadlines, code review, offer letters, and verifiable completion certificates.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="<?= baseUrl('internships.php') ?>" class="btn btn-gold btn-lg">
                <i class="bi bi-briefcase me-2"></i>Browse Internships
            </a>
            <a href="<?= baseUrl('apply.php') ?>" class="btn btn-outline-light btn-lg">
                <i class="bi bi-send me-2"></i>Apply for Internship
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
