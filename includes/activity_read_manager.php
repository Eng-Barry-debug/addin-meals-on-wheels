<?php
/**
 * Activity Read Status Management Functions
 * Handles tracking which activities each user has read
 */

// Include database configuration
require_once 'config.php';

/**
 * Create user_activity_reads table if it doesn't exist
 */
function createUserActivityReadsTable() {
    global $pdo;

    try {
        $sql = "
            CREATE TABLE IF NOT EXISTS user_activity_reads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                activity_id INT NOT NULL,
                read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_activity (user_id, activity_id),
                INDEX idx_user_id (user_id),
                INDEX idx_activity_id (activity_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (activity_id) REFERENCES activity_logs(id) ON DELETE CASCADE
            )
        ";

        $pdo->exec($sql);
        return true;
    } catch (PDOException $e) {
        error_log("Error creating user_activity_reads table: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark an activity as read for a specific user
 */
function markActivityAsRead($user_id, $activity_id) {
    global $pdo;

    try {
        // First ensure the table exists
        createUserActivityReadsTable();

        $stmt = $pdo->prepare("
            INSERT INTO user_activity_reads (user_id, activity_id, read_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE read_at = NOW()
        ");

        return $stmt->execute([$user_id, $activity_id]);
    } catch (PDOException $e) {
        error_log("Error marking activity as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread activities for a user (activities from last 24 hours that haven't been marked as read)
 */
function getUnreadActivities($user_id, $limit = 10) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT al.*, u.name as user_name
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN user_activity_reads uar ON al.id = uar.activity_id AND uar.user_id = ?
            WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND (al.user_id IS NULL OR al.user_id != ?)
            AND uar.id IS NULL
            ORDER BY al.created_at DESC
            LIMIT ?
        ");

        $stmt->execute([$user_id, $user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting unread activities: " . $e->getMessage());
        return [];
    }
}

/**
 * Get unread activity count for a user
 */
function getUnreadActivityCount($user_id) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM activity_logs al
            LEFT JOIN user_activity_reads uar ON al.id = uar.activity_id AND uar.user_id = ?
            WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND (al.user_id IS NULL OR al.user_id != ?)
            AND uar.id IS NULL
        ");

        $stmt->execute([$user_id, $user_id]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error getting unread activity count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Mark all activities as read for a user
 */
function markAllActivitiesAsRead($user_id) {
    global $pdo;

    try {
        // First ensure the table exists
        createUserActivityReadsTable();

        // Get all unread activity IDs for this user
        $unreadStmt = $pdo->prepare("
            SELECT al.id
            FROM activity_logs al
            LEFT JOIN user_activity_reads uar ON al.id = uar.activity_id AND uar.user_id = ?
            WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND (al.user_id IS NULL OR al.user_id != ?)
            AND uar.id IS NULL
        ");

        $unreadStmt->execute([$user_id, $user_id]);
        $unreadActivities = $unreadStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($unreadActivities)) {
            return true; // No unread activities to mark
        }

        // Insert all unread activities as read
        $placeholders = str_repeat('(?, ?),', count($unreadActivities));
        $placeholders = rtrim($placeholders, ',');

        $values = [];
        foreach ($unreadActivities as $activity) {
            $values[] = $user_id;
            $values[] = $activity['id'];
        }

        $stmt = $pdo->prepare("
            INSERT INTO user_activity_reads (user_id, activity_id, read_at)
            VALUES {$placeholders}
            ON DUPLICATE KEY UPDATE read_at = NOW()
        ");

        return $stmt->execute($values);
    } catch (PDOException $e) {
        error_log("Error marking all activities as read: " . $e->getMessage());
        return false;
    }
}
?>
