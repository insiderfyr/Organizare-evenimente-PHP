<?php
// PHP Configuration File - DO NOT COMMIT TO VERSION CONTROL

// This file should be created manually on the server with the correct credentials.
// It is ignored by Git (see .gitignore) to prevent sensitive data from being exposed.

// -- DATABASE SETTINGS --
// Replace with your database credentials. For local (XAMPP/MAMP) typically:
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Usually empty for XAMPP/MAMP root user
define('DB_NAME', 'eventmanager_db'); // Replace with your actual database name

// -- SECURITY SETTINGS --

// reCAPTCHA v2 Keys
// Get your keys from: https://www.google.com/recaptcha/admin/create
// These are EXAMPLE/FAKE keys. You MUST replace them with your own.
define('ENABLE_RECAPTCHA', true); // Set to false to disable reCAPTCHA on forms
define('RECAPTCHA_SITE_KEY', '6LeTA_0UAAAAAO-c_0J-e_0J-e_0J-e_0J'); // Replace with YOUR Site Key
define('RECAPTCHA_SECRET_KEY', '6LeTA_0UAAAAAP-d_0K-f_0K-f_0K-f_0K'); // Replace with YOUR Secret Key

// Rate Limiting
// Prevents brute-force attacks on login and forms
define('ENABLE_RATE_LIMITING', true);
define('RATE_LIMIT_ATTEMPTS', 5); // Max attempts
define('RATE_LIMIT_WINDOW', 300); // Time window in seconds (5 minutes)

?>
