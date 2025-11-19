<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect('/login.php');
}
?>
