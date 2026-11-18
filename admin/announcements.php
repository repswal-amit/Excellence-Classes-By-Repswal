<?php
/**
 * Announcements Management
 * Excellence Classes By Repswal
 */

$adminPageTitle = 'Announcements';
$currentAdminPage = 'announcements';

require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Handle Create / Delete Announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid security token.');
        redirect(baseUrl('admin/announcements.php'));
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_announcement') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $audience = in_array($_POST['target_audience'] ?? '', ['All', 'Interns', 'Admins']) ? $_POST['target_audience'] : 'All';
        $priority = in_array($_POST['priority'] ?? '', ['Low', 'Normal', 'High', 'Urgent']) ? $_POST['priority'] : 'Normal';

        if (empty($title) || empty($content)) {
            setFlash('danger', 'Please provide both title and content.');
            redirect(baseUrl('admin/announcements.php'));
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO announcements (title, content, target_audience, priority, is_active, published_at, created_by, created_at)
                VALUES (?, ?, ?, ?, 1, NOW(), ?, NOW())
            ");
            $stmt->execute([$title, $content, $audience, $priority, $currentAdmin['id']]);

            logAdminAction($pdo, $currentAdmin['id'], 'Create Announcement', 'announcements', (int)$pdo->lastInsertId());
            setFlash('success', 'Announcement broadcasted successfully.');
            redirect(baseUrl('admin/announcements.php'));
        } catch (PDOException $e) {
            setFlash('danger', 'Error: ' . $e->getMessage());
            redirect(baseUrl('admin/announcements.php'));
        }
    }

    if ($action === 'delete_announcement') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([$id]);
            logAdminAction($pdo, $currentAdmin['id'], 'Delete Announcement', 'announcements', $id);
            setFlash('success', 'Announcement removed.');
            redirect(baseUrl('admin/announcements.php'));
        } catch (PDOException $e) {
            setFlash('danger', 'Error: ' . $e->getMessage());
            redirect(baseUrl('admin/announcements.php'));
        }
    }
}

// Fetch announcements
$announcements = $pdo->query("
    SELECT a.*, adm.full_name AS creator_name 
    FROM announcements a
    LEFT JOIN admins adm ON a.created_by = adm.id
    ORDER BY a.created_at DESC
")->fetchAll();
?>

<!-- ═══════════════════ HEADER CONTROLS ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Announcements & Notices</h4>
        <p class="text-muted small mb-0">Publish important updates, schedule changes, and notices to interns and staff.</p>
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createNoticeModal">
        <i class="bi bi-megaphone-fill me-2"></i>New Announcement
    </button>
</div>

<!-- ═══════════════════ ANNOUNCEMENTS LIST ═══════════════════ -->
<div class="row g-4">
    <?php if (empty($announcements)): ?>
        <div class="col-12">
            <div class="panel-card p-5 text-center text-muted">
                <i class="bi bi-megaphone fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-bold">No Announcements Published</h5>
                <p class="small mb-0">Post announcements to inform interns about task deadlines, project reviews, or holiday schedules.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($announcements as $ann): ?>
            <div class="col-lg-6">
                <div class="panel-card h-100 d-flex flex-column">
                    <div class="panel-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-<?= ($ann['priority'] === 'Urgent') ? 'danger' : (($ann['priority'] === 'High') ? 'warning text-dark' : 'primary') ?>">
                                <?= e($ann['priority']) ?>
                            </span>
                            <span class="badge bg-light text-muted border">Audience: <?= e($ann['target_audience']) ?></span>
                        </div>
                        <small class="text-muted"><?= formatDate($ann['created_at']) ?></small>
                    </div>

                    <div class="panel-body flex-grow-1">
                        <h5 class="fw-bold text-dark mb-2"><?= e($ann['title']) ?></h5>
                        <p class="text-secondary small leading-relaxed mb-0">
                            <?= nl2br(e($ann['content'])) ?>
                        </p>
                    </div>

                    <div class="panel-footer d-flex justify-content-between align-items-center">
                        <small class="text-muted">By: <?= e($ann['creator_name'] ?? 'Admin') ?></small>
                        <form method="POST" action="<?= baseUrl('admin/announcements.php') ?>" class="d-inline" onsubmit="return confirm('Delete this announcement?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete_announcement">
                            <input type="hidden" name="id" value="<?= (int)$ann['id'] ?>">
                            <button type="submit" class="btn btn-action btn-action-danger" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ═══════════════════ CREATE ANNOUNCEMENT MODAL ═══════════════════ -->
<div class="modal fade" id="createNoticeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" action="<?= baseUrl('admin/announcements.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_announcement">

                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-megaphone-fill me-2 text-gold"></i>New Announcement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Announcement Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control form-control-lg" placeholder="e.g. Milestone 2 Code Review Schedule" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Target Audience</label>
                            <select name="target_audience" class="form-select">
                                <option value="All">All Users (Interns & Staff)</option>
                                <option value="Interns" selected>Interns Only</option>
                                <option value="Admins">Administrative Staff Only</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Priority Level</label>
                            <select name="priority" class="form-select">
                                <option value="Low">Low</option>
                                <option value="Normal" selected>Normal</option>
                                <option value="High">High</option>
                                <option value="Urgent">Urgent Notice</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Message Content <span class="text-danger">*</span></label>
                            <textarea name="content" rows="5" class="form-control" placeholder="Write full announcement details..." required></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Broadcast Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
