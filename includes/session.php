<?php

function app_session_start()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('BANK_COMPLIANCE_SESSION');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '86400');

    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $cookiePath = '/';

    $params = [
        'lifetime' => 86400,
        'path' => $cookiePath,
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ];

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($params);
    } else {
        session_set_cookie_params(
            $params['lifetime'],
            $params['path'] . '; samesite=' . $params['samesite'],
            '',
            $params['secure'],
            $params['httponly']
        );
    }

    session_start();
}
