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
        $query = "INSERT INTO carts (user_id, session_id, items, total) 
                  VALUES (:user_id, :session_id, '[]', 0.00)";
        
        $params = [
            ':session_id' => $this->session_id,
            ':user_id' => $this->is_logged_in ? $this->user_id : null
        ];
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $this->cart_id = $this->db->lastInsertId();
    }

    public function addItem($menu_item_id, $quantity = 1, $price = 0) {
        // Get current cart items
        $items = $this->getItems();
        
        // Check if item already exists in cart
        $item_key = false;
        foreach ($items as $key => $item) {
            if ($item['menu_item_id'] == $menu_item_id) {
                $item_key = $key;
                break;
            }
        }
        
        if ($item_key !== false) {
            // Update quantity if item exists
            $items[$item_key]['quantity'] += (int)$quantity;
        } else {
            // Add new item
            $items[] = [
                'menu_item_id' => (int)$menu_item_id,
                'quantity' => (int)$quantity,
                'price' => (float)$price
            ];
        }
        
        $this->updateCartItems($items);
    }

    public function removeItem($menu_item_id) {
        $items = $this->getItems();
        $items = array_filter($items, function($item) use ($menu_item_id) {
            return $item['menu_item_id'] != $menu_item_id;
        });
        $this->updateCartItems(array_values($items));
    }

    public function updateQuantity($menu_item_id, $quantity) {
        if ($quantity <= 0) {
            $this->removeItem($menu_item_id);
            return;
        }

        $items = $this->getItems();
        foreach ($items as &$item) {
            if ($item['menu_item_id'] == $menu_item_id) {
                $item['quantity'] = $quantity;
                break;
            }
        }
        $this->updateCartItems($items);
    }

    public function getItems() {
        $stmt = $this->db->prepare("SELECT items FROM carts WHERE cart_id = :cart_id");
        $stmt->execute([':cart_id' => $this->cart_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Return empty array if no result or items is null/empty
        if (empty($result) || empty($result['items'])) {
            return [];
        }
        
        // Decode the JSON string
        $items = json_decode($result['items'], true);
        
        // Return empty array if JSON decode fails
        return is_array($items) ? $items : [];
    }

    public function getTotal() {
        $items = $this->getItems();
        $total = 0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }

    public function clear() {
        $this->updateCartItems([]);
    }

    public function getCartId() {
        return $this->cart_id;
    }

    /**
     * Get the total number of items in the cart
     * @return int Total number of items
     */
    public function getTotalItems() {
        $items = $this->getItems();
        $total = 0;
        foreach ($items as $item) {
            // Ensure quantity is treated as an integer and is a valid number
            $quantity = (int)$item['quantity'];
            if ($quantity > 0) {
                $total += $quantity;
            }
        }
        return $total;
    }

    private function updateCartItems($items) {
        // Calculate total
        $total = 0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $stmt = $this->db->prepare("
            UPDATE carts 
            SET items = :items, total = :total, updated_at = NOW() 
            WHERE cart_id = :cart_id
        ");
        
        $stmt->execute([
            ':items' => json_encode($items),
            ':total' => $total,
            ':cart_id' => $this->cart_id
        ]);
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