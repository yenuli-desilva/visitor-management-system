<?php
/**
 * Users Endpoint: Delete User (Admin Only)
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireAdmin();

$data   = getRequestData();
$userId = (int) ($data['user_id'] ?? $_GET['id'] ?? 0);

if ($userId <= 0) {
    sendResponse(false, 'Valid user ID is required.', null, 400);
}

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);

// Prevent administrator self-deletion
if ($userId === $currentUserId) {
    sendResponse(false, 'You cannot delete your own currently logged-in administrator account.', null, 400);
}

$conn = getDbConnection();
$stmt = $conn->prepare('DELETE FROM Users WHERE UserID = ?');
$stmt->execute([$userId]);

if ($stmt->affected_rows > 0) {
    sendResponse(true, 'User account deleted successfully.');
} else {
    sendResponse(false, 'User not found or already deleted.', null, 404);
}


