<?php

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
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
?>
