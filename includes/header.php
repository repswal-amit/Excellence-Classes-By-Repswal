<?php
/**
 * Public Header Template
 * Excellence Classes By Repswal
 */

ob_start();

// Initialize core
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/validation.php';

initSession();

// Determine page-specific meta
$pageTitle = isset($pageTitle) ? $pageTitle . ' | ' . APP_NAME : APP_NAME . ' — ' . APP_TAGLINE;
$pageDescription = $pageDescription ?? getSetting('site_description', 'Excellence Classes By Repswal offers quality education, IT training programs, and internship opportunities in Nawalgarh, Rajasthan.');
$pageKeywords = $pageKeywords ?? getSetting('meta_keywords', 'excellence classes, repswal, IT training, internship');
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <!-- SEO Meta Tags -->
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="keywords" content="<?= e($pageKeywords) ?>">
    <meta name="author" content="Excellence Classes By Repswal">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e(baseUrl()) ?>">
    <meta property="og:site_name" content="<?= e(APP_NAME) ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= baseUrl('assets/images/favicon.ico') ?>">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= baseUrl('assets/css/style.css') ?>" rel="stylesheet">
    
    <?php if (isset($extraCSS)): ?>
        <?php foreach ((array)$extraCSS as $css): ?>
            <link href="<?= baseUrl('assets/css/' . $css) ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Skip to Content -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg fixed-top" id="mainNavbar">
        <div class="container">
            <!-- Brand -->
            <a class="navbar-brand" href="<?= baseUrl() ?>">
                <div class="brand-logo">
                    <span class="brand-icon"><i class="bi bi-mortarboard-fill"></i></span>
                    <div class="brand-text">
                        <span class="brand-name">Excellence Classes</span>
                        <span class="brand-sub">By Repswal</span>
                    </div>
                </div>
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Nav Links -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'index' ? 'active' : '' ?>" href="<?= baseUrl() ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'about' ? 'active' : '' ?>" href="<?= baseUrl('about.php') ?>">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'courses' ? 'active' : '' ?>" href="<?= baseUrl('courses.php') ?>">Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'internships' ? 'active' : '' ?>" href="<?= baseUrl('internships.php') ?>">Internships</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'contact' ? 'active' : '' ?>" href="<?= baseUrl('contact.php') ?>">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'verify-certificate' ? 'active' : '' ?>" href="<?= baseUrl('verify-certificate.php') ?>">Verify Certificate</a>
                    </li>
                    
                    <!-- Theme Toggle -->
                    <li class="nav-item ms-lg-2">
                        <button class="btn btn-link nav-link theme-toggle" id="themeToggle" title="Toggle theme" aria-label="Toggle dark mode">
                            <i class="bi bi-moon-fill"></i>
                        </button>
                    </li>
                    
                    <!-- Auth Buttons -->
                    <li class="nav-item ms-lg-2">
                        <?php if (isInternLoggedIn()): ?>
                            <a class="btn btn-primary btn-nav" href="<?= baseUrl('intern/dashboard.php') ?>">
                                <i class="bi bi-speedometer2 me-1"></i> Dashboard
                            </a>
                        <?php elseif (isAdminLoggedIn()): ?>
                            <a class="btn btn-primary btn-nav" href="<?= baseUrl('admin/dashboard.php') ?>">
                                <i class="bi bi-speedometer2 me-1"></i> Admin
                            </a>
                        <?php else: ?>
                            <a class="btn btn-primary btn-nav" href="<?= baseUrl('login.php') ?>">
                                <i class="bi bi-box-arrow-in-right me-1"></i> Login
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main id="main-content">
        <?php $flash = displayFlash(); if (!empty($flash)): ?>
            <div class="container mt-3" style="padding-top: 80px;">
                <?= $flash ?>
            </div>
        <?php endif; ?>
