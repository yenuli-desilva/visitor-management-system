<?php
/**
 * Users Endpoint: Update User Details (Admin Only)
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method. POST expected.', null, 405);
}

$data     = getRequestData();
$userId   = (int) ($data['user_id'] ?? 0);
$fullName = trim($data['full_name'] ?? '');
$role     = in_array($data['role'] ?? '', ['admin', 'user'], true) ? $data['role'] : null;
$status   = in_array($data['status'] ?? '', ['active', 'blocked'], true) ? $data['status'] : null;
$password = trim($data['password'] ?? '');

if ($userId <= 0 || empty($fullName) || $role === null || $status === null) {
    sendResponse(false, 'User ID, full name, role, and status are required.', null, 400);
}

$currentUserId = (int) ($_SESSION['user_id'] ?? 0);
if ($userId === $currentUserId && $status === 'blocked') {
    sendResponse(false, 'You cannot block your own currently logged-in account.', null, 400);
}

$conn = getDbConnection();

// Verify user exists
$checkStmt = $conn->prepare('SELECT UserID FROM Users WHERE UserID = ? LIMIT 1');
$checkStmt->execute([$userId]);
if (!$checkStmt->get_result()->fetch_assoc()) {
    sendResponse(false, 'User not found.', null, 404);
}

if (!empty($password)) {
    if (strlen($password) < 6) {
        sendResponse(false, 'Password must be at least 6 characters long.', null, 400);
    }
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $updateStmt = $conn->prepare('
        UPDATE Users 
        SET FullName = ?, Role = ?, Status = ?, Password = ? 
        WHERE UserID = ?
    ');
    $updateStmt->execute([$fullName, $role, $status, $hashedPassword, $userId]);
} else {
    $updateStmt = $conn->prepare('
        UPDATE Users 
        SET FullName = ?, Role = ?, Status = ? 
        WHERE UserID = ?
    ');
    $updateStmt->execute([$fullName, $role, $status, $userId]);
}

sendResponse(true, 'User details updated successfully.');


