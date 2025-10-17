<?php
// Include configuration and check login
require_once __DIR__ . '/../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$page_title = 'My Addresses';

// Handle form submissions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_address'])) {
        $address_name = trim($_POST['address_name']);
        $recipient_name = trim($_POST['recipient_name']);
        $phone = trim($_POST['phone']);
        $street_address = trim($_POST['street_address']);
        $city = trim($_POST['city']);
        $postal_code = trim($_POST['postal_code']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;

        // Validate required fields
        if (empty($address_name) || empty($recipient_name) || empty($phone) || empty($street_address) || empty($city)) {
            $error = 'Please fill in all required fields.';
        } else {
            try {
                // If this is set as default, remove default from other addresses
                if ($is_default) {
                    $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
                }

                $stmt = $pdo->prepare("
                    INSERT INTO user_addresses (user_id, address_name, recipient_name, phone, street_address, city, postal_code, is_default, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$user_id, $address_name, $recipient_name, $phone, $street_address, $city, $postal_code, $is_default]);

                $success = 'Address added successfully!';

            } catch (PDOException $e) {
                $error = 'Error adding address: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['update_address'])) {
        $address_id = (int)$_POST['address_id'];
        $address_name = trim($_POST['address_name']);
        $recipient_name = trim($_POST['recipient_name']);
        $phone = trim($_POST['phone']);
        $street_address = trim($_POST['street_address']);
        $city = trim($_POST['city']);
        $postal_code = trim($_POST['postal_code']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;

        // Validate required fields
        if (empty($address_name) || empty($recipient_name) || empty($phone) || empty($street_address) || empty($city)) {
            $error = 'Please fill in all required fields.';
        } else {
            try {
                // If this is set as default, remove default from other addresses
                if ($is_default) {
                    $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND id != ?")->execute([$user_id, $address_id]);
                }

                $stmt = $pdo->prepare("
                    UPDATE user_addresses
                    SET address_name = ?, recipient_name = ?, phone = ?, street_address = ?, city = ?, postal_code = ?, is_default = ?
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$address_name, $recipient_name, $phone, $street_address, $city, $postal_code, $is_default, $address_id, $user_id]);

                $success = 'Address updated successfully!';

            } catch (PDOException $e) {
                $error = 'Error updating address: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_address'])) {
        $address_id = (int)$_POST['address_id'];

        try {
            $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
            $stmt->execute([$address_id, $user_id]);

            $success = 'Address deleted successfully!';

        } catch (PDOException $e) {
            $error = 'Error deleting address: ' . $e->getMessage();
        }
    } elseif (isset($_POST['set_default'])) {
        $address_id = (int)$_POST['address_id'];

        try {
            // Remove default from all other addresses first
            $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);

            // Set the selected address as default
            $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$address_id, $user_id]);

            $success = 'Default address updated successfully!';

        } catch (PDOException $e) {
            $error = 'Error updating default address: ' . $e->getMessage();
        }
    }

    // After any POST operation, re-fetch users to reflect changes
}

// Fetch user's addresses
try {
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$user_id]);
    $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $addresses = [];
    $error = 'Error fetching addresses: ' . $e->getMessage();
}

// Debug info (remove in production)
if (empty($addresses)) {
    $debug_info = "Debug: User ID = $user_id, Addresses count = " . count($addresses);
}

include '../includes/header.php';
?>

