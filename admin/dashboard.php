<?php
/**
 * Admin Dashboard
 * Excellence Classes By Repswal
 */

$adminPageTitle = 'Dashboard Overview';
$currentAdminPage = 'dashboard';

require_once __DIR__ . '/includes/header.php';

// Fetch Live Statistics
try {
    $totalInterns = (int)$pdo->query("SELECT COUNT(*) FROM interns")->fetchColumn();
    $pendingApps = (int)$pdo->query("SELECT COUNT(*) FROM internship_applications WHERE application_status = 'Pending'")->fetchColumn();
    $approvedApps = (int)$pdo->query("SELECT COUNT(*) FROM internship_applications WHERE application_status = 'Approved'")->fetchColumn();
    $activeInternships = (int)$pdo->query("SELECT COUNT(*) FROM internship_enrollments WHERE internship_status = 'In Progress'")->fetchColumn();
    $completedInternships = (int)$pdo->query("SELECT COUNT(*) FROM internship_enrollments WHERE internship_status = 'Completed'")->fetchColumn();
    $issuedCerts = (int)$pdo->query("SELECT COUNT(*) FROM certificates WHERE certificate_status = 'Issued'")->fetchColumn();
    $totalCourses = (int)$pdo->query("SELECT COUNT(*) FROM courses WHERE is_active = 1")->fetchColumn();
    $totalPrograms = (int)$pdo->query("SELECT COUNT(*) FROM internship_programs WHERE is_active = 1 AND is_archived = 0")->fetchColumn();

    // Recent Applications
    $recentAppsStmt = $pdo->query("
        SELECT a.id, a.application_status, a.created_at, i.full_name, i.email, i.college_name, p.title AS program_title
        FROM internship_applications a
        JOIN interns i ON a.intern_id = i.id
        JOIN internship_programs p ON a.program_id = p.id
        ORDER BY a.created_at DESC
        LIMIT 6
    ");
    $recentApplications = $recentAppsStmt->fetchAll();

    // Recent Submissions
    $recentSubsStmt = $pdo->query("
        SELECT s.id, s.submission_status, s.submitted_at, s.project_link, t.title AS task_title, i.full_name AS intern_name
        FROM task_submissions s
        JOIN tasks t ON s.task_id = t.id
        JOIN interns i ON s.intern_id = i.id
        ORDER BY s.submitted_at DESC
        LIMIT 6
    ");
    $recentSubmissions = $recentSubsStmt->fetchAll();

    // Applications count by program for Chart.js
    $chartStmt = $pdo->query("
        SELECT p.title, COUNT(a.id) as app_count
        FROM internship_programs p
        LEFT JOIN internship_applications a ON p.id = a.program_id
        WHERE p.is_active = 1
        GROUP BY p.id
        ORDER BY app_count DESC
        LIMIT 6
    ");
    $chartData = $chartStmt->fetchAll();

} catch (PDOException $e) {
    $totalInterns = $pendingApps = $approvedApps = $activeInternships = $completedInternships = $issuedCerts = $totalCourses = $totalPrograms = 0;
    $recentApplications = [];
    $recentSubmissions = [];
    $chartData = [];
}
?>

<!-- ═══════════════════ PROPRIETOR BANNER ═══════════════════ -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm p-4 text-white" style="background: linear-gradient(135deg, var(--navy), var(--navy-mid)); border-radius: 16px;">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <span class="badge bg-gold text-dark px-3 py-1 rounded-pill fw-bold">Official Proprietorship Portal</span>
                        <span class="text-white-50 small"><i class="bi bi-geo-alt-fill text-gold me-1"></i>Nawalgarh, Rajasthan</span>
                    </div>
                    <h2 class="fw-bold mb-1">Welcome, <?= e($currentAdmin['name']) ?></h2>
                    <p class="text-white-50 mb-0 small">
                        Full administrative authority enabled for <strong>Excellence Classes By Repswal</strong>. Manage interns, approve applications, oversee tasks, and issue tamper-proof certificates.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <div class="d-inline-flex flex-wrap gap-2">
                        <a href="<?= baseUrl('admin/applications.php') ?>" class="btn btn-gold btn-sm rounded-pill px-3">
                            <i class="bi bi-check2-circle me-1"></i>Review Applications (<?= $pendingApps ?>)
                        </a>
                        <a href="<?= baseUrl('admin/certificates.php') ?>" class="btn btn-outline-light btn-sm rounded-pill px-3">
                            <i class="bi bi-patch-check me-1"></i>Certificates
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════ PRIMARY STAT METRIC CARDS ═══════════════════ -->
<div class="row g-3 mb-4">
    <!-- Total Registered Interns -->
    <div class="col-xl-3 col-md-6">
        <div class="metric-card">
            <div class="metric-info">
                <div class="metric-label">Registered Interns</div>
                <div class="metric-value"><?= number_format($totalInterns) ?></div>
                <div class="metric-meta text-muted">
                    <i class="bi bi-people-fill text-primary"></i> Total student accounts
                </div>
            </div>
            <div class="metric-icon royal">
                <i class="bi bi-people"></i>
            </div>
        </div>
    </div>

    <!-- Pending Applications -->
    <div class="col-xl-3 col-md-6">
        <div class="metric-card">
            <div class="metric-info">
                <div class="metric-label">Pending Applications</div>
                <div class="metric-value text-warning"><?= number_format($pendingApps) ?></div>
                <div class="metric-meta text-warning fw-semibold">
                    <i class="bi bi-hourglass-split"></i> Awaiting approval
                </div>
            </div>
            <div class="metric-icon orange">
                <i class="bi bi-file-earmark-person"></i>
            </div>
        </div>
    </div>

    <!-- Active Internships -->
    <div class="col-xl-3 col-md-6">
        <div class="metric-card">
            <div class="metric-info">
                <div class="metric-label">Active Internships</div>
                <div class="metric-value text-primary"><?= number_format($activeInternships) ?></div>
                <div class="metric-meta text-muted">
                    <i class="bi bi-activity text-success"></i> Currently in progress
                </div>
            </div>
            <div class="metric-icon green">
                <i class="bi bi-briefcase"></i>
            </div>
        </div>
    </div>

    <!-- Certificates Issued -->
    <div class="col-xl-3 col-md-6">
        <div class="metric-card">
            <div class="metric-info">
                <div class="metric-label">Certificates Issued</div>
                <div class="metric-value text-success"><?= number_format($issuedCerts) ?></div>
                <div class="metric-meta text-success">
                    <i class="bi bi-patch-check-fill text-gold"></i> QR Verified Records
                </div>
            </div>
            <div class="metric-icon gold">
                <i class="bi bi-award-fill"></i>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════ SECONDARY METRICS ═══════════════════ -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm p-3 text-center">
            <span class="text-muted small">Approved Applications</span>
            <div class="fs-4 fw-bold text-dark mt-1"><?= $approvedApps ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm p-3 text-center">
            <span class="text-muted small">Completed Internships</span>
            <div class="fs-4 fw-bold text-dark mt-1"><?= $completedInternships ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm p-3 text-center">
            <span class="text-muted small">Active Programs</span>
            <div class="fs-4 fw-bold text-dark mt-1"><?= $totalPrograms ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm p-3 text-center">
            <span class="text-muted small">Active Courses</span>
            <div class="fs-4 fw-bold text-dark mt-1"><?= $totalCourses ?></div>
        </div>
    </div>
</div>

<!-- ═══════════════════ ANALYTICS & QUICK ACTIONS ═══════════════════ -->
<div class="row g-4 mb-4">
    <!-- Chart Column -->
    <div class="col-lg-8">
        <div class="panel-card h-100">
            <div class="panel-header">
                <h5 class="panel-title">
                    <i class="bi bi-bar-chart-fill text-primary"></i> Applications by Internship Program
                </h5>
                <a href="<?= baseUrl('admin/applications.php') ?>" class="btn btn-outline-primary btn-sm rounded-pill">View All</a>
            </div>
            <div class="panel-body">
                <div style="height: 280px; position: relative;">
                    <canvas id="applicationsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Navigation / Module Shortcuts -->
    <div class="col-lg-4">
        <div class="panel-card h-100">
            <div class="panel-header">
                <h5 class="panel-title">
                    <i class="bi bi-lightning-charge-fill text-gold"></i> Quick Actions
                </h5>
            </div>
            <div class="panel-body d-flex flex-column gap-2">
                <a href="<?= baseUrl('admin/applications.php?status=Pending') ?>" class="btn btn-outline-warning text-dark text-start d-flex align-items-center justify-content-between p-3 rounded-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-inbox-fill text-warning fs-5"></i>
                        <span>Pending Applications</span>
                    </div>
                    <span class="badge bg-warning text-dark"><?= $pendingApps ?></span>
                </a>

                <a href="<?= baseUrl('admin/submissions.php?status=Pending') ?>" class="btn btn-outline-primary text-start d-flex align-items-center justify-content-between p-3 rounded-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-code-slash text-primary fs-5"></i>
                        <span>Review Submissions</span>
                    </div>
                    <span class="badge bg-danger"><?= $pendingSubsCount ?></span>
                </a>

                <a href="<?= baseUrl('admin/tasks.php?action=create') ?>" class="btn btn-outline-secondary text-start d-flex align-items-center justify-content-between p-3 rounded-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-plus-circle-fill text-success fs-5"></i>
                        <span>Assign New Task</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>

                <a href="<?= baseUrl('admin/certificates.php') ?>" class="btn btn-outline-dark text-start d-flex align-items-center justify-content-between p-3 rounded-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-patch-check-fill text-gold fs-5"></i>
                        <span>Issue Completion Certificate</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>

                <a href="<?= baseUrl('admin/announcements.php') ?>" class="btn btn-outline-secondary text-start d-flex align-items-center justify-content-between p-3 rounded-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-megaphone-fill text-info fs-5"></i>
                        <span>Broadcast Announcement</span>
                    </div>
                    <i class="bi bi-chevron-right text-muted"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════ RECENT TABLES ═══════════════════ -->
<div class="row g-4">
    <!-- Recent Applications -->
    <div class="col-lg-6">
        <div class="panel-card">
            <div class="panel-header">
                <h5 class="panel-title">
                    <i class="bi bi-person-lines-fill text-primary"></i> Recent Applications
                </h5>
                <a href="<?= baseUrl('admin/applications.php') ?>" class="small text-decoration-none">Manage All</a>
            </div>
            <div class="panel-body p-0">
                <?php if (empty($recentApplications)): ?>
                    <div class="p-4 text-center text-muted small">No applications received yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Candidate</th>
                                    <th>Program</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentApplications as $app): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($app['full_name']) ?></div>
                                            <small class="text-muted"><?= e($app['college_name']) ?></small>
                                        </td>
                                        <td>
                                            <div class="small fw-medium"><?= e($app['program_title']) ?></div>
                                            <small class="text-muted"><?= formatDate($app['created_at']) ?></small>
                                        </td>
                                        <td>
                                            <?= statusBadge($app['application_status']) ?>
                                        </td>
                                        <td>
                                            <a href="<?= baseUrl('admin/applications.php?id=' . (int)$app['id']) ?>" class="btn btn-action" title="Review">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Task Submissions -->
    <div class="col-lg-6">
        <div class="panel-card">
            <div class="panel-header">
                <h5 class="panel-title">
                    <i class="bi bi-clipboard2-check text-primary"></i> Recent Submissions
                </h5>
                <a href="<?= baseUrl('admin/submissions.php') ?>" class="small text-decoration-none">Manage All</a>
            </div>
            <div class="panel-body p-0">
                <?php if (empty($recentSubmissions)): ?>
                    <div class="p-4 text-center text-muted small">No task submissions recorded yet.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Intern</th>
                                    <th>Task</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentSubmissions as $sub): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($sub['intern_name']) ?></div>
                                            <small class="text-muted"><?= formatDate($sub['submitted_at']) ?></small>
                                        </td>
                                        <td>
                                            <div class="small text-truncate" style="max-width: 160px;"><?= e($sub['task_title']) ?></div>
                                            <?php if (!empty($sub['project_link'])): ?>
                                                <a href="<?= e($sub['project_link']) ?>" target="_blank" class="small text-primary"><i class="bi bi-link-45deg"></i> Link</a>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= statusBadge($sub['submission_status']) ?>
                                        </td>
                                        <td>
                                            <a href="<?= baseUrl('admin/submissions.php?id=' . (int)$sub['id']) ?>" class="btn btn-action" title="Grade">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart initialization script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('applicationsChart');
    if (ctx) {
        <?php
            $labels = [];
            $counts = [];
            foreach ($chartData as $cd) {
                $labels[] = $cd['title'];
                $counts[] = (int)$cd['app_count'];
            }
        ?>
        const labels = <?= json_encode($labels) ?>;
        const data = <?= json_encode($counts) ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels.length ? labels : ['Web Dev', 'Python', 'Java', 'Full Stack', 'App Dev'],
                datasets: [{
                    label: 'Applications',
                    data: data.length ? data : [0, 0, 0, 0, 0],
                    backgroundColor: 'rgba(26, 86, 219, 0.75)',
                    borderColor: '#1a56db',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
