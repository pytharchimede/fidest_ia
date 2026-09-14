<?php

declare(strict_types=1);

namespace FidestIA\Core;

final class AdminAuth
{
    public static function start(array $config): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name('fidest_ia_admin');
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict',
                'cookie_secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            ]);
        }
    }

    public static function login(array $config, string $token): bool
    {
        self::start($config);
        $expected = trim((string) ($config['admin']['token'] ?? ''));
        if ($expected === '') {
            return false;
        }

        if (!hash_equals($expected, trim($token))) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['fidest_ia_admin'] = true;
        $_SESSION['fidest_ia_admin_login_at'] = time();
        return true;
    }

    public static function check(array $config): bool
    {
        self::start($config);
        return (bool) ($_SESSION['fidest_ia_admin'] ?? false);
    }

    public static function logout(array $config): void
    {
        self::start($config);
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