<!-- Addresses Page -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Page Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">My Addresses</h1>
                    <p class="text-gray-600">Manage your delivery addresses for faster checkout</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <button onclick="openAddAddressModal()"
                           class="inline-flex items-center px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-opacity-90 transition-colors">
                        <i class="fas fa-plus mr-2"></i> Add New Address
                    </button>
                </div>
            </div>

            <!-- Alerts -->
            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-400 text-green-700 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($addresses)): ?>
                <!-- No Addresses State -->
                <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                    <div class="max-w-md mx-auto">
                        <i class="fas fa-map-marker-alt text-6xl text-gray-300 mb-6"></i>
                        <h2 class="text-2xl font-bold text-gray-700 mb-4">No addresses saved</h2>
                        <p class="text-gray-600 mb-6">Add your delivery addresses to make ordering faster and easier.</p>

                        <?php if (isset($debug_info)): ?>
                            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-left">
                                <h4 class="font-semibold text-yellow-800 mb-2">Debug Information:</h4>
                                <p class="text-sm text-yellow-700"><?php echo htmlspecialchars($debug_info); ?></p>
                                <p class="text-sm text-yellow-700 mt-2">💡 <strong>Tip:</strong> Add your first address using the button below!</p>
                            </div>
                        <?php endif; ?>

                        <button onclick="openAddAddressModal()"
                               class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-primary to-secondary text-white font-bold rounded-lg hover:shadow-lg transform hover:-translate-y-1 transition-all duration-300">
                            <i class="fas fa-plus mr-2"></i>
                            Add Your First Address
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <!-- Addresses Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <?php foreach ($addresses as $address): ?>
                        <div class="bg-white rounded-xl shadow-lg p-6 relative <?php echo $address['is_default'] ? 'ring-2 ring-primary' : ''; ?>">
                            <?php if ($address['is_default']): ?>
                                <div class="absolute top-4 right-4 bg-primary text-white px-3 py-1 rounded-full text-xs font-semibold">
                                    <i class="fas fa-star mr-1"></i>Default
                                </div>
                            <?php endif; ?>

                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-gray-900 mb-1">
                                        <?php echo htmlspecialchars($address['address_name']); ?>
                                    </h3>
                                    <p class="text-gray-600 text-sm">
                                        <?php echo htmlspecialchars($address['recipient_name']); ?>
                                    </p>
                                </div>
                                <div class="flex space-x-2 ml-4">
                                    <button onclick="editAddress(<?php echo htmlspecialchars(json_encode($address)); ?>)"
                                           class="text-blue-600 hover:text-blue-800 p-2 rounded-full hover:bg-blue-50 transition-colors">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if (!$address['is_default']): ?>
                                        <form method="POST" class="inline"
                                              onsubmit="return confirm('Are you sure you want to delete this address?')">
                                            <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                            <button type="submit" name="delete_address"
                                                   class="text-red-600 hover:text-red-800 p-2 rounded-full hover:bg-red-50 transition-colors">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="space-y-2 text-gray-600">
                                <div class="flex items-center">
                                    <i class="fas fa-phone text-primary mr-3"></i>
                                    <span><?php echo htmlspecialchars($address['phone']); ?></span>
                                </div>
                                <div class="flex items-start">
                                    <i class="fas fa-map-marker-alt text-primary mr-3 mt-1"></i>
                                    <div>
                                        <span><?php echo htmlspecialchars($address['street_address']); ?></span>
                                        <?php if (!empty($address['city'])): ?>
                                            <br><span><?php echo htmlspecialchars($address['city']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($address['postal_code'])): ?>
                                            <br><span><?php echo htmlspecialchars($address['postal_code']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <?php if (!$address['is_default']): ?>
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                        <input type="hidden" name="is_default" value="1">
                                        <button type="submit" name="set_default"
                                               class="text-primary hover:text-primary-dark text-sm font-medium transition-colors">
                                            <i class="fas fa-star mr-1"></i>Set as Default
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Address Statistics -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Address Overview</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary mb-2"><?php echo count($addresses); ?></div>
                            <div class="text-gray-600">Total Addresses</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600 mb-2">
                                <?php
                                $default_count = 0;
                                foreach ($addresses as $address) {
                                    if ($address['is_default']) $default_count++;
                                }
                                echo $default_count;
                                ?>
                            </div>
                            <div class="text-gray-600">Default Address</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600 mb-2">
                                <?php
                                $recent_count = 0;
                                $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
                                foreach ($addresses as $address) {
                                    if ($address['created_at'] > $thirty_days_ago) $recent_count++;
                                }
                                echo $recent_count;
                                ?>
                            </div>
                            <div class="text-gray-600">Added Recently</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600 mb-2">
                                <?php echo count($addresses); ?>
                            </div>
                            <div class="text-gray-600">Available</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

<!-- Add Address Modal -->
<div id="addAddressModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-primary to-secondary p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-plus mr-2"></i>Add New Address
                </h3>
                <button onclick="closeModal('addAddressModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <form method="POST" class="space-y-6">
                <input type="hidden" name="add_address" value="1">

                <div>
                    <label for="address_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        Address Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="address_name" id="address_name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                           placeholder="e.g., Home, Work, Office">
                </div>

                <div>
                    <label for="recipient_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        Recipient Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="recipient_name" id="recipient_name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                           placeholder="Full name of the person receiving orders">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">
                        Phone Number <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" name="phone" id="phone" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                           placeholder="+254 7XX XXX XXX">
                </div>

                <div>
                    <label for="street_address" class="block text-sm font-semibold text-gray-700 mb-2">
                        Street Address <span class="text-red-500">*</span>
                    </label>
                    <textarea name="street_address" id="street_address" rows="3" required
                             class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                             placeholder="Street name, building, apartment number"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="city" class="block text-sm font-semibold text-gray-700 mb-2">
                            City <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="city" id="city" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                               placeholder="Nairobi, Mombasa, etc.">
                    </div>
                    <div>
                        <label for="postal_code" class="block text-sm font-semibold text-gray-700 mb-2">
                            Postal Code
                        </label>
                        <input type="text" name="postal_code" id="postal_code"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                               placeholder="00100">
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_default" id="is_default" value="1"
                           class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="is_default" class="ml-2 text-sm text-gray-700">
                        Set as default address
                    </label>
                </div>

                <!-- Modal Footer -->
                <div class="flex gap-3 pt-4">
                    <button type="submit"
                           class="flex-1 bg-primary hover:bg-primary-dark text-white py-3 px-6 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-save mr-2"></i>Add Address
                    </button>
                    <button type="button" onclick="closeModal('addAddressModal')"
                           class="bg-gray-500 hover:bg-gray-600 text-white py-3 px-6 rounded-lg font-semibold transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Address Modal -->
<div id="editAddressModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 p-6 text-white rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold">
                    <i class="fas fa-edit mr-2"></i>Edit Address
                </h3>
                <button onclick="closeModal('editAddressModal')" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <div class="p-6">
            <form method="POST" class="space-y-6">
                <input type="hidden" name="address_id" id="edit_address_id">
                <input type="hidden" name="update_address" value="1">

                <div>
                    <label for="edit_address_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        Address Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="address_name" id="edit_address_name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                           placeholder="e.g., Home, Work, Office">
                </div>

                <div>
                    <label for="edit_recipient_name" class="block text-sm font-semibold text-gray-700 mb-2">
                        Recipient Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="recipient_name" id="edit_recipient_name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                           placeholder="Full name of the person receiving orders">
                </div>

                <div>
                    <label for="edit_phone" class="block text-sm font-semibold text-gray-700 mb-2">
                        Phone Number <span class="text-red-500">*</span>
                    </label>
                    <input type="tel" name="phone" id="edit_phone" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                           placeholder="+254 7XX XXX XXX">
                </div>

                <div>
                    <label for="edit_street_address" class="block text-sm font-semibold text-gray-700 mb-2">
                        Street Address <span class="text-red-500">*</span>
                    </label>
                    <textarea name="street_address" id="edit_street_address" rows="3" required
                             class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                             placeholder="Street name, building, apartment number"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="edit_city" class="block text-sm font-semibold text-gray-700 mb-2">
                            City <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="city" id="edit_city" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                               placeholder="Nairobi, Mombasa, etc.">
                    </div>
                    <div>
                        <label for="edit_postal_code" class="block text-sm font-semibold text-gray-700 mb-2">
                            Postal Code
                        </label>
                        <input type="text" name="postal_code" id="edit_postal_code"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors"
                               placeholder="00100">
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_default" id="edit_is_default" value="1"
                           class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="edit_is_default" class="ml-2 text-sm text-gray-700">
                        Set as default address
                    </label>
                </div>

                <!-- Modal Footer -->
                <div class="flex gap-3 pt-4">
                    <button type="submit"
                           class="flex-1 bg-primary hover:bg-primary-dark text-white py-3 px-6 rounded-lg font-semibold transition-colors">
                        <i class="fas fa-save mr-2"></i>Update Address
                    </button>
                    <button type="button" onclick="closeModal('editAddressModal')"
                           class="bg-gray-500 hover:bg-gray-600 text-white py-3 px-6 rounded-lg font-semibold transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Modal functions
function openAddAddressModal() {
    document.getElementById('addAddressModal').classList.remove('hidden');
    document.getElementById('address_name').focus();
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

function editAddress(address) {
    document.getElementById('edit_address_id').value = address.id;
    document.getElementById('edit_address_name').value = address.address_name;
    document.getElementById('edit_recipient_name').value = address.recipient_name;
    document.getElementById('edit_phone').value = address.phone;
    document.getElementById('edit_street_address').value = address.street_address;
    document.getElementById('edit_city').value = address.city;
    document.getElementById('edit_postal_code').value = address.postal_code;
    document.getElementById('edit_is_default').checked = address.is_default == 1;

    document.getElementById('editAddressModal').classList.remove('hidden');
}

// Close modal when clicking outside
window.onclick = function(event) {
    const addModal = document.getElementById('addAddressModal');
    const editModal = document.getElementById('editAddressModal');

    if (event.target === addModal) {
        addModal.classList.add('hidden');
    }
    if (event.target === editModal) {
        editModal.classList.add('hidden');
    }
}

// Form validation
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const requiredFields = form.querySelectorAll('input[required], textarea[required], select[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('border-red-500');
                isValid = false;
            } else {
                field.classList.remove('border-red-500');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
