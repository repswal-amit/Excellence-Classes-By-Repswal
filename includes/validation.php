<?php
/**
 * Input Validation Helper
 * Excellence Classes By Repswal
 */

class Validator {
    private array $errors = [];
    private array $data;
    
    public function __construct(array $data) {
        $this->data = $data;
    }
    
    /**
     * Validate required field
     */
    public function required(string $field, string $label = ''): self {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = trim($this->data[$field] ?? '');
        
        if (empty($value)) {
            $this->errors[$field] = "{$label} is required.";
        }
        
        return $this;
    }
    
    /**
     * Validate email format
     */
    public function email(string $field, string $label = 'Email'): self {
        $value = trim($this->data[$field] ?? '');
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$label} must be a valid email address.";
        }
        
        return $this;
    }
    
    /**
     * Validate minimum length
     */
    public function minLength(string $field, int $min, string $label = ''): self {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = trim($this->data[$field] ?? '');
        
        if (!empty($value) && mb_strlen($value) < $min) {
            $this->errors[$field] = "{$label} must be at least {$min} characters.";
        }
        
        return $this;
    }
    
    /**
     * Validate maximum length
     */
    public function maxLength(string $field, int $max, string $label = ''): self {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = trim($this->data[$field] ?? '');
        
        if (!empty($value) && mb_strlen($value) > $max) {
            $this->errors[$field] = "{$label} must not exceed {$max} characters.";
        }
        
        return $this;
    }
    
    /**
     * Validate phone number
     */
    public function phone(string $field, string $label = 'Phone'): self {
        $value = trim($this->data[$field] ?? '');
        
        if (!empty($value) && !preg_match('/^[0-9+\-\s()]{7,20}$/', $value)) {
            $this->errors[$field] = "{$label} must be a valid phone number.";
        }
        
        return $this;
    }
    
    /**
     * Validate password strength
     */
    public function password(string $field, string $label = 'Password'): self {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value)) {
            if (mb_strlen($value) < 8) {
                $this->errors[$field] = "{$label} must be at least 8 characters.";
            } elseif (!preg_match('/[A-Z]/', $value)) {
                $this->errors[$field] = "{$label} must contain at least one uppercase letter.";
            } elseif (!preg_match('/[a-z]/', $value)) {
                $this->errors[$field] = "{$label} must contain at least one lowercase letter.";
            } elseif (!preg_match('/[0-9]/', $value)) {
                $this->errors[$field] = "{$label} must contain at least one number.";
            }
        }
        
        return $this;
    }
    
    /**
     * Validate password confirmation
     */
    public function confirm(string $field, string $confirmField, string $label = 'Password'): self {
        $value = $this->data[$field] ?? '';
        $confirm = $this->data[$confirmField] ?? '';
        
        if (!empty($value) && $value !== $confirm) {
            $this->errors[$confirmField] = "{$label} confirmation does not match.";
        }
        
        return $this;
    }
    
    /**
     * Validate field is in allowed values
     */
    public function in(string $field, array $allowed, string $label = ''): self {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = trim($this->data[$field] ?? '');
        
        if (!empty($value) && !in_array($value, $allowed)) {
            $this->errors[$field] = "{$label} contains an invalid value.";
        }
        
        return $this;
    }
    
    /**
     * Validate numeric
     */
    public function numeric(string $field, string $label = ''): self {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = trim($this->data[$field] ?? '');
        
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field] = "{$label} must be a number.";
        }
        
        return $this;
    }
    
    /**
     * Validate date format
     */
    public function date(string $field, string $format = 'Y-m-d', string $label = ''): self {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = trim($this->data[$field] ?? '');
        
        if (!empty($value)) {
            $d = DateTime::createFromFormat($format, $value);
            if (!$d || $d->format($format) !== $value) {
                $this->errors[$field] = "{$label} must be a valid date.";
            }
        }
        
        return $this;
    }
    
    /**
     * Validate URL format
     */
    public function url(string $field, string $label = 'URL'): self {
        $value = trim($this->data[$field] ?? '');
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field] = "{$label} must be a valid URL.";
        }
        
        return $this;
    }
    
    /**
     * Custom validation
     */
    public function custom(string $field, callable $callback, string $errorMessage): self {
        $value = trim($this->data[$field] ?? '');
        
        if (!empty($value) && !$callback($value)) {
            $this->errors[$field] = $errorMessage;
        }
        
        return $this;
    }
    
    /**
     * Check if validation passed
     */
    public function passes(): bool {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails(): bool {
        return !empty($this->errors);
    }
    
    /**
     * Get all errors
     */
    public function errors(): array {
        return $this->errors;
    }
    
    /**
     * Get first error
     */
    public function firstError(): ?string {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
    
    /**
     * Get error for a specific field
     */
    public function error(string $field): ?string {
        return $this->errors[$field] ?? null;
    }
    
    /**
     * Get sanitized value
     */
    public function getValue(string $field): string {
        return trim($this->data[$field] ?? '');
    }
}

/**
 * Sanitize input
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate file upload
 */
function validateFileUpload(array $file, array $allowedTypes = [], int $maxSize = 0): array {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = match($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            default => 'File upload error occurred.'
        };
        return $errors;
    }
    
    // Check file size
    $maxSize = $maxSize ?: MAX_UPLOAD_SIZE;
    if ($file['size'] > $maxSize) {
        $errors[] = 'File size exceeds the maximum allowed size of ' . round($maxSize / 1024 / 1024, 2) . ' MB.';
    }
    
    // Check file type
    if (!empty($allowedTypes)) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedTypes)) {
            $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes);
        }
    }
    
    // Check for real file upload
    if (!is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'Invalid file upload.';
    }
    
    return $errors;
}
