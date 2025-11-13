<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/api.php';
require_once __DIR__ . '/includes/helpers.php';

requireAuth();

$currentUser = currentUser();

$formError = null;
$formSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidateId = $_POST['candidate_id'] ?? '';
    $meetingRoomId = $_POST['meeting_room_id'] ?: generateMeetingRoomId();
    $startDate = $_POST['start_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $durationMinutes = (int) ($_POST['duration'] ?? 60);
    $codingTaskId = $_POST['coding_task_id'] ?? null;
    $lotId = $_POST['lot_id'] ?? null;

    if (!$candidateId || !$startDate || !$startTime) {
        $formError = 'Candidate, start date, and start time are required.';
    } else {
        try {
            $start = new DateTime("{$startDate} {$startTime}");
            $end = clone $start;
            $end->modify("+{$durationMinutes} minutes");

            $payload = [
                'candidateId' => $candidateId,
                'meetingRoomId' => $meetingRoomId,
                'schedule' => [
                    'start' => $start->format(DateTime::ATOM),
                    'end' => $end->format(DateTime::ATOM),
                ],
            ];

            if (!empty($currentUser['id'])) {
                $payload['interviewerId'] = $currentUser['id'];
            }
            if (!empty($lotId)) {
                $payload['lotId'] = $lotId;
            }
            if (!empty($codingTaskId)) {
                $payload['codingTaskId'] = $codingTaskId;
            }

            $response = apiRequest('/admin/interviews', 'POST', $payload);

            if ($response['status'] === 201) {
                $formSuccess = 'Interview scheduled successfully.';
            } else {
                $formError = $response['error'] ?? 'Failed to schedule interview.';
            }
        } catch (Exception $e) {
            $formError = 'Invalid date or time.';
        }
    }
}

$interviewResponse = apiRequest('/admin/interviews');
$candidateResponse = apiRequest('/admin/candidates');

$interviews = $interviewResponse['data']['interviews'] ?? [];
$candidateOptions = extractCandidateOptions($candidateResponse['data']['candidates'] ?? []);

if ($formSuccess) {
    $interviewResponse = apiRequest('/admin/interviews');
    $interviews = $interviewResponse['data']['interviews'] ?? $interviews;
}

$pageTitle = 'Interviews';
$activeNav = 'interviews';
require __DIR__ . '/includes/header.php';
?>

<main class="min-h-screen">
    <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Interviews</h1>
                <p class="text-gray-600">View upcoming interviews and schedule new sessions.</p>
            </div>
            <button data-modal-open="scheduleModal" class="bg-primary text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-600 transition-colors">
                + Schedule Interview
            </button>
        </div>

        <?php if ($interviewResponse['error']): ?>
            <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm px-4 py-3 rounded-lg">
                <?= htmlspecialchars($interviewResponse['error']) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr class="text-left text-sm font-semibold text-gray-600">
                        <th class="px-6 py-3">Candidate</th>
                        <th class="px-6 py-3">Interviewer</th>
                        <th class="px-6 py-3">Schedule</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                    <?php if (!$interviews): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                No interviews scheduled yet. Click "Schedule Interview" to get started.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($interviews as $interview): ?>
                            <?php
                                $candidate = $interview['candidate']['name'] ?? 'N/A';
                                $interviewer = $interview['interviewer']['name'] ?? 'N/A';
                                $start = formatDateTime($interview['schedule']['start'] ?? null);
                                $end = formatDateTime($interview['schedule']['end'] ?? null);
                                $status = $interview['status'] ?? 'scheduled';
                                $badgeClasses = match ($status) {
                                    'live' => 'bg-green-100 text-green-800',
                                    'completed' => 'bg-blue-100 text-blue-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                                $meetingRoomId = $interview['meetingRoomId'] ?? '—';
                            ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($candidate) ?></p>
                                    <p class="text-xs text-gray-500">Room: <?= htmlspecialchars($meetingRoomId) ?></p>
                                </td>
                                <td class="px-6 py-4"><?= htmlspecialchars($interviewer) ?></td>
                                <td class="px-6 py-4">
                                    <div><?= $start ?></div>
                                    <div class="text-xs text-gray-500">Ends <?= $end ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full capitalize <?= $badgeClasses ?>">
                                        <?= htmlspecialchars($status) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 space-x-2">
                                    <a href="#" class="text-primary hover:underline text-xs">View</a>
                                    <a href="#" class="text-gray-500 hover:underline text-xs">Reschedule</a>
                                    <a href="#" class="text-red-500 hover:underline text-xs">Cancel</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="scheduleModal" class="fixed inset-0 bg-black/30 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl border border-gray-200">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-900">Schedule Interview</h2>
            <button data-modal-close class="text-gray-500 hover:text-gray-700">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <?php if ($formError): ?>
                <div class="bg-red-50 text-red-600 text-sm p-3 rounded-lg mb-4">
                    <?= htmlspecialchars($formError) ?>
                </div>
            <?php elseif ($formSuccess): ?>
                <div class="bg-green-50 text-green-700 text-sm p-3 rounded-lg mb-4">
                    <?= htmlspecialchars($formSuccess) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Candidate</label>
                    <select name="candidate_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Select candidate</option>
                        <?php foreach ($candidateOptions as $option): ?>
                            <option value="<?= htmlspecialchars($option['id']) ?>">
                                <?= htmlspecialchars($option['name']) ?> (<?= htmlspecialchars($option['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" name="start_date" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                        <input type="time" name="start_time" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Duration (minutes)</label>
                        <input type="number" name="duration" min="15" step="15" value="60" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Meeting Room ID</label>
                        <input type="text" name="meeting_room_id" placeholder="Auto-generated if blank" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Coding Task ID (optional)</label>
                    <input type="text" name="coding_task_id" placeholder="Paste coding task ID" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lot ID (optional)</label>
                    <input type="text" name="lot_id" placeholder="Associate with lot (optional)" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                </div>

                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" data-modal-close class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-blue-600">
                        Schedule Interview
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('scheduleModal');
        const openBtn = document.querySelector('[data-modal-open="scheduleModal"]');
        const closeElements = modal.querySelectorAll('[data-modal-close]');

        openBtn?.addEventListener('click', () => modal.classList.remove('hidden'));
        closeElements.forEach((el) => el.addEventListener('click', () => modal.classList.add('hidden')));
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.classList.add('hidden');
            }
        });
    });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

