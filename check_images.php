<?php
// check_images.php - Script to identify missing image files
require_once 'includes/config.php';

$uploadDir = 'uploads/menu/';
$missingImages = [];

try {
    // Fetch all menu items with images
    $stmt = $pdo->query("SELECT id, name, image, additional_images FROM menu_items WHERE image IS NOT NULL OR additional_images IS NOT NULL OR additional_images != '[]'");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($items as $item) {
        // Check main image
        if (!empty($item['image']) && !file_exists($uploadDir . $item['image'])) {
            $missingImages[] = ['type' => 'main', 'item_id' => $item['id'], 'item_name' => $item['name'], 'image' => $item['image']];
        }

        // Check additional images
        $additionalImages = json_decode($item['additional_images'] ?? '[]', true) ?: [];
        foreach ($additionalImages as $img) {
            if (!file_exists($uploadDir . $img)) {
                $missingImages[] = ['type' => 'additional', 'item_id' => $item['id'], 'item_name' => $item['name'], 'image' => $img];
            }
        }
    }

    if (!empty($missingImages)) {
        echo "Missing Images Found:\n";
        foreach ($missingImages as $missing) {
            echo "{$missing['type']} image for '{$missing['item_name']}' (ID: {$missing['item_id']}): {$missing['image']}\n";
        }
    } else {
        echo "All images are present.\n";
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
