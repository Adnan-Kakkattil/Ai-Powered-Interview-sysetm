<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/api.php';
require_once __DIR__ . '/includes/helpers.php';

requireRole(['admin']);

$currentUser = currentUser();

$interviewResponse = apiRequest('/admin/interviews');
$candidateResponse = apiRequest('/admin/candidates');

$interviews = [];
if ($interviewResponse['status'] === 200 && isset($interviewResponse['data']['interviews'])) {
    $interviews = $interviewResponse['data']['interviews'];
} else {
    $interviews = [
        [
            'candidate' => ['name' => 'Sarah Johnson'],
            'interviewer' => ['name' => 'Michael Chen'],
            'status' => 'live',
            'schedule' => ['start' => date(DATE_ATOM, strtotime('-5 minutes')), 'end' => date(DATE_ATOM, strtotime('+25 minutes'))],
            'attentivenessScore' => 94,
        ],
        [
            'candidate' => ['name' => 'David Rodriguez'],
            'interviewer' => ['name' => 'Lisa Wang'],
            'status' => 'scheduled',
            'schedule' => ['start' => date(DATE_ATOM, strtotime('+30 minutes')), 'end' => date(DATE_ATOM, strtotime('+90 minutes'))],
        ],
        [
            'candidate' => ['name' => 'Emily Zhang'],
            'interviewer' => ['name' => 'James Miller'],
            'status' => 'completed',
            'schedule' => ['start' => date(DATE_ATOM, strtotime('-2 hours')), 'end' => date(DATE_ATOM, strtotime('-1 hour'))],
            'score' => 87,
        ],
    ];
}

$candidates = [];
if ($candidateResponse['status'] === 200 && isset($candidateResponse['data']['candidates'])) {
    $candidates = $candidateResponse['data']['candidates'];
} else {
    $candidates = array_fill(0, 1247, ['status' => 'invited']);
}

$activeInterviews = count(array_filter($interviews, fn($i) => ($i['status'] ?? '') === 'live'));
$scheduledToday = count(array_filter($interviews, function ($interview) {
    $start = $interview['schedule']['start'] ?? null;
    if (!$start) {
        return false;
    }
    $scheduledDate = (new DateTime($start))->format('Y-m-d');
    return $scheduledDate === (new DateTime())->format('Y-m-d');
}));
$completedAssessments = count(array_filter($interviews, fn($i) => ($i['status'] ?? '') === 'completed'));
$totalCandidates = count($candidates);

$performanceSeries = [85, 92, 78, 88, 95, 82, 90];
$successBreakdown = [
    ['value' => 78, 'name' => 'Successful Hires'],
    ['value' => 15, 'name' => 'In Process'],
    ['value' => 7, 'name' => 'Rejected'],
];

