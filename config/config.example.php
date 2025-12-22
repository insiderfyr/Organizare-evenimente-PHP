<?php
// PHP Configuration File - Example

// -- DATABASE SETTINGS --
// Replace with your database credentials from InfinityFree
define('DB_HOST', 'sql_host_from_infinityfree');
define('DB_USER', 'your_infinityfree_username');
define('DB_PASS', 'your_infinityfree_password');
define('DB_NAME', 'your_infinityfree_dbname');

// -- SECURITY SETTINGS --

// reCAPTCHA v2 Keys
// Get your keys from: https://www.google.com/recaptcha/admin/create
define('ENABLE_RECAPTCHA', true); // Set to false to disable reCAPTCHA on forms
define('RECAPTCHA_SITE_KEY', 'your_recaptcha_site_key_here');
define('RECAPTCHA_SECRET_KEY', 'your_recaptcha_secret_key_here');

// Rate Limiting
// Prevents brute-force attacks on login and forms
define('ENABLE_RATE_LIMITING', true);
define('RATE_LIMIT_ATTEMPTS', 5); // Max attempts
define('RATE_LIMIT_WINDOW', 300); // Time window in seconds (5 minutes)

?>
