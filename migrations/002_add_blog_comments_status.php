<?php
// This migration adds a status column to the blog_comments table if it doesn't exist

$migration = new class {
    public function up($pdo) {
        try {
            // Check if status column exists
            $stmt = $pdo->query("SHOW COLUMNS FROM blog_comments LIKE 'status'");
            if ($stmt->rowCount() === 0) {
                // Add status column with default value 'pending'
                $pdo->exec("ALTER TABLE blog_comments ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
                
                // Update existing comments to 'approved' status
                $pdo->exec("UPDATE blog_comments SET status = 'approved' WHERE status IS NULL");
                
                echo "Added status column to blog_comments table.\n";
            } else {
                echo "Status column already exists in blog_comments table.\n";
            }
            
            // Add index on status for better query performance
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_blog_comments_status ON blog_comments(status)");
            
            return true;
        } catch (PDOException $e) {
            echo "Migration failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    public function down($pdo) {
        try {
            // Remove the status column (be careful with this in production!)
            $pdo->exec("ALTER TABLE blog_comments DROP COLUMN IF EXISTS status");
            return true;
        } catch (PDOException $e) {
            echo "Migration rollback failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
};

return $migration;
