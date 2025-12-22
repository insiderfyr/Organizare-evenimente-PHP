<?php
require_once '../includes/functions.php';
require_once '../db/db_connect.php';

// Initialize secure session
init_secure_session();

// Set security headers
set_security_headers();

$success = '';
$error = '';

function process_contact_form(&$error, &$success, &$name, &$email, &$subject, &$message) {
    global $conn;

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "All fields are required!";
    } else if (!validate_email($email)) {
        $error = "Invalid email address!";
    } else if (strlen($name) < 2 || strlen($name) > 100) {
        $error = "Name must be between 2 and 100 characters!";
    } else if (strlen($subject) < 5 || strlen($subject) > 200) {
        $error = "Subject must be between 5 and 200 characters!";
    } else if (strlen($message) < 10) {
        $error = "Message must be at least 10 characters!";
    } else {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message, ip_address) VALUES (?, ?, ?, ?, ?)");
        $ip_address = get_client_ip();
        $stmt->bind_param("sssss", $name, $email, $subject, $message, $ip_address);

        if ($stmt->execute()) {
            $success = "Your message has been sent successfully! We will get back to you soon.";
            log_security_event('CONTACT_FORM_SUBMITTED', "From: $email, Subject: $subject");
            
            $name = $email = $subject = $message = '';
            
            reset_rate_limit('contact');
        } else {
            $error = "Error sending message. Please try again later.";
            log_security_event('CONTACT_FORM_ERROR', "Database error");
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token()) {
        $error = "Invalid security token. Please try again.";
        log_security_event('CSRF_VALIDATION_FAILED', 'Contact form submission with invalid CSRF token');
    } else if (ENABLE_RATE_LIMITING && is_rate_limited('contact', 3, 600)) {
        $remaining_time = get_rate_limit_reset_time('contact', 600);
        $minutes = ceil($remaining_time / 60);
        $error = "Too many contact form submissions. Please try again in $minutes minute(s).";
        log_security_event('RATE_LIMIT_EXCEEDED', 'Contact form rate limit exceeded');
    } else {
        $name = sanitize($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $subject = sanitize($_POST['subject']);
        $message = sanitize_text($_POST['message'], 5000);
        
        if (ENABLE_RECAPTCHA) {
            $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';
            if (!verify_recaptcha($recaptcha_response, RECAPTCHA_SECRET_KEY)) {
                $error = "Please complete the reCAPTCHA verification.";
                log_security_event('RECAPTCHA_FAILED', 'Failed reCAPTCHA on contact form');
            } else {
                process_contact_form($error, $success, $name, $email, $subject, $message);
            }
        } else {
            process_contact_form($error, $success, $name, $email, $subject, $message);
        }
    }
}
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<section class="py-5" style="background-color: #f9fafb; min-height: 80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="text-center mb-5">
                    <h2 class="mb-2">Contact Us</h2>
                    <p class="text-muted">Have a question? We'd love to hear from you.</p>
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

                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="bg-white p-4 rounded shadow h-100">
                            <div class="text-center mb-3">
                                <i class="bi bi-envelope text-primary" style="font-size: 2.5rem;"></i>
                            </div>
                            <h5 class="text-center mb-2">Email</h5>
                            <p class="text-center text-muted">contact@eventmanager.com</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="bg-white p-4 rounded shadow h-100">
                            <div class="text-center mb-3">
                                <i class="bi bi-telephone text-primary" style="font-size: 2.5rem;"></i>
                            </div>
                            <h5 class="text-center mb-2">Phone</h5>
                            <p class="text-center text-muted">+40 123 456 789</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="bg-white p-4 rounded shadow h-100">
                            <div class="text-center mb-3">
                                <i class="bi bi-geo-alt text-primary" style="font-size: 2.5rem;"></i>
                            </div>
                            <h5 class="text-center mb-2">Location</h5>
                            <p class="text-center text-muted">Bucharest, Romania</p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="contact.php" class="bg-white p-4 rounded shadow">
                    <?php echo csrf_token_field(); ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Your Name *</label>
                            <input type="text" class="form-control" id="name" name="name"
                                   value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" 
                                   required minlength="2" maxlength="100">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Your Email *</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject *</label>
                        <input type="text" class="form-control" id="subject" name="subject"
                               value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>" 
                               required minlength="5" maxlength="200">
                        <small class="text-muted">5-200 characters</small>
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label">Message *</label>
                        <textarea class="form-control" id="message" name="message" rows="6" 
                                  required minlength="10" maxlength="5000"><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                        <small class="text-muted">Minimum 10 characters</small>
                    </div>

                    <?php if (ENABLE_RECAPTCHA): ?>
                    <div class="mb-3 d-flex justify-content-center">
                        <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
                    </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between">
                        <a href="/index.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Home
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
