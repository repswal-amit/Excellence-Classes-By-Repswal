<?php
/**
 * Authentication Helper
 * Excellence Classes By Repswal
 * 
 * Handles session management and authentication checks.
 */

/**
 * Initialize secure session
 */
function initSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            ini_set('session.cookie_secure', '1');
        }
        
        session_name(SESSION_NAME);
        session_start();
    }
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['_last_regeneration'])) {
        $_SESSION['_last_regeneration'] = time();
    } elseif (time() - $_SESSION['_last_regeneration'] > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['_last_regeneration'] = time();
    }
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn(): bool {
    initSession();
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_role']);
}

/**
 * Check if intern is logged in
 */
function isInternLoggedIn(): bool {
    initSession();
    return isset($_SESSION['intern_id']);
}

/**
 * Require admin login - redirect if not authenticated
 */
function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        setFlash('warning', 'Please log in to access the admin dashboard.');
        redirect(baseUrl('admin/login.php'));
    }
}

/**
 * Require intern login - redirect if not authenticated
 */
function requireInternLogin(): void {
    if (!isInternLoggedIn()) {
        setFlash('warning', 'Please log in to access your dashboard.');
        redirect(baseUrl('login.php'));
    }
}

/**
 * Get current admin data
 */
function getCurrentAdmin(): ?array {
    if (!isAdminLoggedIn()) return null;
    
    return [
        'id' => $_SESSION['admin_id'],
        'name' => $_SESSION['admin_name'],
        'email' => $_SESSION['admin_email'],
        'role' => $_SESSION['admin_role'],
        'role_id' => $_SESSION['admin_role_id'],
        'designation' => $_SESSION['admin_designation'] ?? ''
    ];
}

/**
 * Get current intern data
 */
function getCurrentIntern(): ?array {
    if (!isInternLoggedIn()) return null;
    
    return [
        'id' => $_SESSION['intern_id'],
        'name' => $_SESSION['intern_name'],
        'email' => $_SESSION['intern_email']
    ];
}

/**
 * Check if admin has a specific permission
 */
function hasPermission(string $permission): bool {
    if (!isAdminLoggedIn()) return false;
    
    $role = $_SESSION['admin_role'] ?? '';
    
    // Super admin and proprietor have all permissions
    if (in_array($role, ['super_admin', 'proprietor'])) {
        return true;
    }
    
    $permissions = $_SESSION['admin_permissions'] ?? [];
    
    if (isset($permissions['all']) && $permissions['all'] === true) {
        return true;
    }
    
    return isset($permissions[$permission]) && $permissions[$permission] === true;
}

/**
 * Require a specific permission
 */
function requirePermission(string $permission): void {
    if (!hasPermission($permission)) {
        setFlash('danger', 'You do not have permission to perform this action.');
        redirect(baseUrl('admin/dashboard.php'));
    }
}

/**
 * Check if the current admin can issue certificates
 */
function canIssueCertificates(): bool {
    return hasPermission('issue_certificates');
}

/**
 * Check if the current admin can revoke certificates
 */
function canRevokeCertificates(): bool {
    return hasPermission('revoke_certificates');
}

/**
 * Login an admin
 */
function loginAdmin(array $admin, array $permissions): void {
    initSession();
    session_regenerate_id(true);
    
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_name'] = $admin['full_name'];
    $_SESSION['admin_email'] = $admin['email'];
    $_SESSION['admin_role'] = $admin['role_slug'];
    $_SESSION['admin_role_id'] = $admin['role_id'];
    $_SESSION['admin_designation'] = $admin['designation'] ?? '';
    $_SESSION['admin_permissions'] = $permissions;
    $_SESSION['_last_regeneration'] = time();
}

/**
 * Login an intern
 */
function loginIntern(array $intern): void {
    initSession();
    session_regenerate_id(true);
    
    $_SESSION['intern_id'] = $intern['id'];
    $_SESSION['intern_name'] = $intern['full_name'];
    $_SESSION['intern_email'] = $intern['email'];
    $_SESSION['_last_regeneration'] = time();
}

/**
 * Logout
 */
function logout(): void {
    initSession();
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Check login rate limiting
 */
function isLoginRateLimited(PDO $pdo, string $email, string $userType = 'intern'): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts 
        FROM login_attempts 
        WHERE email = ? AND user_type = ? AND success = 0 
        AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $stmt->execute([$email, $userType, LOGIN_LOCKOUT_DURATION]);
    $result = $stmt->fetch();
    
    return ($result['attempts'] ?? 0) >= MAX_LOGIN_ATTEMPTS;
}

/**
 * Record a login attempt
 */
function recordLoginAttempt(PDO $pdo, string $email, string $userType = 'intern', bool $success = false): void {
    $stmt = $pdo->prepare("
        INSERT INTO login_attempts (email, ip_address, user_type, success, attempted_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$email, getUserIP(), $userType, $success ? 1 : 0]);
}
