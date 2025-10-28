<?php
/**
 * Get count of records from a table with optional where clause
 * 
 * @param PDO $pdo Database connection
 * @param string $table Table name
 * @param string $where WHERE clause (without the WHERE keyword)
 * @param array $params Parameters for prepared statement
 * @return int Number of records
 */
function getCount($pdo, $table, $where = '', $params = []) {
    try {
        $sql = "SELECT COUNT(*) as count FROM $table";
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)($result['count'] ?? 0);
    } catch (PDOException $e) {
        error_log("Error getting count from $table: " . $e->getMessage());
        return 0;
    }
}

/**
 * Update post like count
 */
function updatePostLikeCount($pdo, $postId, $increment = 1) {
    try {
        // Update the like count
        $stmt = $pdo->prepare("UPDATE blog_posts SET like_count = GREATEST(0, like_count + :increment) WHERE id = :post_id");
        $stmt->execute([':increment' => $increment, ':post_id' => $postId]);
        
        // Get the updated like count
        $stmt = $pdo->prepare("SELECT like_count FROM blog_posts WHERE id = ?");
        $stmt->execute([$postId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? (int)$result['like_count'] : 0;
    } catch (PDOException $e) {
        error_log("Error updating like count: " . $e->getMessage());
        return 0;
    }
}

// getBlogComments function has been moved to includes/config.php to avoid duplication

/**
 * Get time ago format
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $time = time() - $time;
    
    $units = [
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    ];
    
    foreach ($units as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '') . ' ago';
    }
    
    return 'just now';
}

/**
 * Get count of completed orders with optional filters
 */
function getCompletedOrdersCount($pdo, $where_conditions = [], $params = []) {
    try {
        // Always filter for completed orders only
        $conditions = $where_conditions;
        $conditions[] = "status IN ('delivered', 'completed')";

        $sql = "SELECT COUNT(*) as count FROM orders";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    } catch (PDOException $e) {
        error_log("Error in getCompletedOrdersCount: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get total revenue from orders with optional filters
 */
function getTotalRevenue($pdo, $where_conditions = [], $params = []) {
    try {
        // Remove any existing status filters since we always want completed orders
        $conditions = [];
        $new_params = [];

        foreach ($where_conditions as $condition) {
            if (strpos($condition, 'status = :status_filter') === false &&
                strpos($condition, 'status IN') === false) {
                $conditions[] = $condition;
            }
        }

        foreach ($params as $key => $value) {
            if ($key !== ':status_filter') {
                $new_params[$key] = $value;
            }
        }

        // Always filter for completed orders only
        $conditions[] = "status IN ('delivered', 'completed')";

        $sql = "SELECT COALESCE(SUM(total), 0) as total_revenue FROM orders";
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $pdo->prepare($sql);
        foreach ($new_params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return (float)$stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];
    } catch (PDOException $e) {
        error_log("Error in getTotalRevenue: " . $e->getMessage());
        return 0.0;
    }
}

/**
 * Delete a record from a table by ID
 */
function deleteRecord($table, $id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        error_log("Error in deleteRecord: " . $e->getMessage());
        return false;
    }
}

/**
 * Get a single record by ID
 */
function getRecordById($table, $id, $columns = '*') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT $columns FROM `$table` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getRecordById: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all records from a table with optional where clause and order
 */
function getAllRecords($table, $where = '', $orderBy = 'id DESC', $limit = '', $params = []) {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM `$table`";
        
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        
        if (!empty($orderBy)) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if (!empty($limit)) {
            $sql .= " LIMIT $limit";
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getAllRecords: " . $e->getMessage());
        return [];
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

/**
 * Upload file with validation
 */
function uploadFile($file, $targetDir = '../uploads/', $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error'];
    }
    
    $fileName = basename($file['name']);
    $targetPath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    
    // Check if file already exists
    $counter = 1;
    $originalName = pathinfo($fileName, PATHINFO_FILENAME);
    while (file_exists($targetPath)) {
        $fileName = $originalName . '_' . $counter . '.' . $fileType;
        $targetPath = $targetDir . $fileName;
        $counter++;
    }
    
    // Check file type
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Sorry, only ' . implode(', ', $allowedTypes) . ' files are allowed.'];
    }
    
    // Check file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Sorry, your file is too large. Maximum size is 5MB.'];
    }
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [
            'success' => true, 
            'file_name' => $fileName,
            'file_path' => $targetPath,
            'message' => 'File uploaded successfully'
        ];
    } else {
        return ['success' => false, 'message' => 'Sorry, there was an error uploading your file.'];
    }
}

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Format date
 */
function formatDate($date, $format = 'M j, Y') {
    $dateTime = new DateTime($date);
    return $dateTime->format($format);
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = 'KES') {
    return $currency . ' ' . number_format($amount, 2);
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $type, $message) {
    $_SESSION['message'] = ['type' => $type, 'text' => $message];
    header("Location: $url");
    exit();
}

/**
 * Generate a random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/**
 * Generate a unique order number
 */
function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Check if user is logged in and has required role
 */
function checkAuth($requiredRole = 'admin') {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== $requiredRole) {
        header('Location: /admin/login.php');
        exit();
    }
}

/**
 * Get pagination variables
 */
function getPagination($currentPage, $totalItems, $perPage) {
    $totalPages = ceil($totalItems / $perPage);
    $offset = ($currentPage - 1) * $perPage;
    
    return [
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'per_page' => $perPage,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'start_item' => $offset + 1,
        'end_item' => min($offset + $perPage, $totalItems),
        'total_items' => $totalItems
    ];
}

/**
 * Get pagination links
 */
function getPaginationLinks($currentPage, $totalPages, $baseUrl, $queryParams = []) {
    $links = [];
    $maxLinks = 5; // Number of page links to show around current page
    
    // Build query string from params
    $queryString = '';
    if (!empty($queryParams)) {
        $queryString = '&' . http_build_query($queryParams);
    }
    
    // Previous page link
    if ($currentPage > 1) {
        $links[] = [
            'url' => $baseUrl . '?page=' . ($currentPage - 1) . $queryString,
            'label' => '&laquo; Previous',
            'active' => false,
            'disabled' => false
        ];
    }
    
    // Page links
    $start = max(1, $currentPage - floor($maxLinks / 2));
    $end = min($totalPages, $start + $maxLinks - 1);
    
    // Adjust start if we're near the end
    if ($end - $start < $maxLinks - 1) {
        $start = max(1, $end - $maxLinks + 1);
    }
    
    // First page
    if ($start > 1) {
        $links[] = [
            'url' => $baseUrl . '?page=1' . $queryString,
            'label' => '1',
            'active' => false,
            'disabled' => false
        ];
        
        if ($start > 2) {
            $links[] = [
                'url' => '',
                'label' => '...',
                'active' => false,
                'disabled' => true
            ];
        }
    }
    
    // Middle pages
    for ($i = $start; $i <= $end; $i++) {
        $links[] = [
            'url' => $baseUrl . '?page=' . $i . $queryString,
            'label' => (string)$i,
            'active' => $i == $currentPage,
            'disabled' => false
        ];
    }
    
    // Last page
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $links[] = [
                'url' => '',
                'label' => '...',
                'active' => false,
                'disabled' => true
            ];
        }
        
        $links[] = [
            'url' => $baseUrl . '?page=' . $totalPages . $queryString,
            'label' => (string)$totalPages,
            'active' => false,
            'disabled' => false
        ];
    }
    
    // Next page link
    if ($currentPage < $totalPages) {
        $links[] = [
            'url' => $baseUrl . '?page=' . ($currentPage + 1) . $queryString,
            'label' => 'Next &raquo;',
            'active' => false,
            'disabled' => false
        ];
    }
    
    return $links;
}
