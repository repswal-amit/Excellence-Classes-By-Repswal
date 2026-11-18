<?php
/**
 * CSRF Protection
 * Excellence Classes By Repswal
 */

/**
 * Generate CSRF token
 */
function generateCSRFToken(): string {
    initSession();
    
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF hidden input field
 */
function csrfField(): string {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Validate CSRF token
 */
function validateCSRFToken(?string $token = null): bool {
    initSession();
    
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    }
    
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    if ((time() - ($_SESSION['csrf_token_time'] ?? 0)) > CSRF_TOKEN_LIFETIME) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Alias for validateCSRFToken
 */
function verifyCsrfToken(?string $token = null): bool {
    return validateCSRFToken($token);
}

/**
 * Verify CSRF token and redirect on failure
 */
function verifyCSRF(): void {
    if (!validateCSRFToken()) {
        setFlash('danger', 'Invalid or expired security token. Please try again.');
        redirect($_SERVER['HTTP_REFERER'] ?? baseUrl());
    }
}
