<?php
/**
 * Audit Logs
 * Excellence Classes By Repswal
 */

$adminPageTitle = 'Audit Logs';
$currentAdminPage = 'audit-logs';

require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

$tab = $_GET['tab'] ?? 'admin';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

if ($tab === 'certificates') {
    // Certificate Audit Logs
    $count = (int)$pdo->query("SELECT COUNT(*) FROM certificate_audit_logs")->fetchColumn();
    $pagination = paginate($count, $perPage, $page);

    $logs = $pdo->query("
        SELECT cl.*, c.certificate_id AS cert_number, c.intern_name,
               adm.full_name AS admin_name
        FROM certificate_audit_logs cl
        JOIN certificates c ON cl.certificate_id = c.id
        LEFT JOIN admins adm ON cl.performed_by = adm.id
        ORDER BY cl.created_at DESC
        LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
    ")->fetchAll();

} else {
    // Admin Audit Logs
    $count = (int)$pdo->query("SELECT COUNT(*) FROM admin_audit_logs")->fetchColumn();
    $pagination = paginate($count, $perPage, $page);

    $logs = $pdo->query("
        SELECT al.*, adm.full_name AS admin_name, adm.email AS admin_email
        FROM admin_audit_logs al
        LEFT JOIN admins adm ON al.admin_id = adm.id
        ORDER BY al.created_at DESC
        LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
    ")->fetchAll();
}
?>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Cryptographic & Security Audit Trail</h4>
        <p class="text-muted small mb-0">Immutable records of administrative operations, certificate issuance, status changes, and IP addresses.</p>
    </div>
</div>

<!-- ═══════════════════ TABS ═══════════════════ -->
<ul class="nav nav-pills mb-4">
    <li class="nav-item">
        <a class="nav-link <?= ($tab !== 'certificates') ? 'active' : '' ?>" href="<?= baseUrl('admin/audit-logs.php?tab=admin') ?>">
            <i class="bi bi-shield-lock me-1"></i>Administrative Actions
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($tab === 'certificates') ? 'active' : '' ?>" href="<?= baseUrl('admin/audit-logs.php?tab=certificates') ?>">
            <i class="bi bi-patch-check me-1"></i>Certificate Verification & Issuance Logs
        </a>
    </li>
</ul>

<!-- ═══════════════════ AUDIT LOGS TABLE ═══════════════════ -->
<div class="panel-card">
    <div class="panel-body p-0">
        <?php if ($tab === 'certificates'): ?>
            <!-- Certificate Logs -->
            <?php if (empty($logs)): ?>
                <div class="p-5 text-center text-muted">No certificate audit events recorded yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Certificate</th>
                                <th>Recipient</th>
                                <th>Action</th>
                                <th>Performer</th>
                                <th>IP Address</th>
                                <th>Details / Reason</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $l): ?>
                                <tr>
                                    <td>#<?= (int)$l['id'] ?></td>
                                    <td>
                                        <a href="<?= baseUrl('verify-certificate.php?id=' . urlencode($l['cert_number'])) ?>" target="_blank" class="fw-bold font-monospace">
                                            <?= e($l['cert_number']) ?>
                                        </a>
                                    </td>
                                    <td><?= e($l['intern_name']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= ($l['action'] === 'Revoked') ? 'danger' : (($l['action'] === 'Issued') ? 'success' : 'info') ?>">
                                            <?= e($l['action']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="small"><?= e($l['admin_name'] ?? $l['performer_type']) ?></span>
                                    </td>
                                    <td><code class="small text-muted"><?= e($l['ip_address'] ?? '—') ?></code></td>
                                    <td><small class="text-secondary"><?= e($l['details'] ?? '—') ?></small></td>
                                    <td><small class="text-muted"><?= formatDateTime($l['created_at']) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">
                    <?= renderPagination($pagination, baseUrl('admin/audit-logs.php?tab=certificates&')) ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Administrative Logs -->
            <?php if (empty($logs)): ?>
                <div class="p-5 text-center text-muted">No administrative audit events recorded yet.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Administrator</th>
                                <th>Action Performed</th>
                                <th>Target Entity</th>
                                <th>IP Address</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $al): ?>
                                <tr>
                                    <td>#<?= (int)$al['id'] ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($al['admin_name'] ?? 'System') ?></div>
                                        <small class="text-muted"><?= e($al['admin_email'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary-subtle text-primary fw-semibold"><?= e($al['action']) ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($al['entity_type'])): ?>
                                            <span class="small fw-semibold"><?= e($al['entity_type']) ?></span>
                                            <?php if (!empty($al['entity_id'])): ?>
                                                <code class="text-muted">#<?= (int)$al['entity_id'] ?></code>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code class="small text-muted"><?= e($al['ip_address'] ?? '—') ?></code></td>
                                    <td><small class="text-muted"><?= formatDateTime($al['created_at']) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top">
                    <?= renderPagination($pagination, baseUrl('admin/audit-logs.php?tab=admin&')) ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
