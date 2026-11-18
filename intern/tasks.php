<?php
/**
 * Intern Tasks & Submission Portal
 * Excellence Classes By Repswal
 */

$internPageTitle = 'My Assigned Tasks';
$currentInternPage = 'tasks';

require_once __DIR__ . '/includes/header.php';

// Handle Task Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_task'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid security token.');
        redirect(baseUrl('intern/tasks.php'));
    }

    $taskId = (int)($_POST['task_id'] ?? 0);
    $projectLink = trim($_POST['project_link'] ?? '');
    $submissionText = trim($_POST['submission_text'] ?? '');

    if ($taskId <= 0) {
        setFlash('danger', 'Invalid task selected.');
        redirect(baseUrl('intern/tasks.php'));
    }

    if (empty($projectLink) && empty($submissionText)) {
        setFlash('danger', 'Please provide either a project repository link or submission notes.');
        redirect(baseUrl('intern/tasks.php'));
    }

    try {
        // Verify task belongs to this intern's enrollment
        $checkTask = $pdo->prepare("
            SELECT t.id 
            FROM tasks t
            JOIN internship_enrollments e ON t.enrollment_id = e.id
            WHERE t.id = ? AND e.intern_id = ?
        ");
        $checkTask->execute([$taskId, $internId]);
        if (!$checkTask->fetch()) {
            setFlash('danger', 'Unauthorized: Task not found in your enrollment.');
            redirect(baseUrl('intern/tasks.php'));
        }

        // Insert or update submission
        $subStmt = $pdo->prepare("
            INSERT INTO task_submissions (task_id, intern_id, project_link, submission_text, submission_status, submitted_at, updated_at)
            VALUES (?, ?, ?, ?, 'Pending', NOW(), NOW())
            ON DUPLICATE KEY UPDATE project_link = VALUES(project_link), submission_text = VALUES(submission_text), submission_status = 'Pending', submitted_at = NOW(), updated_at = NOW()
        ");
        $subStmt->execute([$taskId, $internId, $projectLink, $submissionText]);

        setFlash('success', 'Your task submission has been received and is currently under mentor review.');
        redirect(baseUrl('intern/tasks.php'));

    } catch (PDOException $e) {
        setFlash('danger', 'Error submitting task: ' . $e->getMessage());
        redirect(baseUrl('intern/tasks.php'));
    }
}

// Fetch all tasks for this intern
try {
    $stmt = $pdo->prepare("
        SELECT t.*, p.title AS program_title,
               s.id AS submission_id, s.project_link, s.submission_text, s.submission_status, s.admin_feedback, s.submitted_at
        FROM tasks t
        JOIN internship_enrollments e ON t.enrollment_id = e.id
        JOIN internship_programs p ON e.program_id = p.id
        LEFT JOIN task_submissions s ON t.id = s.task_id AND s.intern_id = ?
        WHERE e.intern_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$internId, $internId]);
    $tasks = $stmt->fetchAll();
} catch (PDOException $e) {
    $tasks = [];
}
?>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Assigned Tasks & Project Deliverables</h4>
        <p class="text-muted small mb-0">Review task specifications, submit GitHub links or deliverables, and check mentor evaluations.</p>
    </div>
</div>

