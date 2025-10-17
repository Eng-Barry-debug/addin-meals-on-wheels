<?php
// Include database connection
require_once '../../admin/includes/config.php';

/**
 * Get count of records from a table with optional WHERE clause
 *
 * @param PDO $pdo Database connection
 * @param string $table Table name
 * @param string $where_clause Optional WHERE clause (without WHERE keyword)
 * @return int Number of matching records
 */
function getCount($pdo, $table, $where_clause = '') {
    try {
        $sql = "SELECT COUNT(*) FROM $table";
        if (!empty($where_clause)) {
            $sql .= " WHERE " . $where_clause;
        }

        $stmt = $pdo->query($sql);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("getCount error: " . $e->getMessage());
        return 0;
    }
}

// Delivery-specific helper functions

function getDeliveryCounts($pdo) {
    try {
        $pendingDeliveries = getCount($pdo, 'orders', 'status = "ready_for_delivery"');
        $outForDelivery = getCount($pdo, 'orders', 'status = "out_for_delivery"');
        $completedToday = getCount($pdo, 'orders', 'status = "delivered" AND DATE(updated_at) = CURDATE()');

        return [
            'success' => true,
            'pending' => $pendingDeliveries,
            'active' => $outForDelivery,
            'completed_today' => $completedToday
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

function updateDeliveryStatus($pdo, $orderId, $status, $userId) {
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        $result = $stmt->execute([$status, $orderId]);

        if ($result) {
            // Log the status change
            require_once '../../includes/ActivityLogger.php';
            $activityLogger = new ActivityLogger($pdo);
            $activityLogger->log('delivery', 'status_update', "Order {$orderId} status changed to {$status}", 'order', $orderId);

            return [
                'success' => true,
                'message' => "Order status updated to {$status}"
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update order status'
            ];
        }
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

function getDeliveryEarnings($pdo, $userId, $period = 'today') {
    try {
        $whereClause = '';
        $params = [];

        switch ($period) {
            case 'today':
                $whereClause = 'DATE(updated_at) = CURDATE()';
                break;
            case 'week':
                $whereClause = 'updated_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
                break;
            case 'month':
                $whereClause = 'DATE_FORMAT(updated_at, "%Y-%m") = DATE_FORMAT(CURDATE(), "%Y-%m")';
                break;
            default:
                $whereClause = '1=1';
        }

        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) as delivery_count,
                COALESCE(SUM(total * 0.1), 0) as earnings,
                AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_time
            FROM orders
            WHERE status = 'delivered' AND {$whereClause}
        ");
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'data' => $result
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

function getDeliveryRoutes($pdo, $userId) {
    try {
        // Get current delivery routes (grouped by area)
        $stmt = $pdo->prepare("
            SELECT
                LEFT(delivery_address, 20) as area,
                COUNT(*) as order_count,
                GROUP_CONCAT(id) as order_ids,
                MIN(created_at) as earliest_order,
                MAX(created_at) as latest_order
            FROM orders
            WHERE status IN ('ready_for_delivery', 'out_for_delivery')
            GROUP BY LEFT(delivery_address, 20)
            ORDER BY order_count DESC
            LIMIT 10
        ");
        $stmt->execute();
        $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'success' => true,
            'routes' => $routes
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// API endpoint handler
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    switch ($_GET['action']) {
        case 'get_counts':
            echo json_encode(getDeliveryCounts($pdo));
            break;

        case 'get_earnings':
            $period = $_GET['period'] ?? 'today';
            echo json_encode(getDeliveryEarnings($pdo, $_SESSION['user_id'] ?? 0, $period));
            break;

        case 'get_routes':
            echo json_encode(getDeliveryRoutes($pdo, $_SESSION['user_id'] ?? 0));
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
    exit();
}
?>
