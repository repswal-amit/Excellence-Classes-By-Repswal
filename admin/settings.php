<?php
/**
 * Website Settings Management
 * Excellence Classes By Repswal
 */

$adminPageTitle = 'Website Settings';
$currentAdminPage = 'settings';

require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid security token.');
        redirect(baseUrl('admin/settings.php'));
    }

    $settingsToUpdate = [
        'site_name' => trim($_POST['site_name'] ?? 'Excellence Classes By Repswal'),
        'site_tagline' => trim($_POST['site_tagline'] ?? 'Learn. Develop. Grow.'),
        'site_description' => trim($_POST['site_description'] ?? ''),
        'site_email' => trim($_POST['site_email'] ?? 'info@excellenceclasses.com'),
        'site_phone' => trim($_POST['site_phone'] ?? ''),
        'site_address' => trim($_POST['site_address'] ?? 'Nawalgarh, Jhunjhunu, Rajasthan, India - 333308'),
        'proprietor_name' => trim($_POST['proprietor_name'] ?? 'Amit Repswal'),
        'proprietor_designation' => trim($_POST['proprietor_designation'] ?? 'Proprietor'),
        'hero_title' => trim($_POST['hero_title'] ?? ''),
        'hero_subtitle' => trim($_POST['hero_subtitle'] ?? ''),
        'about_text' => trim($_POST['about_text'] ?? ''),
        'facebook_url' => trim($_POST['facebook_url'] ?? ''),
        'twitter_url' => trim($_POST['twitter_url'] ?? ''),
        'instagram_url' => trim($_POST['instagram_url'] ?? ''),
        'linkedin_url' => trim($_POST['linkedin_url'] ?? ''),
        'youtube_url' => trim($_POST['youtube_url'] ?? ''),
        'meta_keywords' => trim($_POST['meta_keywords'] ?? '')
    ];

    try {
        $pdo->beginTransaction();

        $upd = $pdo->prepare("
            INSERT INTO website_settings (setting_key, setting_value, updated_by, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by = VALUES(updated_by), updated_at = NOW()
        ");

        foreach ($settingsToUpdate as $key => $val) {
            $upd->execute([$key, $val, $currentAdmin['id']]);
        }

        $pdo->commit();

        logAdminAction($pdo, $currentAdmin['id'], 'Update Website Settings', 'website_settings');
        setFlash('success', 'Website settings updated successfully.');
        redirect(baseUrl('admin/settings.php'));

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        setFlash('danger', 'Database error: ' . $e->getMessage());
        redirect(baseUrl('admin/settings.php'));
    }
}

// Fetch current settings
$allSettings = [];
try {
    $rows = $pdo->query("SELECT setting_key, setting_value FROM website_settings")->fetchAll();
    foreach ($rows as $r) {
        $allSettings[$r['setting_key']] = $r['setting_value'];
    }
} catch (PDOException $e) {
    // Fail gracefully
}
?>

<!-- ═══════════════════ SETTINGS FORM ═══════════════════ -->
<form method="POST" action="<?= baseUrl('admin/settings.php') ?>" class="needs-validation" novalidate>
    <?= csrfField() ?>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">System & Website Configuration</h4>
            <p class="text-muted small mb-0">Control institutional branding, contact details, SEO meta tags, and homepage text.</p>
        </div>
        <button type="submit" name="save_settings" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
            <i class="bi bi-check2-circle me-1"></i>Save Settings
        </button>
    </div>

    <div class="row g-4">
        <!-- General Institutional Identity -->
        <div class="col-lg-6">
            <div class="panel-card h-100">
                <div class="panel-header">
                    <h5 class="panel-title"><i class="bi bi-building text-primary"></i> Institutional Identity</h5>
                </div>
                <div class="panel-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Institute / Organization Name</label>
                        <input type="text" name="site_name" class="form-control" value="<?= e($allSettings['site_name'] ?? 'Excellence Classes By Repswal') ?>" required>
                        <div class="form-text small">Sole Proprietorship educational services firm.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Institute Tagline</label>
                        <input type="text" name="site_tagline" class="form-control" value="<?= e($allSettings['site_tagline'] ?? 'Learn. Develop. Grow.') ?>" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Proprietor Full Name</label>
                            <input type="text" name="proprietor_name" class="form-control" value="<?= e($allSettings['proprietor_name'] ?? 'Amit Repswal') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Proprietor Designation</label>
                            <input type="text" name="proprietor_designation" class="form-control" value="<?= e($allSettings['proprietor_designation'] ?? 'Proprietor') ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Campus Address</label>
                        <textarea name="site_address" rows="3" class="form-control" required><?= e($allSettings['site_address'] ?? 'Nawalgarh, Jhunjhunu, Rajasthan, India - 333308') ?></textarea>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Official Email</label>
                            <input type="email" name="site_email" class="form-control" value="<?= e($allSettings['site_email'] ?? 'info@excellenceclasses.com') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Official Phone</label>
                            <input type="text" name="site_phone" class="form-control" value="<?= e($allSettings['site_phone'] ?? '') ?>" placeholder="+91 ...">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Homepage & SEO Content -->
        <div class="col-lg-6">
            <div class="panel-card h-100">
                <div class="panel-header">
                    <h5 class="panel-title"><i class="bi bi-window-fullscreen text-primary"></i> Homepage Content & SEO</h5>
                </div>
                <div class="panel-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hero Heading</label>
                        <input type="text" name="hero_title" class="form-control" value="<?= e($allSettings['hero_title'] ?? 'Build Your Future in Technology') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Hero Subtitle</label>
                        <textarea name="hero_subtitle" rows="2" class="form-control"><?= e($allSettings['hero_subtitle'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">About Section Brief</label>
                        <textarea name="about_text" rows="3" class="form-control"><?= e($allSettings['about_text'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Meta Description (SEO)</label>
                        <textarea name="site_description" rows="2" class="form-control"><?= e($allSettings['site_description'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="form-label fw-semibold">Meta Keywords</label>
                        <input type="text" name="meta_keywords" class="form-control" value="<?= e($allSettings['meta_keywords'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Media Channels -->
        <div class="col-12">
            <div class="panel-card">
                <div class="panel-header">
                    <h5 class="panel-title"><i class="bi bi-share text-primary"></i> Social Profiles & Outreach</h5>
                </div>
                <div class="panel-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold"><i class="bi bi-facebook text-primary me-1"></i>Facebook Page</label>
                            <input type="url" name="facebook_url" class="form-control" value="<?= e($allSettings['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold"><i class="bi bi-linkedin text-primary me-1"></i>LinkedIn Page</label>
                            <input type="url" name="linkedin_url" class="form-control" value="<?= e($allSettings['linkedin_url'] ?? '') ?>" placeholder="https://linkedin.com/company/...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold"><i class="bi bi-instagram text-danger me-1"></i>Instagram</label>
                            <input type="url" name="instagram_url" class="form-control" value="<?= e($allSettings['instagram_url'] ?? '') ?>" placeholder="https://instagram.com/...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold"><i class="bi bi-twitter-x me-1"></i>Twitter / X Profile</label>
                            <input type="url" name="twitter_url" class="form-control" value="<?= e($allSettings['twitter_url'] ?? '') ?>" placeholder="https://twitter.com/...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold"><i class="bi bi-youtube text-danger me-1"></i>YouTube Channel</label>
                            <input type="url" name="youtube_url" class="form-control" value="<?= e($allSettings['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/...">
                        </div>
                    </div>
                </div>
                <div class="panel-footer text-end">
                    <button type="submit" name="save_settings" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="bi bi-check2-circle me-1"></i>Save All Settings
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
