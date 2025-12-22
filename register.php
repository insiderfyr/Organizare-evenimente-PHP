<?php
require_once 'includes/functions.php';
require_once 'db/db_connect.php';

// Initialize secure session
init_secure_session();

// Set security headers
set_security_headers();

if (is_logged_in()){
    redirect('index.php');
}

$error = '';
$success = '';

function process_registration(&$error, &$success, &$username, &$email) {
    global $conn;

    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required!";
    } else if (!validate_email($email)){
        $error = "Invalid email address!";
    } else if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else if (strlen($username) < 3) {
        $error = "Username must be at least 3 characters!";
    } else if (!validate_password_strength($password, 8)) {
        $error = "Password must be at least 8 characters!";
    } else if (strlen($username) > 50) {
        $error = "Username is too long (max 50 characters)!";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Username or email already exists!";
            log_security_event('REGISTER_FAILED', "Duplicate username/email: $username / $email");
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt->execute()) {
                $success = "Account created successfully! You can now login.";
                log_security_event('REGISTER_SUCCESS', "New user registered: $username");
                
                $username = $email = '';
                
                header("refresh:2;url=login.php");
            } else {
                $error = "Error creating account! Please try again.";
                log_security_event('REGISTER_ERROR', "Database error during registration");
            }
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        $error = "Invalid security token. Please try again.";
        log_security_event('CSRF_VALIDATION_FAILED', 'Register attempt with invalid CSRF token');
    } else if (ENABLE_RATE_LIMITING && is_rate_limited('register', RATE_LIMIT_ATTEMPTS, RATE_LIMIT_WINDOW)) {
        $remaining_time = get_rate_limit_reset_time('register', RATE_LIMIT_WINDOW);
        $minutes = ceil($remaining_time / 60);
        $error = "Too many registration attempts. Please try again in $minutes minute(s).";
        log_security_event('RATE_LIMIT_EXCEEDED', 'Register rate limit exceeded');
    } else {
        $username = sanitize($_POST['username']);
        $email = sanitize_email($_POST['email']);
        
        if (ENABLE_RECAPTCHA) {
            $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
            if (!verify_recaptcha($recaptcha_response, RECAPTCHA_SECRET_KEY)) {
                $error = "Please complete the reCAPTCHA verification.";
                log_security_event('RECAPTCHA_FAILED', 'Failed reCAPTCHA on registration');
            } else {
                process_registration($error, $success, $username, $email);
            }
        } else {
            process_registration($error, $success, $username, $email);
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
                    <h2 class="text-white mb-2">Register</h2>
                    <p class="text-white opacity-75">Create a new account</p>
                </div>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" class="bg-white p-4 rounded shadow">
                    <?php echo csrf_token_field(); ?>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username"
                               value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" 
                               required maxlength="50" minlength="3">
                        <small class="text-muted">3-50 characters</small>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               required minlength="8">
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               required minlength="8">
                    </div>

                    <?php if (ENABLE_RECAPTCHA): ?>
                    <div class="mb-3 d-flex justify-content-center">
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary w-100 btn-custom">
                        <i class="bi bi-person-plus"></i> Register
                    </button>

                    <div class="mt-3 text-center">
                        <p class="mb-0">Already have an account? <a href="login.php">Sign in here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
