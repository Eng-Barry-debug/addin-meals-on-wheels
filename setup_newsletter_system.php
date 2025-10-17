<?php
// setup_newsletter_system.php - Complete newsletter system setup

require_once 'includes/config.php';

try {
    echo "🚀 Setting up complete newsletter system...\n\n";

    // 1. Create newsletter_campaigns table
    $sql = "CREATE TABLE IF NOT EXISTS newsletter_campaigns (
        id INT PRIMARY KEY AUTO_INCREMENT,
        subject VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        template VARCHAR(100) DEFAULT 'default',
        status ENUM('draft', 'scheduled', 'sending', 'sent', 'cancelled') DEFAULT 'draft',
        scheduled_at TIMESTAMP NULL,
        sent_at TIMESTAMP NULL,
        total_recipients INT DEFAULT 0,
        sent_count INT DEFAULT 0,
        delivered_count INT DEFAULT 0,
        opened_count INT DEFAULT 0,
        clicked_count INT DEFAULT 0,
        bounced_count INT DEFAULT 0,
        unsubscribed_count INT DEFAULT 0,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "✅ Newsletter campaigns table created!\n";

    // 2. Update newsletter_subscriptions table with tracking fields
    $alterQueries = [
        "ALTER TABLE newsletter_subscriptions ADD COLUMN unsubscribe_token VARCHAR(64) UNIQUE",
        "ALTER TABLE newsletter_subscriptions ADD COLUMN last_email_sent TIMESTAMP NULL",
        "ALTER TABLE newsletter_subscriptions ADD COLUMN total_emails_received INT DEFAULT 0",
        "ALTER TABLE newsletter_subscriptions ADD COLUMN emails_opened INT DEFAULT 0",
        "ALTER TABLE newsletter_subscriptions ADD COLUMN emails_clicked INT DEFAULT 0"
    ];

    foreach ($alterQueries as $query) {
        try {
            $pdo->exec($query);
            echo "✅ Added tracking column to subscriptions table!\n";
        } catch (Exception $e) {
            echo "⚠️  Column may already exist: " . $e->getMessage() . "\n";
        }
    }

    // 3. Generate unsubscribe tokens for existing subscribers
    $stmt = $pdo->prepare('SELECT id FROM newsletter_subscriptions WHERE unsubscribe_token IS NULL');
    $stmt->execute();
    $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($subscribers as $subscriber) {
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare('UPDATE newsletter_subscriptions SET unsubscribe_token = ? WHERE id = ?');
        $stmt->execute([$token, $subscriber['id']]);
    }

    echo "✅ Generated unsubscribe tokens for " . count($subscribers) . " existing subscribers!\n";

    // 4. Create newsletter templates table
    $sql = "CREATE TABLE IF NOT EXISTS newsletter_templates (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        html_template TEXT NOT NULL,
        text_template TEXT,
        thumbnail VARCHAR(255),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    $pdo->exec($sql);
    echo "✅ Newsletter templates table created!\n";

    // 5. Insert default newsletter template
    $defaultTemplate = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <title>{{SUBJECT}}</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f8f9fa; }
            .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; border-bottom: 3px solid #C1272D; padding-bottom: 20px; margin-bottom: 30px; }
            .logo { font-size: 24px; font-weight: bold; color: #C1272D; margin-bottom: 10px; }
            .content { margin: 20px 0; }
            .cta-button { display: inline-block; background-color: #C1272D; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; text-align: center; }
            .unsubscribe { color: #C1272D; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='logo'>Addins Meals on Wheels</div>
                <h1>{{SUBJECT}}</h1>
            </div>
            <div class='content'>
                {{CONTENT}}
            </div>
            <div class='footer'>
                <p>You received this email because you subscribed to our newsletter.</p>
                <p><a href='{{UNSUBSCRIBE_URL}}' class='unsubscribe'>Unsubscribe</a> | <a href='{{WEBSITE_URL}}'>Visit Website</a></p>
                <p>&copy; {{YEAR}} Addins Meals on Wheels. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $stmt = $pdo->prepare("INSERT INTO newsletter_templates (name, description, html_template) VALUES (?, ?, ?)");
    $stmt->execute([
        'Default Template',
        'Clean and professional newsletter template with Addins branding',
        $defaultTemplate
    ]);

    echo "✅ Default newsletter template created!\n";

    echo "\n🎉 Complete newsletter system setup finished!\n";
    echo "📋 Summary:\n";
    echo "   • Newsletter campaigns table: ✅ Created\n";
    echo "   • Subscriptions tracking: ✅ Updated\n";
    echo "   • Unsubscribe tokens: ✅ Generated\n";
    echo "   • Templates system: ✅ Ready\n";
    echo "   • Default template: ✅ Installed\n";

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
