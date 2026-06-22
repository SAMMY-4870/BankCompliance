<?php

include("../includes/session.php");
app_session_start();

session_unset();

session_destroy();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    $cookieOptions = [
        'expires' => time() - 42000,
        'path' => $params['path'] ?: '/',
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax'
    ];

    if (!empty($params['domain'])) {
        $cookieOptions['domain'] = $params['domain'];
    }

    setcookie(session_name(), '', $cookieOptions);
}

header("Location: login.php");

?>
