<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';

if (getAuthToken()) {
    $role = currentUser()['role'] ?? null;
    redirect(userHomePath($role));
}

redirect('login.php');

