<?php
/**
 * Task Management
 * Excellence Classes By Repswal
 */

$adminPageTitle = 'Task Management';
$currentAdminPage = 'tasks';

require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Handle Task Creation / Edit / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid security token.');
        redirect(baseUrl('admin/tasks.php'));
    }

    $action = $_POST['action'] ?? '';

    // Create Task (Can assign to single enrollment OR all active in a program)
    if ($action === 'create_task') {
        $assignmentType = $_POST['assignment_type'] ?? 'single'; // 'single' or 'program'
        $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
        $programId = (int)($_POST['program_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $referenceMaterials = trim($_POST['reference_materials'] ?? '');
        $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

        if (empty($title)) {
            setFlash('danger', 'Task title is required.');
            redirect(baseUrl('admin/tasks.php'));
        }

        try {
            $pdo->beginTransaction();

            if ($assignmentType === 'program' && $programId > 0) {
                // Fetch all active enrollments for this program
                $activeEnrollStmt = $pdo->prepare("SELECT id FROM internship_enrollments WHERE program_id = ? AND internship_status IN ('Not Started', 'In Progress')");
                $activeEnrollStmt->execute([$programId]);
                $enrollments = $activeEnrollStmt->fetchAll();

                if (empty($enrollments)) {
                    $pdo->rollBack();
                    setFlash('warning', 'No active enrollments found for this program.');
                    redirect(baseUrl('admin/tasks.php'));
                }

                $insertStmt = $pdo->prepare("
                    INSERT INTO tasks (enrollment_id, title, description, reference_materials, deadline, assigned_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");

                foreach ($enrollments as $en) {
                    $insertStmt->execute([$en['id'], $title, $description, $referenceMaterials, $deadline, $currentAdmin['id']]);
                    
                    // Update total_tasks count in internship_progress
                    $pdo->prepare("UPDATE internship_progress SET total_tasks = total_tasks + 1 WHERE enrollment_id = ?")->execute([$en['id']]);
                }

                $count = count($enrollments);
                $pdo->commit();
                logAdminAction($pdo, $currentAdmin['id'], 'Bulk Assign Task', 'tasks', null, null, ['program_id' => $programId, 'count' => $count]);
                setFlash('success', "Task assigned to {$count} enrolled interns in this program.");

            } else {
                // Assign to single enrollment
                if ($enrollmentId <= 0) {
                    $pdo->rollBack();
                    setFlash('danger', 'Please select a valid intern enrollment.');
                    redirect(baseUrl('admin/tasks.php'));
                }

                $insertStmt = $pdo->prepare("
                    INSERT INTO tasks (enrollment_id, title, description, reference_materials, deadline, assigned_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $insertStmt->execute([$enrollmentId, $title, $description, $referenceMaterials, $deadline, $currentAdmin['id']]);
                $newTaskId = (int)$pdo->lastInsertId();

                // Update progress total
                $pdo->prepare("UPDATE internship_progress SET total_tasks = total_tasks + 1 WHERE enrollment_id = ?")->execute([$enrollmentId]);

                $pdo->commit();
                logAdminAction($pdo, $currentAdmin['id'], 'Create Task', 'tasks', $newTaskId);
                setFlash('success', "Task created and assigned successfully.");
            }

            redirect(baseUrl('admin/tasks.php'));

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            setFlash('danger', 'Database error: ' . $e->getMessage());
            redirect(baseUrl('admin/tasks.php'));
        }
    }

    // Delete Task
    if ($action === 'delete_task') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        try {
            // Find enrollment
            $tStmt = $pdo->prepare("SELECT enrollment_id FROM tasks WHERE id = ?");
            $tStmt->execute([$taskId]);
            $tRow = $tStmt->fetch();

            if ($tRow) {
                $del = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
                $del->execute([$taskId]);
                // Decrease total_tasks
                $pdo->prepare("UPDATE internship_progress SET total_tasks = GREATEST(0, total_tasks - 1) WHERE enrollment_id = ?")->execute([$tRow['enrollment_id']]);
            }

            logAdminAction($pdo, $currentAdmin['id'], 'Delete Task', 'tasks', $taskId);
            setFlash('success', 'Task removed.');
            redirect(baseUrl('admin/tasks.php'));
        } catch (PDOException $e) {
            setFlash('danger', 'Error: ' . $e->getMessage());
            redirect(baseUrl('admin/tasks.php'));
        }
    }
}

// Fetch active enrollments for assignment dropdown
$activeEnrollments = $pdo->query("
    SELECT e.id, i.full_name, p.title AS program_title
    FROM internship_enrollments e
    JOIN interns i ON e.intern_id = i.id
    JOIN internship_programs p ON e.program_id = p.id
    WHERE e.internship_status IN ('Not Started', 'In Progress')
    ORDER BY i.full_name ASC
")->fetchAll();

$programs = $pdo->query("SELECT id, title FROM internship_programs WHERE is_active = 1 ORDER BY title ASC")->fetchAll();

// Fetch tasks list
$tasks = $pdo->query("
    SELECT t.*, i.full_name AS intern_name, p.title AS program_title,
           s.id AS submission_id, s.submission_status, s.submitted_at
    FROM tasks t
    JOIN internship_enrollments e ON t.enrollment_id = e.id
    JOIN interns i ON e.intern_id = i.id
    JOIN internship_programs p ON e.program_id = p.id
    LEFT JOIN task_submissions s ON t.id = s.task_id AND s.intern_id = i.id
    ORDER BY t.created_at DESC
    LIMIT 50
")->fetchAll();
?>

<!-- ═══════════════════ HEADER CONTROLS ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Task Management</h4>
        <p class="text-muted small mb-0">Create practical milestones, set deadlines, and track intern submission workflows.</p>
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createTaskModal">
        <i class="bi bi-plus-circle-fill me-2"></i>Assign New Task
    </button>
</div>

<!-- ═══════════════════ TASKS TABLE ═══════════════════ -->
<div class="panel-card">
    <div class="panel-header">
        <h5 class="panel-title">
            <i class="bi bi-list-task text-primary"></i> Assigned Tasks (<?= count($tasks) ?>)
        </h5>
    </div>

    <div class="panel-body p-0">
        <?php if (empty($tasks)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-clipboard fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-bold">No Tasks Assigned Yet</h5>
                <p class="small mb-3">Assign practical milestones to enrolled interns or entire program batches.</p>
                <button type="button" class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                    <i class="bi bi-plus-circle me-1"></i>Create First Task
                </button>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Task Title</th>
                            <th>Assigned Intern</th>
                            <th>Program</th>
                            <th>Deadline</th>
                            <th>Submission Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $t): ?>
                            <tr>
                                <td class="fw-bold">#<?= (int)$t['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($t['title']) ?></div>
                                    <small class="text-muted"><?= e(truncate($t['description'] ?? '', 75)) ?></small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($t['intern_name']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border"><?= e($t['program_title']) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($t['deadline'])): ?>
                                        <div class="small fw-semibold"><?= formatDate($t['deadline']) ?></div>
                                    <?php else: ?>
                                        <span class="text-muted small">No deadline</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($t['submission_status'])): ?>
                                        <?= statusBadge($t['submission_status']) ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary">Not Submitted</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <?php if (!empty($t['submission_id'])): ?>
                                            <a href="<?= baseUrl('admin/submissions.php?id=' . (int)$t['submission_id']) ?>" class="btn btn-action btn-action-success" title="View Submission">
                                                <i class="bi bi-clipboard-check"></i>
                                            </a>
                                        <?php endif; ?>

                                        <form method="POST" action="<?= baseUrl('admin/tasks.php') ?>" class="d-inline" onsubmit="return confirm('Delete this task?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete_task">
                                            <input type="hidden" name="task_id" value="<?= (int)$t['id'] ?>">
                                            <button type="submit" class="btn btn-action btn-action-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════ CREATE TASK MODAL ═══════════════════ -->
<div class="modal fade" id="createTaskModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" action="<?= baseUrl('admin/tasks.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_task">

                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-gold"></i>Assign Practical Task</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- Assignment Target Mode -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Assignment Scope</label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="assignment_type" id="typeSingle" value="single" checked onchange="toggleScope(this.value)">
                                <label class="form-check-label fw-semibold" for="typeSingle">Single Intern Enrollment</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="assignment_type" id="typeProgram" value="program" onchange="toggleScope(this.value)">
                                <label class="form-check-label fw-semibold" for="typeProgram">All Active Interns in Program</label>
                            </div>
                        </div>
                    </div>

                    <div id="singleEnrollmentDiv" class="mb-3">
                        <label class="form-label fw-semibold">Select Intern Enrollment</label>
                        <select name="enrollment_id" class="form-select form-select-lg">
                            <option value="">-- Choose active enrolled intern --</option>
                            <?php foreach ($activeEnrollments as $ae): ?>
                                <option value="<?= (int)$ae['id'] ?>">
                                    <?= e($ae['full_name']) ?> — <?= e($ae['program_title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="programBatchDiv" class="mb-3 d-none">
                        <label class="form-label fw-semibold">Select Internship Program</label>
                        <select name="program_id" class="form-select form-select-lg">
                            <option value="">-- Choose target program batch --</option>
                            <?php foreach ($programs as $prg): ?>
                                <option value="<?= (int)$prg['id'] ?>">
                                    <?= e($prg['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text small">A task will be created for each intern currently enrolled in this program.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Task Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Build Responsive Navbar & Glassmorphism Cards" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Detailed Task Requirements</label>
                        <textarea name="description" rows="4" class="form-control" placeholder="Explain the specific features, deliverables, and acceptance criteria..."></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Reference Materials / GitHub Starter</label>
                            <input type="text" name="reference_materials" class="form-control" placeholder="https://github.com/... or documentation link">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Submission Deadline</label>
                            <input type="date" name="deadline" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Assign Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleScope(val) {
    const single = document.getElementById('singleEnrollmentDiv');
    const prog = document.getElementById('programBatchDiv');
    if (val === 'program') {
        single.classList.add('d-none');
        prog.classList.remove('d-none');
    } else {
        single.classList.remove('d-none');
        prog.classList.add('d-none');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