$recentActivity = [
    [
        'icon' => 'ri-calendar-check-line',
        'color' => 'green',
        'text' => 'Interview scheduled with <span class="font-medium">Alex Thompson</span>',
        'meta' => 'Senior React Developer • 2 hours ago',
    ],
    [
        'icon' => 'ri-user-add-line',
        'color' => 'blue',
        'text' => 'New candidate <span class="font-medium">Maria Garcia</span> added',
        'meta' => 'UX Designer position • 4 hours ago',
    ],
    [
        'icon' => 'ri-file-check-line',
        'color' => 'purple',
        'text' => 'Assessment completed by <span class="font-medium">Robert Kim</span>',
        'meta' => 'Score: 92% • 6 hours ago',
    ],
    [
        'icon' => 'ri-alert-line',
        'color' => 'orange',
        'text' => 'System maintenance scheduled',
        'meta' => 'Tomorrow 2:00 AM - 4:00 AM • 8 hours ago',
    ],
    [
        'icon' => 'ri-settings-line',
        'color' => 'gray',
        'text' => 'Interview settings updated',
        'meta' => 'Coding environment configuration • 12 hours ago',
    ],
];

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<main class="min-h-screen">
    <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Dashboard Overview</h1>
            <p class="text-gray-600">Monitor and manage your interview operations from a centralized control center</p>
            <?php if ($interviewResponse['error'] || $candidateResponse['error']): ?>
                <div class="mt-4 bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm px-4 py-3 rounded-lg">
                    <?= htmlspecialchars($interviewResponse['error'] ?? $candidateResponse['error'] ?? 'Using sample data; API unavailable.') ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid lg:grid-cols-4 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="ri-live-line text-xl text-green-600"></i>
                    </div>
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1"><?= $activeInterviews ?></h3>
                <p class="text-gray-600 text-sm">Active Interviews</p>
                <div class="mt-3 text-xs text-green-600">Live sessions right now</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="ri-calendar-schedule-line text-xl text-blue-600"></i>
                    </div>
                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">Today</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1"><?= $scheduledToday ?></h3>
                <p class="text-gray-600 text-sm">Scheduled Interviews</p>
                <div class="mt-3 text-xs text-blue-600">Remaining for today</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="ri-group-line text-xl text-purple-600"></i>
                    </div>
                    <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full"><?= $totalCandidates ? '+15%' : '—' ?></span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1"><?= number_format($totalCandidates) ?></h3>
                <p class="text-gray-600 text-sm">Total Candidates</p>
                <div class="mt-3 text-xs text-purple-600">Active in database</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="ri-file-list-3-line text-xl text-orange-600"></i>
                    </div>
                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">This week</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1"><?= $completedAssessments ?></h3>
                <p class="text-gray-600 text-sm">Completed Assessments</p>
                <div class="mt-3 text-xs text-orange-600">Avg score: 78%</div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-6 mb-8">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-gray-900">Quick Actions</h2>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <a href="#" class="bg-primary text-white p-4 rounded-xl hover:bg-blue-600 transition-colors text-left !rounded-button whitespace-nowrap">
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                                <i class="ri-calendar-schedule-line text-white"></i>
                            </div>
                            <h3 class="font-semibold mb-1">Schedule New Interview</h3>
                            <p class="text-sm text-blue-100">Set up interviews with candidates</p>
                        </a>
                        <a href="#" class="bg-secondary text-white p-4 rounded-xl hover:bg-emerald-600 transition-colors text-left !rounded-button whitespace-nowrap">
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                                <i class="ri-user-add-line text-white"></i>
                            </div>
                            <h3 class="font-semibold mb-1">Add New Candidate</h3>
                            <p class="text-sm text-emerald-100">Register candidates to system</p>
                        </a>
                        <a href="#" class="bg-purple-600 text-white p-4 rounded-xl hover:bg-purple-700 transition-colors text-left !rounded-button whitespace-nowrap">
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                                <i class="ri-file-chart-line text-white"></i>
                            </div>
                            <h3 class="font-semibold mb-1">Generate Reports</h3>
                            <p class="text-sm text-purple-100">Create detailed analytics reports</p>
                        </a>
                        <a href="#" class="bg-orange-600 text-white p-4 rounded-xl hover:bg-orange-700 transition-colors text-left !rounded-button whitespace-nowrap">
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center mb-3">
                                <i class="ri-bar-chart-line text-white"></i>
                            </div>
                            <h3 class="font-semibold mb-1">View Analytics</h3>
                            <p class="text-sm text-orange-100">Access performance insights</p>
                        </a>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">System Status</h2>
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="ri-server-line text-green-600 text-sm"></i>
                            </div>
                            <span class="text-sm text-gray-700">Server Status</span>
                        </div>
                        <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">Operational</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="ri-database-line text-green-600 text-sm"></i>
                            </div>
                            <span class="text-sm text-gray-700">Database</span>
                        </div>
                        <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">Connected</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="ri-video-line text-green-600 text-sm"></i>
                            </div>
                            <span class="text-sm text-gray-700">Video System</span>
                        </div>
                        <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">Available</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <i class="ri-brain-line text-yellow-600 text-sm"></i>
                            </div>
                            <span class="text-sm text-gray-700">AI Processing</span>
                        </div>
                        <span class="text-xs text-yellow-600 bg-yellow-50 px-2 py-1 rounded-full">High Load</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Live Interview Monitoring</h2>
                    <a href="#" class="text-primary text-sm hover:text-blue-600 whitespace-nowrap">View All</a>
                </div>
                <div class="space-y-4">
                    <?php foreach ($interviews as $interview): ?>
                        <?php
                            $status = $interview['status'] ?? 'scheduled';
                            $badgeClasses = match ($status) {
                                'live' => 'bg-green-100 text-green-800',
                                'completed' => 'bg-blue-100 text-blue-800',
                                'scheduled' => 'bg-yellow-100 text-yellow-800',
                                default => 'bg-gray-100 text-gray-800',
                            };
                            $actionLabel = match ($status) {
                                'live' => 'Monitor',
                                'scheduled' => 'Join',
                                'completed' => 'Report',
                                default => 'Details',
                            };
                            $actionClasses = match ($status) {
                                'live' => 'bg-primary text-white',
                                'scheduled' => 'bg-secondary text-white',
                                'completed' => 'border border-gray-300 text-gray-700',
                                default => 'bg-gray-200 text-gray-700',
                            };
                            $candidateName = $interview['candidate']['name'] ?? 'Candidate';
                            $interviewerName = $interview['interviewer']['name'] ?? 'Interviewer';
                            $role = $interview['candidate']['role'] ?? 'Role TBD';
                            $score = $interview['score'] ?? $interview['attentivenessScore'] ?? null;
                        ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="<?= $status === 'completed' ? 'ri-check-line text-blue-600' : ($status === 'live' ? 'ri-video-on-line text-green-600' : 'ri-time-line text-yellow-600') ?>"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900"><?= htmlspecialchars($candidateName) ?></h4>
                                    <p class="text-sm text-gray-600"><?= htmlspecialchars($role) ?> • <?= htmlspecialchars($interviewerName) ?></p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-1 <?= $badgeClasses ?> text-xs rounded-full capitalize"><?= htmlspecialchars($status) ?></span>
                                <?php if ($score): ?>
                                    <div class="text-xs text-gray-600"><?= $status === 'completed' ? "Score: {$score}%" : "Attention: {$score}%" ?></div>
                                <?php endif; ?>
                                <button class="<?= $actionClasses ?> px-3 py-1 !rounded-button text-xs hover:opacity-90 whitespace-nowrap"><?= $actionLabel ?></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Recent Activity</h2>
                    <a href="#" class="text-primary text-sm hover:text-blue-600 whitespace-nowrap">View All</a>
                </div>
                <div class="space-y-4">
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-<?= $activity['color'] ?>-100 rounded-lg flex items-center justify-center mt-1">
                                <i class="<?= $activity['icon'] ?> text-<?= $activity['color'] ?>-600 text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-900"><?= $activity['text'] ?></p>
                                <p class="text-xs text-gray-500"><?= htmlspecialchars($activity['meta']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Performance Analytics</h2>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 bg-primary text-white text-xs !rounded-button whitespace-nowrap">7 Days</button>
                        <button class="px-3 py-1 text-gray-600 text-xs !rounded-button border border-gray-300 hover:bg-gray-50 whitespace-nowrap">30 Days</button>
                    </div>
                </div>
                <div class="h-64" id="performanceChart" data-series='<?= json_encode($performanceSeries) ?>'></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Interview Success Rate</h2>
                    <div class="text-sm text-gray-600">This month</div>
                </div>
                <div class="h-64" id="successChart" data-series='<?= json_encode($successBreakdown) ?>'></div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Quick Navigation</h2>
            </div>
            <div class="grid md:grid-cols-4 gap-4">
                <a href="#" class="block p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl hover:from-blue-100 hover:to-blue-200 transition-colors">
                    <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="ri-calendar-line text-white text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Interview Management</h3>
                    <p class="text-sm text-gray-600 mb-3">Schedule, monitor, and manage all interviews</p>
                    <div class="text-xs text-blue-600"><?= $activeInterviews ?> active interviews</div>
                </a>
                <a href="#" class="block p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-xl hover:from-green-100 hover:to-green-200 transition-colors">
                    <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="ri-group-line text-white text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Candidate Database</h3>
                    <p class="text-sm text-gray-600 mb-3">Manage candidate profiles and applications</p>
                    <div class="text-xs text-green-600"><?= number_format($totalCandidates) ?> total candidates</div>
                </a>
                <a href="#" class="block p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl hover:from-purple-100 hover:to-purple-200 transition-colors">
                    <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="ri-bar-chart-line text-white text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">Analytics Reports</h3>
                    <p class="text-sm text-gray-600 mb-3">Detailed performance insights and metrics</p>
                    <div class="text-xs text-purple-600"><?= $completedAssessments ?> reports generated</div>
                </a>
                <a href="#" class="block p-6 bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl hover:from-orange-100 hover:to-orange-200 transition-colors">
                    <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mb-4">
                        <i class="ri-settings-line text-white text-xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-2">System Settings</h3>
                    <p class="text-sm text-gray-600 mb-3">Configure system preferences and security</p>
                    <div class="text-xs text-orange-600">All systems operational</div>
                </a>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const performanceContainer = document.getElementById('performanceChart');
        if (performanceContainer) {
            const performanceChart = echarts.init(performanceContainer);
            const seriesData = JSON.parse(performanceContainer.dataset.series || '[]');
            performanceChart.setOption({
                animation: false,
                grid: { top: 20, right: 20, bottom: 40, left: 40 },
                xAxis: {
                    type: 'category',
                    data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    axisLabel: { color: '#6b7280', fontSize: 12 }
                },
                yAxis: {
                    type: 'value',
                    axisLabel: { color: '#6b7280', fontSize: 12 }
                },
                series: [{
                    data: seriesData,
                    type: 'line',
                    smooth: true,
                    lineStyle: { color: 'rgba(87, 181, 231, 1)', width: 3 },
                    itemStyle: { color: 'rgba(87, 181, 231, 1)' },
                    areaStyle: {
                        color: {
                            type: 'linear',
                            x: 0, y: 0, x2: 0, y2: 1,
                            colorStops: [
                                { offset: 0, color: 'rgba(87, 181, 231, 0.1)' },
                                { offset: 1, color: 'rgba(87, 181, 231, 0.01)' }
                            ]
                        }
                    },
                    showSymbol: false
                }],
                tooltip: {
                    trigger: 'axis',
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    textStyle: { color: '#1f2937' },
                    borderColor: '#e5e7eb',
                    borderWidth: 1
                }
            });
        }

        const successContainer = document.getElementById('successChart');
        if (successContainer) {
            const successChart = echarts.init(successContainer);
            const successData = JSON.parse(successContainer.dataset.series || '[]');
            successChart.setOption({
                animation: false,
                grid: { top: 20, right: 20, bottom: 20, left: 20 },
                tooltip: {
                    trigger: 'item',
                    backgroundColor: 'rgba(255, 255, 255, 0.9)',
                    textStyle: { color: '#1f2937' },
                    borderColor: '#e5e7eb',
                    borderWidth: 1
                },
                series: [{
                    type: 'pie',
                    radius: ['40%', '70%'],
                    center: ['50%', '50%'],
                    data: successData.map((item) => ({
                        ...item,
                        itemStyle: {
                            borderRadius: 4,
                            borderColor: '#fff',
                            borderWidth: 2
                        }
                    })),
                    label: {
                        show: true,
                        position: 'outside',
                        formatter: '{b}: {c}%',
                        fontSize: 12,
                        color: '#374151'
                    }
                }]
            });
        }
    });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

