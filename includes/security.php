<?php
/**
 * Security functions for CSRF protection, session management, and input validation
 */

// ============================================
// CSRF PROTECTION
// ============================================

/**
 * Generate a CSRF token and store it in session
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > 3600) { // Token expires after 1 hour
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from POST request
 */
function validate_csrf_token() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Check if token has expired
    if (isset($_SESSION['csrf_token_time']) && (time() - $_SESSION['csrf_token_time']) > 3600) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Output CSRF token as hidden input field
 */
function csrf_token_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// ============================================
// SESSION SECURITY
// ============================================

/**
 * Initialize secure session
 */
function init_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        // Secure session configuration
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1); // Use only over HTTPS
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
        
        // Regenerate session ID periodically to prevent session fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

/**
 * Regenerate session ID after login
 */
function regenerate_session_after_login() {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
    $_SESSION['last_activity'] = time();
}

/**
 * Check session timeout
 */
function check_session_timeout() {
    $timeout = 3600; // 1 hour
    
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        return false;
    }
    
    $_SESSION['last_activity'] = time();
    return true;
}

// ============================================
// RATE LIMITING (Simple implementation)
// ============================================

/**
 * Check if action is rate limited
 * @param string $action - Action identifier (e.g., 'login', 'register')
 * @param int $max_attempts - Maximum attempts allowed
 * @param int $time_window - Time window in seconds
 */
function is_rate_limited($action, $max_attempts = 5, $time_window = 300) {
    $key = 'rate_limit_' . $action . '_' . get_client_ip();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => time()
        ];
    }
    
    $rate_data = $_SESSION[$key];
    
    // Reset if time window has passed
    if (time() - $rate_data['first_attempt'] > $time_window) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return false;
    }
    
    // Check if limit exceeded
    if ($rate_data['attempts'] >= $max_attempts) {
        return true;
    }
    
    // Increment attempts
    $_SESSION[$key]['attempts']++;
    return false;
}

/**
 * Reset rate limit for an action
 */
function reset_rate_limit($action) {
    $key = 'rate_limit_' . $action . '_' . get_client_ip();
    unset($_SESSION[$key]);
}

/**
 * Get remaining time until rate limit resets
 */
function get_rate_limit_reset_time($action, $time_window = 300) {
    $key = 'rate_limit_' . $action . '_' . get_client_ip();
    
    if (!isset($_SESSION[$key])) {
        return 0;
    }
    
    $elapsed = time() - $_SESSION[$key]['first_attempt'];
    $remaining = $time_window - $elapsed;
    
    return max(0, $remaining);
}

// ============================================
// INPUT VALIDATION & SANITIZATION
// ============================================

/**
 * Enhanced sanitization for text input
 */
function sanitize_text($data, $max_length = 255) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return mb_substr($data, 0, $max_length);
}

/**
 * Sanitize email
 */
function sanitize_email($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Validate email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate URL
 */
function validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Sanitize integer
 */
function sanitize_int($value) {
    return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Validate password strength
 */
function validate_password_strength($password, $min_length = 8) {
    if (strlen($password) < $min_length) {
        return false;
    }
    
    // Optional: Add more complexity requirements
    // - At least one uppercase letter
    // - At least one lowercase letter
    // - At least one number
    // - At least one special character
    
    return true;
}

// ============================================
// HTTP REQUEST VALIDATION
// ============================================

/**
 * Get client IP address
 */
function get_client_ip() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Check if request comes from same origin (basic check)
 */
function validate_request_origin() {
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return true;
    }
    
    // Check for referer (not 100% reliable but adds a layer)
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        $server_host = $_SERVER['HTTP_HOST'];
        
        // Allow localhost variants for development
        $allowed_hosts = [$server_host, 'localhost', '127.0.0.1'];
        
        if (!in_array($referer_host, $allowed_hosts)) {
            return false;
        }
    }
    
    return true;
}

// ============================================
// RECAPTCHA VALIDATION
// ============================================

/**
 * Verify reCAPTCHA v2 response
 * @param string $recaptcha_response - The response from reCAPTCHA
 * @param string $secret_key - Your reCAPTCHA secret key
 */
function verify_recaptcha($recaptcha_response, $secret_key) {
    if (empty($recaptcha_response)) {
        return false;
    }

    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $secret_key,
        'response' => $recaptcha_response,
        'remoteip' => get_client_ip()
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $result = curl_exec($ch);
    
    // Debugging: Check for cURL errors
    if (curl_errno($ch)) {
        $_SESSION['recaptcha_error'] = 'cURL Error: ' . curl_error($ch);
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);

    if ($result === false) {
        $_SESSION['recaptcha_error'] = 'Failed to get response from Google reCAPTCHA server.';
        return false;
    }

    $json = json_decode($result);

    // Debugging: Check for Google's error codes
    if (!$json->success && !empty($json->{'error-codes'})) {
        $_SESSION['recaptcha_error'] = 'Google reCAPTCHA Error: ' . implode(', ', $json->{'error-codes'});
    }

    return $json->success ?? false;
}

// ============================================
// SECURITY HEADERS
// ============================================

/**
 * Set security headers
 */
function set_security_headers() {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (basic)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' data: https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; frame-src https://www.google.com/; connect-src 'self' https://cdn.jsdelivr.net;");
}

// ============================================
// ERROR LOGGING
// ============================================

/**
 * Log security event
 */
function log_security_event($event_type, $details = '') {
    $log_file = __DIR__ . '/../logs/security.log';
    $log_dir = dirname($log_file);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = get_client_ip();
    $user_id = $_SESSION['user_id'] ?? 'Guest';
    
    $log_message = "[$timestamp] [$event_type] [IP: $ip] [User: $user_id] $details\n";
    
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}
?>
