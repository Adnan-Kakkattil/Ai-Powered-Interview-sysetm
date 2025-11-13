<?php

declare(strict_types=1);

session_start();

/**
 * Base configuration for the PHP UI.
 */
const API_BASE_URL = 'http://localhost:5000/api';

/**
 * Helper to get stored JWT from session.
 */
function getAuthToken(): ?string
{
    return $_SESSION['auth_token'] ?? null;
}

/**
 * Persist JWT in the session.
 */
function setAuthToken(?string $token): void
{
    if ($token) {
        $_SESSION['auth_token'] = $token;
    } else {
        unset($_SESSION['auth_token']);
    }
}

/**
 * Basic redirect helper.
 */
function redirect(string $path): void
{
    header("Location: {$path}");
    exit();
}

