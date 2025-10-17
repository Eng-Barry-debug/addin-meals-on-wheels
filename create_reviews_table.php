<?php
// create_reviews_table.php - Create customer reviews table

require_once 'includes/config.php';

try {
    // Create customer_reviews table
    $sql = "CREATE TABLE IF NOT EXISTS customer_reviews (
        id INT PRIMARY KEY AUTO_INCREMENT,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255),
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        review_text TEXT NOT NULL,
        service_type VARCHAR(100),
        review_date DATE NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "✅ Customer reviews table created successfully!\n";

    // Insert some sample reviews
    $sample_reviews = [
        [
            'John Doe',
            'john@example.com',
            5,
            'Absolutely delicious food! The pizza was perfect and delivery was on time. Highly recommend!',
            'Pizza',
            '2024-01-15',
            'approved'
        ],
        [
            'Sarah Smith',
            'sarah@example.com',
            5,
            'The catering service for our event was outstanding. Professional staff and amazing food quality.',
            'Catering',
            '2024-01-20',
            'approved'
        ],
        [
            'Mike Johnson',
            'mike@example.com',
            4,
            'Great cookies and cupcakes! Fresh and tasty. Will definitely order again.',
            'Bakery',
            '2024-01-25',
            'approved'
        ],
        [
            'Emily Davis',
            'emily@example.com',
            5,
            'Exceptional service and food quality. The attention to detail is impressive.',
            'General',
            '2024-02-01',
            'approved'
        ],
        [
            'David Wilson',
            'david@example.com',
            4,
            'Very satisfied with the catering. Food was delicious and presentation was beautiful.',
            'Catering',
            '2024-02-05',
            'approved'
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO customer_reviews
        (customer_name, customer_email, rating, review_text, service_type, review_date, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($sample_reviews as $review) {
        try {
            $stmt->execute($review);
            echo "✅ Sample review added: {$review[0]}\n";
        } catch (Exception $e) {
            echo "⚠️  Sample review already exists: {$review[0]}\n";
        }
    }

    echo "\n🎉 Customer reviews system is ready!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
