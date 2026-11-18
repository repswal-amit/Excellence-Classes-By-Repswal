<?php
/**
 * Manage Internship Applications
 * Excellence Classes By Repswal
 */

$adminPageTitle = 'Internship Applications';
$currentAdminPage = 'applications';

require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();
$errors = [];

// Handle Application Approval / Rejection / Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_application_status'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid security token.');
        redirect(baseUrl('admin/applications.php'));
    }

    $appId = (int)($_POST['application_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $adminRemarks = trim($_POST['admin_remarks'] ?? '');
    $startDate = trim($_POST['start_date'] ?? date('Y-m-d'));
    $endDate = trim($_POST['end_date'] ?? date('Y-m-d', strtotime('+4 weeks')));

    if ($appId <= 0 || !in_array($newStatus, ['Pending', 'Approved', 'Rejected', 'Cancelled'])) {
        setFlash('danger', 'Invalid application ID or status.');
        redirect(baseUrl('admin/applications.php'));
    }

    try {
        $pdo->beginTransaction();

        // Fetch application details
        $appStmt = $pdo->prepare("SELECT * FROM internship_applications WHERE id = ?");
        $appStmt->execute([$appId]);
        $app = $appStmt->fetch();

        if (!$app) {
            $pdo->rollBack();
            setFlash('danger', 'Application not found.');
            redirect(baseUrl('admin/applications.php'));
        }

        // Update application
        $updateApp = $pdo->prepare("
            UPDATE internship_applications 
            SET application_status = ?, admin_remarks = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ");
        $updateApp->execute([$newStatus, $adminRemarks, $currentAdmin['id'], $appId]);

        // If Approved, create or update internship_enrollments
        if ($newStatus === 'Approved') {
            $checkEnroll = $pdo->prepare("SELECT id FROM internship_enrollments WHERE application_id = ?");
            $checkEnroll->execute([$appId]);
            $enrollment = $checkEnroll->fetch();

            $enrollmentId = 0;
            if (!$enrollment) {
                $createEnroll = $pdo->prepare("
                    INSERT INTO internship_enrollments (application_id, intern_id, program_id, start_date, end_date, internship_status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'Not Started', NOW())
                ");
                $createEnroll->execute([$appId, $app['intern_id'], $app['program_id'], $startDate, $endDate]);
                $enrollmentId = (int)$pdo->lastInsertId();

                // Initialize internship_progress record
                $initProgress = $pdo->prepare("
                    INSERT INTO internship_progress (enrollment_id, total_tasks, completed_tasks, progress_percentage, created_at)
                    VALUES (?, 0, 0, 0.00, NOW())
                ");
                $initProgress->execute([$enrollmentId]);

                // Create initial Offer Letter document record
                $docId = generateDocumentId();
                $createDoc = $pdo->prepare("
                    INSERT INTO documents (document_id, enrollment_id, intern_id, document_type, issued_date, status, issued_by, created_at)
                    VALUES (?, ?, ?, 'Offer Letter', CURDATE(), 'Draft', ?, NOW())
                ");
                $createDoc->execute([$docId, $enrollmentId, $app['intern_id'], $currentAdmin['id']]);

            } else {
                $enrollmentId = (int)$enrollment['id'];
                $updateEnroll = $pdo->prepare("UPDATE internship_enrollments SET start_date = ?, end_date = ? WHERE id = ?");
                $updateEnroll->execute([$startDate, $endDate, $enrollmentId]);
            }
        }

        $pdo->commit();

        logAdminAction($pdo, $currentAdmin['id'], 'Update Application Status', 'internship_applications', $appId, ['status' => $app['application_status']], ['status' => $newStatus]);

        setFlash('success', "Application #{$appId} has been updated to {$newStatus}.");
        redirect(baseUrl('admin/applications.php'));

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlash('danger', 'Database error: ' . $e->getMessage());
        redirect(baseUrl('admin/applications.php'));
    }
}

// Filtering & Pagination
$statusFilter = trim($_GET['status'] ?? '');
$programFilter = (int)($_GET['program_id'] ?? 0);
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$where = ["1=1"];
$params = [];

if (!empty($statusFilter) && in_array($statusFilter, ['Pending', 'Approved', 'Rejected', 'Cancelled'])) {
    $where[] = "a.application_status = ?";
    $params[] = $statusFilter;
}

if ($programFilter > 0) {
    $where[] = "a.program_id = ?";
    $params[] = $programFilter;
}

if (!empty($search)) {
    $where[] = "(i.full_name LIKE ? OR i.email LIKE ? OR i.college_name LIKE ? OR i.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(" AND ", $where);

// Total items
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM internship_applications a
    JOIN interns i ON a.intern_id = i.id
    JOIN internship_programs p ON a.program_id = p.id
    WHERE {$whereClause}
");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();

$pagination = paginate($totalItems, $perPage, $page);

// Fetch Applications
$sql = "
    SELECT a.*, i.full_name, i.email, i.phone, i.college_name, i.course_degree, i.current_year,
           p.title AS program_title, p.duration AS program_duration,
           adm.full_name AS reviewer_name,
           e.id AS enrollment_id, e.internship_status, e.start_date, e.end_date
    FROM internship_applications a
    JOIN interns i ON a.intern_id = i.id
    JOIN internship_programs p ON a.program_id = p.id
    LEFT JOIN admins adm ON a.reviewed_by = adm.id
    LEFT JOIN internship_enrollments e ON a.id = e.application_id
    WHERE {$whereClause}
    ORDER BY a.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

// Programs for filter dropdown
$programsList = $pdo->query("SELECT id, title FROM internship_programs ORDER BY title ASC")->fetchAll();
?>

<!-- ═══════════════════ FILTER BAR ═══════════════════ -->
<div class="panel-card p-3 mb-4">
    <form method="GET" action="<?= baseUrl('admin/applications.php') ?>" class="row g-2 align-items-center">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Search candidate, email, college..." value="<?= e($search) ?>">
            </div>
        </div>

        <div class="col-md-3">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All Application Statuses</option>
                <option value="Pending" <?= ($statusFilter === 'Pending') ? 'selected' : '' ?>>Pending (Review Required)</option>
                <option value="Approved" <?= ($statusFilter === 'Approved') ? 'selected' : '' ?>>Approved</option>
                <option value="Rejected" <?= ($statusFilter === 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                <option value="Cancelled" <?= ($statusFilter === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
            </select>
        </div>

        <div class="col-md-3">
            <select name="program_id" class="form-select" onchange="this.form.submit()">
                <option value="0">All Programs</option>
                <?php foreach ($programsList as $pl): ?>
                    <option value="<?= (int)$pl['id'] ?>" <?= ($programFilter === (int)$pl['id']) ? 'selected' : '' ?>>
                        <?= e($pl['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Filter</button>
            <?php if (!empty($search) || !empty($statusFilter) || $programFilter > 0): ?>
                <a href="<?= baseUrl('admin/applications.php') ?>" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ═══════════════════ APPLICATIONS TABLE ═══════════════════ -->
<div class="panel-card">
    <div class="panel-header">
        <h5 class="panel-title">
            <i class="bi bi-file-earmark-person-fill text-primary"></i>
            Applications List (<?= number_format($totalItems) ?>)
        </h5>
        <div class="text-muted small">
            Proprietor review and official enrollment controls
        </div>
    </div>

    <div class="panel-body p-0">
        <?php if (empty($applications)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-bold">No Applications Found</h5>
                <p class="small mb-0">Try clearing your filters or check back when new applications arrive.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Candidate Details</th>
                            <th>Academic Info</th>
                            <th>Target Program</th>
                            <th>Applied Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td class="fw-bold">#<?= (int)$app['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($app['full_name']) ?></div>
                                    <div class="small text-muted"><i class="bi bi-envelope me-1"></i><?= e($app['email']) ?></div>
                                    <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= e($app['phone']) ?></div>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= e($app['college_name']) ?></div>
                                    <div class="small text-muted"><?= e($app['course_degree']) ?> (<?= e($app['current_year']) ?>)</div>
                                </td>
                                <td>
                                    <div class="small fw-bold text-primary"><?= e($app['program_title']) ?></div>
                                    <div class="small text-muted"><?= e($app['program_duration']) ?></div>
                                </td>
                                <td>
                                    <div class="small"><?= formatDate($app['created_at']) ?></div>
                                    <small class="text-muted"><?= date('h:i A', strtotime($app['created_at'])) ?></small>
                                </td>
                                <td>
                                    <?= statusBadge($app['application_status']) ?>
                                    <?php if (!empty($app['reviewer_name'])): ?>
                                        <div class="text-muted" style="font-size: 0.7rem;">by <?= e($app['reviewer_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#reviewModal<?= (int)$app['id'] ?>">
                                        <i class="bi bi-sliders me-1"></i>Review
                                    </button>
                                </td>
                            </tr>

                            <!-- Review & Status Modal -->
                            <div class="modal fade" id="reviewModal<?= (int)$app['id'] ?>" tabindex="-1" aria-labelledby="reviewLabel<?= (int)$app['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content border-0 shadow-lg">
                                        <form method="POST" action="<?= baseUrl('admin/applications.php') ?>">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="application_id" value="<?= (int)$app['id'] ?>">

                                            <div class="modal-header bg-navy text-white">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="bi bi-file-earmark-person-fill text-gold fs-4"></i>
                                                    <h5 class="modal-title fw-bold" id="reviewLabel<?= (int)$app['id'] ?>">
                                                        Review Application #<?= (int)$app['id'] ?> — <?= e($app['full_name']) ?>
                                                    </h5>
                                                </div>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>

                                            <div class="modal-body p-4">
                                                <!-- Candidate Summary Grid -->
                                                <div class="row g-3 p-3 bg-light rounded-3 mb-4 small">
                                                    <div class="col-md-6">
                                                        <span class="text-muted d-block">Candidate:</span>
                                                        <strong><?= e($app['full_name']) ?></strong> (<?= e($app['email']) ?>)
                                                    </div>
                                                    <div class="col-md-6">
                                                        <span class="text-muted d-block">Phone:</span>
                                                        <strong><?= e($app['phone']) ?></strong>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <span class="text-muted d-block">College / University:</span>
                                                        <strong><?= e($app['college_name']) ?></strong>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <span class="text-muted d-block">Course & Year:</span>
                                                        <strong><?= e($app['course_degree']) ?></strong> (<?= e($app['current_year']) ?>)
                                                    </div>
                                                    <div class="col-12 border-top pt-2">
                                                        <span class="text-muted d-block">Program Applied For:</span>
                                                        <strong class="text-primary"><?= e($app['program_title']) ?></strong> (<?= e($app['program_duration']) ?>)
                                                    </div>
                                                </div>

                                                <?php if (!empty($app['cover_letter'])): ?>
                                                    <div class="mb-4">
                                                        <label class="form-label fw-bold text-dark small">Candidate's Motivation / Cover Letter:</label>
                                                        <div class="p-3 bg-white border rounded text-secondary small leading-relaxed">
                                                            <?= nl2br(e($app['cover_letter'])) ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <hr>

                                                <!-- Status Selection -->
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-dark">Decision Status <span class="text-danger">*</span></label>
                                                    <select name="status" class="form-select form-select-lg" required>
                                                        <option value="Pending" <?= ($app['application_status'] === 'Pending') ? 'selected' : '' ?>>Pending (Under Evaluation)</option>
                                                        <option value="Approved" <?= ($app['application_status'] === 'Approved') ? 'selected' : '' ?>>Approved (Enroll intern & generate Offer Letter)</option>
                                                        <option value="Rejected" <?= ($app['application_status'] === 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                                                        <option value="Cancelled" <?= ($app['application_status'] === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                                    </select>
                                                </div>

                                                <!-- Dates if Approved -->
                                                <div class="row g-3 mb-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold">Internship Start Date</label>
                                                        <input type="date" name="start_date" class="form-control" value="<?= e($app['start_date'] ?? date('Y-m-d')) ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold">Internship End Date</label>
                                                        <input type="date" name="end_date" class="form-control" value="<?= e($app['end_date'] ?? date('Y-m-d', strtotime('+4 weeks'))) ?>">
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Administrative Remarks / Internal Notes</label>
                                                    <textarea name="admin_remarks" rows="3" class="form-control" placeholder="Optional notes for record or feedback..."><?= e($app['admin_remarks'] ?? '') ?></textarea>
                                                </div>
                                            </div>

                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_application_status" class="btn btn-primary fw-bold">
                                                    <i class="bi bi-check2-circle me-1"></i>Save Application Decision
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
                <?= renderPagination($pagination, baseUrl('admin/applications.php?status=' . urlencode($statusFilter) . '&program_id=' . $programFilter . '&search=' . urlencode($search) . '&')) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
