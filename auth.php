<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'
    ]);
    session_start();
}

function require_login()
{
    if (empty($_SESSION['farmacia_autenticada'])) {
        header('Location: login.php');
        exit;
    }
}
