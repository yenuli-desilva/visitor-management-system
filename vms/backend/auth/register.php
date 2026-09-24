<?php
/**
 * Authentication Endpoint: Register
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method. POST expected.', null, 405);
}

$data = getRequestData();
$fullName = trim($data['full_name'] ?? '');
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';
$confirmPassword = $data['confirm_password'] ?? '';

// 1. Validate required fields
if (empty($fullName) || empty($username) || empty($password) || empty($confirmPassword)) {
    sendResponse(false, 'All fields (full name, username, password, confirm password) are required.', null, 400);
}

if (strlen($fullName) < 2) {
    sendResponse(false, 'Full name must be at least 2 characters long.', null, 400);
}

// 2. Validate username format
if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    sendResponse(false, 'Username must be between 3 and 30 alphanumeric characters or underscores.', null, 400);
}

// 3. Validate password strength
if (strlen($password) < 6) {
    sendResponse(false, 'Password must be at least 6 characters long.', null, 400);
}

// 4. Validate password confirmation
if ($password !== $confirmPassword) {
    sendResponse(false, 'Password confirmation does not match.', null, 400);
}

$conn = getDbConnection();

// 5. Check username uniqueness
$checkStmt = $conn->prepare('SELECT UserID FROM Users WHERE Username = ? LIMIT 1');
$checkStmt->execute([$username]);
if ($checkStmt->get_result()->fetch_assoc()) {
    sendResponse(false, 'Username is already taken. Please select another.', null, 409);
}

// 6. Hash password securely
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

// 7. Insert new ordinary user record
$insertStmt = $conn->prepare('
    INSERT INTO Users (Username, Password, FullName, Role, Status, CreatedAt)
    VALUES (?, ?, ?, "user", "active", NOW())
');
$insertStmt->execute([$username, $hashedPassword, $fullName]);
$newUserId = (int) $conn->insert_id;

sendResponse(true, 'User registered successfully. You may now sign in.', [
    'user_id'   => $newUserId,
    'username'  => $username,
    'full_name' => $fullName,
    'role'      => 'user'
], 201);


