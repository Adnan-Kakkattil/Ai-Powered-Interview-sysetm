<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/api.php';
require_once __DIR__ . '/includes/helpers.php';

requireAuth();

$currentUser = currentUser();

$alertError = null;
$alertSuccess = null;
$actionContext = null;
$createInput = [];
$editInput = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionContext = $_POST['action'] ?? 'create';

    if ($actionContext === 'update') {
        $editInput = $_POST;
        $candidateId = trim($_POST['candidate_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $resumeUrl = trim($_POST['resume_url'] ?? '');
        $skillsInput = $_POST['skills'] ?? '';
        $status = trim($_POST['status'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $skills = $skillsInput === '' ? [] : array_filter(array_map('trim', explode(',', $skillsInput)));

        if (!$candidateId) {
            $alertError = 'Candidate ID is required.';
        } else {
            $payload = [];
            if ($name !== '') {
                $payload['name'] = $name;
            }
            if ($email !== '') {
                $payload['email'] = $email;
            }
            if ($password !== '') {
                $payload['password'] = $password;
            }
            if ($resumeUrl !== '') {
                $payload['resumeUrl'] = $resumeUrl;
            }
            $payload['skills'] = $skills;
            if ($status !== '') {
                $payload['status'] = $status;
            }
            $payload['notes'] = $notes;

            $response = apiRequest("/admin/candidates/{$candidateId}", 'PATCH', $payload);
            if ($response['status'] === 200) {
                $alertSuccess = 'Candidate updated successfully.';
                $editInput = [];
            } else {
                $alertError = $response['error'] ?? 'Failed to update candidate.';
            }
        }
    } elseif ($actionContext === 'archive') {
        $candidateId = trim($_POST['candidate_id'] ?? '');
        if (!$candidateId) {
            $alertError = 'Candidate ID is required.';
        } else {
            $response = apiRequest("/admin/candidates/{$candidateId}/archive", 'POST');
            if ($response['status'] === 200) {
                $alertSuccess = 'Candidate archived successfully.';
            } else {
                $alertError = $response['error'] ?? 'Failed to archive candidate.';
            }
        }
    } else {
        $createInput = $_POST;
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $resumeUrl = trim($_POST['resume_url'] ?? '');
        $skills = array_filter(array_map('trim', explode(',', $_POST['skills'] ?? '')));
        $notes = trim($_POST['notes'] ?? '');

        if (!$name || !$email) {
            $alertError = 'Name and email are required.';
        } else {
            $payload = [
                'name' => $name,
                'email' => $email,
            ];

            if ($password !== '') {
                $payload['password'] = $password;
            }
            if ($resumeUrl !== '') {
                $payload['resumeUrl'] = $resumeUrl;
            }
            if (!empty($skills)) {
                $payload['skills'] = $skills;
            }
            if ($notes !== '') {
                $payload['notes'] = $notes;
            }

            $response = apiRequest('/admin/candidates', 'POST', $payload);

            if ($response['status'] === 201) {
                $alertSuccess = 'Candidate created successfully.';
                $createInput = [];
            } else {
                $alertError = $response['error'] ?? 'Failed to create candidate.';
            }
        }
    }
}

$candidateResponse = apiRequest('/admin/candidates');
$candidates = $candidateResponse['data']['candidates'] ?? [];

if ($formSuccess) {
    $candidateResponse = apiRequest('/admin/candidates');
    $candidates = $candidateResponse['data']['candidates'] ?? $candidates;
}

$pageTitle = 'Candidates';
$activeNav = 'candidates';
require __DIR__ . '/includes/header.php';
?>

<main class="min-h-screen">
    <div class="max-w-7xl mx-auto px-6 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Candidates</h1>
                <p class="text-gray-600">Manage candidate profiles and invite them to interviews.</p>
            </div>
            <button data-modal-open="candidateModal" class="bg-secondary text-white px-4 py-2 rounded-lg font-medium hover:bg-emerald-600 transition-colors">
                + Add Candidate
            </button>
        </div>

        <?php if ($candidateResponse['error']): ?>
            <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm px-4 py-3 rounded-lg">
                <?= htmlspecialchars($candidateResponse['error']) ?>
            </div>
        <?php endif; ?>
        <?php if ($alertSuccess): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg">
                <?= htmlspecialchars($alertSuccess) ?>
            </div>
        <?php endif; ?>
        <?php if ($alertError): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-600 text-sm px-4 py-3 rounded-lg">
                <?= htmlspecialchars($alertError) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr class="text-left text-sm font-semibold text-gray-600">
                        <th class="px-6 py-3">Name</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Skills</th>
                        <th class="px-6 py-3">Created</th>
                        <th class="px-6 py-3">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                    <?php if (!$candidates): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                No candidates yet. Click "Add Candidate" to register someone new.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($candidates as $candidate): ?>
                            <?php
                                $profile = $candidate['profile'] ?? [];
                                $status = $profile['lotStatus'] ?? $candidate['status'] ?? 'Pending';
                                $skillsList = $profile['skills'] ?? $candidate['skills'] ?? [];
                                $candidateData = [
                                    'id' => (string)($candidate['_id'] ?? $candidate['id'] ?? ''),
                                    'name' => $candidate['name'] ?? 'Candidate',
                                    'email' => $candidate['email'] ?? '',
                                    'status' => $candidate['status'] ?? 'invited',
                                    'lotStatus' => $status,
                                    'skills' => $skillsList,
                                    'resumeUrl' => $profile['resumeUrl'] ?? '',
                                    'notes' => $profile['notes'] ?? '',
                                    'createdAt' => $candidate['createdAt'] ?? null,
                                    'createdAtFormatted' => formatDateTime($candidate['createdAt'] ?? null),
                                ];
                                $candidateJson = htmlspecialchars(json_encode($candidateData), ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-gray-900"><?= htmlspecialchars($candidate['name'] ?? 'Candidate') ?></p>
                                    <p class="text-xs text-gray-500">User ID: <?= htmlspecialchars($candidate['_id'] ?? $candidate['id'] ?? 'N/A') ?></p>
                                </td>
                                <td class="px-6 py-4"><?= htmlspecialchars($candidate['email'] ?? '—') ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-50 text-blue-600 capitalize">
                                        <?= htmlspecialchars(str_replace('_', ' ', $status)) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($skillsList): ?>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($skillsList as $skill): ?>
                                                <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full"><?= htmlspecialchars($skill) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-500">No skills listed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <?= formatDateTime($candidate['createdAt'] ?? null) ?>
                                </td>
                                <td class="px-6 py-4 space-x-3">
                                    <a href="#" data-action="view" data-candidate="<?= $candidateJson ?>" class="text-primary hover:underline text-xs">View</a>
                                    <a href="#" data-action="edit" data-candidate="<?= $candidateJson ?>" class="text-gray-500 hover:underline text-xs">Edit</a>
                                    <a href="#" data-action="archive" data-candidate="<?= $candidateJson ?>" class="text-red-500 hover:underline text-xs">Archive</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div id="candidateModal" data-modal class="fixed inset-0 bg-black/30 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl border border-gray-200">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-900">Add Candidate</h2>
            <button data-modal-close class="text-gray-500 hover:text-gray-700">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($createInput['name'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary" placeholder="Jane Doe" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($createInput['email'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary" placeholder="candidate@example.com" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Temporary Password (optional)</label>
                    <input type="text" name="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary" placeholder="Leave blank to auto-generate" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Resume URL</label>
                    <input type="url" name="resume_url" value="<?= htmlspecialchars($createInput['resume_url'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary" placeholder="https://..." />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Skills (comma separated)</label>
                    <input type="text" name="skills" value="<?= htmlspecialchars($createInput['skills'] ?? '') ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary" placeholder="React, Node.js, SQL" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary" placeholder="Add internal notes"><?= htmlspecialchars($createInput['notes'] ?? '') ?></textarea>
                </div>
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" data-modal-close class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-secondary text-white rounded-lg font-medium hover:bg-emerald-600">
                        Create Candidate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="viewCandidateModal" data-modal class="fixed inset-0 bg-black/30 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl border border-gray-200">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-900">Candidate Details</h2>
            <button data-modal-close class="text-gray-500 hover:text-gray-700">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900" id="viewCandidateName">Candidate</h3>
                <p class="text-sm text-gray-500" id="viewCandidateEmail">email@example.com</p>
            </div>
            <div class="grid md:grid-cols-2 gap-4 text-sm text-gray-700">
                <div>
                    <span class="font-medium text-gray-500">Status:</span>
                    <span id="viewCandidateStatus">INVITED</span>
                </div>
                <div>
                    <span class="font-medium text-gray-500">Lot Status:</span>
                    <span id="viewCandidateLotStatus">Unassigned</span>
                </div>
                <div>
                    <span class="font-medium text-gray-500">Created:</span>
                    <span id="viewCandidateCreated">—</span>
                </div>
            </div>
            <div>
                <span class="block font-medium text-gray-500 mb-2">Skills</span>
                <div class="flex flex-wrap gap-2" id="viewCandidateSkills">
                    <span class="text-xs text-gray-500">No skills listed</span>
                </div>
            </div>
            <div>
                <span class="block font-medium text-gray-500 mb-2">Resume</span>
                <a href="#" id="viewCandidateResumeLink" target="_blank" rel="noopener noreferrer" class="text-primary text-sm hover:underline">Open resume</a>
                <p id="viewCandidateResumeFallback" class="text-xs text-gray-500 hidden">No resume link provided.</p>
            </div>
            <div>
                <span class="block font-medium text-gray-500 mb-2">Notes</span>
                <p class="text-sm text-gray-600" id="viewCandidateNotes">No notes available.</p>
            </div>
        </div>
    </div>
</div>

<div id="editCandidateModal" data-modal class="fixed inset-0 bg-black/30 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl border border-gray-200">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-900">Edit Candidate</h2>
            <button data-modal-close class="text-gray-500 hover:text-gray-700">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <form method="POST" id="editCandidateForm" class="space-y-4">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="candidate_id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reset Password (optional)</label>
                    <input type="text" name="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary" placeholder="Leave blank to keep current password" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Resume URL</label>
                    <input type="url" name="resume_url" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary" placeholder="https://..." />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Skills (comma separated)</label>
                    <input type="text" name="skills" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary">
                        <option value="invited">Invited</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-secondary"></textarea>
                </div>
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" data-modal-close class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-secondary text-white rounded-lg font-medium hover:bg-emerald-600">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" id="archiveForm" class="hidden">
    <input type="hidden" name="action" value="archive">
    <input type="hidden" name="candidate_id" id="archiveFormCandidateId">
</form>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modals = {
            candidateModal: document.getElementById('candidateModal'),
            viewCandidateModal: document.getElementById('viewCandidateModal'),
            editCandidateModal: document.getElementById('editCandidateModal'),
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

        const viewName = document.getElementById('viewCandidateName');
        const viewEmail = document.getElementById('viewCandidateEmail');
        const viewStatus = document.getElementById('viewCandidateStatus');
        const viewLotStatus = document.getElementById('viewCandidateLotStatus');
        const viewSkills = document.getElementById('viewCandidateSkills');
        const viewResumeLink = document.getElementById('viewCandidateResumeLink');
        const viewResumeFallback = document.getElementById('viewCandidateResumeFallback');
        const viewCreated = document.getElementById('viewCandidateCreated');
        const viewNotes = document.getElementById('viewCandidateNotes');

        const safeCandidateData = (btn) => {
            try {
                return JSON.parse(btn.dataset.candidate || '{}');
            } catch (error) {
                console.warn('Failed to parse candidate data', error);
                return {};
            }
        };

        document.querySelectorAll('[data-action="view"]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                const data = safeCandidateData(btn);
                viewName.textContent = data.name || 'Candidate';
                viewEmail.textContent = data.email || '—';
                viewStatus.textContent = (data.status || 'invited').toUpperCase();
                viewLotStatus.textContent = data.lotStatus || 'Unassigned';
                viewCreated.textContent = data.createdAtFormatted || '—';
                viewNotes.textContent = data.notes || 'No notes available.';

                if (Array.isArray(data.skills) && data.skills.length) {
                    viewSkills.innerHTML = data.skills
                        .map((skill) => `<span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">${skill}</span>`)
                        .join('');
                } else {
                    viewSkills.innerHTML = '<span class="text-xs text-gray-500">No skills listed</span>';
                }

                if (data.resumeUrl) {
                    viewResumeLink.href = data.resumeUrl;
                    viewResumeLink.classList.remove('hidden');
                    viewResumeFallback.classList.add('hidden');
                } else {
                    viewResumeLink.href = '#';
                    viewResumeLink.classList.add('hidden');
                    viewResumeFallback.classList.remove('hidden');
                }

                openModal('viewCandidateModal');
            });
        });

        const editForm = document.getElementById('editCandidateForm');

        document.querySelectorAll('[data-action="edit"]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                const data = safeCandidateData(btn);
                editForm.querySelector('[name="candidate_id"]').value = data.id || '';
                editForm.querySelector('[name="name"]').value = data.name || '';
                editForm.querySelector('[name="email"]').value = data.email || '';
                editForm.querySelector('[name="resume_url"]').value = data.resumeUrl || '';
                editForm.querySelector('[name="skills"]').value = Array.isArray(data.skills) ? data.skills.join(', ') : '';
                editForm.querySelector('[name="notes"]').value = data.notes || '';
                editForm.querySelector('[name="password"]').value = '';
                const statusSelect = editForm.querySelector('[name="status"]');
                if (statusSelect) {
                    statusSelect.value = data.status || 'invited';
                }
                openModal('editCandidateModal');
            });
        });

        const archiveForm = document.getElementById('archiveForm');
        const archiveCandidateIdInput = document.getElementById('archiveFormCandidateId');

        document.querySelectorAll('[data-action="archive"]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();
                const data = safeCandidateData(btn);
                if (confirm(`Archive ${data.name || 'this candidate'}?`)) {
                    archiveCandidateIdInput.value = data.id || '';
                    archiveForm.submit();
                }
            });
        });
    });
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>

