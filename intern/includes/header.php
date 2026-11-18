<?php
/**
 * Intern Dashboard Header & Sidebar
 * Excellence Classes By Repswal
 */

ob_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/validation.php';

initSession();
requireInternLogin();

$internId = (int)$_SESSION['intern_id'];
$internPageTitle = $internPageTitle ?? 'Intern Portal';
$currentInternPage = $currentInternPage ?? basename($_SERVER['PHP_SELF'], '.php');

$pdo = getDBConnection();

// Fetch intern profile
try {
    $internStmt = $pdo->prepare("SELECT * FROM interns WHERE id = ?");
    $internStmt->execute([$internId]);
    $internProfile = $internStmt->fetch();

    if (!$internProfile || (int)$internProfile['is_active'] !== 1) {
        logout();
        setFlash('danger', 'Your account is inactive. Please contact administration.');
        redirect(baseUrl('login.php'));
    }

    // Pending tasks count
    $pendingTasksCount = (int)$pdo->prepare("
        SELECT COUNT(t.id) 
        FROM tasks t
        JOIN internship_enrollments e ON t.enrollment_id = e.id
        LEFT JOIN task_submissions s ON t.id = s.task_id AND s.intern_id = ?
        WHERE e.intern_id = ? AND (s.id IS NULL OR s.submission_status IN ('Pending', 'Resubmit'))
    ")->execute([$internId, $internId]) ? $pdo->query("
        SELECT COUNT(t.id) 
        FROM tasks t
        JOIN internship_enrollments e ON t.enrollment_id = e.id
        LEFT JOIN task_submissions s ON t.id = s.task_id AND s.intern_id = {$internId}
        WHERE e.intern_id = {$internId} AND (s.id IS NULL OR s.submission_status IN ('Resubmit'))
    ")->fetchColumn() : 0;

} catch (PDOException $e) {
    $internProfile = [
        'full_name' => $_SESSION['intern_name'] ?? 'Intern',
        'email' => $_SESSION['intern_email'] ?? ''
    ];
    $pendingTasksCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= e($internPageTitle) ?> | Intern Portal — Excellence Classes By Repswal</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= baseUrl('assets/css/style.css') ?>" rel="stylesheet">
    <link href="<?= baseUrl('assets/css/dashboard.css') ?>" rel="stylesheet">
</head>
<body>

<div class="dashboard-wrapper">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ═══════════════════ INTERN SIDEBAR ═══════════════════ -->
    <aside class="sidebar" id="internSidebar">
        <!-- Sidebar Brand -->
        <div class="sidebar-header">
            <a href="<?= baseUrl('intern/dashboard.php') ?>" class="sidebar-brand">
                <div class="sidebar-brand-icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div class="sidebar-brand-text">
                    <div class="sidebar-brand-title">Excellence Classes</div>
                    <div class="sidebar-brand-sub">Intern Portal</div>
                </div>
            </a>
        </div>

        <!-- Sidebar User Badge -->
        <div class="sidebar-user">
            <div class="sidebar-user-avatar">
                <?= strtoupper(substr($internProfile['full_name'] ?? 'I', 0, 1)) ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name" title="<?= e($internProfile['full_name']) ?>"><?= e($internProfile['full_name']) ?></div>
                <div class="sidebar-user-role">Intern</div>
            </div>
        </div>

        <!-- Sidebar Navigation -->
        <ul class="sidebar-nav">
            <li class="nav-section-title">Overview</li>
            
            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentInternPage === 'dashboard') ? 'active' : '' ?>" href="<?= baseUrl('intern/dashboard.php') ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentInternPage === 'applications') ? 'active' : '' ?>" href="<?= baseUrl('intern/applications.php') ?>">
                    <i class="bi bi-file-earmark-person"></i>
                    <span>My Applications</span>
                </a>
            </li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentInternPage === 'internship') ? 'active' : '' ?>" href="<?= baseUrl('intern/internship.php') ?>">
                    <i class="bi bi-briefcase"></i>
                    <span>My Internship Track</span>
                </a>
            </li>

            <li class="nav-section-title">Assignments & Submissions</li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentInternPage === 'tasks') ? 'active' : '' ?>" href="<?= baseUrl('intern/tasks.php') ?>">
                    <i class="bi bi-list-task"></i>
                    <span>My Tasks</span>
                    <?php if ($pendingTasksCount > 0): ?>
                        <span class="badge bg-warning text-dark"><?= $pendingTasksCount ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentInternPage === 'submissions') ? 'active' : '' ?>" href="<?= baseUrl('intern/submissions.php') ?>">
                    <i class="bi bi-clipboard2-check"></i>
                    <span>Submissions & Feedback</span>
                </a>
            </li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentInternPage === 'announcements') ? 'active' : '' ?>" href="<?= baseUrl('intern/announcements.php') ?>">
                    <i class="bi bi-megaphone"></i>
                    <span>Announcements</span>
                </a>
            </li>

            <li class="nav-section-title">Credentials</li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentInternPage === 'documents') ? 'active' : '' ?>" href="<?= baseUrl('intern/documents.php') ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Offer & Joining Letters</span>
                </a>
            </li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentInternPage === 'certificates') ? 'active' : '' ?>" href="<?= baseUrl('intern/certificates.php') ?>">
                    <i class="bi bi-patch-check-fill text-gold"></i>
                    <span>My Certificates</span>
                </a>
            </li>

            <li class="nav-section-title">Account</li>

            <li class="sidebar-nav-item">
                <a class="sidebar-nav-link <?= ($currentInternPage === 'profile') ? 'active' : '' ?>" href="<?= baseUrl('intern/profile.php') ?>">
                    <i class="bi bi-person-gear"></i>
                    <span>Profile & Password</span>
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
                <h1 class="topbar-title"><?= e($internPageTitle) ?></h1>
            </div>

            <div class="topbar-right">
                <a href="<?= baseUrl('apply.php') ?>" class="btn btn-outline-primary btn-sm rounded-pill d-none d-sm-inline-flex align-items-center gap-1">
                    <i class="bi bi-plus-circle"></i> New Application
                </a>

                <a href="<?= baseUrl() ?>" target="_blank" class="topbar-btn" title="View Public Website">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>

                <button class="topbar-btn" id="themeToggle" title="Toggle Light/Dark Theme">
                    <i class="bi bi-moon-fill"></i>
                </button>

                <div class="dropdown">
                    <div class="topbar-user" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="topbar-avatar">
                            <?= strtoupper(substr($internProfile['full_name'] ?? 'I', 0, 1)) ?>
                        </div>
                        <div class="d-none d-md-block text-start">
                            <div class="topbar-user-name"><?= e($internProfile['full_name']) ?></div>
                            <div class="topbar-user-sub"><?= e($internProfile['email']) ?></div>
                        </div>
                        <i class="bi bi-chevron-down text-muted small ms-1"></i>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><h6 class="dropdown-header">Signed in as <strong><?= e($internProfile['full_name']) ?></strong></h6></li>
                        <li><a class="dropdown-item" href="<?= baseUrl('intern/profile.php') ?>"><i class="bi bi-person me-2"></i>My Profile</a></li>
                        <li><a class="dropdown-item" href="<?= baseUrl('intern/certificates.php') ?>"><i class="bi bi-patch-check me-2"></i>Certificates</a></li>
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
