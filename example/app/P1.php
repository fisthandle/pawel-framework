<?php
declare(strict_types=1);

class P1 extends \P1\Base {
    const ROLE_ADMIN = 'admin';
    const ROLE_USER = 'user';

    public static function isLoggedIn(): bool {
        return self::currentUser() !== null;
    }

    public static function isAdmin(): bool {
        return (self::currentUser()['role'] ?? '') === self::ROLE_ADMIN;
    }

    public static function currentUser(): ?array {
        return $_SESSION['user'] ?? null;
    }
}