<!-- ═══════════════════ TASKS CARDS ═══════════════════ -->
<div class="row g-4">
    <?php if (empty($tasks)): ?>
        <div class="col-12">
            <div class="panel-card p-5 text-center text-muted">
                <i class="bi bi-clipboard fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-bold">No Tasks Currently Assigned</h5>
                <p class="small mb-0">Your mentor will assign tasks as you progress through your curriculum.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($tasks as $t): ?>
            <?php
                $isApproved = ($t['submission_status'] === 'Approved');
                $isPending = ($t['submission_status'] === 'Pending');
                $isResubmit = ($t['submission_status'] === 'Resubmit');
            ?>
            <div class="col-lg-6">
                <div class="task-item-card h-100 d-flex flex-column <?= $isApproved ? 'completed' : '' ?>">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-light text-primary border small"><?= e($t['program_title']) ?></span>
                        <div>
                            <?php if (!empty($t['submission_status'])): ?>
                                <?= statusBadge($t['submission_status']) ?>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary">Not Submitted</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h5 class="fw-bold text-dark mb-2"><?= e($t['title']) ?></h5>
                    <p class="text-secondary small leading-relaxed flex-grow-1">
                        <?= nl2br(e($t['description'] ?? 'No description provided.')) ?>
                    </p>

                    <?php if (!empty($t['reference_materials'])): ?>
                        <div class="mb-3 small">
                            <span class="text-muted d-block fw-semibold">Reference Materials / Starter:</span>
                            <a href="<?= e($t['reference_materials']) ?>" target="_blank" class="text-primary text-break">
                                <i class="bi bi-box-arrow-up-right me-1"></i><?= e($t['reference_materials']) ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($t['admin_feedback'])): ?>
                        <div class="p-3 bg-light rounded-3 mb-3 border-start border-3 <?= $isApproved ? 'border-success' : 'border-warning' ?>">
                            <div class="small fw-bold text-dark mb-1"><i class="bi bi-chat-left-dots me-1"></i>Mentor Feedback:</div>
                            <div class="small text-secondary"><?= nl2br(e($t['admin_feedback'])) ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                        <div class="small text-muted">
                            <?php if (!empty($t['deadline'])): ?>
                                <i class="bi bi-calendar-event me-1"></i>Deadline: <strong><?= formatDate($t['deadline']) ?></strong>
                            <?php else: ?>
                                <i class="bi bi-clock me-1"></i>Flexible Deadline
                            <?php endif; ?>
                        </div>

                        <?php if ($isApproved): ?>
                            <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#submitModal<?= (int)$t['id'] ?>">
                                <i class="bi bi-check-circle me-1"></i>Approved (View)
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#submitModal<?= (int)$t['id'] ?>">
                                <i class="bi bi-send me-1"></i><?= !empty($t['submission_id']) ? 'Update Submission' : 'Submit Task' ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Submit Task Modal -->
            <div class="modal fade" id="submitModal<?= (int)$t['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow-lg">
                        <form method="POST" action="<?= baseUrl('intern/tasks.php') ?>">
                            <?= csrfField() ?>
                            <input type="hidden" name="submit_task" value="1">
                            <input type="hidden" name="task_id" value="<?= (int)$t['id'] ?>">

                            <div class="modal-header bg-navy text-white">
                                <h5 class="modal-title fw-bold">Submit Deliverables — <?= e($t['title']) ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body p-4">
                                <div class="p-3 bg-light rounded-3 mb-3 small">
                                    <strong>Task Instructions:</strong>
                                    <div class="text-secondary mt-1"><?= nl2br(e($t['description'] ?? '')) ?></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">GitHub Repository / Live Hosted Demo Link</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-github"></i></span>
                                        <input type="url" name="project_link" class="form-control" value="<?= e($t['project_link'] ?? '') ?>" placeholder="https://github.com/your-username/your-repo">
                                    </div>
                                    <div class="form-text small">Ensure the repository is public or accessible for review.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Submission Notes & Implementation Details</label>
                                    <textarea name="submission_text" rows="4" class="form-control" placeholder="Describe what you built, libraries utilized, challenges resolved, or steps to run..."><?= e($t['submission_text'] ?? '') ?></textarea>
                                </div>

                                <?php if (!empty($t['admin_feedback'])): ?>
                                    <div class="alert alert-warning small mb-0">
                                        <strong>Previous Feedback:</strong> <?= e($t['admin_feedback']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <?php if (!$isApproved): ?>
                                    <button type="submit" class="btn btn-primary fw-bold">
                                        <i class="bi bi-send-fill me-1"></i>Send for Mentor Review
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
