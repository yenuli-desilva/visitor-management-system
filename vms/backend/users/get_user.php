<?php
/**
 * Users Endpoint: Get Single User by ID (Admin Only)
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireAdmin();

$userId = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0);

if ($userId <= 0) {
    sendResponse(false, 'Valid user ID is required.', null, 400);
}

$conn = getDbConnection();
$stmt = $conn->prepare('
    SELECT UserID, Username, FullName, Role, Status, CreatedAt 
    FROM Users 
    WHERE UserID = ? 
    LIMIT 1
');
$stmt->execute([$userId]);
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    sendResponse(false, 'User not found.', null, 404);
}

sendResponse(true, 'User retrieved successfully.', [
    'user' => $user
]);


