<?php

declare(strict_types=1);

?>
<?php $activeNav = $activeNav ?? 'dashboard'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>AI-Powered Interview System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#10b981'
                    },
                    borderRadius: {
                        none: '0px',
                        sm: '4px',
                        DEFAULT: '8px',
                        md: '12px',
                        lg: '16px',
                        xl: '20px',
                        '2xl': '24px',
                        '3xl': '32px',
                        full: '9999px',
                        button: '8px'
                    }
                }
            }
        }
    </script>
    <style>
        :where([class^="ri-"])::before {
            content: "\f3c2";
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <header class="bg-white shadow-sm border-b border-gray-200">
        <nav class="w-full px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-8">
                    <div class="font-['Pacifico'] text-2xl text-primary">logo</div>
                    <div class="hidden md:flex space-x-6">
                        <a href="dashboard.php" class="<?= $activeNav === 'dashboard' ? 'text-primary font-medium border-b-2 border-primary pb-1' : 'text-gray-700 hover:text-primary font-medium' ?>">Dashboard</a>
                        <a href="interviews.php" class="<?= $activeNav === 'interviews' ? 'text-primary font-medium border-b-2 border-primary pb-1' : 'text-gray-700 hover:text-primary font-medium' ?>">Interviews</a>
                        <a href="candidates.php" class="<?= $activeNav === 'candidates' ? 'text-primary font-medium border-b-2 border-primary pb-1' : 'text-gray-700 hover:text-primary font-medium' ?>">Candidates</a>
                        <a href="#" class="<?= $activeNav === 'analytics' ? 'text-primary font-medium border-b-2 border-primary pb-1' : 'text-gray-700 hover:text-primary font-medium' ?>">Analytics</a>
                        <a href="#" class="<?= $activeNav === 'settings' ? 'text-primary font-medium border-b-2 border-primary pb-1' : 'text-gray-700 hover:text-primary font-medium' ?>">Settings</a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 flex items-center justify-center relative">
                        <i class="ri-notification-2-line text-xl text-gray-600"></i>
                        <div class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full"></div>
                    </div>
                    <div class="flex items-center space-x-2 cursor-pointer">
                        <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                            <i class="ri-user-fill text-white text-sm"></i>
                        </div>
                        <span class="text-gray-700 font-medium"><?= htmlspecialchars($currentUser['name'] ?? 'Admin') ?></span>
                        <div class="w-4 h-4 flex items-center justify-center">
                            <i class="ri-arrow-down-s-line text-gray-500"></i>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    </header>

