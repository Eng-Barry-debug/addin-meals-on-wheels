<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once '../../includes/config.php';

/**
 * Get count of records from a table with optional where clause
 */
function getCount($pdo, $table, $where = '') {
    try {
        $sql = "SELECT COUNT(*) as count FROM `$table`";
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        $stmt = $pdo->query($sql);
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        error_log("Error in getCount: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get status badge class based on status
 */
function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        'processing' => 'bg-blue-100 text-blue-800',
        'shipped' => 'bg-indigo-100 text-indigo-800',
        'delivered' => 'bg-green-100 text-green-800',
        'cancelled' => 'bg-red-100 text-red-800',
        'active' => 'bg-green-100 text-green-800',
        'inactive' => 'bg-gray-100 text-gray-800',
        'draft' => 'bg-gray-100 text-gray-800',
        'published' => 'bg-green-100 text-green-800',
        'featured' => 'bg-purple-100 text-purple-800',
        'new' => 'bg-blue-100 text-blue-800',
        'in_review' => 'bg-yellow-100 text-yellow-800',
        'resolved' => 'bg-green-100 text-green-800',
        'closed' => 'bg-gray-100 text-gray-800',
    ];

    return $classes[strtolower($status)] ?? 'bg-gray-100 text-gray-800';
}

// Ambassador-specific helper functions

function getAmbassadorCounts($pdo) {
    try {
        // For demo purposes until created_by column is added to users table
        // TODO: Update with proper queries once database is migrated
        return [
            'success' => true,
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'message' => 'Referral system pending database update'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

function getAmbassadorEarnings($pdo, $userId, $period = 'month') {
    try {
        // For demo purposes until referral system is implemented
        // TODO: Update with proper earnings calculation once created_by column exists
        return [
            'success' => true,
            'data' => [
                'referral_count' => 0,
                'earnings' => 0,
                'avg_order_value' => 0
            ],
            'message' => 'Earnings tracking pending referral system implementation'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

function getAmbassadorReferrals($pdo, $userId, $limit = 20) {
    try {
        // For demo purposes until referral system is implemented
        // TODO: Update with proper referral queries once created_by column exists
        return [
            'success' => true,
            'referrals' => [],
            'message' => 'Referral tracking pending database update'
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
            echo json_encode(getAmbassadorCounts($pdo));
            break;

        case 'get_earnings':
            $period = $_GET['period'] ?? 'month';
            echo json_encode(getAmbassadorEarnings($pdo, $_SESSION['user_id'] ?? 0, $period));
            break;

        case 'get_referrals':
            $limit = (int)($_GET['limit'] ?? 20);
            echo json_encode(getAmbassadorReferrals($pdo, $_SESSION['user_id'] ?? 0, $limit));
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
