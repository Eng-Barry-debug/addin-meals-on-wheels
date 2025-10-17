<?php
// api/get_menu_items.php - API endpoint for fetching menu items by IDs

require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['ids']) || !is_array($input['ids'])) {
        throw new Exception('Invalid request data');
    }

    $itemIds = array_map('intval', $input['ids']);

    if (empty($itemIds)) {
        throw new Exception('No valid item IDs provided');
    }

    // Create placeholders for SQL query
    $placeholders = str_repeat('?,', count($itemIds) - 1) . '?';

    // Fetch menu items
    $stmt = $pdo->prepare("
        SELECT id, name, price, image, description
        FROM menu_items
        WHERE id IN ($placeholders) AND status = 'active'
        ORDER BY FIELD(id, $placeholders)
    ");

    // Execute with item IDs as parameters (twice - once for WHERE IN, once for ORDER BY FIELD)
    $params = array_merge($itemIds, $itemIds);
    $stmt->execute($params);

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return success response
    echo json_encode([
        'success' => true,
        'items' => $items,
        'count' => count($items)
    ]);

} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
