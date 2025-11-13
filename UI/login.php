<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/api.php';

$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $response = apiRequest('/auth/login', 'POST', [
        'email' => $email,
        'password' => $password,
    ], false);

    if ($response['status'] === 200 && isset($response['data']['token'])) {
        setAuthToken($response['data']['token']);
        $_SESSION['user'] = $response['data']['user'] ?? null;
        redirect('dashboard.php');
    } else {
        $errorMessage = $response['error'] ?? 'Unable to login. Check credentials.';
    }
}

$pageTitle = 'Login';
$currentUser = null;
require __DIR__ . '/includes/header.php';
?>

<main class="min-h-screen flex items-center justify-center py-16">
    <div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-md border border-gray-200">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6 text-center">Admin Login</h1>
<?php if ($errorMessage): ?>
            <div class="bg-red-50 text-red-600 text-sm p-3 rounded-lg mb-4">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
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
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                    placeholder="********"
                />
            </div>
            <button
                type="submit"
                class="w-full bg-primary text-white py-2 rounded-lg font-medium hover:bg-blue-600 transition-colors"
            >
                Sign In
            </button>
        </form>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

