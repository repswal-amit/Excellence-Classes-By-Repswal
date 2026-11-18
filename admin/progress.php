<?php
/**
 * Intern Progress & Completion Approval
 * Excellence Classes By Repswal
 */

$adminPageTitle = 'Internship Progress & Evaluation';
$currentAdminPage = 'progress';

require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Handle Completion Approval / Rating Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_evaluation'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid security token.');
        redirect(baseUrl('admin/progress.php'));
    }

    $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
    $rating = !empty($_POST['performance_rating']) ? (float)$_POST['performance_rating'] : null;
    $remarks = trim($_POST['remarks'] ?? '');
    $approveCompletion = isset($_POST['approve_completion']) ? 1 : 0;

    if ($enrollmentId <= 0) {
        setFlash('danger', 'Invalid enrollment ID.');
        redirect(baseUrl('admin/progress.php'));
    }

    try {
        $pdo->beginTransaction();

        // Update progress table
        $stmt = $pdo->prepare("
            UPDATE internship_progress 
            SET performance_rating = ?, remarks = ?, updated_by = ?, updated_at = NOW()
            WHERE enrollment_id = ?
        ");
        $stmt->execute([$rating, $remarks, $currentAdmin['id'], $enrollmentId]);

        // If Completion is Approved
        if ($approveCompletion === 1) {
            $enrUpd = $pdo->prepare("
                UPDATE internship_enrollments 
                SET internship_status = 'Completed', completion_approved = 1,
                    completion_approved_by = ?, completion_approved_at = NOW(),
                    completion_remarks = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $enrUpd->execute([$currentAdmin['id'], $remarks, $enrollmentId]);

            // Update or initialize Certificate eligibility
            $checkCert = $pdo->prepare("SELECT id FROM certificates WHERE enrollment_id = ?");
            $checkCert->execute([$enrollmentId]);
            $existingCert = $checkCert->fetch();

            if (!$existingCert) {
                // Fetch enrollment details
                $info = $pdo->prepare("
                    SELECT e.intern_id, e.program_id, e.start_date, e.end_date,
                           i.full_name, p.title AS domain_title
                    FROM internship_enrollments e
                    JOIN interns i ON e.intern_id = i.id
                    JOIN internship_programs p ON e.program_id = p.id
                    WHERE e.id = ?
                ");
                $info->execute([$enrollmentId]);
                $d = $info->fetch();

                if ($d) {
                    $certId = generateCertificateId();
                    $initCert = $pdo->prepare("
                        INSERT INTO certificates (certificate_id, enrollment_id, intern_id, program_id, intern_name, internship_domain, start_date, end_date, issue_date, certificate_status, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'Pending Approval', NOW())
                    ");
                    $initCert->execute([
                        $certId, $enrollmentId, $d['intern_id'], $d['program_id'],
                        $d['full_name'], $d['domain_title'], $d['start_date'], $d['end_date']
                    ]);
                }
            }

            logAdminAction($pdo, $currentAdmin['id'], 'Approve Internship Completion', 'internship_enrollments', $enrollmentId);
            setFlash('success', "Internship completion approved! Certificate eligibility set to 'Pending Approval'.");

        } else {
            logAdminAction($pdo, $currentAdmin['id'], 'Update Evaluation & Rating', 'internship_progress', $enrollmentId);
            setFlash('success', "Internship evaluation updated.");
        }

        $pdo->commit();
        redirect(baseUrl('admin/progress.php'));

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setFlash('danger', 'Database error: ' . $e->getMessage());
        redirect(baseUrl('admin/progress.php'));
    }
}

// Fetch all enrollments with progress details
$enrollments = $pdo->query("
    SELECT e.*, i.full_name AS intern_name, i.email AS intern_email, i.college_name,
           p.title AS program_title, p.duration AS program_duration,
           pr.total_tasks, pr.completed_tasks, pr.progress_percentage, pr.performance_rating, pr.remarks AS progress_remarks,
           c.id AS certificate_id, c.certificate_status
    FROM internship_enrollments e
    JOIN interns i ON e.intern_id = i.id
    JOIN internship_programs p ON e.program_id = p.id
    LEFT JOIN internship_progress pr ON e.id = pr.enrollment_id
    LEFT JOIN certificates c ON e.id = c.enrollment_id
    ORDER BY e.created_at DESC
")->fetchAll();
?>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Internship Progress & Completion Evaluation</h4>
        <p class="text-muted small mb-0">Track milestone percentages, record mentor ratings, and grant official completion approval for certificate issuance.</p>
    </div>
</div>

<!-- ═══════════════════ PROGRESS TABLE ═══════════════════ -->
<div class="panel-card">
    <div class="panel-header">
        <h5 class="panel-title">
            <i class="bi bi-graph-up-arrow text-primary"></i>
            Enrolled Interns Track (<?= count($enrollments) ?>)
        </h5>
    </div>

    <div class="panel-body p-0">
        <?php if (empty($enrollments)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-speedometer2 fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-bold">No Active Enrollments</h5>
                <p class="small mb-0">Approve pending applications to initiate intern enrollments and track milestones.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Intern</th>
                            <th>Program</th>
                            <th>Tenure Dates</th>
                            <th>Milestones</th>
                            <th>Progress Bar</th>
                            <th>Status</th>
                            <th>Rating</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollments as $enr): ?>
                            <?php
                                $pct = (float)($enr['progress_percentage'] ?? 0);
                                $isCompleted = ($enr['completion_approved'] == 1);
                            ?>
                            <tr>
                                <td class="fw-bold">#<?= (int)$enr['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($enr['intern_name']) ?></div>
                                    <small class="text-muted"><?= e($enr['intern_email']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border"><?= e($enr['program_title']) ?></span>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= formatDate($enr['start_date']) ?> — <?= formatDate($enr['end_date']) ?></div>
                                </td>
                                <td>
                                    <div class="small">
                                        <strong><?= (int)($enr['completed_tasks'] ?? 0) ?></strong> / <?= (int)($enr['total_tasks'] ?? 0) ?> tasks
                                    </div>
                                </td>
                                <td style="min-width: 130px;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress-bar-custom flex-grow-1" style="height: 6px;">
                                            <div class="progress-fill <?= ($pct >= 100) ? 'success' : '' ?>" style="width: <?= min(100, $pct) ?>%;"></div>
                                        </div>
                                        <span class="small fw-bold text-dark"><?= $pct ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isCompleted): ?>
                                        <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Completed</span>
                                    <?php else: ?>
                                        <?= statusBadge($enr['internship_status']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($enr['performance_rating'])): ?>
                                        <span class="badge bg-gold text-dark fw-bold">
                                            <i class="bi bi-star-fill text-warning me-1"></i><?= $enr['performance_rating'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#evalModal<?= (int)$enr['id'] ?>">
                                            <i class="bi bi-award me-1"></i>Evaluate
                                        </button>
                                        
                                        <?php if ($isCompleted): ?>
                                            <a href="<?= baseUrl('admin/certificates.php?enrollment_id=' . (int)$enr['id']) ?>" class="btn btn-sm btn-gold rounded-pill px-3" title="Certificate Issuance">
                                                <i class="bi bi-patch-check"></i> Certificate
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <!-- Evaluation Modal -->
                            <div class="modal fade" id="evalModal<?= (int)$enr['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content border-0 shadow-lg">
                                        <form method="POST" action="<?= baseUrl('admin/progress.php') ?>">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="enrollment_id" value="<?= (int)$enr['id'] ?>">

                                            <div class="modal-header bg-navy text-white">
                                                <h5 class="modal-title fw-bold">
                                                    Progress & Completion — <?= e($enr['intern_name']) ?>
                                                </h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body p-4">
                                                <div class="p-3 bg-light rounded-3 mb-4 small">
                                                    <div class="row g-2">
                                                        <div class="col-md-6"><strong>Intern:</strong> <?= e($enr['intern_name']) ?></div>
                                                        <div class="col-md-6"><strong>Program:</strong> <?= e($enr['program_title']) ?></div>
                                                        <div class="col-md-6"><strong>Start Date:</strong> <?= formatDate($enr['start_date']) ?></div>
                                                        <div class="col-md-6"><strong>End Date:</strong> <?= formatDate($enr['end_date']) ?></div>
                                                        <div class="col-md-6"><strong>Tasks Approved:</strong> <?= (int)($enr['completed_tasks'] ?? 0) ?> of <?= (int)($enr['total_tasks'] ?? 0) ?></div>
                                                        <div class="col-md-6"><strong>Completion Rate:</strong> <?= $pct ?>%</div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-dark small">Performance Rating (Scale 1.0 - 5.0)</label>
                                                    <input type="number" step="0.1" min="1.0" max="5.0" name="performance_rating" class="form-control" value="<?= e($enr['performance_rating'] ?? '4.5') ?>">
                                                </div>

                                                <div class="mb-4">
                                                    <label class="form-label fw-bold text-dark small">Completion Remarks / Testimonial</label>
                                                    <textarea name="remarks" rows="3" class="form-control" placeholder="Observations regarding coding skills, discipline, and task submissions..."><?= e($enr['progress_remarks'] ?? '') ?></textarea>
                                                </div>

                                                <!-- Final Completion Approval Toggle -->
                                                <div class="card p-3 border-2 <?= $isCompleted ? 'border-success bg-success-subtle' : 'border-primary bg-light' ?>">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="approve_completion" id="compCheck<?= (int)$enr['id'] ?>" value="1" <?= $isCompleted ? 'checked' : '' ?>>
                                                        <label class="form-check-label fw-bold text-dark" for="compCheck<?= (int)$enr['id'] ?>">
                                                            Approve Official Internship Completion
                                                        </label>
                                                    </div>
                                                    <small class="text-secondary mt-1 d-block ps-4">
                                                        Checking this marks the internship as successfully completed and authorizes the Proprietor to issue the official Certificate of Internship.
                                                    </small>
                                                </div>
                                            </div>

                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_evaluation" class="btn btn-primary fw-bold">
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
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
