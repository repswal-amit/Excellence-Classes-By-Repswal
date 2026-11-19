<?php
/**
 * Helper Functions
 * Excellence Classes By Repswal
 */

/**
 * Sanitize output to prevent XSS
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 */
function redirect(string $url): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header("Location: $url");
    } else {
        echo '<script>window.location.href=' . json_encode($url) . ';</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . e($url) . '"></noscript>';
    }
    exit;
}

/**
 * Get the base URL of the application
 */
function baseUrl(string $path = ''): string {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);
    $protocol = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    $rootDir = null;
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $projectRoot = realpath(ROOT_PATH);
    
    if ($docRoot && $projectRoot) {
        if ($docRoot === $projectRoot) {
            $rootDir = '';
        } elseif (strpos($projectRoot, $docRoot) === 0) {
            $rootDir = str_replace('\\', '/', substr($projectRoot, strlen($docRoot)));
        }
    }
    
    if ($rootDir === null) {
        $parsed = parse_url(APP_URL);
        $rootDir = $parsed['path'] ?? '';
    }
    
    $rootDir = rtrim($rootDir, '/');
    $path = ltrim($path, '/');
    
    return $protocol . '://' . $host . ($rootDir ? $rootDir : '') . '/' . $path;
}

/**
 * Get asset URL
 */
function asset(string $path): string {
    return baseUrl('assets/' . ltrim($path, '/'));
}

/**
 * Set flash message
 */
function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Display flash message as HTML
 */
function displayFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    
    $type = e($flash['type']);
    $message = e($flash['message']);
    $icon = match($type) {
        'success' => 'bi-check-circle-fill',
        'danger', 'error' => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        'info' => 'bi-info-circle-fill',
        default => 'bi-info-circle-fill'
    };
    if ($type === 'error') $type = 'danger';
    
    return <<<HTML
    <div class="alert alert-{$type} alert-dismissible fade show d-flex align-items-center" role="alert">
        <i class="bi {$icon} me-2"></i>
        <div>{$message}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    HTML;
}

/**
 * Format date
 */
function formatDate(?string $date, string $format = 'd M Y'): string {
    if (!$date) return 'N/A';
    try {
        return (new DateTime($date))->format($format);
    } catch (Exception $e) {
        return 'N/A';
    }
}

/**
 * Format date with time
 */
function formatDateTime(?string $date, string $format = 'd M Y, h:i A'): string {
    return formatDate($date, $format);
}

/**
 * Generate a cryptographically secure random string
 */
function generateSecureId(int $length = 16): string {
    return bin2hex(random_bytes($length));
}

/**
 * Generate a certificate ID in format: ECBR-YYYY-XXXXXX
 */
function generateCertificateId(): string {
    $year = date('Y');
    $random = strtoupper(bin2hex(random_bytes(4)));
    return "ECBR-{$year}-{$random}";
}

/**
 * Generate a document ID in format: DOC-YYYY-XXXXXX
 */
function generateDocumentId(): string {
    $year = date('Y');
    $random = strtoupper(bin2hex(random_bytes(4)));
    return "DOC-{$year}-{$random}";
}

/**
 * Get user's IP address
 */
function getUserIP(): string {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Alias for getUserIP
 */
function getClientIP(): string {
    return getUserIP();
}

/**
 * Truncate text
 */
function truncate(string $text, int $length = 100, string $suffix = '...'): string {
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Create a URL-friendly slug
 */
function createSlug(string $text): string {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Check if current page matches the given path
 */
function isActivePage(string $page): bool {
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
    return $currentPage === $page;
}

/**
 * Get active class for navigation
 */
function activeClass(string $page): string {
    return isActivePage($page) ? 'active' : '';
}

/**
 * Paginate results
 */
function paginate(int $totalItems, int $perPage = 10, int $currentPage = 1): array {
    $totalPages = max(1, ceil($totalItems / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'total_items' => $totalItems,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * Render pagination HTML
 */
function renderPagination(array $pagination, string $baseUrl = '?'): string {
    if ($pagination['total_pages'] <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous
    $prevDisabled = $pagination['has_previous'] ? '' : ' disabled';
    $prevPage = $pagination['current_page'] - 1;
    $html .= "<li class=\"page-item{$prevDisabled}\"><a class=\"page-link\" href=\"{$baseUrl}page={$prevPage}\"><i class=\"bi bi-chevron-left\"></i></a></li>";
    
    // Page numbers
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    if ($start > 1) {
        $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$baseUrl}page=1\">1</a></li>";
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $activeClass = ($i === $pagination['current_page']) ? ' active' : '';
        $html .= "<li class=\"page-item{$activeClass}\"><a class=\"page-link\" href=\"{$baseUrl}page={$i}\">{$i}</a></li>";
    }
    
    if ($end < $pagination['total_pages']) {
        if ($end < $pagination['total_pages'] - 1) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"{$baseUrl}page={$pagination['total_pages']}\">{$pagination['total_pages']}</a></li>";
    }
    
    // Next
    $nextDisabled = $pagination['has_next'] ? '' : ' disabled';
    $nextPage = $pagination['current_page'] + 1;
    $html .= "<li class=\"page-item{$nextDisabled}\"><a class=\"page-link\" href=\"{$baseUrl}page={$nextPage}\"><i class=\"bi bi-chevron-right\"></i></a></li>";
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Log an admin action to audit log
 */
function logAdminAction(PDO $pdo, ?int $adminId, string $action, ?string $entityType = null, ?int $entityId = null, $oldValues = null, $newValues = null): void {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_audit_logs (admin_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $adminId,
            $action,
            $entityType,
            $entityId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            getUserIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}

/**
 * Get status badge HTML
 */
function statusBadge(string $status): string {
    $status = e($status);
    $class = match($status) {
        'Pending', 'Not Started', 'Pending Approval' => 'bg-warning text-dark',
        'Approved', 'Completed', 'Issued', 'Valid' => 'bg-success',
        'Rejected', 'Cancelled', 'Revoked' => 'bg-danger',
        'In Progress' => 'bg-primary',
        'Not Eligible' => 'bg-secondary',
        'Open' => 'bg-success',
        'Closed' => 'bg-danger',
        'Resubmit' => 'bg-info',
        default => 'bg-secondary'
    };
    return "<span class=\"badge {$class}\">{$status}</span>";
}

/**
 * Ensure storage directories exist
 */
function ensureStorageDirectories(): void {
    $dirs = [
        STORAGE_PATH,
        CERTIFICATE_PATH,
        DOCUMENT_PATH,
        STORAGE_PATH . '/logs',
        UPLOAD_PATH
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
