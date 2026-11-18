<?php
/**
 * Intern Dashboard Overview
 * Excellence Classes By Repswal
 */

$internPageTitle = 'Dashboard';
$currentInternPage = 'dashboard';

require_once __DIR__ . '/includes/header.php';

// Fetch intern's active or latest enrollment
try {
    $enrStmt = $pdo->prepare("
        SELECT e.*, p.title AS program_title, p.duration AS program_duration, p.mode AS program_mode,
               pr.total_tasks, pr.completed_tasks, pr.progress_percentage, pr.performance_rating,
               c.certificate_id, c.certificate_status
        FROM internship_enrollments e
        JOIN internship_programs p ON e.program_id = p.id
        LEFT JOIN internship_progress pr ON e.id = pr.enrollment_id
        LEFT JOIN certificates c ON e.id = c.enrollment_id
        WHERE e.intern_id = ?
        ORDER BY e.created_at DESC
        LIMIT 1
    ");
    $enrStmt->execute([$internId]);
    $currentEnrollment = $enrStmt->fetch();

    // Fetch latest application status
    $appStmt = $pdo->prepare("
        SELECT a.*, p.title AS program_title 
        FROM internship_applications a
        JOIN internship_programs p ON a.program_id = p.id
        WHERE a.intern_id = ?
        ORDER BY a.created_at DESC
        LIMIT 1
    ");
    $appStmt->execute([$internId]);
    $latestApp = $appStmt->fetch();

    // Recent Tasks assigned to this intern
    $tasksStmt = $pdo->prepare("
        SELECT t.*, s.id AS submission_id, s.submission_status, s.admin_feedback
        FROM tasks t
        JOIN internship_enrollments e ON t.enrollment_id = e.id
        LEFT JOIN task_submissions s ON t.id = s.task_id AND s.intern_id = ?
        WHERE e.intern_id = ?
        ORDER BY t.created_at DESC
        LIMIT 5
    ");
    $tasksStmt->execute([$internId, $internId]);
    $recentTasks = $tasksStmt->fetchAll();

    // Latest Announcements
    $annStmt = $pdo->query("
        SELECT * FROM announcements 
        WHERE is_active = 1 AND target_audience IN ('All', 'Interns')
        ORDER BY created_at DESC
        LIMIT 2
    ");
    $announcements = $annStmt->fetchAll();

} catch (PDOException $e) {
    $currentEnrollment = null;
    $latestApp = null;
    $recentTasks = [];
    $announcements = [];
}

$progressPercentage = (float)($currentEnrollment['progress_percentage'] ?? 0);
$totalTasks = (int)($currentEnrollment['total_tasks'] ?? count($recentTasks));
$completedTasks = (int)($currentEnrollment['completed_tasks'] ?? 0);
$pendingTasks = max(0, $totalTasks - $completedTasks);
?>

<!-- ═══════════════════ WELCOME BANNER ═══════════════════ -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm p-4 text-white position-relative" style="background: linear-gradient(135deg, var(--navy), var(--navy-mid)); border-radius: 16px;">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-gold text-dark px-3 py-1 rounded-pill fw-bold">Intern Portal</span>
                        <span class="text-white-50 small"><?= e($internProfile['college_name'] ?? 'Student') ?></span>
                    </div>
                    <h2 class="fw-bold mb-1">Welcome back, <?= e($internProfile['full_name']) ?>!</h2>
                    <p class="text-white-50 mb-0 small">
                        <?php if ($currentEnrollment): ?>
                            Active track: <strong><?= e($currentEnrollment['program_title']) ?></strong> (<?= formatDate($currentEnrollment['start_date']) ?> to <?= formatDate($currentEnrollment['end_date']) ?>)
                        <?php elseif ($latestApp): ?>
                            Application for <strong><?= e($latestApp['program_title']) ?></strong> is currently <strong><?= e($latestApp['application_status']) ?></strong>.
                        <?php else: ?>
                            Get started by submitting your first internship application.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <?php if (!$currentEnrollment && !$latestApp): ?>
                        <a href="<?= baseUrl('apply.php') ?>" class="btn btn-gold btn-sm rounded-pill px-4">
                            <i class="bi bi-send-fill me-1"></i>Apply for Internship
                        </a>
                    <?php elseif ($currentEnrollment && (int)$currentEnrollment['completion_approved'] === 1): ?>
                        <a href="<?= baseUrl('intern/certificates.php') ?>" class="btn btn-gold btn-sm rounded-pill px-4">
                            <i class="bi bi-award-fill me-1"></i>View Certificate
                        </a>
                    <?php else: ?>
                        <a href="<?= baseUrl('intern/tasks.php') ?>" class="btn btn-gold btn-sm rounded-pill px-4">
                            <i class="bi bi-list-task me-1"></i>View Tasks
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════ ANNOUNCEMENTS TICKER ═══════════════════ -->
<?php if (!empty($announcements)): ?>
    <div class="row mb-4">
        <?php foreach ($announcements as $an): ?>
            <div class="col-md-6 mb-2 mb-md-0">
                <div class="alert alert-info border-0 shadow-sm d-flex align-items-start gap-3 p-3 mb-0 rounded-3">
                    <i class="bi bi-megaphone-fill fs-4 text-primary"></i>
                    <div>
                        <div class="fw-bold text-dark small mb-1"><?= e($an['title']) ?></div>
                        <div class="small text-secondary"><?= e(truncate($an['content'], 110)) ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ═══════════════════ METRIC CARDS ═══════════════════ -->
<div class="row g-3 mb-4">
    <!-- Internship Track -->
    <div class="col-xl-3 col-md-6">
        <div class="metric-card">
            <div class="metric-info">
                <div class="metric-label">Internship Program</div>
                <div class="fs-5 fw-bold text-dark text-truncate" style="max-width: 170px;">
                    <?= e($currentEnrollment['program_title'] ?? ($latestApp['program_title'] ?? 'None')) ?>
                </div>
                <div class="metric-meta text-muted small">
                    Status: <?= statusBadge($currentEnrollment['internship_status'] ?? ($latestApp['application_status'] ?? 'No Application')) ?>
                </div>
            </div>
            <div class="metric-icon royal">
                <i class="bi bi-briefcase"></i>
            </div>
        </div>
    </div>

    <!-- Overall Progress -->
    <div class="col-xl-3 col-md-6">
        <div class="metric-card">
            <div class="metric-info">
                <div class="metric-label">Completion Progress</div>
                <div class="metric-value text-primary"><?= $progressPercentage ?>%</div>
                <div class="metric-meta">
                    <div class="progress-bar-custom w-100" style="height: 6px;">
                        <div class="progress-fill <?= ($progressPercentage >= 100) ? 'success' : '' ?>" style="width: <?= min(100, $progressPercentage) ?>%;"></div>
                    </div>
                </div>
            </div>
            <div class="metric-icon green">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
        </div>
    </div>

    <!-- Assigned Tasks -->
    <div class="col-xl-3 col-md-6">
        <div class="metric-card">
            <div class="metric-info">
                <div class="metric-label">Tasks Overview</div>
                <div class="metric-value text-dark"><?= $completedTasks ?> / <?= $totalTasks ?></div>
                <div class="metric-meta text-muted">
                    <span class="text-warning fw-semibold"><?= $pendingTasks ?> pending</span>
                </div>
            </div>
            <div class="metric-icon orange">
                <i class="bi bi-list-check"></i>
            </div>
        </div>
    </div>

    <!-- Certificate Status -->
    <div class="col-xl-3 col-md-6">
        <div class="metric-card">
            <div class="metric-info">
                <div class="metric-label">Official Certificate</div>
                <div class="fs-6 fw-bold text-dark">
                    <?= !empty($currentEnrollment['certificate_id']) ? e($currentEnrollment['certificate_id']) : 'Pending Approval' ?>
                </div>
                <div class="metric-meta">
                    <?= statusBadge($currentEnrollment['certificate_status'] ?? 'Not Eligible') ?>
                </div>
            </div>
            <div class="metric-icon gold">
                <i class="bi bi-award-fill"></i>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════ RECENT TASKS & MILESTONES ═══════════════════ -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="panel-card">
            <div class="panel-header">
                <h5 class="panel-title">
                    <i class="bi bi-list-task text-primary"></i> Current Assigned Tasks
                </h5>
                <a href="<?= baseUrl('intern/tasks.php') ?>" class="small text-decoration-none">View All Tasks</a>
            </div>

            <div class="panel-body p-0">
                <?php if (empty($recentTasks)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-clipboard fs-1 d-block mb-2 text-secondary"></i>
                        <h6 class="fw-bold">No Tasks Currently Assigned</h6>
                        <p class="small mb-0">Your mentor will assign practical milestones once your program commences.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Task Title</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTasks as $task): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= e($task['title']) ?></div>
                                            <small class="text-muted"><?= e(truncate($task['description'] ?? '', 70)) ?></small>
                                        </td>
                                        <td>
                                            <?php if (!empty($task['deadline'])): ?>
                                                <small class="fw-semibold text-dark"><?= formatDate($task['deadline']) ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Flexible</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($task['submission_status'])): ?>
                                                <?= statusBadge($task['submission_status']) ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary">Not Submitted</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= baseUrl('intern/tasks.php?id=' . (int)$task['id']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                                <i class="bi bi-send me-1"></i>Submit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Shortcuts / Program Summary -->
    <div class="col-lg-4">
        <div class="panel-card">
            <div class="panel-header">
                <h5 class="panel-title"><i class="bi bi-bookmark-star-fill text-gold"></i> Program Details</h5>
            </div>
            <div class="panel-body">
                <?php if ($currentEnrollment): ?>
                    <div class="mb-3">
                        <small class="text-muted text-uppercase fw-semibold d-block">Internship Domain</small>
                        <div class="fw-bold text-primary fs-6"><?= e($currentEnrollment['program_title']) ?></div>
                    </div>

                    <div class="row g-2 mb-3 small">
                        <div class="col-6">
                            <span class="text-muted d-block">Start Date:</span>
                            <strong><?= formatDate($currentEnrollment['start_date']) ?></strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block">End Date:</span>
                            <strong><?= formatDate($currentEnrollment['end_date']) ?></strong>
                        </div>
                    </div>

                    <div class="mb-4">
                        <small class="text-muted text-uppercase fw-semibold d-block mb-1">Performance Rating</small>
                        <?php if (!empty($currentEnrollment['performance_rating'])): ?>
                            <span class="badge bg-gold text-dark fs-6 px-3 py-1">
                                <i class="bi bi-star-fill text-warning me-1"></i><?= $currentEnrollment['performance_rating'] ?> / 5.0
                            </span>
                        <?php else: ?>
                            <small class="text-muted">In Progress (Evaluated upon completion)</small>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="<?= baseUrl('intern/documents.php') ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-file-earmark-text me-1"></i>View Offer Letter
                        </a>
                        <?php if ((int)$currentEnrollment['completion_approved'] === 1): ?>
                            <a href="<?= baseUrl('intern/certificates.php') ?>" class="btn btn-gold btn-sm">
                                <i class="bi bi-patch-check-fill me-1"></i>Download Certificate
                            </a>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <p class="text-muted small mb-3">
                        You are not currently enrolled in an active internship. Browse our open programs and apply today.
                    </p>
                    <a href="<?= baseUrl('apply.php') ?>" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-send me-1"></i>Submit Application
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
