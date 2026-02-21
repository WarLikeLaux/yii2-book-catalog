<?php

declare(strict_types=1);

namespace app\application\ports;

interface AuthServiceInterface
{
    public function isGuest(): bool;

    public function login(string $username, string $password, bool $rememberMe): void;

    public function logout(): void;
}
