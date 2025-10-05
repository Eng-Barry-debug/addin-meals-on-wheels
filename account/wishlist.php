<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/Wishlist.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: ../auth/login.php');
    exit();
}

// Initialize wishlist
$wishlist = new Wishlist($pdo, $_SESSION['user_id']);
$wishlist_items = $wishlist->getWishlistItems();
?>

<div class="bg-gray-50 min-h-screen py-8">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">My Wishlist</h1>
            <a href="menu.php" class="text-primary hover:text-primary-dark">
                <i class="fas fa-arrow-left mr-1"></i> Continue Shopping
            </a>
        </div>

        <?php if (empty($wishlist_items)): ?>
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <i class="far fa-heart text-5xl text-gray-300 mb-4"></i>
                <h2 class="text-xl font-medium text-gray-700 mb-2">Your wishlist is empty</h2>
                <p class="text-gray-500 mb-6">Save your favorite items here to view them later</p>
                <a href="menu.php" class="inline-block bg-primary hover:bg-primary-dark text-white font-medium py-2 px-6 rounded-md transition-colors">
                    Browse Menu
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                        <a href="menu-single.php?id=<?= $item['id'] ?>" class="block">
                            <div class="h-48 bg-gray-100 overflow-hidden">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="uploads/menu/<?= htmlspecialchars($item['image']) ?>" 
                                         alt="<?= htmlspecialchars($item['name']) ?>"
                                         class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center">
                                        <i class="fas fa-utensils text-4xl text-gray-300"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-4">
                                <div class="flex justify-between items-start">
                                    <h3 class="font-medium text-gray-900 mb-1"><?= htmlspecialchars($item['name']) ?></h3>
                                    <form class="wishlist-form" method="POST" action="menu-single.php" onsubmit="return toggleWishlist(event, <?= $item['id'] ?>)">
                                        <input type="hidden" name="wishlist_action" value="remove">
                                        <input type="hidden" name="menu_item_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-600 focus:outline-none">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                    </form>
                                </div>
                                <?php if (!empty($item['category_name'])): ?>
                                    <p class="text-sm text-gray-500 mb-2"><?= htmlspecialchars($item['category_name']) ?></p>
                                <?php endif; ?>
                                <p class="text-lg font-bold text-primary">KSh <?= number_format($item['price'], 2) ?></p>
                                <a href="/menu-single.php?id=<?= $item['id'] ?>" class="mt-3 inline-block w-full text-center bg-primary hover:bg-primary-dark text-white py-2 px-4 rounded-md transition-colors">
                                    View Details
                                </a>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Toggle wishlist item
function toggleWishlist(event, menuItemId) {
    event.preventDefault();
    event.stopPropagation();
    
    const form = event.target.closest('form');
    const formData = new FormData(form);
    
    // Toggle the action for next time
    const actionInput = form.querySelector('input[name="wishlist_action"]');
    actionInput.value = actionInput.value === 'add' ? 'remove' : 'add';
    
    // Toggle heart icon
    const heartIcon = form.querySelector('i');
    if (heartIcon) {
        heartIcon.classList.toggle('far');
        heartIcon.classList.toggle('fas');
    }
    
    // If on wishlist page, remove the item from the DOM
    if (window.location.pathname.endsWith('wishlist.php')) {
        form.closest('.bg-white').style.opacity = '0';
        setTimeout(() => {
            form.closest('.bg-white').remove();
            // If no more items, show empty state
            if (document.querySelectorAll('.bg-white').length === 0) {
                window.location.reload();
            }
        }, 300);
    }
    
    // Send AJAX request
    fetch('', {
        method: 'POST',
        body: formData
    });
    
    return false;
}
</script>

<?php require_once '../includes/footer.php'; ?>
