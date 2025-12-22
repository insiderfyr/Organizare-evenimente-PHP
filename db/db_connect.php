<?php
// Require the configuration file which holds all credentials and settings
require_once __DIR__ . '/../config/config.php';

// Establish database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check for connection errors
if ($conn->connect_error) {
    // In a production environment, you might want to log this error instead of displaying it.
    // For now, we die to stop script execution immediately.
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to utf8mb4 for full Unicode support
if (!$conn->set_charset("utf8mb4")) {
    // Log or handle the error if charset cannot be set
    // For now, we output an error message.
    error_log("Error loading character set utf8mb4: " . $conn->error);
}
?>
