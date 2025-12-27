<?php
ini_set('session.use_strict_mode', 1);
session_set_cookie_params([
    'httponly' => true,
    'secure' => true,
    'samesite' => 'Strict'
]);
session_start();

/* Unset all session variables */
$_SESSION = [];

/* Destroy the session */
session_destroy();

/* Redirect to login */
header('Location: login.php');
exit;
