<?php
class Cart {
    private $db;
    private $cart_id;
    private $user_id;
    private $session_id;
    private $is_logged_in = false;

    public function __construct($db) {
        $this->db = $db;
        $this->session_id = session_id();
        $this->initializeCart();
    }

    private function initializeCart() {
        // Check if user is logged in
        if (isset($_SESSION['user_id'])) {
            $this->user_id = $_SESSION['user_id'];
            $this->is_logged_in = true;
            
            // Find existing cart for logged-in user
            $stmt = $this->db->prepare("
                SELECT * FROM carts 
                WHERE (user_id = :user_id OR session_id = :session_id)
                AND status = 'active' 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([
                ':user_id' => $this->user_id,
                ':session_id' => $this->session_id
            ]);
        } else {
            // Find existing cart for guest
            $stmt = $this->db->prepare("
                SELECT * FROM carts 
                WHERE session_id = :session_id 
                AND status = 'active' 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([':session_id' => $this->session_id]);
        }

        $cart = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart) {
            $this->cart_id = $cart['cart_id'];
            // If user logged in and cart doesn't have user_id, update it
            if ($this->is_logged_in && !$cart['user_id']) {
                $this->updateCartUserId($this->user_id);
            }
        } else {
            $this->createNewCart();
        }
    }

    private function createNewCart() {
        $query = "INSERT INTO carts (user_id, session_id) VALUES (:user_id, :session_id)";

        $params = [
            ':session_id' => $this->session_id,
            ':user_id' => $this->is_logged_in ? $this->user_id : null
        ];

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $this->cart_id = $this->db->lastInsertId();
    }

    public function addItem($menu_item_id, $quantity = 1, $price = 0) {
        // Check if item already exists in cart
        $stmt = $this->db->prepare("
            SELECT cart_item_id, quantity FROM cart_items
            WHERE cart_id = :cart_id AND menu_item_id = :menu_item_id
        ");
        $stmt->execute([
            ':cart_id' => $this->cart_id,
            ':menu_item_id' => $menu_item_id
        ]);
        $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_item) {
            // Update quantity if item exists
            $new_quantity = $existing_item['quantity'] + $quantity;
            $stmt = $this->db->prepare("
                UPDATE cart_items
                SET quantity = :quantity, updated_at = NOW()
                WHERE cart_item_id = :cart_item_id
            ");
            $stmt->execute([
                ':quantity' => $new_quantity,
                ':cart_item_id' => $existing_item['cart_item_id']
            ]);
        } else {
            // Add new item
            $stmt = $this->db->prepare("
                INSERT INTO cart_items (cart_id, menu_item_id, quantity, price)
                VALUES (:cart_id, :menu_item_id, :quantity, :price)
            ");
            $stmt->execute([
                ':cart_id' => $this->cart_id,
                ':menu_item_id' => $menu_item_id,
                ':quantity' => $quantity,
                ':price' => $price
            ]);
        }

        $this->updateCartTotal();
    }

    public function removeItem($menu_item_id) {
        $stmt = $this->db->prepare("
            DELETE FROM cart_items
            WHERE cart_id = :cart_id AND menu_item_id = :menu_item_id
        ");
        $stmt->execute([
            ':cart_id' => $this->cart_id,
            ':menu_item_id' => $menu_item_id
        ]);

        $this->updateCartTotal();
    }

    public function updateQuantity($menu_item_id, $quantity) {
        if ($quantity <= 0) {
            $this->removeItem($menu_item_id);
            return;
        }

        $stmt = $this->db->prepare("
            UPDATE cart_items
            SET quantity = :quantity, updated_at = NOW()
            WHERE cart_id = :cart_id AND menu_item_id = :menu_item_id
        ");
        $stmt->execute([
            ':quantity' => $quantity,
            ':cart_id' => $this->cart_id,
            ':menu_item_id' => $menu_item_id
        ]);

        $this->updateCartTotal();
    }

    public function getItems() {
        $stmt = $this->db->prepare("
            SELECT ci.menu_item_id, ci.quantity, ci.price
            FROM cart_items ci
            WHERE ci.cart_id = :cart_id
        ");
        $stmt->execute([':cart_id' => $this->cart_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert to the expected format for compatibility
        $formatted_items = [];
        foreach ($items as $item) {
            $formatted_items[$item['menu_item_id']] = [
                'menu_item_id' => $item['menu_item_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ];
        }

        return $formatted_items;
    }

    public function getTotal() {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(price * quantity), 0) as total
            FROM cart_items
            WHERE cart_id = :cart_id
        ");
        $stmt->execute([':cart_id' => $this->cart_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (float)$result['total'];
    }

    public function clear() {
        $stmt = $this->db->prepare("DELETE FROM cart_items WHERE cart_id = :cart_id");
        $stmt->execute([':cart_id' => $this->cart_id]);
        $this->updateCartTotal();
    }

    /**
     * Get the total number of items in the cart
     * @return int Total number of items
     */
    public function getTotalItems() {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(quantity), 0) as total
            FROM cart_items
            WHERE cart_id = :cart_id
        ");
        $stmt->execute([':cart_id' => $this->cart_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['total'];
    }

    private function updateCartTotal() {
        $total = $this->getTotal();
        $stmt = $this->db->prepare("
            UPDATE carts
            SET updated_at = NOW()
            WHERE cart_id = :cart_id
        ");
        $stmt->execute([':cart_id' => $this->cart_id]);
    }

    private function updateCartItems($items) {
        // This method is no longer needed with the new schema
        // Keeping it for backward compatibility
    }

    private function updateCartUserId($user_id) {
        $stmt = $this->db->prepare("
            UPDATE carts 
            SET user_id = :user_id 
            WHERE cart_id = :cart_id
        ");
        $stmt->execute([
            ':user_id' => $user_id,
            ':cart_id' => $this->cart_id
        ]);
    }
}