<?php
// Include security functions
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../config/config.php';

// Backward compatibility wrapper for old sanitize() calls
function sanitize($data) {
    return sanitize_text($data);
}

function redirect($path){
    header("Location: $path");
    exit();
}

function is_logged_in(){
    return isset($_SESSION['user_id']);
}

function get_user_id(){
    return $_SESSION['user_id'] ?? null;
}

function get_user_role() {
    return $_SESSION['role'] ?? null;
}

function has_role($required_role) {
    $role = get_user_role();
    
    if ($required_role === 'admin') {
        return $role === 'admin';
    }
    
    if ($required_role === 'organizer') {
        return in_array($role, ['admin', 'organizer']);
    }
    
    return true; // user role or any authenticated user
}
?>
