<?php
/**
 * Users Endpoint: Toggle Account Status (Block / Unblock - Admin Only)
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
if ($userId === $currentUserId) {
    sendResponse(false, 'You cannot block your own currently logged-in account.', null, 400);
}

$conn = getDbConnection();
$checkStmt = $conn->prepare('SELECT UserID, Username, Status FROM Users WHERE UserID = ? LIMIT 1');
$checkStmt->execute([$userId]);
$user = $checkStmt->get_result()->fetch_assoc();

if (!$user) {
    sendResponse(false, 'User not found.', null, 404);
}

$newStatus = ($user['Status'] === 'active') ? 'blocked' : 'active';
$updateStmt = $conn->prepare('UPDATE Users SET Status = ? WHERE UserID = ?');
$updateStmt->execute([$newStatus, $userId]);

sendResponse(true, "User account status changed to '{$newStatus}'.", [
    'user_id'    => $userId,
    'new_status' => $newStatus
]);


