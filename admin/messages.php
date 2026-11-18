<?php
/**
 * Contact Form Inquiries Management
 * Excellence Classes By Repswal
 */

$adminPageTitle = 'Contact Messages';
$currentAdminPage = 'messages';

require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Handle Mark Read / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid security token.');
        redirect(baseUrl('admin/messages.php'));
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'mark_read') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE contact_messages SET is_read = 1, read_by = ?, read_at = NOW() WHERE id = ?")->execute([$currentAdmin['id'], $id]);
        setFlash('success', 'Message marked as read.');
        redirect(baseUrl('admin/messages.php'));
    }

    if ($action === 'delete_message') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$id]);
        logAdminAction($pdo, $currentAdmin['id'], 'Delete Contact Message', 'contact_messages', $id);
        setFlash('success', 'Message deleted.');
        redirect(baseUrl('admin/messages.php'));
    }
}

// Fetch messages
$messages = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
?>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Contact Messages & Course Inquiries</h4>
        <p class="text-muted small mb-0">Direct messages submitted from the public website contact form.</p>
    </div>
</div>

<!-- ═══════════════════ MESSAGES TABLE ═══════════════════ -->
<div class="panel-card">
    <div class="panel-header">
        <h5 class="panel-title">
            <i class="bi bi-envelope-fill text-primary"></i> Inquiries (<?= count($messages) ?>)
        </h5>
    </div>

    <div class="panel-body p-0">
        <?php if (empty($messages)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-bold">No Messages Received</h5>
                <p class="small mb-0">Messages submitted via the public contact form will appear here.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Sender</th>
                            <th>Subject</th>
                            <th>Received On</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                            <tr class="<?= ((int)$msg['is_read'] === 0) ? 'fw-semibold bg-light' : '' ?>">
                                <td>#<?= (int)$msg['id'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($msg['name']) ?></div>
                                    <small class="text-muted"><?= e($msg['email']) ?><?php if (!empty($msg['phone'])): ?> • <?= e($msg['phone']) ?><?php endif; ?></small>
                                </td>
                                <td>
                                    <div class="text-dark"><?= e($msg['subject']) ?></div>
                                    <small class="text-muted"><?= e(truncate($msg['message'], 60)) ?></small>
                                </td>
                                <td>
                                    <div class="small"><?= formatDate($msg['created_at']) ?></div>
                                    <small class="text-muted"><?= date('h:i A', strtotime($msg['created_at'])) ?></small>
                                </td>
                                <td>
                                    <?php if ((int)$msg['is_read'] === 1): ?>
                                        <span class="badge bg-light text-muted border">Read</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary">New</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-action" data-bs-toggle="modal" data-bs-target="#viewMsgModal<?= (int)$msg['id'] ?>" title="Read Message">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <form method="POST" action="<?= baseUrl('admin/messages.php') ?>" class="d-inline" onsubmit="return confirm('Delete message?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="delete_message">
                                            <input type="hidden" name="id" value="<?= (int)$msg['id'] ?>">
                                            <button type="submit" class="btn btn-action btn-action-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <!-- View Message Modal -->
                            <div class="modal fade" id="viewMsgModal<?= (int)$msg['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg">
                                        <div class="modal-header bg-navy text-white">
                                            <h5 class="modal-title fw-bold">Message from <?= e($msg['name']) ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="p-3 bg-light rounded-3 mb-3 small">
                                                <div><strong>Email:</strong> <a href="mailto:<?= e($msg['email']) ?>"><?= e($msg['email']) ?></a></div>
                                                <?php if (!empty($msg['phone'])): ?>
                                                    <div><strong>Phone:</strong> <a href="tel:<?= e($msg['phone']) ?>"><?= e($msg['phone']) ?></a></div>
                                                <?php endif; ?>
                                                <div><strong>Date:</strong> <?= formatDateTime($msg['created_at']) ?></div>
                                                <div><strong>Subject:</strong> <?= e($msg['subject']) ?></div>
                                            </div>

                                            <label class="form-label fw-bold small text-dark">Message Content:</label>
                                            <div class="p-3 bg-white border rounded text-secondary leading-relaxed small">
                                                <?= nl2br(e($msg['message'])) ?>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light justify-content-between">
                                            <?php if ((int)$msg['is_read'] === 0): ?>
                                                <form method="POST" action="<?= baseUrl('admin/messages.php') ?>">
                                                    <?= csrfField() ?>
                                                    <input type="hidden" name="action" value="mark_read">
                                                    <input type="hidden" name="id" value="<?= (int)$msg['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-check2 me-1"></i>Mark Read
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <div></div>
                                            <?php endif; ?>
                                            <a href="mailto:<?= e($msg['email']) ?>?subject=Re: <?= urlencode($msg['subject']) ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-reply me-1"></i>Reply by Email
                                            </a>
                                        </div>
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
