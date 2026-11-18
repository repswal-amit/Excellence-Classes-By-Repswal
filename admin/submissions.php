<?php
/**
 * Review Task Submissions
 * Excellence Classes By Repswal
 */

$adminPageTitle = 'Task Submissions';
$currentAdminPage = 'submissions';

require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Handle Review / Grading Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_submission'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid security token.');
        redirect(baseUrl('admin/submissions.php'));
    }

    $subId = (int)($_POST['submission_id'] ?? 0);
    $status = $_POST['status'] ?? 'Pending';
    $feedback = trim($_POST['admin_feedback'] ?? '');

    if ($subId <= 0 || !in_array($status, ['Pending', 'Approved', 'Rejected', 'Resubmit'])) {
        setFlash('danger', 'Invalid submission ID or status.');
        redirect(baseUrl('admin/submissions.php'));
    }

    try {
        $pdo->beginTransaction();

        // Fetch submission with task and enrollment
        $subStmt = $pdo->prepare("
            SELECT s.*, t.enrollment_id 
            FROM task_submissions s
            JOIN tasks t ON s.task_id = t.id
            WHERE s.id = ?
        ");
        $subStmt->execute([$subId]);
        $sub = $subStmt->fetch();

        if (!$sub) {
            $pdo->rollBack();
            setFlash('danger', 'Submission not found.');
            redirect(baseUrl('admin/submissions.php'));
        }

        $oldStatus = $sub['submission_status'];

        // Update submission
        $upd = $pdo->prepare("
            UPDATE task_submissions 
            SET submission_status = ?, admin_feedback = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ");
        $upd->execute([$status, $feedback, $currentAdmin['id'], $subId]);

        // Recalculate progress for this enrollment
        $enrollmentId = (int)$sub['enrollment_id'];

        // Count approved tasks for this enrollment
        $compStmt = $pdo->prepare("
            SELECT COUNT(DISTINCT s.task_id) 
            FROM task_submissions s
            JOIN tasks t ON s.task_id = t.id
            WHERE t.enrollment_id = ? AND s.submission_status = 'Approved'
        ");
        $compStmt->execute([$enrollmentId]);
        $completedCount = (int)$compStmt->fetchColumn();

        // Total tasks for this enrollment
        $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE enrollment_id = ?");
        $totalStmt->execute([$enrollmentId]);
        $totalTasks = (int)$totalStmt->fetchColumn();

        $percentage = ($totalTasks > 0) ? round(($completedCount / $totalTasks) * 100, 2) : 0.00;

        // Update progress table
        $progUpd = $pdo->prepare("
            UPDATE internship_progress 
            SET total_tasks = ?, completed_tasks = ?, progress_percentage = ?, updated_by = ?, updated_at = NOW()
            WHERE enrollment_id = ?
        ");
        $progUpd->execute([$totalTasks, $completedCount, $percentage, $currentAdmin['id'], $enrollmentId]);

        // If progress is active, ensure enrollment is marked In Progress
        if ($percentage > 0) {
            $pdo->prepare("UPDATE internship_enrollments SET internship_status = 'In Progress' WHERE id = ? AND internship_status = 'Not Started'")->execute([$enrollmentId]);
        }

        $pdo->commit();

        logAdminAction($pdo, $currentAdmin['id'], 'Review Task Submission', 'task_submissions', $subId, ['status' => $oldStatus], ['status' => $status]);
        setFlash('success', "Submission #{$subId} marked as '{$status}'. Progress recalculated ({$percentage}%).");
        redirect(baseUrl('admin/submissions.php'));

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setFlash('danger', 'Error updating submission: ' . $e->getMessage());
        redirect(baseUrl('admin/submissions.php'));
    }
}

// Filter parameters
$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$where = ["1=1"];
$params = [];

if (!empty($statusFilter) && in_array($statusFilter, ['Pending', 'Approved', 'Rejected', 'Resubmit'])) {
    $where[] = "s.submission_status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $where[] = "(i.full_name LIKE ? OR t.title LIKE ? OR i.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(" AND ", $where);

// Count
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM task_submissions s
    JOIN tasks t ON s.task_id = t.id
    JOIN interns i ON s.intern_id = i.id
    WHERE {$whereClause}
");
$countStmt->execute($params);
$totalSubs = (int)$countStmt->fetchColumn();

$pagination = paginate($totalSubs, $perPage, $page);

// Fetch submissions
$sql = "
    SELECT s.*, t.title AS task_title, t.description AS task_description, t.deadline,
           i.full_name AS intern_name, i.email AS intern_email,
           p.title AS program_title,
           adm.full_name AS reviewer_name
    FROM task_submissions s
    JOIN tasks t ON s.task_id = t.id
    JOIN interns i ON s.intern_id = i.id
    JOIN internship_enrollments e ON t.enrollment_id = e.id
    JOIN internship_programs p ON e.program_id = p.id
    LEFT JOIN admins adm ON s.reviewed_by = adm.id
    WHERE {$whereClause}
    ORDER BY s.submitted_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$submissions = $stmt->fetchAll();
?>

<!-- ═══════════════════ FILTER BAR ═══════════════════ -->
<div class="panel-card p-3 mb-4">
    <form method="GET" action="<?= baseUrl('admin/submissions.php') ?>" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Search by intern, task, email..." value="<?= e($search) ?>">
            </div>
        </div>

        <div class="col-md-4">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All Submission Statuses</option>
                <option value="Pending" <?= ($statusFilter === 'Pending') ? 'selected' : '' ?>>Pending Review</option>
                <option value="Approved" <?= ($statusFilter === 'Approved') ? 'selected' : '' ?>>Approved</option>
                <option value="Rejected" <?= ($statusFilter === 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                <option value="Resubmit" <?= ($statusFilter === 'Resubmit') ? 'selected' : '' ?>>Needs Resubmission</option>
            </select>
        </div>

        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Filter</button>
            <?php if (!empty($search) || !empty($statusFilter)): ?>
                <a href="<?= baseUrl('admin/submissions.php') ?>" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ═══════════════════ SUBMISSIONS TABLE ═══════════════════ -->
<div class="panel-card">
    <div class="panel-header">
        <h5 class="panel-title">
            <i class="bi bi-clipboard2-check-fill text-primary"></i>
            Intern Task Submissions (<?= number_format($totalSubs) ?>)
        </h5>
        <div class="text-muted small">
            Review code repositories, provide mentor feedback, and update milestone progress
        </div>
    </div>

    <div class="panel-body p-0">
        <?php if (empty($submissions)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-bold">No Submissions Found</h5>
                <p class="small mb-0">No records match your selected filter criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Intern</th>
                            <th>Task & Program</th>
                            <th>Submitted On</th>
                            <th>Deliverables</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $sub): ?>
                            <tr>
                                <td class="fw-bold">#<?= (int)$sub['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($sub['intern_name']) ?></div>
                                    <small class="text-muted"><?= e($sub['intern_email']) ?></small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($sub['task_title']) ?></div>
                                    <small class="badge bg-light text-primary border"><?= e($sub['program_title']) ?></small>
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
                                    <?php endif; ?>
                                    <?php if (!empty($sub['submission_text'])): ?>
                                        <span class="badge bg-light text-secondary border small">Notes</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= statusBadge($sub['submission_status']) ?>
                                    <?php if (!empty($sub['reviewer_name'])): ?>
                                        <div class="text-muted" style="font-size: 0.7rem;">by <?= e($sub['reviewer_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#reviewSubModal<?= (int)$sub['id'] ?>">
                                        <i class="bi bi-pencil-square me-1"></i>Evaluate
                                    </button>
                                </td>
                            </tr>

                            <!-- Evaluation Modal -->
                            <div class="modal fade" id="reviewSubModal<?= (int)$sub['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content border-0 shadow-lg">
                                        <form method="POST" action="<?= baseUrl('admin/submissions.php') ?>">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="submission_id" value="<?= (int)$sub['id'] ?>">

                                            <div class="modal-header bg-navy text-white">
                                                <h5 class="modal-title fw-bold">Evaluate Submission #<?= (int)$sub['id'] ?> — <?= e($sub['intern_name']) ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body p-4">
                                                <!-- Task Requirement Preview -->
                                                <div class="p-3 bg-light rounded-3 mb-3">
                                                    <span class="text-muted small text-uppercase fw-semibold d-block">Task Title</span>
                                                    <h6 class="fw-bold text-dark mb-1"><?= e($sub['task_title']) ?></h6>
                                                    <p class="small text-secondary mb-0"><?= nl2br(e($sub['task_description'] ?? '')) ?></p>
                                                </div>

                                                <!-- Deliverable Details -->
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-dark small">Repository / Project Link:</label>
                                                    <?php if (!empty($sub['project_link'])): ?>
                                                        <div>
                                                            <a href="<?= e($sub['project_link']) ?>" target="_blank" class="fw-bold text-primary text-break">
                                                                <i class="bi bi-link-45deg me-1"></i><?= e($sub['project_link']) ?>
                                                            </a>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="text-muted small">No external repository link provided.</div>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if (!empty($sub['submission_text'])): ?>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold text-dark small">Intern's Submission Notes:</label>
                                                        <div class="p-3 bg-white border rounded text-secondary small leading-relaxed">
                                                            <?= nl2br(e($sub['submission_text'])) ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <hr>

                                                <!-- Status Selection -->
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-dark">Evaluation Decision <span class="text-danger">*</span></label>
                                                    <select name="status" class="form-select form-select-lg" required>
                                                        <option value="Approved" <?= ($sub['submission_status'] === 'Approved') ? 'selected' : '' ?>>Approved (Milestone Completed & Progress Updated)</option>
                                                        <option value="Resubmit" <?= ($sub['submission_status'] === 'Resubmit') ? 'selected' : '' ?>>Needs Resubmission (Request Revisions)</option>
                                                        <option value="Rejected" <?= ($sub['submission_status'] === 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                                                        <option value="Pending" <?= ($sub['submission_status'] === 'Pending') ? 'selected' : '' ?>>Pending Review</option>
                                                    </select>
                                                </div>

                                                <!-- Feedback -->
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-dark small">Mentor Feedback / Code Review Comments</label>
                                                    <textarea name="admin_feedback" rows="3" class="form-control" placeholder="Provide constructive remarks, code improvements, or notes for the intern..."><?= e($sub['admin_feedback'] ?? '') ?></textarea>
                                                </div>
                                            </div>

                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="review_submission" class="btn btn-primary fw-bold">
                                                    <i class="bi bi-check2-circle me-1"></i>Save Evaluation
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-3 border-top">
                <?= renderPagination($pagination, baseUrl('admin/submissions.php?status=' . urlencode($statusFilter) . '&search=' . urlencode($search) . '&')) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
