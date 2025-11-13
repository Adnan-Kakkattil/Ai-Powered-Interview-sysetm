<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function requireAuth(): void
{
    if (!getAuthToken()) {
        redirect('login.php');
    }
}

function currentUser(): array
{
    return $_SESSION['user'] ?? [];
}

function userHomePath(?string $role): string
{
    return match ($role) {
        'candidate' => 'candidate_dashboard.php',
        'admin' => 'dashboard.php',
        default => 'login.php',
    };
}

function requireRole(array $roles): void
{
    requireAuth();
    $role = currentUser()['role'] ?? null;
    if (!in_array($role, $roles, true)) {
        redirect(userHomePath($role));
    }
}

function formatDateTime(?string $isoString, string $format = 'M d, Y h:i A'): string
{
    if (!$isoString) {
        return 'TBD';
    }

    try {
        return (new DateTime($isoString))->format($format);
    } catch (Exception $e) {
        return $isoString;
    }
}

function extractCandidateOptions(array $candidates): array
{
    return array_values(array_map(function ($candidate) {
        $userId = $candidate['_id'] ?? ($candidate['id'] ?? null);
        $profile = $candidate['profile'] ?? [];
        $status = $profile['lotStatus'] ?? $candidate['status'] ?? 'unknown';

        return [
            'id' => (string) $userId,
            'name' => $candidate['name'] ?? 'Candidate',
            'email' => $candidate['email'] ?? 'N/A',
            'status' => ucfirst(str_replace('_', ' ', $status)),
        ];
    }, $candidates));
}

function generateMeetingRoomId(): string
{
    return 'room-' . bin2hex(random_bytes(4));
}

