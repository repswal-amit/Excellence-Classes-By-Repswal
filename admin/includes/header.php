<?php
/**
 * Admin Panel Header
 * Excellence Classes By Repswal
 */

ob_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/validation.php';

initSession();
requireAdminLogin();

$currentAdmin = getCurrentAdmin();
$adminPageTitle = $adminPageTitle ?? 'Admin Dashboard';
$currentAdminPage = $currentAdminPage ?? basename($_SERVER['PHP_SELF'], '.php');

$pdo = getDBConnection();

// Fetch live counts for notification badges
$pendingAppsCount = 0;
$pendingSubsCount = 0;
$unreadMessagesCount = 0;

try {
    $pendingAppsCount = (int)$pdo->query("SELECT COUNT(*) FROM internship_applications WHERE application_status = 'Pending'")->fetchColumn();
    $pendingSubsCount = (int)$pdo->query("SELECT COUNT(*) FROM task_submissions WHERE submission_status = 'Pending'")->fetchColumn();
    $unreadMessagesCount = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
} catch (PDOException $e) {
    // Fail gracefully
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= e($adminPageTitle) ?> | Admin — Excellence Classes By Repswal</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= baseUrl('assets/css/style.css') ?>" rel="stylesheet">
    <link href="<?= baseUrl('assets/css/dashboard.css') ?>" rel="stylesheet">
    
    <!-- Chart.js (for analytics) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>
<body>

<div class="dashboard-wrapper">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ═══════════════════ ADMIN SIDEBAR ═══════════════════ -->
    <aside class="sidebar" id="adminSidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <a href="<?= baseUrl('admin/dashboard.php') ?>" class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div class="sidebar-brand-text">
                    <div class="sidebar-brand-title">Excellence Classes</div>
                    <div class="sidebar-brand-sub">Admin Portal</div>
                </div>
            </a>
        </div>

        <!-- Sidebar User Badge -->
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?= strtoupper(substr($currentAdmin['name'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name" title="<?= e($currentAdmin['name']) ?>"><?= e($currentAdmin['name']) ?></div>
                <div class="sidebar-user-role"><?= e($currentAdmin['designation'] ?: ucfirst(str_replace('_', ' ', $currentAdmin['role']))) ?></div>
            </div>
        </div>

        <!-- Sidebar Navigation Menu -->
        <ul class="sidebar-nav">
            <li class="nav-section-title">Core Management</li>
            
            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentAdminPage === 'dashboard') ? 'active' : '' ?>" href="<?= baseUrl('admin/dashboard.php') ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentAdminPage === 'applications') ? 'active' : '' ?>" href="<?= baseUrl('admin/applications.php') ?>">
                    <i class="bi bi-file-earmark-person"></i>
                    <span>Applications</span>
                    <?php if ($pendingAppsCount > 0): ?>
                        <span class="badge bg-warning text-dark"><?= $pendingAppsCount ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentAdminPage === 'interns') ? 'active' : '' ?>" href="<?= baseUrl('admin/interns.php') ?>">
                    <i class="bi bi-people-fill"></i>
                    <span>Interns</span>
                </a>
            </li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentAdminPage === 'programs') ? 'active' : '' ?>" href="<?= baseUrl('admin/programs.php') ?>">
                    <i class="bi bi-briefcase-fill"></i>
                    <span>Internship Programs</span>
                </a>
            </li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentAdminPage === 'courses') ? 'active' : '' ?>" href="<?= baseUrl('admin/courses.php') ?>">
                    <i class="bi bi-journal-code"></i>
                    <span>Training Courses</span>
                </a>
            </li>

            <li class="nav-section-title">Tasks & Progression</li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentAdminPage === 'tasks') ? 'active' : '' ?>" href="<?= baseUrl('admin/tasks.php') ?>">
                    <i class="bi bi-list-task"></i>
                    <span>Tasks Assigned</span>
                </a>
            </li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentAdminPage === 'submissions') ? 'active' : '' ?>" href="<?= baseUrl('admin/submissions.php') ?>">
                    <i class="bi bi-clipboard-check"></i>
                    <span>Submissions</span>
                    <?php if ($pendingSubsCount > 0): ?>
                        <span class="badge bg-danger"><?= $pendingSubsCount ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentAdminPage === 'progress') ? 'active' : '' ?>" href="<?= baseUrl('admin/progress.php') ?>">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Intern Progress</span>
                </a>
            </li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentAdminPage === 'announcements') ? 'active' : '' ?>" href="<?= baseUrl('admin/announcements.php') ?>">
                    <i class="bi bi-megaphone-fill"></i>
                    <span>Announcements</span>
                </a>
            </li>

            <li class="nav-section-title">Credentials & Documents</li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentAdminPage === 'documents') ? 'active' : '' ?>" href="<?= baseUrl('admin/documents.php') ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Offer & Joining Letters</span>
                </a>
            </li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentAdminPage === 'certificates') ? 'active' : '' ?>" href="<?= baseUrl('admin/certificates.php') ?>">
                    <i class="bi bi-patch-check-fill text-gold"></i>
                    <span>Certificates</span>
                </a>
            </li>

            <li class="nav-section-title">Administration</li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentAdminPage === 'messages') ? 'active' : '' ?>" href="<?= baseUrl('admin/messages.php') ?>">
                    <i class="bi bi-envelope"></i>
                    <span>Messages</span>
                    <?php if ($unreadMessagesCount > 0): ?>
                        <span class="badge bg-info text-dark"><?= $unreadMessagesCount ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentAdminPage === 'settings') ? 'active' : '' ?>" href="<?= baseUrl('admin/settings.php') ?>">
                    <i class="bi bi-sliders"></i>
                    <span>Website Settings</span>
                </a>
            </li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentAdminPage === 'audit-logs') ? 'active' : '' ?>" href="<?= baseUrl('admin/audit-logs.php') ?>">
                    <i class="bi bi-shield-check"></i>
                    <span>Audit Logs</span>
                </a>
            </li>

            <li class="sidebar-nav-item mt-3">
                <a class="sidebar-nav-link text-danger" href="<?= baseUrl('logout.php') ?>">
                    <i class="bi bi-box-arrow-right text-danger"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- ═══════════════════ MAIN CONTENT CONTAINER ═══════════════════ -->
    <div class="dashboard-main">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle-btn d-lg-none" id="sidebarToggle" aria-label="Toggle Navigation">
                    <i class="bi bi-list"></i>
                </button>
                <h1 class="topbar-title"><?= e($adminPageTitle) ?></h1>
            </div>

            <div class="topbar-right">
                <a href="<?= baseUrl() ?>" target="_blank" class="topbar-btn" title="View Public Website">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>

                <button class="topbar-btn" id="themeToggle" title="Toggle Light/Dark Theme">
                    <i class="bi bi-moon-fill"></i>
                </button>

                <div class="dropdown">
                    <div class="topbar-user" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="topbar-avatar">
                            <?= strtoupper(substr($currentAdmin['name'] ?? 'A', 0, 1)) ?>
                        </div>
                        <div class="d-none d-md-block text-start">
                            <div class="topbar-user-name"><?= e($currentAdmin['name']) ?></div>
                            <div class="topbar-user-sub"><?= e($currentAdmin['designation'] ?: 'Administrator') ?></div>
                        </div>
                        <i class="bi bi-chevron-down text-muted small ms-1"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><h6 class="dropdown-header">Signed in as <strong><?= e($currentAdmin['name']) ?></strong></h6></li>
                        <li><a class="dropdown-item" href="<?= baseUrl('admin/settings.php') ?>"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><a class="dropdown-item" href="<?= baseUrl('admin/audit-logs.php') ?>"><i class="bi bi-shield-check me-2"></i>Audit Logs</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= baseUrl('logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main class="dashboard-content">
            <?php $flash = displayFlash(); if (!empty($flash)): ?>
                <div class="mb-4"><?= $flash ?></div>
            <?php endif; ?>
