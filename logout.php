<?php
/**
 * Logout Handler
 * Excellence Classes By Repswal
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

initSession();

$wasAdmin = isAdminLoggedIn();

logout();

setFlash('info', 'You have been logged out successfully.');

if ($wasAdmin) {
    redirect(baseUrl('admin/login.php'));
} else {
    redirect(baseUrl('login.php'));
}
