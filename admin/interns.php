<?php
/**
 * Manage Registered Interns
 * Excellence Classes By Repswal
 */

$adminPageTitle = 'Manage Interns';
$currentAdminPage = 'interns';

require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Handle Status Toggle or Account Modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid security token.');
        redirect(baseUrl('admin/interns.php'));
    }

    $internId = (int)($_POST['intern_id'] ?? 0);
    $newStatus = (int)($_POST['is_active'] ?? 1);

    try {
        $stmt = $pdo->prepare("UPDATE interns SET is_active = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $internId]);

        logAdminAction($pdo, $currentAdmin['id'], 'Toggle Intern Active Status', 'interns', $internId, null, ['is_active' => $newStatus]);

        setFlash('success', 'Intern status updated successfully.');
        redirect(baseUrl('admin/interns.php'));
    } catch (PDOException $e) {
        setFlash('danger', 'Error updating intern: ' . $e->getMessage());
        redirect(baseUrl('admin/interns.php'));
    }
}

// Search & Filter
$search = trim($_GET['search'] ?? '');
$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;

$where = ["1=1"];
$params = [];

if ($statusFilter !== null && in_array($statusFilter, [0, 1])) {
    $where[] = "is_active = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ? OR college_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = implode(" AND ", $where);

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM interns WHERE {$whereClause}");
$countStmt->execute($params);
$totalInterns = (int)$countStmt->fetchColumn();

$pagination = paginate($totalInterns, $perPage, $page);

// Fetch interns
$sql = "
    SELECT i.*,
           (SELECT COUNT(*) FROM internship_applications WHERE intern_id = i.id) AS applications_count,
           (SELECT COUNT(*) FROM internship_enrollments WHERE intern_id = i.id AND internship_status = 'In Progress') AS active_enrollments_count,
           (SELECT COUNT(*) FROM certificates WHERE intern_id = i.id AND certificate_status = 'Issued') AS certificates_count
    FROM interns i
    WHERE {$whereClause}
    ORDER BY i.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$interns = $stmt->fetchAll();
?>

<!-- ═══════════════════ SEARCH & FILTER BAR ═══════════════════ -->
<div class="panel-card p-3 mb-4">
    <form method="GET" action="<?= baseUrl('admin/interns.php') ?>" class="row g-2 align-items-center">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, email, phone, or college..." value="<?= e($search) ?>">
            </div>
        </div>

        <div class="col-md-3">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All Account Statuses</option>
                <option value="1" <?= ($statusFilter === 1) ? 'selected' : '' ?>>Active Accounts</option>
                <option value="0" <?= ($statusFilter === 0) ? 'selected' : '' ?>>Suspended / Inactive</option>
            </select>
        </div>

        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Filter</button>
            <?php if (!empty($search) || $statusFilter !== null): ?>
                <a href="<?= baseUrl('admin/interns.php') ?>" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- ═══════════════════ INTERNS TABLE ═══════════════════ -->
<div class="panel-card">
    <div class="panel-header">
        <h5 class="panel-title">
            <i class="bi bi-people-fill text-primary"></i>
            Registered Interns Directory (<?= number_format($totalInterns) ?>)
        </h5>
        <div class="text-muted small">
            Authenticated intern profiles and participation tracking
        </div>
    </div>

    <div class="panel-body p-0">
        <?php if (empty($interns)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-bold">No Interns Found</h5>
                <p class="small mb-0">No records match the selected filter criteria.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Intern Details</th>
                            <th>College & Degree</th>
                            <th>Applications</th>
                            <th>Active Track</th>
                            <th>Certificates</th>
                            <th>Account Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($interns as $it): ?>
                            <tr>
                                <td class="fw-bold">#<?= (int)$it['id'] ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="topbar-avatar" style="width: 34px; height: 34px; font-size: 0.85rem;">
                                            <?= strtoupper(substr($it['full_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= e($it['full_name']) ?></div>
                                            <div class="small text-muted"><?= e($it['email']) ?> • <?= e($it['phone']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= e($it['college_name']) ?></div>
                                    <div class="small text-muted"><?= e($it['course_degree']) ?> (<?= e($it['current_year']) ?>)</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= (int)$it['applications_count'] ?></span>
                                </td>
                                <td>
                                    <?php if ((int)$it['active_enrollments_count'] > 0): ?>
                                        <span class="badge bg-primary-subtle text-primary">Active (<?= (int)$it['active_enrollments_count'] ?>)</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int)$it['certificates_count'] > 0): ?>
                                        <span class="badge bg-gold text-dark fw-bold"><i class="bi bi-award-fill me-1"></i><?= (int)$it['certificates_count'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int)$it['is_active'] === 1): ?>
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger">Suspended</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-action" data-bs-toggle="modal" data-bs-target="#internModal<?= (int)$it['id'] ?>" title="View Profile">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        
                                        <!-- Toggle Status Form -->
                                        <form method="POST" action="<?= baseUrl('admin/interns.php') ?>" class="d-inline" onsubmit="return confirm('Change active status for this intern?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="intern_id" value="<?= (int)$it['id'] ?>">
                                            <input type="hidden" name="is_active" value="<?= ((int)$it['is_active'] === 1) ? '0' : '1' ?>">
                                            <input type="hidden" name="toggle_status" value="1">
                                            <?php if ((int)$it['is_active'] === 1): ?>
                                                <button type="submit" class="btn btn-action btn-action-danger" title="Suspend Account">
                                                    <i class="bi bi-person-x"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-action btn-action-success" title="Reactivate Account">
                                                    <i class="bi bi-person-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <!-- Profile Modal -->
                            <div class="modal fade" id="internModal<?= (int)$it['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content border-0 shadow-lg">
                                        <div class="modal-header bg-navy text-white">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-person-badge text-gold fs-4"></i>
                                                <h5 class="modal-title fw-bold">Intern Profile — <?= e($it['full_name']) ?></h5>
                                            </div>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="row g-3 mb-4">
                                                <div class="col-md-6">
                                                    <label class="text-muted small">Full Legal Name</label>
                                                    <div class="fw-bold text-dark fs-6"><?= e($it['full_name']) ?></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-muted small">Email Address</label>
                                                    <div class="fw-bold text-dark"><?= e($it['email']) ?></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-muted small">Mobile Number</label>
                                                    <div class="fw-bold text-dark"><?= e($it['phone']) ?></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-muted small">College / University</label>
                                                    <div class="fw-bold text-dark"><?= e($it['college_name']) ?></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-muted small">Degree Program</label>
                                                    <div class="fw-bold text-dark"><?= e($it['course_degree']) ?> (<?= e($it['current_year']) ?>)</div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-muted small">Registered On</label>
                                                    <div class="fw-bold text-dark"><?= formatDateTime($it['created_at']) ?></div>
                                                </div>
                                            </div>

                                            <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>Account Activity:</strong>
                                                    <span class="text-muted small ms-2">Last login: <?= !empty($it['last_login']) ? formatDateTime($it['last_login']) : 'Never' ?></span>
                                                </div>
                                                <a href="<?= baseUrl('admin/applications.php?search=' . urlencode($it['email'])) ?>" class="btn btn-sm btn-outline-primary">
                                                    View Applications (<?= (int)$it['applications_count'] ?>)
                                                </a>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="p-3 border-top">
                <?= renderPagination($pagination, baseUrl('admin/interns.php?status=' . $statusFilter . '&search=' . urlencode($search) . '&')) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
