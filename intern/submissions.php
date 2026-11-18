<?php
/**
 * Intern Task Submissions & Feedback History
 * Excellence Classes By Repswal
 */

$internPageTitle = 'Submissions & Feedback';
$currentInternPage = 'submissions';

require_once __DIR__ . '/includes/header.php';

// Fetch all submissions by this intern
try {
    $stmt = $pdo->prepare("
        SELECT s.*, t.title AS task_title, t.description AS task_description, t.deadline,
               p.title AS program_title,
               adm.full_name AS reviewer_name
        FROM task_submissions s
        JOIN tasks t ON s.task_id = t.id
        JOIN internship_enrollments e ON t.enrollment_id = e.id
        JOIN internship_programs p ON e.program_id = p.id
        LEFT JOIN admins adm ON s.reviewed_by = adm.id
        WHERE s.intern_id = ?
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$internId]);
    $submissions = $stmt->fetchAll();
} catch (PDOException $e) {
    $submissions = [];
}
?>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Submissions & Mentor Feedback</h4>
        <p class="text-muted small mb-0">Complete record of your project submissions, evaluation outcomes, and mentor remarks.</p>
    </div>
    <a href="<?= baseUrl('intern/tasks.php') ?>" class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-list-task me-1"></i>View Tasks
    </a>
</div>

<!-- ═══════════════════ SUBMISSIONS TABLE ═══════════════════ -->
<div class="panel-card">
    <div class="panel-body p-0">
        <?php if (empty($submissions)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-clipboard2-check fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-bold">No Submissions Recorded</h5>
                <p class="small mb-3">You have not submitted deliverables for any tasks yet.</p>
                <a href="<?= baseUrl('intern/tasks.php') ?>" class="btn btn-primary rounded-pill px-4">Browse Tasks</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Task</th>
                            <th>Program</th>
                            <th>Submitted On</th>
                            <th>Deliverables</th>
                            <th>Status</th>
                            <th>Mentor Feedback</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $sub): ?>
                            <tr>
                                <td class="fw-bold">#<?= (int)$sub['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($sub['task_title']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border"><?= e($sub['program_title']) ?></span>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= formatDate($sub['submitted_at']) ?></div>
                                    <small class="text-muted"><?= date('h:i A', strtotime($sub['submitted_at'])) ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($sub['project_link'])): ?>
                                        <a href="<?= e($sub['project_link']) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0">
                                            <i class="bi bi-box-arrow-up-right me-1"></i>Repo
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= statusBadge($sub['submission_status']) ?>
                                </td>
                                <td>
                                    <?php if (!empty($sub['admin_feedback'])): ?>
                                        <div class="small text-secondary"><?= e(truncate($sub['admin_feedback'], 65)) ?></div>
                                        <?php if (!empty($sub['reviewer_name'])): ?>
                                            <div class="text-muted" style="font-size: 0.7rem;">by <?= e($sub['reviewer_name']) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Awaiting Review</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= baseUrl('intern/tasks.php?id=' . (int)$sub['task_id']) ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                        <i class="bi bi-eye"></i> Details
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
