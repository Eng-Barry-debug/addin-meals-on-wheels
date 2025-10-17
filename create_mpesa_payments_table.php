<?php
// create_mpesa_payments_table.php
require_once 'includes/config.php';

try {
    $sql = "
    CREATE TABLE IF NOT EXISTS mpesa_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        reference VARCHAR(100) NOT NULL,
        paybill_number VARCHAR(20) NOT NULL,
        account_number VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        phone_number VARCHAR(20),
        transaction_code VARCHAR(50),
        status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        INDEX idx_reference (reference),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "M-Pesa payments table created successfully!\n";

} catch (PDOException $e) {
    die("Error creating table: " . $e->getMessage() . "\n");
}
?>
