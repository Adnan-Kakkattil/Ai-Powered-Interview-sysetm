<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/api.php';
require_once __DIR__ . '/includes/helpers.php';

requireRole(['candidate']);

$currentUser = currentUser();

$profileResponse = apiRequest('/candidates/me');
$profile = $profileResponse['data']['profile'] ?? [];
$profileError = $profileResponse['error'] ?? null;

$interviewResponse = apiRequest('/candidates/interviews');
$interviews = $interviewResponse['data']['interviews'] ?? [];
$interviewError = $interviewResponse['error'] ?? null;

$upcomingInterview = null;
$now = new DateTimeImmutable();
foreach ($interviews as $interview) {
    $startIso = $interview['schedule']['start'] ?? null;
    if (!$startIso) {
        continue;
    }
    try {
        $start = new DateTimeImmutable($startIso);
        if ($start >= $now && ($upcomingInterview === null || $start < new DateTimeImmutable($upcomingInterview['schedule']['start']))) {
            $upcomingInterview = $interview;
        }
    } catch (Exception $e) {
        continue;
    }
}

$pageTitle = 'Candidate Dashboard';
$activeNav = 'candidate_home';
require __DIR__ . '/includes/header.php';
?>

<main class="min-h-screen">
    <div class="max-w-6xl mx-auto px-6 py-10 space-y-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-1">Welcome back, <?= htmlspecialchars($currentUser['name'] ?? 'Candidate') ?> 👋</h1>
                <p class="text-gray-600">Track your interview schedule, join live sessions, and stay prepared.</p>
            </div>
            <?php if ($upcomingInterview): ?>
                <?php
                    $startLabel = formatDateTime($upcomingInterview['schedule']['start'] ?? null);
                    $roomId = $upcomingInterview['meetingRoomId'] ?? 'N/A';
                ?>
                <div class="bg-white border border-blue-100 shadow-sm rounded-2xl px-6 py-4 text-sm text-blue-700 max-w-sm">
                    <div class="font-semibold text-blue-800 mb-1">Next interview</div>
                    <div class="mb-1"><?= htmlspecialchars($startLabel) ?></div>
                    <div class="text-xs text-blue-600 mb-3">Room ID: <?= htmlspecialchars($roomId) ?></div>
                    <a href="#" class="inline-flex items-center px-4 py-2 bg-primary text-white text-xs font-semibold rounded-button shadow hover:bg-brand-700 transition">
                        Join Room
                        <i class="ri-arrow-right-up-line ml-2 text-sm"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <section class="grid md:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Profile Overview</h2>
                <?php if ($profileError): ?>
                    <div class="bg-red-50 text-red-600 text-sm p-3 rounded-lg"><?= htmlspecialchars($profileError) ?></div>
                <?php else: ?>
                    <dl class="space-y-3 text-sm text-gray-700">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Name</dt>
                            <dd class="font-medium text-gray-900"><?= htmlspecialchars($currentUser['name'] ?? 'Candidate') ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Email</dt>
                            <dd class="font-medium text-gray-900"><?= htmlspecialchars($currentUser['email'] ?? '') ?></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Status</dt>
                            <dd class="font-medium capitalize text-gray-900"><?= htmlspecialchars($profile['lotStatus'] ?? 'Applicant') ?></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 mb-1">Skills</dt>
                            <dd>
                                <?php if (!empty($profile['skills'])): ?>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($profile['skills'] as $skill): ?>
                                            <span class="px-2 py-1 text-xs bg-gray-100 rounded-full text-gray-600"><?= htmlspecialchars($skill) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-gray-500">No skills listed yet.</span>
                                <?php endif; ?>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 mb-1">Resume</dt>
                            <dd>
                                <?php if (!empty($profile['resumeUrl'])): ?>
                                    <a href="<?= htmlspecialchars($profile['resumeUrl']) ?>" target="_blank" rel="noopener noreferrer" class="text-primary text-sm hover:underline">View resume</a>
                                <?php else: ?>
                                    <span class="text-xs text-gray-500">No resume uploaded.</span>
                                <?php endif; ?>
                            </dd>
                        </div>
                    </dl>
                <?php endif; ?>
            </div>
            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-blue-900 mb-3">Interview Tips</h2>
                <ul class="space-y-2 text-sm text-blue-700">
                    <li class="flex items-start space-x-2">
                        <i class="ri-focus-3-line mt-0.5"></i>
                        <span>Join your session 5 minutes early to test audio/video.</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <i class="ri-lightbulb-flash-line mt-0.5"></i>
                        <span>Keep your resume and any supporting documents nearby.</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <i class="ri-code-line mt-0.5"></i>
                        <span>Practice common coding challenges in the primary language listed for the role.</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <i class="ri-eye-line mt-0.5"></i>
                        <span>Maintain eye contact and stay engaged—the AI attentiveness tracker notices!</span>
                    </li>
                </ul>
            </div>
        </section>

        <section id="interviews" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Your Interviews</h2>
                    <p class="text-sm text-gray-500">Upcoming and historic interview sessions.</p>
                </div>
            </div>
            <?php if ($interviewError): ?>
                <div class="bg-red-50 text-red-600 text-sm p-3 rounded-lg"><?= htmlspecialchars($interviewError) ?></div>
            <?php elseif (!$interviews): ?>
                <div class="text-sm text-gray-500 py-8 text-center">No interviews scheduled yet. Check back once the hiring team schedules one.</div>
            <?php else: ?>
                <div class="overflow-hidden rounded-xl border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-100 text-sm text-gray-700">
                        <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wide">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold">Schedule</th>
                                <th class="px-6 py-3 text-left font-semibold">Interviewer</th>
                                <th class="px-6 py-3 text-left font-semibold">Status</th>
                                <th class="px-6 py-3 text-left font-semibold">Meeting</th>
                                <th class="px-6 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($interviews as $interview): ?>
                                <?php
                                    $startAt = formatDateTime($interview['schedule']['start'] ?? null);
                                    $endAt = formatDateTime($interview['schedule']['end'] ?? null);
                                    $status = $interview['status'] ?? 'scheduled';
                                    $statusClass = match ($status) {
                                        'completed' => 'bg-green-100 text-green-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        'live' => 'bg-amber-100 text-amber-700',
                                        default => 'bg-blue-100 text-blue-700',
                                    };
                                    $meetingRoomId = $interview['meetingRoomId'] ?? 'N/A';
                                    $interviewerName = $interview['interviewer']['name'] ?? 'Hiring Team';
                                ?>
                                <tr class="hover:bg-gray-50/60 transition">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-gray-900"><?= htmlspecialchars($startAt) ?></div>
                                        <div class="text-xs text-gray-500">Ends <?= htmlspecialchars($endAt) ?></div>
                                    </td>
                                    <td class="px-6 py-4"><?= htmlspecialchars($interviewerName) ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full <?= $statusClass ?> capitalize">
                                            <?= htmlspecialchars($status) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <div class="font-medium"><?= htmlspecialchars($meetingRoomId) ?></div>
                                        <?php if (!empty($interview['codingTask']['title'])): ?>
                                            <div class="text-xs text-gray-500">Task: <?= htmlspecialchars($interview['codingTask']['title']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="#" class="inline-flex items-center px-3 py-1.5 rounded-button text-xs font-semibold bg-primary text-white shadow hover:bg-brand-700 transition">
                                            Join Room
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>

