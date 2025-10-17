<?php
/**
 * Sample Notification Creator
 * Creates sample notifications for testing the notification system
 */

require_once 'includes/config.php';
require_once 'includes/notifications.php';

// Create sample notifications for admin user (assuming admin user ID is 1)
$admin_id = 1; // Change this to the actual admin user ID

echo "Creating sample notifications for admin user ID: $admin_id\n\n";

// Create some sample notifications
$notifications = [
    [
        'title' => 'Welcome to the Notification System',
        'message' => 'Your notification system is now active. You will receive important updates about orders, customers, and system events.',
        'type' => 'success',
        'priority' => 'medium'
    ],
    [
        'title' => 'New Order Received',
        'message' => 'Order #1001 has been placed by customer John Doe. Please review and process the order.',
        'type' => 'info',
        'priority' => 'high',
        'action_url' => 'orders.php?highlight=1001'
    ],
    [
        'title' => 'Payment Received',
        'message' => 'Payment of $25.50 has been received for order #1001. Order status updated to Paid.',
        'type' => 'success',
        'priority' => 'medium'
    ],
    [
        'title' => 'System Maintenance Scheduled',
        'message' => 'Scheduled maintenance will occur tonight at 2:00 AM EST. Expected downtime: 30 minutes.',
        'type' => 'warning',
        'priority' => 'high'
    ],
    [
        'title' => 'Newsletter Campaign Sent',
        'message' => 'Your newsletter "Holiday Special Offers" has been sent to 150 subscribers. Open rate: 23%.',
        'type' => 'info',
        'priority' => 'medium',
        'action_url' => 'create_newsletter.php?edit=1'
    ],
    [
        'title' => 'Low Stock Alert',
        'message' => 'Chicken Burger ingredients are running low. Current stock: 5 portions. Please reorder.',
        'type' => 'warning',
        'priority' => 'urgent'
    ],
    [
        'title' => 'New Customer Registration',
        'message' => 'Sarah Johnson has registered as a new customer. Welcome email sent successfully.',
        'type' => 'info',
        'priority' => 'low'
    ],
    [
        'title' => 'Database Backup Completed',
        'message' => 'Automated database backup completed successfully at 3:00 AM. Backup size: 2.3 GB.',
        'type' => 'success',
        'priority' => 'low'
    ]
];

foreach ($notifications as $index => $notification) {
    $notification_id = createNotification(
        $admin_id,
        $notification['title'],
        $notification['message'],
        $notification['type'],
        $notification['priority'],
        $notification['action_url'] ?? null,
        null
    );

    if ($notification_id) {
        echo "✅ Created notification: {$notification['title']}\n";
    } else {
        echo "❌ Failed to create notification: {$notification['title']}\n";
    }
}

// Display notification statistics
echo "\n=== NOTIFICATION STATISTICS ===\n";
$stats = getNotificationStats($admin_id);
echo "Total notifications: {$stats['total']}\n";
echo "Unread notifications: {$stats['unread']}\n";
echo "System notifications: {$stats['system']}\n";
echo "Warnings: {$stats['warnings']}\n";
echo "Errors: {$stats['errors']}\n";

echo "\n=== SAMPLE NOTIFICATIONS CREATED ===\n";
?>
