<?php

declare(strict_types=1);

namespace FidestIA\Core;

final class Csrf
{
    public static function token(): string
    {
        if (!isset($_SESSION['fidest_ia_csrf'])) {
            $_SESSION['fidest_ia_csrf'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['fidest_ia_csrf'];
    }

    public static function validate(?string $token): bool
    {
        return is_string($token) && isset($_SESSION['fidest_ia_csrf'])
            && hash_equals((string) $_SESSION['fidest_ia_csrf'], $token);
    }
}
