<?php
class Wishlist {
    private $db;
    private $user_id;

    public function __construct($db, $user_id) {
        $this->db = $db;
        $this->user_id = $user_id;
    }

    // Add item to wishlist
    public function addItem($menu_item_id) {
        if (!$this->isInWishlist($menu_item_id)) {
            $stmt = $this->db->prepare("INSERT INTO wishlist (user_id, menu_item_id) VALUES (?, ?)");
            return $stmt->execute([$this->user_id, $menu_item_id]);
        }
        return false;
    }

    // Remove item from wishlist
    public function removeItem($menu_item_id) {
        $stmt = $this->db->prepare("DELETE FROM wishlist WHERE user_id = ? AND menu_item_id = ?");
        return $stmt->execute([$this->user_id, $menu_item_id]);
    }

    // Check if item is in wishlist
    public function isInWishlist($menu_item_id) {
        $stmt = $this->db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND menu_item_id = ?");
        $stmt->execute([$this->user_id, $menu_item_id]);
        return $stmt->rowCount() > 0;
    }

    // Get all wishlist items for user
    public function getWishlistItems() {
        $stmt = $this->db->prepare("
            SELECT m.*, c.name as category_name 
            FROM wishlist w 
            JOIN menu_items m ON w.menu_item_id = m.id 
            LEFT JOIN categories c ON m.category_id = c.id
            WHERE w.user_id = ? AND m.status = 'active'
        ");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
