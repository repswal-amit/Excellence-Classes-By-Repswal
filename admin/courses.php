<?php
/**
 * Manage Training Courses
 * Excellence Classes By Repswal
 */

$adminPageTitle = 'Manage Courses';
$currentAdminPage = 'courses';

require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Handle Create / Edit / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid security token.');
        redirect(baseUrl('admin/courses.php'));
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save_course') {
        $courseId = (int)($_POST['course_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $duration = trim($_POST['duration'] ?? '3 Months');
        $level = in_array($_POST['level'] ?? '', ['Beginner', 'Intermediate', 'Advanced', 'All Levels']) ? $_POST['level'] : 'All Levels';
        $mode = in_array($_POST['mode'] ?? '', ['Online', 'Offline', 'Hybrid']) ? $_POST['mode'] : 'Online';
        $shortDesc = trim($_POST['short_description'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $iconClass = trim($_POST['icon_class'] ?? 'bi-journal-code');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title)) {
            setFlash('danger', 'Course title is required.');
            redirect(baseUrl('admin/courses.php'));
        }

        $slug = createSlug($title);

        try {
            if ($courseId > 0) {
                $stmt = $pdo->prepare("
                    UPDATE courses 
                    SET title = ?, slug = ?, duration = ?, level = ?, mode = ?,
                        short_description = ?, description = ?, icon_class = ?, sort_order = ?, is_active = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$title, $slug, $duration, $level, $mode, $shortDesc, $description, $iconClass, $sortOrder, $isActive, $courseId]);

                logAdminAction($pdo, $currentAdmin['id'], 'Update Course', 'courses', $courseId);
                setFlash('success', "Course '{$title}' updated successfully.");
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO courses (title, slug, duration, level, mode, short_description, description, icon_class, sort_order, is_active, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$title, $slug, $duration, $level, $mode, $shortDesc, $description, $iconClass, $sortOrder, $isActive, $currentAdmin['id']]);
                $newId = (int)$pdo->lastInsertId();

                logAdminAction($pdo, $currentAdmin['id'], 'Create Course', 'courses', $newId);
                setFlash('success', "Course '{$title}' created successfully.");
            }

            redirect(baseUrl('admin/courses.php'));

        } catch (PDOException $e) {
            setFlash('danger', 'Database error: ' . $e->getMessage());
            redirect(baseUrl('admin/courses.php'));
        }
    }

    if ($action === 'delete_course') {
        $courseId = (int)($_POST['course_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$courseId]);

            logAdminAction($pdo, $currentAdmin['id'], 'Delete Course', 'courses', $courseId);
            setFlash('success', 'Course removed successfully.');
            redirect(baseUrl('admin/courses.php'));
        } catch (PDOException $e) {
            setFlash('danger', 'Error deleting course: ' . $e->getMessage());
            redirect(baseUrl('admin/courses.php'));
        }
    }
}

// Fetch all courses
$courses = $pdo->query("SELECT * FROM courses ORDER BY sort_order ASC, id ASC")->fetchAll();
?>

<!-- ═══════════════════ HEADER CONTROLS ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Training Courses Management</h4>
        <p class="text-muted small mb-0">Create and modify academic courses, durations, levels, and public descriptions.</p>
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createCourseModal">
        <i class="bi bi-plus-circle-fill me-2"></i>Add New Course
    </button>
</div>

