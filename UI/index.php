<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$token = getAuthToken();

if ($token) {
    redirect('dashboard.php');
}

redirect('login.php');

