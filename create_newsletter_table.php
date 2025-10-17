<?php
// create_newsletter_table.php - Create newsletter subscriptions table

require_once 'includes/config.php';

try {
    // Create newsletter_subscriptions table
    $sql = "CREATE TABLE IF NOT EXISTS newsletter_subscriptions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        email VARCHAR(255) NOT NULL UNIQUE,
        subscription_date DATE NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        unsubscribed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "✅ Newsletter subscriptions table created successfully!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
