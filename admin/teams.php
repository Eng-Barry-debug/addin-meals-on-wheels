<?php
$page_title = "Team Members - Admin Dashboard";
require_once 'includes/config.php';

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            $action = $_POST['action'];

            if ($action === 'add' || $action === 'edit') {
                $name = trim($_POST['name'] ?? '');
                $position = trim($_POST['position'] ?? '');
                $bio = trim($_POST['bio'] ?? '');
                $image = trim($_POST['image'] ?? '');
                $sort_order = (int)($_POST['sort_order'] ?? 0);
                $is_active = isset($_POST['is_active']) ? 1 : 0;

                if (empty($name) || empty($position)) {
                    throw new Exception('Name and position are required.');
                }

                if ($action === 'add') {
                    $stmt = $pdo->prepare("
                        INSERT INTO team_members (name, position, bio, image, sort_order, is_active)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $position, $bio, $image, $sort_order, $is_active]);
                    $message = 'Team member added successfully!';
                    $message_type = 'success';
                } else {
                    $id = (int)($_POST['id'] ?? 0);
                    if ($id <= 0) {
                        throw new Exception('Invalid team member ID.');
                    }

                    $stmt = $pdo->prepare("
                        UPDATE team_members
                        SET name = ?, position = ?, bio = ?, image = ?, sort_order = ?, is_active = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $position, $bio, $image, $sort_order, $is_active, $id]);
                    $message = 'Team member updated successfully!';
                    $message_type = 'success';
                }
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Invalid team member ID.');
                }

                $stmt = $pdo->prepare("DELETE FROM team_members WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Team member deleted successfully!';
                $message_type = 'success';
            } elseif ($action === 'toggle_status') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('Invalid team member ID.');
                }

                $stmt = $pdo->prepare("UPDATE team_members SET is_active = !is_active WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Team member status updated successfully!';
                $message_type = 'success';
            }
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}

