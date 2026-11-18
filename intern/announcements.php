<?php
/**
 * Intern Announcements Feed
 * Excellence Classes By Repswal
 */

$internPageTitle = 'Announcements';
$currentInternPage = 'announcements';

require_once __DIR__ . '/includes/header.php';

// Fetch active announcements
try {
    $stmt = $pdo->query("
        SELECT * FROM announcements 
        WHERE is_active = 1 AND target_audience IN ('All', 'Interns')
        ORDER BY created_at DESC
    ");
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    $announcements = [];
}
?>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Official Notices & Announcements</h4>
        <p class="text-muted small mb-0">Important updates, schedule announcements, and milestone reminders from institute administration.</p>
    </div>
</div>

<!-- ═══════════════════ FEED ═══════════════════ -->
<div class="row g-4">
    <?php if (empty($announcements)): ?>
        <div class="col-12">
            <div class="panel-card p-5 text-center text-muted">
                <i class="bi bi-megaphone fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-bold">No Announcements at this Time</h5>
                <p class="small mb-0">Check back regularly for announcements regarding code reviews and schedules.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($announcements as $an): ?>
            <div class="col-lg-6">
                <div class="panel-card h-100 d-flex flex-column">
                    <div class="panel-header d-flex justify-content-between align-items-center">
                        <span class="badge bg-<?= ($an['priority'] === 'Urgent') ? 'danger' : (($an['priority'] === 'High') ? 'warning text-dark' : 'primary') ?>">
                            <?= e($an['priority']) ?>
                        </span>
                        <small class="text-muted"><?= formatDate($an['created_at']) ?></small>
                    </div>

                    <div class="panel-body flex-grow-1">
                        <h5 class="fw-bold text-dark mb-2"><?= e($an['title']) ?></h5>
                        <p class="text-secondary small leading-relaxed mb-0">
                            <?= nl2br(e($an['content'])) ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
