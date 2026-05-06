<?php
// Simple PESO admin auth helper
if (session_status() === PHP_SESSION_NONE) session_start();

function peso_require_admin($loginRedirect = 'login.php')
{
    if (empty($_SESSION['peso_admin_logged_in']) || $_SESSION['peso_admin_logged_in'] !== true) {
        header('Location: ' . $loginRedirect);
        exit;
    }
}
