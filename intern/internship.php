<?php
/**
 * My Internship Track & Curriculum
 * Excellence Classes By Repswal
 */

$internPageTitle = 'My Internship Track';
$currentInternPage = 'internship';

require_once __DIR__ . '/includes/header.php';

// Fetch active or completed enrollment
try {
    $stmt = $pdo->prepare("
        SELECT e.*, p.title AS program_title, p.description AS program_description, p.technologies, p.skills_covered, p.duration AS program_duration, p.mode AS program_mode,
               pr.total_tasks, pr.completed_tasks, pr.progress_percentage, pr.performance_rating, pr.remarks AS progress_remarks,
               c.certificate_id, c.certificate_status
        FROM internship_enrollments e
        JOIN internship_programs p ON e.program_id = p.id
        LEFT JOIN internship_progress pr ON e.id = pr.enrollment_id
        LEFT JOIN certificates c ON e.id = c.enrollment_id
        WHERE e.intern_id = ?
        ORDER BY e.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$internId]);
    $enrollment = $stmt->fetch();
} catch (PDOException $e) {
    $enrollment = null;
}

$techs = !empty($enrollment['technologies']) ? json_decode($enrollment['technologies'], true) : [];
$skills = !empty($enrollment['skills_covered']) ? json_decode($enrollment['skills_covered'], true) : [];
$pct = (float)($enrollment['progress_percentage'] ?? 0);
?>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">My Internship Track</h4>
        <p class="text-muted small mb-0">Overview of your enrolled domain, syllabus, progress metrics, and completion standing.</p>
    </div>
</div>

<?php if (!$enrollment): ?>
    <div class="panel-card p-5 text-center text-muted">
        <i class="bi bi-briefcase fs-1 d-block mb-2 text-secondary"></i>
        <h5 class="fw-bold">No Active Internship Track</h5>
        <p class="small mb-3">You are not currently enrolled in an active internship. Apply for an open program to begin.</p>
        <a href="<?= baseUrl('apply.php') ?>" class="btn btn-primary rounded-pill px-4">Apply for Internship</a>
    </div>
<?php else: ?>
    <!-- Track Overview Card -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="panel-card h-100">
                <div class="panel-header d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary-subtle text-primary mb-1">Enrolled Domain</span>
                        <h4 class="fw-bold text-dark mb-0"><?= e($enrollment['program_title']) ?></h4>
                    </div>
                    <?= statusBadge($enrollment['internship_status']) ?>
                </div>

                <div class="panel-body">
                    <p class="text-secondary leading-relaxed mb-4">
                        <?= nl2br(e($enrollment['program_description'] ?? 'Hands-on project development and milestone submissions under direct mentorship.')) ?>
                    </p>

                    <h6 class="fw-bold text-dark mb-2">Technologies & Stack</h6>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <?php foreach ((array)$techs as $t): ?>
                            <span class="badge bg-light text-dark border px-3 py-2"><?= e($t) ?></span>
                        <?php endforeach; ?>
                    </div>

                    <h6 class="fw-bold text-dark mb-2">Competencies & Skills Covered</h6>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <?php foreach ((array)$skills as $s): ?>
                            <span class="badge bg-primary-subtle text-primary border px-3 py-2"><?= e($s) ?></span>
                        <?php endforeach; ?>
                    </div>

                    <div class="p-3 bg-light rounded-3">
                        <div class="row g-3 text-center small">
                            <div class="col-sm-4">
                                <span class="text-muted d-block">Start Date</span>
                                <strong class="text-dark fs-6"><?= formatDate($enrollment['start_date']) ?></strong>
                            </div>
                            <div class="col-sm-4 border-start border-end">
                                <span class="text-muted d-block">End Date</span>
                                <strong class="text-dark fs-6"><?= formatDate($enrollment['end_date']) ?></strong>
                            </div>
                            <div class="col-sm-4">
                                <span class="text-muted d-block">Duration & Mode</span>
                                <strong class="text-dark fs-6"><?= e($enrollment['program_duration']) ?> (<?= e($enrollment['program_mode']) ?>)</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Standing Sidebar -->
        <div class="col-lg-4">
            <div class="panel-card h-100">
                <div class="panel-header">
                    <h5 class="panel-title"><i class="bi bi-speedometer2 text-primary"></i> Progress Standing</h5>
                </div>
                <div class="panel-body">
                    <div class="text-center my-3">
                        <div class="fs-1 fw-bold text-primary mb-1"><?= $pct ?>%</div>
                        <div class="small text-muted mb-3">Overall Milestone Completion</div>
                        <div class="progress-bar-custom mx-auto mb-3" style="max-width: 200px; height: 8px;">
                            <div class="progress-fill <?= ($pct >= 100) ? 'success' : '' ?>" style="width: <?= min(100, $pct) ?>%;"></div>
                        </div>
                    </div>

                    <div class="p-3 bg-light rounded-3 mb-3 small">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Total Milestones:</span>
                            <strong><?= (int)($enrollment['total_tasks'] ?? 0) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Approved Tasks:</span>
                            <strong class="text-success"><?= (int)($enrollment['completed_tasks'] ?? 0) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-muted">Mentor Rating:</span>
                            <strong><?= !empty($enrollment['performance_rating']) ? $enrollment['performance_rating'] . ' / 5.0' : 'In Progress' ?></strong>
                        </div>
                    </div>

                    <?php if ((int)$enrollment['completion_approved'] === 1): ?>
                        <div class="alert alert-success small mb-3">
                            <i class="bi bi-check-circle-fill me-1"></i>
                            <strong>Internship Completed!</strong> Your completion has been officially approved by the Proprietor.
                        </div>
                        <a href="<?= baseUrl('intern/certificates.php') ?>" class="btn btn-gold w-100 fw-bold">
                            <i class="bi bi-patch-check-fill me-1"></i>View Certificate
                        </a>
                    <?php else: ?>
                        <a href="<?= baseUrl('intern/tasks.php') ?>" class="btn btn-primary w-100 fw-bold">
                            <i class="bi bi-list-task me-1"></i>Complete Tasks
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
