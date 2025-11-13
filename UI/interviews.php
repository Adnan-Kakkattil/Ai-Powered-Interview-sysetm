<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/api.php';
require_once __DIR__ . '/includes/helpers.php';

requireRole(['admin']);

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
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $interviewId = $_POST['interview_id'] ?? '';

    if (!$interviewId) {
        $formError = 'Interview identifier missing.';
    } elseif ($action === 'cancel') {
        $response = apiRequest("/interviews/{$interviewId}/cancel", 'POST');
        if ($response['status'] === 200) {
            $formSuccess = 'Interview cancelled.';
        } else {
            $formError = $response['error'] ?? 'Unable to cancel interview.';
        }
    } elseif ($action === 'update') {
        $startDate = $_POST['start_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $durationMinutes = (int) ($_POST['duration'] ?? 60);
        $meetingRoomId = $_POST['meeting_room_id'] ?? '';
        $codingTaskId = $_POST['coding_task_id'] ?? '';
        $status = $_POST['status'] ?? '';

        try {
            $payload = [];

            if ($startDate && $startTime) {
                $start = new DateTime("{$startDate} {$startTime}");
                $end = clone $start;
                $end->modify("+{$durationMinutes} minutes");
                $payload['schedule'] = [
                    'start' => $start->format(DateTime::ATOM),
                    'end' => $end->format(DateTime::ATOM),
                ];
            }

            if (!empty($meetingRoomId)) {
                $payload['meetingRoomId'] = $meetingRoomId;
            }

            if (!empty($codingTaskId)) {
                $payload['codingTaskId'] = $codingTaskId;
            }

            if (!empty($status)) {
                $payload['status'] = $status;
            }

            if (empty($payload)) {
                $formError = 'No changes submitted.';
            } else {
                $response = apiRequest("/interviews/{$interviewId}", 'PATCH', $payload);
                if ($response['status'] === 200) {
                    $formSuccess = 'Interview updated successfully.';
                } else {
                    $formError = $response['error'] ?? 'Unable to update interview.';
                }
            }
        } catch (Exception $e) {
            $formError = 'Invalid date/time provided.';
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

        <?php if ($formSuccess): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg">
                <?= htmlspecialchars($formSuccess) ?>
            </div>
        <?php endif; ?>
        <?php if ($formError): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-600 text-sm px-4 py-3 rounded-lg">
                <?= htmlspecialchars($formError) ?>
            </div>
        <?php endif; ?>
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
                                <?php
                                    $interviewData = [
                                        'id' => (string) ($interview['_id'] ?? $interview['id'] ?? ''),
                                        'candidate' => $candidate,
                                        'interviewer' => $interviewer,
                                        'meetingRoomId' => $meetingRoomId,
                                        'start' => $interview['schedule']['start'] ?? null,
                                        'end' => $interview['schedule']['end'] ?? null,
                                        'status' => $status,
                                        'codingTaskId' => $interview['codingTask']['_id'] ?? $interview['codingTaskId'] ?? '',
                                        'lotId' => $interview['lot']['_id'] ?? $interview['lotId'] ?? '',
                                    ];
                                    $interviewJson = htmlspecialchars(json_encode($interviewData), ENT_QUOTES, 'UTF-8');
                                ?>
                                <td class="px-6 py-4 space-x-2">
                                    <a href="#" data-action="view-interview" data-interview="<?= $interviewJson ?>" class="text-primary hover:underline text-xs">View</a>
                                    <a href="#" data-action="edit-interview" data-interview="<?= $interviewJson ?>" class="text-gray-500 hover:underline text-xs">Reschedule</a>
                                    <a href="#" data-action="cancel-interview" data-interview="<?= $interviewJson ?>" class="text-red-500 hover:underline text-xs">Cancel</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="scheduleModal" data-modal class="fixed inset-0 bg-black/30 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl border border-gray-200">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-900">Schedule Interview</h2>
            <button data-modal-close class="text-gray-500 hover:text-gray-700">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create">
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

<div id="viewInterviewModal" data-modal class="fixed inset-0 bg-black/30 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl border border-gray-200">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-900">Interview Details</h2>
            <button data-modal-close class="text-gray-500 hover:text-gray-700">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="p-6 space-y-4 text-sm text-gray-700">
            <div>
                <span class="font-medium text-gray-500">Candidate:</span>
                <span id="viewInterviewCandidate">Candidate</span>
            </div>
            <div>
                <span class="font-medium text-gray-500">Interviewer:</span>
                <span id="viewInterviewInterviewer">Interviewer</span>
            </div>
            <div>
                <span class="font-medium text-gray-500">Meeting Room:</span>
                <span id="viewInterviewRoom">—</span>
            </div>
            <div>
                <span class="font-medium text-gray-500">Schedule:</span>
                <div>
                    <span id="viewInterviewStart">—</span>
                    <span class="text-gray-400"> → </span>
                    <span id="viewInterviewEnd">—</span>
                </div>
            </div>
            <div>
                <span class="font-medium text-gray-500">Status:</span>
                <span id="viewInterviewStatus" class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">Scheduled</span>
            </div>
            <div>
                <span class="font-medium text-gray-500">Coding Task ID:</span>
                <span id="viewInterviewCodingTask">—</span>
            </div>
            <div>
                <span class="font-medium text-gray-500">Lot ID:</span>
                <span id="viewInterviewLot">—</span>
            </div>
        </div>
    </div>
</div>

<div id="editInterviewModal" data-modal class="fixed inset-0 bg-black/30 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl border border-gray-200">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-900">Reschedule Interview</h2>
            <button data-modal-close class="text-gray-500 hover:text-gray-700">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <form method="POST" id="editInterviewForm" class="space-y-4">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="interview_id">
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" name="start_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                        <input type="time" name="start_time" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duration (minutes)</label>
                    <input type="number" name="duration" min="15" step="15" value="60" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Meeting Room ID</label>
                    <input type="text" name="meeting_room_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Coding Task ID</label>
                    <input type="text" name="coding_task_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Keep current</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="live">Live</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" data-modal-close class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-blue-600">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" id="cancelInterviewForm" class="hidden">
    <input type="hidden" name="action" value="cancel">
    <input type="hidden" name="interview_id">
</form>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modals = {
            scheduleModal: document.getElementById('scheduleModal'),
            viewInterviewModal: document.getElementById('viewInterviewModal'),
            editInterviewModal: document.getElementById('editInterviewModal'),
        };

        const openModal = (id) => {
            const modal = modals[id];
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        };

        const closeModal = (modal) => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
            trigger.addEventListener('click', () => openModal(trigger.dataset.modalOpen));
        });

        document.querySelectorAll('[data-modal]').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal(modal);
                }
            });
        });

        document.querySelectorAll('[data-modal-close]').forEach((button) => {
            button.addEventListener('click', () => {
                const modal = button.closest('[data-modal]');
                if (modal) closeModal(modal);
            });
        });

        const formatDate = (isoString) => {
            if (!isoString) return '—';
            try {
                const d = new Date(isoString);
                return d.toLocaleString();
            } catch (error) {
                return isoString;
            }
        };

        const safeData = (el) => {
            try {
                return JSON.parse(el.dataset.interview || '{}');
            } catch (error) {
                console.warn('Failed to parse interview data', error);
                return {};
            }
        };

        document.querySelectorAll('[data-action="view-interview"]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                const data = safeData(btn);
                document.getElementById('viewInterviewCandidate').textContent = data.candidate || '—';
                document.getElementById('viewInterviewInterviewer').textContent = data.interviewer || '—';
                document.getElementById('viewInterviewRoom').textContent = data.meetingRoomId || '—';
                document.getElementById('viewInterviewStart').textContent = formatDate(data.start);
                document.getElementById('viewInterviewEnd').textContent = formatDate(data.end);
                document.getElementById('viewInterviewStatus').textContent = (data.status || 'scheduled').toUpperCase();
                document.getElementById('viewInterviewCodingTask').textContent = data.codingTaskId || '—';
                document.getElementById('viewInterviewLot').textContent = data.lotId || '—';
                openModal('viewInterviewModal');
            });
        });

        const editForm = document.getElementById('editInterviewForm');
        document.querySelectorAll('[data-action="edit-interview"]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                const data = safeData(btn);
                editForm.querySelector('[name="interview_id"]').value = data.id || '';
                editForm.querySelector('[name="meeting_room_id"]').value = data.meetingRoomId || '';
                editForm.querySelector('[name="coding_task_id"]').value = data.codingTaskId || '';
                editForm.querySelector('[name="status"]').value = data.status || '';

                if (data.start) {
                    const start = new Date(data.start);
                    editForm.querySelector('[name="start_date"]').value = start.toISOString().slice(0, 10);
                    editForm.querySelector('[name="start_time"]').value = start.toISOString().slice(11, 16);
                } else {
                    editForm.querySelector('[name="start_date"]').value = '';
                    editForm.querySelector('[name="start_time"]').value = '';
                }

                openModal('editInterviewModal');
            });
        });

        const cancelForm = document.getElementById('cancelInterviewForm');
        document.querySelectorAll('[data-action="cancel-interview"]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                const data = safeData(btn);
                if (confirm(`Cancel interview for ${data.candidate || 'this candidate'}?`)) {
                    cancelForm.querySelector('[name="interview_id"]').value = data.id || '';
                    cancelForm.submit();
                }
            });
        });
    });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

