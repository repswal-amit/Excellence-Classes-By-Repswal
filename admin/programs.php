<?php
/**
 * Manage Internship Programs
 * Excellence Classes By Repswal
 */

$adminPageTitle = 'Manage Internship Programs';
$currentAdminPage = 'programs';

require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Handle Create / Edit / Archive / Toggle Open-Closed
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid security token.');
        redirect(baseUrl('admin/programs.php'));
    }

    $action = $_POST['action'] ?? '';

    // Toggle Open / Closed Status
    if ($action === 'toggle_status') {
        $progId = (int)($_POST['program_id'] ?? 0);
        $newStatus = ($_POST['new_status'] === 'Open') ? 'Open' : 'Closed';

        try {
            $stmt = $pdo->prepare("UPDATE internship_programs SET application_status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $progId]);

            logAdminAction($pdo, $currentAdmin['id'], 'Toggle Program Application Status', 'internship_programs', $progId, null, ['status' => $newStatus]);
            setFlash('success', "Program application status updated to '{$newStatus}'.");
            redirect(baseUrl('admin/programs.php'));
        } catch (PDOException $e) {
            setFlash('danger', 'Error updating program: ' . $e->getMessage());
            redirect(baseUrl('admin/programs.php'));
        }
    }

    // Save Program (Create or Update)
    if ($action === 'save_program') {
        $progId = (int)($_POST['program_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $duration = trim($_POST['duration'] ?? '4 Weeks');
        $mode = in_array($_POST['mode'] ?? '', ['Online', 'Offline', 'Hybrid']) ? $_POST['mode'] : 'Online';
        $status = ($_POST['application_status'] === 'Open') ? 'Open' : 'Closed';
        $shortDesc = trim($_POST['short_description'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $eligibility = trim($_POST['eligibility'] ?? '');
        $iconClass = trim($_POST['icon_class'] ?? 'bi-code-slash');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $rawTechs = trim($_POST['technologies'] ?? '');
        $rawSkills = trim($_POST['skills_covered'] ?? '');

        // Convert comma-separated inputs to clean JSON arrays
        $techArray = array_values(array_filter(array_map('trim', explode(',', $rawTechs))));
        $skillArray = array_values(array_filter(array_map('trim', explode(',', $rawSkills))));

        if (empty($title)) {
            setFlash('danger', 'Program title is required.');
            redirect(baseUrl('admin/programs.php'));
        }

        $slug = createSlug($title);

        try {
            if ($progId > 0) {
                // Update
                $stmt = $pdo->prepare("
                    UPDATE internship_programs 
                    SET title = ?, slug = ?, duration = ?, mode = ?, application_status = ?,
                        short_description = ?, description = ?, eligibility = ?,
                        technologies = ?, skills_covered = ?, icon_class = ?, sort_order = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $title, $slug, $duration, $mode, $status,
                    $shortDesc, $description, $eligibility,
                    json_encode($techArray), json_encode($skillArray), $iconClass, $sortOrder,
                    $progId
                ]);

                logAdminAction($pdo, $currentAdmin['id'], 'Update Internship Program', 'internship_programs', $progId);
                setFlash('success', "Program '{$title}' updated successfully.");

            } else {
                // Create
                $stmt = $pdo->prepare("
                    INSERT INTO internship_programs 
                    (title, slug, duration, mode, application_status, short_description, description, eligibility, technologies, skills_covered, icon_class, sort_order, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $title, $slug, $duration, $mode, $status,
                    $shortDesc, $description, $eligibility,
                    json_encode($techArray), json_encode($skillArray), $iconClass, $sortOrder,
                    $currentAdmin['id']
                ]);
                $newId = (int)$pdo->lastInsertId();

                logAdminAction($pdo, $currentAdmin['id'], 'Create Internship Program', 'internship_programs', $newId);
                setFlash('success', "New program '{$title}' created successfully.");
            }

            redirect(baseUrl('admin/programs.php'));

        } catch (PDOException $e) {
            setFlash('danger', 'Database error: ' . $e->getMessage());
            redirect(baseUrl('admin/programs.php'));
        }
    }

    // Archive / Delete Program
    if ($action === 'archive_program') {
        $progId = (int)($_POST['program_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE internship_programs SET is_archived = 1, application_status = 'Closed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$progId]);

            logAdminAction($pdo, $currentAdmin['id'], 'Archive Internship Program', 'internship_programs', $progId);
            setFlash('success', 'Program archived successfully.');
            redirect(baseUrl('admin/programs.php'));
        } catch (PDOException $e) {
            setFlash('danger', 'Error archiving program: ' . $e->getMessage());
            redirect(baseUrl('admin/programs.php'));
        }
    }
}

// Fetch all active programs
$programs = $pdo->query("
    SELECT p.*,
           (SELECT COUNT(*) FROM internship_applications WHERE program_id = p.id) AS applications_count,
           (SELECT COUNT(*) FROM internship_enrollments WHERE program_id = p.id AND internship_status = 'In Progress') AS active_count
    FROM internship_programs p
    WHERE p.is_archived = 0
    ORDER BY p.sort_order ASC, p.id ASC
")->fetchAll();
?>

<!-- ═══════════════════ HEADER CONTROLS ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Internship Program Directory</h4>
        <p class="text-muted small mb-0">Independently manage curriculum tracks, application statuses, technologies, and seats.</p>
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createProgramModal">
        <i class="bi bi-plus-circle-fill me-2"></i>Create New Program
    </button>
</div>

<!-- ═══════════════════ PROGRAMS GRID ═══════════════════ -->
<div class="row g-4">
    <?php foreach ($programs as $prog): ?>
        <?php
            $isOpen = ($prog['application_status'] === 'Open');
            $techs = !empty($prog['technologies']) ? json_decode($prog['technologies'], true) : [];
            $skills = !empty($prog['skills_covered']) ? json_decode($prog['skills_covered'], true) : [];
            $icon = !empty($prog['icon_class']) ? $prog['icon_class'] : 'bi-briefcase';
        ?>
        <div class="col-lg-4 col-md-6">
            <div class="panel-card h-100 d-flex flex-column">
                <div class="panel-header d-flex justify-content-between align-items-start">
                    <div class="d-flex align-items-center gap-2">
                        <div class="metric-icon royal" style="width: 44px; height: 44px; font-size: 1.25rem;">
                            <i class="bi <?= e($icon) ?>"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0 text-dark"><?= e($prog['title']) ?></h6>
                            <small class="text-muted"><?= e($prog['duration']) ?> • <?= e($prog['mode']) ?></small>
                        </div>
                    </div>

                    <!-- Application status badge -->
                    <?php if ($isOpen): ?>
                        <span class="badge bg-success-subtle text-success">Open</span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary">Closed</span>
                    <?php endif; ?>
                </div>

                <div class="panel-body flex-grow-1">
                    <p class="text-secondary small leading-relaxed mb-3">
                        <?= e(truncate($prog['short_description'] ?? '', 120)) ?>
                    </p>

                    <?php if (!empty($techs)): ?>
                        <div class="mb-3 d-flex flex-wrap gap-1">
                            <?php foreach (array_slice((array)$techs, 0, 4) as $t): ?>
                                <span class="badge bg-light text-dark border small"><?= e($t) ?></span>
                            <?php endforeach; ?>
                            <?php if (count((array)$techs) > 4): ?>
                                <span class="badge bg-light text-muted border small">+<?= count((array)$techs) - 4 ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="p-2 bg-light rounded-3 d-flex justify-content-around text-center small mb-2">
                        <div>
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Applications</span>
                            <span class="fw-bold text-dark"><?= (int)$prog['applications_count'] ?></span>
                        </div>
                        <div class="border-start"></div>
                        <div>
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Active Interns</span>
                            <span class="fw-bold text-primary"><?= (int)$prog['active_count'] ?></span>
                        </div>
                    </div>
                </div>

                <div class="panel-footer d-flex justify-content-between align-items-center">
                    <!-- Toggle Status Form -->
                    <form method="POST" action="<?= baseUrl('admin/programs.php') ?>" class="d-inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="program_id" value="<?= (int)$prog['id'] ?>">
                        <input type="hidden" name="new_status" value="<?= $isOpen ? 'Closed' : 'Open' ?>">
                        <?php if ($isOpen): ?>
                            <button type="submit" class="btn btn-sm btn-outline-warning text-dark" title="Close Applications">
                                <i class="bi bi-door-closed me-1"></i>Close Apps
                            </button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-sm btn-outline-success" title="Open Applications">
                                <i class="bi bi-door-open me-1"></i>Open Apps
                            </button>
                        <?php endif; ?>
                    </form>

                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= (int)$prog['id'] ?>">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </button>
                        
                        <form method="POST" action="<?= baseUrl('admin/programs.php') ?>" class="d-inline" onsubmit="return confirm('Archive this internship program? It will no longer appear on public pages.');">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="archive_program">
                            <input type="hidden" name="program_id" value="<?= (int)$prog['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Archive">
                                <i class="bi bi-archive"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Program Modal -->
        <div class="modal fade" id="editModal<?= (int)$prog['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg">
                    <form method="POST" action="<?= baseUrl('admin/programs.php') ?>">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="save_program">
                        <input type="hidden" name="program_id" value="<?= (int)$prog['id'] ?>">

                        <div class="modal-header bg-navy text-white">
                            <h5 class="modal-title fw-bold">Edit Program: <?= e($prog['title']) ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Program Title</label>
                                    <input type="text" name="title" class="form-control" value="<?= e($prog['title']) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Duration</label>
                                    <input type="text" name="duration" class="form-control" value="<?= e($prog['duration']) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Mode</label>
                                    <select name="mode" class="form-select">
                                        <option value="Online" <?= ($prog['mode'] === 'Online') ? 'selected' : '' ?>>Online</option>
                                        <option value="Offline" <?= ($prog['mode'] === 'Offline') ? 'selected' : '' ?>>Offline</option>
                                        <option value="Hybrid" <?= ($prog['mode'] === 'Hybrid') ? 'selected' : '' ?>>Hybrid</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Application Status</label>
                                    <select name="application_status" class="form-select">
                                        <option value="Open" <?= ($prog['application_status'] === 'Open') ? 'selected' : '' ?>>Open</option>
                                        <option value="Closed" <?= ($prog['application_status'] === 'Closed') ? 'selected' : '' ?>>Closed</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Icon Class</label>
                                    <input type="text" name="icon_class" class="form-control" value="<?= e($prog['icon_class'] ?? 'bi-code-slash') ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Short Summary</label>
                                    <input type="text" name="short_description" class="form-control" value="<?= e($prog['short_description']) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Full Curriculum & Description</label>
                                    <textarea name="description" rows="4" class="form-control"><?= e($prog['description']) ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Eligibility Criteria</label>
                                    <textarea name="eligibility" rows="2" class="form-control"><?= e($prog['eligibility']) ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Technologies (comma-separated)</label>
                                    <input type="text" name="technologies" class="form-control" value="<?= e(implode(', ', (array)$techs)) ?>" placeholder="HTML5, CSS3, JavaScript, PHP">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Skills Covered (comma-separated)</label>
                                    <input type="text" name="skills_covered" class="form-control" value="<?= e(implode(', ', (array)$skills)) ?>" placeholder="Frontend, Backend, Database">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Sort Display Order</label>
                                    <input type="number" name="sort_order" class="form-control" value="<?= (int)$prog['sort_order'] ?>">
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary fw-bold">Update Program</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php endforeach; ?>
</div>

<!-- ═══════════════════ CREATE PROGRAM MODAL ═══════════════════ -->
<div class="modal fade" id="createProgramModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" action="<?= baseUrl('admin/programs.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save_program">
                <input type="hidden" name="program_id" value="0">

                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-gold"></i>Create Internship Program</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Program Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Cloud Computing Internship" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Duration <span class="text-danger">*</span></label>
                            <input type="text" name="duration" class="form-control" value="4 Weeks" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Mode</label>
                            <select name="mode" class="form-select">
                                <option value="Online">Online</option>
                                <option value="Offline">Offline</option>
                                <option value="Hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Application Status</label>
                            <select name="application_status" class="form-select">
                                <option value="Open">Open</option>
                                <option value="Closed" selected>Closed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Icon Class (Bootstrap Icons)</label>
                            <input type="text" name="icon_class" class="form-control" value="bi-code-slash">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Short Summary</label>
                            <input type="text" name="short_description" class="form-control" placeholder="Brief 1-2 sentence overview for cards">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Comprehensive Description</label>
                            <textarea name="description" rows="4" class="form-control" placeholder="Full syllabus and project overview..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Eligibility Criteria</label>
                            <textarea name="eligibility" rows="2" class="form-control" placeholder="Prerequisites and eligible student courses..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Technologies (comma-separated)</label>
                            <input type="text" name="technologies" class="form-control" placeholder="e.g. AWS, Docker, Kubernetes">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Skills Covered (comma-separated)</label>
                            <input type="text" name="skills_covered" class="form-control" placeholder="e.g. Cloud Architecture, DevOps">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sort Display Order</label>
                            <input type="number" name="sort_order" class="form-control" value="10">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Create Program</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