<!-- ═══════════════════ COURSES TABLE ═══════════════════ -->
<div class="panel-card">
    <div class="panel-header">
        <h5 class="panel-title">
            <i class="bi bi-journal-code text-primary"></i> Courses List (<?= count($courses) ?>)
        </h5>
    </div>
    <div class="panel-body p-0">
        <?php if (empty($courses)): ?>
            <div class="p-5 text-center text-muted">No courses added yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Course Title</th>
                            <th>Level</th>
                            <th>Duration</th>
                            <th>Mode</th>
                            <th>Status</th>
                            <th>Order</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td>
                                    <div class="metric-icon royal" style="width: 36px; height: 36px; font-size: 1.1rem;">
                                        <i class="bi <?= e($c['icon_class'] ?? 'bi-book') ?>"></i>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($c['title']) ?></div>
                                    <small class="text-muted"><?= e(truncate($c['short_description'] ?? '', 80)) ?></small>
                                </td>
                                <td><span class="badge bg-primary-subtle text-primary"><?= e($c['level']) ?></span></td>
                                <td><?= e($c['duration']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= e($c['mode']) ?></span></td>
                                <td>
                                    <?php if ((int)$c['is_active'] === 1): ?>
                                        <span class="badge bg-success-subtle text-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int)$c['sort_order'] ?></td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-action" data-bs-toggle="modal" data-bs-target="#editCourseModal<?= (int)$c['id'] ?>" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="<?= baseUrl('admin/courses.php') ?>" class="d-inline" onsubmit="return confirm('Delete this course?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete_course">
                                            <input type="hidden" name="course_id" value="<?= (int)$c['id'] ?>">
                                            <button type="submit" class="btn btn-action btn-action-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editCourseModal<?= (int)$c['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content border-0 shadow-lg">
                                        <form method="POST" action="<?= baseUrl('admin/courses.php') ?>">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="save_course">
                                            <input type="hidden" name="course_id" value="<?= (int)$c['id'] ?>">

                                            <div class="modal-header bg-navy text-white">
                                                <h5 class="modal-title fw-bold">Edit Course: <?= e($c['title']) ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body p-4">
                                                <div class="row g-3">
                                                    <div class="col-md-8">
                                                        <label class="form-label fw-semibold">Course Title</label>
                                                        <input type="text" name="title" class="form-control" value="<?= e($c['title']) ?>" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label fw-semibold">Duration</label>
                                                        <input type="text" name="duration" class="form-control" value="<?= e($c['duration']) ?>" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label fw-semibold">Level</label>
                                                        <select name="level" class="form-select">
                                                            <option value="All Levels" <?= ($c['level'] === 'All Levels') ? 'selected' : '' ?>>All Levels</option>
                                                            <option value="Beginner" <?= ($c['level'] === 'Beginner') ? 'selected' : '' ?>>Beginner</option>
                                                            <option value="Intermediate" <?= ($c['level'] === 'Intermediate') ? 'selected' : '' ?>>Intermediate</option>
                                                            <option value="Advanced" <?= ($c['level'] === 'Advanced') ? 'selected' : '' ?>>Advanced</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label fw-semibold">Mode</label>
                                                        <select name="mode" class="form-select">
                                                            <option value="Online" <?= ($c['mode'] === 'Online') ? 'selected' : '' ?>>Online</option>
                                                            <option value="Offline" <?= ($c['mode'] === 'Offline') ? 'selected' : '' ?>>Offline</option>
                                                            <option value="Hybrid" <?= ($c['mode'] === 'Hybrid') ? 'selected' : '' ?>>Hybrid</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label fw-semibold">Icon Class</label>
                                                        <input type="text" name="icon_class" class="form-control" value="<?= e($c['icon_class'] ?? 'bi-book') ?>">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold">Short Summary</label>
                                                        <input type="text" name="short_description" class="form-control" value="<?= e($c['short_description'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label fw-semibold">Detailed Description</label>
                                                        <textarea name="description" rows="3" class="form-control"><?= e($c['description'] ?? '') ?></textarea>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label fw-semibold">Sort Order</label>
                                                        <input type="number" name="sort_order" class="form-control" value="<?= (int)$c['sort_order'] ?>">
                                                    </div>
                                                    <div class="col-md-6 d-flex align-items-center pt-3">
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="is_active" id="activeCheck<?= (int)$c['id'] ?>" <?= ((int)$c['is_active'] === 1) ? 'checked' : '' ?>>
                                                            <label class="form-check-label fw-semibold" for="activeCheck<?= (int)$c['id'] ?>">Active on Website</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary fw-bold">Save Changes</button>
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

<!-- ═══════════════════ CREATE COURSE MODAL ═══════════════════ -->
<div class="modal fade" id="createCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" action="<?= baseUrl('admin/courses.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="save_course">
                <input type="hidden" name="course_id" value="0">

                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-gold"></i>Add New Course</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Course Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Data Structures & Algorithms" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Duration <span class="text-danger">*</span></label>
                            <input type="text" name="duration" class="form-control" value="3 Months" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Level</label>
                            <select name="level" class="form-select">
                                <option value="All Levels">All Levels</option>
                                <option value="Beginner" selected>Beginner</option>
                                <option value="Intermediate">Intermediate</option>
                                <option value="Advanced">Advanced</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Mode</label>
                            <select name="mode" class="form-select">
                                <option value="Online" selected>Online</option>
                                <option value="Offline">Offline</option>
                                <option value="Hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Icon Class</label>
                            <input type="text" name="icon_class" class="form-control" value="bi-journal-code">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Short Summary</label>
                            <input type="text" name="short_description" class="form-control" placeholder="1-2 sentences summarizing the course">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Detailed Description</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="Topics covered and prerequisites..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" value="10">
                        </div>
                        <div class="col-md-6 d-flex align-items-center pt-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="newActive" checked>
                                <label class="form-check-label fw-semibold" for="newActive">Active Immediately</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Create Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
