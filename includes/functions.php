<?php
// Utility Functions

// Format price
function formatPrice($price) {
    return '$' . number_format($price, 2);
}

// Format date
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

// Calculate days between two dates
function calculateDays($pickup_date, $return_date) {
    $datetime1 = new DateTime($pickup_date);
    $datetime2 = new DateTime($return_date);
    $interval = $datetime1->diff($datetime2);
    return $interval->days + 1;
}

// Calculate total price
function calculateTotal($price_per_day, $days) {
    return $price_per_day * $days;
}

// Check if car is available for dates
function isCarAvailable($car_id, $pickup_date, $return_date, $exclude_reservation_id = null) {
    $sql = "SELECT COUNT(*) as count FROM reservations 
            WHERE car_id = ? AND status IN ('Confirmed', 'Pending')
            AND ((pickup_date <= ? AND return_date >= ?) 
            OR (pickup_date <= ? AND return_date >= ?)
            OR (pickup_date >= ? AND return_date <= ?))";
    
    $params = [$car_id, $pickup_date, $pickup_date, $return_date, $return_date, $pickup_date, $return_date];
    
    if ($exclude_reservation_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_reservation_id;
    }
    
    $result = fetchOne($sql, $params);
    return $result['count'] == 0;
}

// Upload file
function uploadFile($file, $folder = 'cars') {
    $target_dir = UPLOAD_PATH . $folder . '/';
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file
    if (!in_array($file_extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size too large. Maximum size is 5MB.'];
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'filename' => $folder . '/' . $new_filename];
    } else {
        return ['success' => false, 'message' => 'Error uploading file.'];
    }
}

// Delete file
function deleteFile($filename) {
    $file_path = UPLOAD_PATH . $filename;
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    return true;
}

// Pagination
function getPagination($total_items, $items_per_page, $current_page) {
    $total_pages = ceil($total_items / $items_per_page);
    
    return [
        'total_items' => $total_items,
        'items_per_page' => $items_per_page,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => ($current_page - 1) * $items_per_page,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'prev_page' => $current_page - 1,
        'next_page' => $current_page + 1
    ];
}

// Send email (basic implementation)
function sendEmail($to, $subject, $message) {
    $headers = "From: " . ADMIN_EMAIL . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Log activity
function logActivity($user_id, $action, $details = '') {
    $sql = "INSERT INTO activity_logs (user_id, action, details, created_at) 
            VALUES (?, ?, ?, NOW())";
    executeQuery($sql, [$user_id, $action, $details]);
}
?>
