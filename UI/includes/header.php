<?php

declare(strict_types=1);

$user = $currentUser ?? [];
$role = $user['role'] ?? null;
$activeNav = $activeNav ?? '';

$navItems = [];
$homeHref = 'index.php';
$roleLabel = 'Guest';

if ($role === 'admin') {
    $navItems = [
        ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'dashboard.php'],
        ['key' => 'interviews', 'label' => 'Interviews', 'href' => 'interviews.php'],
        ['key' => 'candidates', 'label' => 'Candidates', 'href' => 'candidates.php'],
        ['key' => 'analytics', 'label' => 'Analytics', 'href' => '#'],
        ['key' => 'settings', 'label' => 'Settings', 'href' => '#'],
    ];
    $homeHref = 'dashboard.php';
    $roleLabel = 'Administrator';
} elseif ($role === 'candidate') {
    $navItems = [
        ['key' => 'candidate_home', 'label' => 'My Dashboard', 'href' => 'candidate_dashboard.php'],
        ['key' => 'candidate_interviews', 'label' => 'My Interviews', 'href' => 'candidate_dashboard.php#interviews'],
    ];
    $homeHref = 'candidate_dashboard.php';
    $roleLabel = 'Candidate';
}

$displayName = $user['name'] ?? 'Welcome';
$displayEmail = $user['email'] ?? '';
$initial = strtoupper(substr($displayName, 0, 1));
$navClass = static fn($key) => $activeNav === $key ? 'nav-link-active' : 'nav-link';
$hasUser = !empty($user);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>SmartHire Interview Control</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"DM Sans"', 'ui-sans-serif', 'system-ui'],
                        brand: ['"DM Sans"', 'ui-sans-serif'],
                    },
                    colors: {
                        primary: '#2563eb',
                        secondary: '#10b981',
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            700: '#1d4ed8',
                        },
                    },
                    borderRadius: {
                        none: '0px',
                        sm: '4px',
                        DEFAULT: '10px',
                        md: '14px',
                        lg: '20px',
                        xl: '24px',
                        '2xl': '30px',
                        '3xl': '40px',
                        full: '9999px',
                        button: '10px',
                    },
                    boxShadow: {
                        header: '0 10px 30px -20px rgba(37, 99, 235, 0.4)',
                    },
                },
            },
        };
    </script>
    <style>
        body {
            font-family: 'DM Sans', sans-serif;
        }

        :where([class^="ri-"])::before {
            content: "\f3c2";
        }

        .nav-link-active {
            color: #2563eb;
            font-weight: 600;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 0.25rem;
        }

        .nav-link {
            color: #4b5563;
            font-weight: 500;
        }

        .nav-link:hover {
            color: #2563eb;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <header class="bg-white/85 backdrop-blur border-b border-gray-200 shadow-sm sticky top-0 z-40">
        <nav class="w-full px-6 py-4">
            <div class="flex items-center justify-between gap-6 max-w-7xl mx-auto">
                <div class="flex items-center space-x-8">
                    <a href="<?= htmlspecialchars($homeHref) ?>" class="flex items-center space-x-3">
                        <div class="bg-brand-100 text-brand-700 font-bold text-lg md:text-xl px-3 py-1 rounded-full shadow-sm">
                            SmartHire
                        </div>
                        <span class="hidden md:block text-xs tracking-[0.35em] uppercase text-gray-500">
                            Interview Control
                        </span>
                    </a>
                    <?php if (!empty($navItems)): ?>
                        <div class="hidden md:flex space-x-6 text-sm">
                            <?php foreach ($navItems as $item): ?>
                                <a href="<?= htmlspecialchars($item['href']) ?>" class="<?= $navClass($item['key']) ?>"><?= htmlspecialchars($item['label']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if ($hasUser): ?>
                        <button class="relative w-10 h-10 flex items-center justify-center rounded-full bg-brand-50 text-brand-700 hover:bg-brand-100 transition">
                            <i class="ri-notification-3-line text-lg"></i>
                            <span class="absolute -top-0.5 -right-0.5 w-3 h-3 bg-rose-500 border border-white rounded-full"></span>
                        </button>
                        <div class="relative">
                            <button type="button" class="flex items-center space-x-3 group" data-profile-trigger>
                                <div class="w-9 h-9 bg-gradient-to-r from-primary to-brand-700 rounded-full flex items-center justify-center text-white font-semibold shadow">
                                    <?= $initial ?>
                                </div>
                                <div class="hidden sm:flex flex-col items-start leading-tight">
                                    <span class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($displayName) ?></span>
                                    <span class="text-xs text-gray-500"><?= htmlspecialchars($roleLabel) ?></span>
                                </div>
                                <i class="ri-arrow-down-s-line text-gray-400 group-hover:text-gray-600 text-lg"></i>
                            </button>
                            <form action="logout.php" method="POST" id="logoutForm"></form>
                            <div class="absolute right-0 mt-3 hidden min-w-[220px] bg-white rounded-xl shadow-lg border border-gray-100 py-3 px-4" data-profile-menu>
                                <div class="pb-3 mb-3 border-b border-gray-100 text-sm text-gray-500">
                                    Signed in as<br>
                                    <span class="font-medium text-gray-900"><?= htmlspecialchars($displayEmail) ?></span>
                                </div>
                                <a href="<?= htmlspecialchars($homeHref) ?>" class="flex items-center space-x-2 text-sm py-2 text-gray-600 hover:text-primary">
                                    <i class="ri-dashboard-2-line"></i>
                                    <span>Go to Dashboard</span>
                                </a>
                                <a href="#" class="flex items-center space-x-2 text-sm py-2 text-gray-600 hover:text-primary">
                                    <i class="ri-settings-3-line"></i>
                                    <span>Account Settings</span>
                                </a>
                                <button type="submit" form="logoutForm" class="flex items-center space-x-2 text-sm py-2 text-rose-600 hover:text-rose-700 w-full text-left">
                                    <i class="ri-logout-box-r-line"></i>
                                    <span>Sign out</span>
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="px-4 py-2 rounded-button bg-primary text-white text-sm font-medium shadow hover:bg-brand-700 transition">Sign in</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const trigger = document.querySelector('[data-profile-trigger]');
            const menu = document.querySelector('[data-profile-menu]');
            if (!trigger || !menu) return;

            trigger.addEventListener('click', (event) => {
                event.preventDefault();
                menu.classList.toggle('hidden');
            });

            document.addEventListener('click', (event) => {
                if (!menu.classList.contains('hidden') && !trigger.contains(event.target) && !menu.contains(event.target)) {
                    menu.classList.add('hidden');
                }
            });
        });
    </script>