// Get team members
$team_members = [];
try {
    $stmt = $pdo->prepare("
        SELECT * FROM team_members
        ORDER BY sort_order ASC, name ASC
    ");
    $stmt->execute();
    $team_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = 'Error loading team members: ' . $e->getMessage();
    $message_type = 'error';
}

include 'includes/header.php';
?>

<!-- Main Content -->
<div class="p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Team Members</h1>
            <p class="text-gray-600">Manage your team members and their information</p>
        </div>
        <button onclick="openAddModal()" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-medium transition-colors">
            <i class="fas fa-plus mr-2"></i>Add Team Member
        </button>
    </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type === 'success' ? 'bg-green-100 border border-green-200 text-green-700' : 'bg-red-100 border border-red-200 text-red-700'; ?>">
                <div class="flex items-center">
                    <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Team Members Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php if (!empty($team_members)): ?>
                <?php foreach ($team_members as $member): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <!-- Member Image -->
                        <div class="h-48 bg-gray-200 flex items-center justify-center relative">
                            <?php if (!empty($member['image']) && file_exists('../uploads/team/' . $member['image'])): ?>
                                <img src="../uploads/team/<?php echo htmlspecialchars($member['image']); ?>"
                                     alt="<?php echo htmlspecialchars($member['name']); ?>"
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="text-6xl text-gray-400">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>

                            <!-- Status Badge -->
                            <div class="absolute top-2 right-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $member['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>

                        <!-- Member Info -->
                        <div class="p-4">
                            <h3 class="text-lg font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($member['name']); ?></h3>
                            <p class="text-primary font-medium mb-2"><?php echo htmlspecialchars($member['position']); ?></p>
                            <p class="text-gray-600 text-sm line-clamp-2 mb-4">
                                <?php echo htmlspecialchars(substr($member['bio'] ?? '', 0, 100) . ((strlen($member['bio'] ?? '') > 100) ? '...' : '')); ?>
                            </p>

                            <!-- Actions -->
                            <div class="flex justify-between items-center">
                                <div class="flex space-x-3">
                                    <button onclick="editMember(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars(addslashes($member['name'])); ?>', '<?php echo htmlspecialchars(addslashes($member['position'])); ?>', '<?php echo htmlspecialchars(addslashes($member['bio'] ?? '')); ?>', '<?php echo htmlspecialchars(addslashes($member['image'] ?? '')); ?>', <?php echo $member['sort_order']; ?>, <?php echo $member['is_active']; ?>)"
                                            class="text-primary hover:text-primary-dark text-sm font-medium">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </button>
                                    <button onclick="deleteMember(<?php echo $member['id']; ?>)"
                                            class="text-red-600 hover:text-red-800 text-sm font-medium">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                </div>
                                <button onclick="toggleStatus(<?php echo $member['id']; ?>, <?php echo $member['is_active'] ? 'false' : 'true'; ?>)"
                                        class="text-sm font-medium <?php echo $member['is_active'] ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800'; ?>">
                                    <i class="fas <?php echo $member['is_active'] ? 'fa-times' : 'fa-check'; ?> mr-1"></i>
                                    <?php echo $member['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-600 mb-2">No Team Members Found</h3>
                    <p class="text-gray-500 mb-6">Start by adding your first team member to showcase your team.</p>
                    <button onclick="openAddModal()" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <i class="fas fa-plus mr-2"></i>Add First Team Member
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="memberModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto relative">
            <!-- Close button at top right -->
            <button id="closeModalBtn" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 z-10">
                <i class="fas fa-times text-xl"></i>
            </button>

            <div class="p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-gray-800" id="modalTitle">Add Team Member</h3>
                </div>

                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" id="modalAction" value="add">
                    <input type="hidden" name="id" id="memberId" value="">

                    <!-- Name -->
                    <div>
                        <label for="memberName" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" id="memberName" name="name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary text-sm">
                    </div>

                    <!-- Position -->
                    <div>
                        <label for="memberPosition" class="block text-sm font-medium text-gray-700 mb-2">Position *</label>
                        <input type="text" id="memberPosition" name="position" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary text-sm">
                    </div>

                    <!-- Bio -->
                    <div>
                        <label for="memberBio" class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                        <textarea id="memberBio" name="bio" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary text-sm"
                                  placeholder="Brief description of the team member"></textarea>
                    </div>

                    <!-- Image Upload -->
                    <div>
                        <label for="memberImage" class="block text-sm font-medium text-gray-700 mb-2">Image</label>
                        <div class="space-y-3">
                            <input type="file" id="memberImageFile" name="image_file" accept="image/*"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary text-sm"
                                   onchange="previewImage(this)">
                            <input type="hidden" id="memberImage" name="image" value="">
                            <div id="imagePreview" class="hidden">
                                <img id="previewImg" src="" alt="Preview" class="w-20 h-20 object-cover rounded-lg border border-gray-300">
                                <button type="button" onclick="removeImage()" class="ml-2 text-red-500 hover:text-red-700 text-sm">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </div>
                            <p class="text-xs text-gray-500">Upload a team member photo (JPG, PNG, GIF)</p>
                        </div>
                    </div>

                    <!-- Sort Order -->
                    <div>
                        <label for="memberSortOrder" class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                        <input type="number" id="memberSortOrder" name="sort_order" min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary text-sm"
                               placeholder="0">
                    </div>

                    <!-- Active Status -->
                    <div class="flex items-center">
                        <input type="checkbox" id="memberIsActive" name="is_active" checked class="mr-2">
                        <label for="memberIsActive" class="text-sm font-medium text-gray-700">Active</label>
                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" onclick="closeModal()"
                                class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors text-sm">
                            Cancel
                        </button>
                        <button type="submit"
                                class="bg-primary hover:bg-primary-dark text-white px-6 py-2 rounded-lg font-medium transition-colors text-sm">
                            <span id="modalSubmitText">Add Member</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Modal functions
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Team Member';
    document.getElementById('modalAction').value = 'add';
    document.getElementById('memberId').value = '';
    document.getElementById('memberName').value = '';
    document.getElementById('memberPosition').value = '';
    document.getElementById('memberBio').value = '';
    document.getElementById('memberImage').value = '';
    document.getElementById('memberSortOrder').value = '0';
    document.getElementById('memberIsActive').checked = true;
    document.getElementById('modalSubmitText').textContent = 'Add Member';
    document.getElementById('memberModal').classList.remove('hidden');
}

function editMember(id, name, position, bio, image, sortOrder, isActive) {
    document.getElementById('modalTitle').textContent = 'Edit Team Member';
    document.getElementById('modalAction').value = 'edit';
    document.getElementById('memberId').value = id;
    document.getElementById('memberName').value = name;
    document.getElementById('memberPosition').value = position;
    document.getElementById('memberBio').value = bio;
    document.getElementById('memberImage').value = image;
    document.getElementById('memberSortOrder').value = sortOrder;
    document.getElementById('memberIsActive').checked = isActive;
    document.getElementById('modalSubmitText').textContent = 'Update Member';
    document.getElementById('memberModal').classList.remove('hidden');
}

function closeModal() {
    const modal = document.getElementById('memberModal');
    if (modal) {
        modal.classList.add('hidden');
        // Clear form fields when closing
        document.getElementById('memberName').value = '';
        document.getElementById('memberPosition').value = '';
        document.getElementById('memberBio').value = '';
        document.getElementById('memberImage').value = '';
        document.getElementById('memberImageFile').value = '';
        document.getElementById('memberSortOrder').value = '0';
        document.getElementById('memberIsActive').checked = true;

        // Hide image preview
        const imagePreview = document.getElementById('imagePreview');
        if (imagePreview) {
            imagePreview.classList.add('hidden');
            document.getElementById('previewImg').src = '';
        }
    }
}

// Toggle status function
function toggleStatus(id, activate) {
    if (confirm(activate ? 'Are you sure you want to activate this team member?' : 'Are you sure you want to deactivate this team member?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="toggle_status">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Delete function
function deleteMember(id) {
    if (confirm('Are you sure you want to delete this team member? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function removeImage() {
    document.getElementById('memberImageFile').value = '';
    document.getElementById('memberImage').value = '';
    document.getElementById('imagePreview').classList.add('hidden');
    document.getElementById('previewImg').src = '';
}

function previewImage(input) {
    const file = input.files[0];
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const hiddenInput = document.getElementById('memberImage');

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.classList.remove('hidden');
            // Set a placeholder filename for the hidden input
            hiddenInput.value = file.name;
        };
        reader.readAsDataURL(file);
    } else {
        preview.classList.add('hidden');
        hiddenInput.value = '';
    }
}

// Initialize modal event listeners when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Close modal when clicking the backdrop
    document.getElementById('memberModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });

    // Close modal when clicking the close button
    document.getElementById('closeModalBtn').addEventListener('click', closeModal);

    // Close modal when clicking Cancel button
    document.querySelector('button[type="button"][onclick*="closeModal"]').addEventListener('click', closeModal);
});
</script>

<?php include 'includes/footer.php'; ?>
