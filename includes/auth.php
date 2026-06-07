<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $redirect_path = (isset($base_path) ? $base_path : "") . "pages/auth/login.php";
    header("location: " . $redirect_path);
    exit;
}
?>
