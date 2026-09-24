<?php

/**
 * Users Endpoint: Add User (Admin Only)
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
$fullName = trim($data['full_name'] ?? '');
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';
$role     = in_array($data['role'] ?? '', ['admin', 'user'], true) ? $data['role'] : 'user';
$status   = in_array($data['status'] ?? '', ['active', 'blocked'], true) ? $data['status'] : 'active';

if (empty($fullName) || empty($username) || empty($password)) {
    sendResponse(false, 'Full name, username, and password are required.', null, 400);
}

if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    sendResponse(false, 'Username must be 3-30 alphanumeric characters or underscores.', null, 400);
}

$conn = getDbConnection();

// Check unique username
$checkStmt = $conn->prepare('SELECT UserID FROM Users WHERE Username = ? LIMIT 1');
$checkStmt->execute([$username]);
if ($checkStmt->get_result()->fetch_assoc()) {
    sendResponse(false, 'Username is already taken.', null, 409);
}

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$insertStmt = $conn->prepare('
    INSERT INTO Users (Username, Password, FullName, Role, Status, CreatedAt)
    VALUES (?, ?, ?, ?, ?, NOW())
');
$insertStmt->execute([$username, $hashedPassword, $fullName, $role, $status]);
$newUserId = (int) $conn->insert_id;

sendResponse(true, 'User account created successfully.', [
    'user_id'   => $newUserId,
    'username'  => $username,
    'full_name' => $fullName,
    'role'      => $role,
    'status'    => $status
], 201);


