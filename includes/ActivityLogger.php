<?php
/**
 * Activity Logger Class
 * Handles logging of all administrative activities
 */

class ActivityLogger {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Log an activity
     */
    public function log($activity_type, $activity_action, $description, $entity_type = null, $entity_id = null, $old_values = null, $new_values = null) {
        try {
            $user_id = $_SESSION['user_id'] ?? null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $stmt = $this->pdo->prepare("
                INSERT INTO activity_logs (user_id, activity_type, activity_action, description, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $user_id,
                $activity_type,
                $activity_action,
                $description,
                $entity_type,
                $entity_id,
                $old_values ? json_encode($old_values) : null,
                $new_values ? json_encode($new_values) : null,
                $ip_address,
                $user_agent
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("Activity logging failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT al.*, u.name as user_name
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to fetch recent activities: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Log order activities
     */
    public function logOrderActivity($action, $order_id, $details = null) {
        $descriptions = [
            'created' => 'New order created',
            'updated' => 'Order updated',
            'status_changed' => 'Order status changed',
            'cancelled' => 'Order cancelled',
            'completed' => 'Order completed'
        ];

        return $this->log('order', $action, $descriptions[$action] ?? "Order {$action}", 'order', $order_id, null, $details);
    }

    /**
     * Log menu item activities
     */
    public function logMenuActivity($action, $menu_id, $menu_name = null) {
        $descriptions = [
            'created' => 'New menu item added',
            'updated' => 'Menu item updated',
            'deleted' => 'Menu item deleted',
            'activated' => 'Menu item activated',
            'deactivated' => 'Menu item deactivated'
        ];

        return $this->log('menu', $action, $descriptions[$action] ?? "Menu {$action}", 'menu_item', $menu_id, null, ['name' => $menu_name]);
    }

    /**
     * Log user activities
     */
    public function logUserActivity($action, $user_id, $user_name = null) {
        $descriptions = [
            'created' => 'New user registered',
            'updated' => 'User profile updated',
            'deleted' => 'User account deleted',
            'activated' => 'User account activated',
            'deactivated' => 'User account deactivated',
            'login' => 'User logged in',
            'logout' => 'User logged out'
        ];

        return $this->log('user', $action, $descriptions[$action] ?? "User {$action}", 'user', $user_id, null, ['name' => $user_name]);
    }

    /**
     * Get activity icon based on type and action
     */
    public function getActivityIcon($activity_type, $activity_action) {
        $icons = [
            'order' => [
                'created' => 'fas fa-shopping-cart',
                'updated' => 'fas fa-edit',
                'status_changed' => 'fas fa-exchange-alt',
                'cancelled' => 'fas fa-times-circle',
                'completed' => 'fas fa-check-circle'
            ],
            'menu' => [
                'created' => 'fas fa-plus',
                'updated' => 'fas fa-edit',
                'deleted' => 'fas fa-trash',
                'activated' => 'fas fa-check',
                'deactivated' => 'fas fa-pause'
            ],
            'user' => [
                'created' => 'fas fa-user-plus',
                'updated' => 'fas fa-user-edit',
                'deleted' => 'fas fa-user-minus',
                'activated' => 'fas fa-user-check',
                'deactivated' => 'fas fa-user-times',
                'login' => 'fas fa-sign-in-alt',
                'logout' => 'fas fa-sign-out-alt'
            ],
            'system' => [
                'backup' => 'fas fa-database',
                'maintenance' => 'fas fa-cog',
                'error' => 'fas fa-exclamation-triangle'
            ]
        ];

        return $icons[$activity_type][$activity_action] ?? 'fas fa-info-circle';
    }

    /**
     * Get activity color based on type
     */
    public function getActivityColor($activity_type) {
        $colors = [
            'order' => 'green',
            'menu' => 'blue',
            'user' => 'purple',
            'system' => 'orange',
            'login' => 'teal',
            'logout' => 'gray'
        ];

        return $colors[$activity_type] ?? 'gray';
    }
}
?>
