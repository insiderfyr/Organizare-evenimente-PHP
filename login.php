<?php
require_once 'includes/functions.php';
require_once 'db/db_connect.php';

// Initialize secure session
init_secure_session();

// Set security headers
set_security_headers();

if (is_logged_in()) {
    redirect('index.php');
}

$error = '';
$show_recaptcha = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!validate_csrf_token()) {
        $error = "Invalid security token. Please try again.";
        log_security_event('CSRF_VALIDATION_FAILED', 'Login attempt with invalid CSRF token');
    } 
    // Check rate limiting
    else if (ENABLE_RATE_LIMITING && is_rate_limited('login', RATE_LIMIT_ATTEMPTS, RATE_LIMIT_WINDOW)) {
        $remaining_time = get_rate_limit_reset_time('login', RATE_LIMIT_WINDOW);
        $minutes = ceil($remaining_time / 60);
        $error = "Too many login attempts. Please try again in $minutes minute(s).";
        log_security_event('RATE_LIMIT_EXCEEDED', 'Login rate limit exceeded');
        $show_recaptcha = true;
    }
    else {
        $username_or_email = sanitize($_POST['username']);
        $password = $_POST['password'];

        if (empty($username_or_email) || empty($password)) {
            $error = "All fields are required!";
        } else {
            $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username_or_email, $username_or_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    // Successful login - regenerate session
                    regenerate_session_after_login();
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];

                    // Reset rate limit on successful login
                    reset_rate_limit('login');
                    
                    log_security_event('LOGIN_SUCCESS', "User: {$user['username']}");

                    $stmt->close();
                    redirect('index.php');
                } else {
                    $error = "Incorrect password!";
                    log_security_event('LOGIN_FAILED', "Failed password for user: $username_or_email");
                }
            } else {
                $error = "Username or email does not exist!";
                log_security_event('LOGIN_FAILED', "Non-existent user: $username_or_email");
            }

            $stmt->close();
        }
    }
}
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<section class="hero-gradient d-flex align-items-center" style="min-height: 80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="text-center mb-4">
                    <h2 class="text-white mb-2">Login</h2>
                    <p class="text-white opacity-75">Sign in to your account</p>
                </div>

                <?php if (isset($_GET['timeout'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="bi bi-clock"></i> Your session has expired. Please login again.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error) && !empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" class="bg-white p-4 rounded shadow">
                    <?php echo csrf_token_field(); ?>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username or Email</label>
                        <input type="text" class="form-control" id="username" name="username"
                               value="<?php echo isset($username_or_email) ? htmlspecialchars($username_or_email) : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <?php if ($show_recaptcha && ENABLE_RECAPTCHA): ?>
                    <div class="mb-3">
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary w-100 btn-custom">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </button>

                    <div class="mt-3 text-center">
                        <p class="mb-0">Don't have an account? <a href="register.php">Register here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
