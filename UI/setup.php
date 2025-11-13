<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/api.php';
require_once __DIR__ . '/includes/helpers.php';

if (getAuthToken()) {
    redirect(userHomePath(currentUser()['role'] ?? null));
}

$successMessage = null;
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($password !== $confirmPassword) {
        $errorMessage = 'Passwords do not match.';
    } else {
        $response = apiRequest('/auth/register-admin', 'POST', [
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], false);

        if ($response['status'] === 201) {
            $successMessage = 'Admin account created successfully. You can now sign in.';
        } else {
            $errorMessage = $response['error'] ?? 'Failed to create admin account.';
        }
    }
}

$pageTitle = 'Initial Setup';
$currentUser = null;
require __DIR__ . '/includes/header.php';
?>

<main class="min-h-screen flex items-center justify-center py-16">
    <div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-lg border border-gray-200">
        <h1 class="text-2xl font-semibold text-gray-900 mb-2 text-center">Initial Admin Setup</h1>
        <p class="text-gray-600 text-sm text-center mb-6">
            Create the first administrator account to access the Smart Interview Management System.
        </p>

        <?php if ($successMessage): ?>
            <div class="bg-green-50 text-green-700 text-sm p-3 rounded-lg mb-4">
                <?= htmlspecialchars($successMessage) ?>
            </div>
            <div class="text-center">
                <a href="login.php" class="inline-block bg-primary text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-600 transition-colors">
                    Go to Sign In
                </a>
            </div>
        <?php else: ?>
            <?php if ($errorMessage): ?>
                <div class="bg-red-50 text-red-600 text-sm p-3 rounded-lg mb-4">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        required
                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                        placeholder="Jane Doe"
                    />
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                        placeholder="admin@example.com"
                    />
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        minlength="8"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                        placeholder="At least 8 characters"
                    />
                </div>
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        required
                        minlength="8"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                        placeholder="Retype password"
                    />
                </div>
                <button
                    type="submit"
                    class="w-full bg-primary text-white py-2 rounded-lg font-medium hover:bg-blue-600 transition-colors"
                >
                    Create Admin
                </button>
            </form>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

