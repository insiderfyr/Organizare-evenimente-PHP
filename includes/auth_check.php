<?php
require_once __DIR__ . '/functions.php';

// Initialize secure session
init_secure_session();

// Set security headers
set_security_headers();

// Check session timeout
if (!check_session_timeout()) {
    session_destroy();
    redirect('/login.php?timeout=1');
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('/login.php');
}
?>
