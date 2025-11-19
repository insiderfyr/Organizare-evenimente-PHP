<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/html; charset=UTF-8');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset(DB_CHARSET);
?>