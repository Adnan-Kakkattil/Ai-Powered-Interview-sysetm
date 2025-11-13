<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

setAuthToken(null);
unset($_SESSION['user']);
session_destroy();

redirect('login.php');

