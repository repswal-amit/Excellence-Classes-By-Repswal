<?php
/**
 * My Internship Applications
 * Excellence Classes By Repswal
 */

$internPageTitle = 'My Applications';
$currentInternPage = 'applications';

require_once __DIR__ . '/includes/header.php';

// Fetch all applications submitted by this intern
try {
    $stmt = $pdo->prepare("
        SELECT a.*, p.title AS program_title, p.duration AS program_duration, p.mode AS program_mode,
               e.id AS enrollment_id, e.internship_status
        FROM internship_applications a
        JOIN internship_programs p ON a.program_id = p.id
        LEFT JOIN internship_enrollments e ON a.id = e.application_id
        WHERE a.intern_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$internId]);
    $myApplications = $stmt->fetchAll();
} catch (PDOException $e) {
    $myApplications = [];
}
?>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">My Internship Applications</h4>
        <p class="text-muted small mb-0">Track application statuses, review remarks from the Proprietor, and access enrollment details.</p>
    </div>
    <a href="<?= baseUrl('apply.php') ?>" class="btn btn-primary rounded-pill px-4">
        <i class="bi bi-plus-circle me-1"></i>Apply for Another Program
    </a>
</div>

<!-- ═══════════════════ APPLICATIONS TABLE ═══════════════════ -->
<div class="panel-card">
    <div class="panel-body p-0">
        <?php if (empty($myApplications)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-bold">No Applications Found</h5>
                <p class="small mb-3">You haven't submitted any internship applications yet.</p>
                <a href="<?= baseUrl('apply.php') ?>" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-send-fill me-1"></i>Apply Now
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Target Program</th>
                            <th>Duration & Mode</th>
                            <th>Applied Date</th>
                            <th>Application Status</th>
                            <th>Feedback / Remarks</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myApplications as $app): ?>
                            <tr>
                                <td class="fw-bold">#<?= (int)$app['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($app['program_title']) ?></div>
                                </td>
                                <td>
                                    <?= e($app['program_duration']) ?> • <span class="badge bg-light text-dark border"><?= e($app['program_mode']) ?></span>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= formatDate($app['created_at']) ?></div>
                                </td>
                                <td>
                                    <?= statusBadge($app['application_status']) ?>
                                </td>
                                <td>
                                    <?php if (!empty($app['admin_remarks'])): ?>
                                        <small class="text-secondary"><?= e($app['admin_remarks']) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">—</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($app['application_status'] === 'Approved'): ?>
                                        <a href="<?= baseUrl('intern/internship.php') ?>" class="btn btn-sm btn-primary rounded-pill px-3">
                                            <i class="bi bi-briefcase me-1"></i>Go to Track
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Under Review</span>
                                    <?php endif; ?>
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
