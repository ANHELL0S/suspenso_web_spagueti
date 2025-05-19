<?php

class Session
{
    private static $lifetime = 300; // 3 minutos de sesion

    public static function start()
    {
        session_start([
            'cookie_lifetime' => self::$lifetime,
            'cookie_secure' => true,
            'cookie_httponly' => true,
            'use_strict_mode' => true
        ]);

        if (!isset($_SESSION['canary'])) {
            session_regenerate_id(true);
            $_SESSION['canary'] = time();
        }

        if ($_SESSION['canary'] < time() - 300) {
            session_regenerate_id(true);
            $_SESSION['canary'] = time();
        }
    }

    public static function login($user_id)
    {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['active_time'] = time();
    }

    public static function isActive()
    {
        return isset($_SESSION['user_id'], $_SESSION['active_time']) &&
            (time() - $_SESSION['active_time']) < self::$lifetime;
    }

    public static function destroy()
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    public static function getRemainingTime()
    {
        return isset($_SESSION['active_time'])
            ? max(0, self::$lifetime - (time() - $_SESSION['active_time']))
            : 0;
    }
}
