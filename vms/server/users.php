<?php
/**
 * Users Management API Endpoint (Administrator Only)
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

// Enforce admin privileges
requireAdmin(true);

$conn = getDbConnection();
$method = $_SERVER['REQUEST_METHOD'];
$payload = getRequestPayload();

// Support _method override
if ($method === 'POST' && isset($payload['_method'])) {
    $method = strtoupper($payload['_method']);
}

$currentUser = getCurrentUser();
$currentUserId = $currentUser['user_id'] ?? 0;

// ----------------------------------------------------
// GET: List all users or single user
// ----------------------------------------------------
if ($method === 'GET') {
    $userId = isset($_GET['id']) ? (int) $_GET['id'] : null;

    if ($userId) {
        $stmt = $conn->prepare('SELECT UserID, Username, FullName, Role, Status, CreatedAt FROM Users WHERE UserID = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            sendJsonResponse(false, 'User not found.', null, 404);
        }
        sendJsonResponse(true, 'User retrieved', ['user' => $user]);
    }

    $stmt = $conn->query('SELECT UserID, Username, FullName, Role, Status, CreatedAt FROM Users ORDER BY UserID ASC');
    $users = $stmt->fetch_all(MYSQLI_ASSOC);

    sendJsonResponse(true, 'Users list retrieved', [
        'users' => $users
    ]);
}

// ----------------------------------------------------
// POST: Add new user
// ----------------------------------------------------
if ($method === 'POST') {
    $username = trim($payload['username'] ?? '');
    $password = $payload['password'] ?? '';
    $fullName = trim($payload['full_name'] ?? '');
    $role     = in_array($payload['role'] ?? '', ['admin', 'user'], true) ? $payload['role'] : 'user';
    $status   = in_array($payload['status'] ?? '', ['active', 'blocked'], true) ? $payload['status'] : 'active';

    if (empty($username) || empty($password) || empty($fullName)) {
        sendJsonResponse(false, 'Username, password, and full name are required.', null, 400);
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        sendJsonResponse(false, 'Username must be 3-30 alphanumeric characters or underscores.', null, 400);
    }

    if (strlen($password) < 6) {
        sendJsonResponse(false, 'Password must be at least 6 characters long.', null, 400);
    }

    // Check unique username
    $checkStmt = $conn->prepare('SELECT UserID FROM Users WHERE Username = ? LIMIT 1');
    $checkStmt->execute([$username]);
    if ($checkStmt->get_result()->fetch_assoc()) {
        sendJsonResponse(false, 'Username is already taken.', null, 409);
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $conn->prepare('
        INSERT INTO Users (Username, Password, FullName, Role, Status, CreatedAt)
        VALUES (?, ?, ?, ?, ?, NOW())
    ');
    $stmt->execute([$username, $hashedPassword, $fullName, $role, $status]);
    $newId = (int) $conn->insert_id;

    sendJsonResponse(true, 'User created successfully.', [
        'user_id' => $newId
    ], 201);
}

// ----------------------------------------------------
// PUT: Edit user or toggle status
// ----------------------------------------------------
if ($method === 'PUT') {
    $userId   = (int) ($payload['user_id'] ?? 0);
    $fullName = trim($payload['full_name'] ?? '');
    $role     = in_array($payload['role'] ?? '', ['admin', 'user'], true) ? $payload['role'] : null;
    $status   = in_array($payload['status'] ?? '', ['active', 'blocked'], true) ? $payload['status'] : null;
    $password = trim($payload['password'] ?? '');

    if ($userId <= 0) {
        sendJsonResponse(false, 'Valid User ID is required.', null, 400);
    }

    // Check target user
    $checkStmt = $conn->prepare('SELECT UserID, Username, Role, Status FROM Users WHERE UserID = ? LIMIT 1');
    $checkStmt->execute([$userId]);
    $targetUser = $checkStmt->get_result()->fetch_assoc();

    if (!$targetUser) {
        sendJsonResponse(false, 'User not found.', null, 404);
    }

    // Quick status toggle action (block/unblock)
    if (isset($payload['toggle_status']) && $payload['toggle_status'] === true) {
        if ($userId === $currentUserId) {
            sendJsonResponse(false, 'You cannot block your own account.', null, 400);
        }
        $newStatus = ($targetUser['Status'] === 'active') ? 'blocked' : 'active';
        $updateStmt = $conn->prepare('UPDATE Users SET Status = ? WHERE UserID = ?');
        $updateStmt->execute([$newStatus, $userId]);
        sendJsonResponse(true, 'User status changed to ' . $newStatus . '.', ['new_status' => $newStatus]);
    }

    // Prevent changing self to blocked
    if ($userId === $currentUserId && $status === 'blocked') {
        sendJsonResponse(false, 'You cannot block your own account.', null, 400);
    }

    if (empty($fullName) || $role === null || $status === null) {
        sendJsonResponse(false, 'Full name, role, and status are required.', null, 400);
    }

    if (!empty($password)) {
        if (strlen($password) < 6) {
            sendJsonResponse(false, 'Password must be at least 6 characters.', null, 400);
        }
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare('
            UPDATE Users 
            SET FullName = ?, Role = ?, Status = ?, Password = ?
            WHERE UserID = ?
        ');
        $stmt->execute([$fullName, $role, $status, $hashed, $userId]);
    } else {
        $stmt = $conn->prepare('
            UPDATE Users 
            SET FullName = ?, Role = ?, Status = ?
            WHERE UserID = ?
        ');
        $stmt->execute([$fullName, $role, $status, $userId]);
    }

    sendJsonResponse(true, 'User details updated successfully.');
}

// ----------------------------------------------------
// DELETE: Delete user
// ----------------------------------------------------
if ($method === 'DELETE') {
    $userId = (int) ($payload['user_id'] ?? $_GET['id'] ?? 0);

    if ($userId <= 0) {
        sendJsonResponse(false, 'Valid User ID is required.', null, 400);
    }

    if ($userId === $currentUserId) {
        sendJsonResponse(false, 'You cannot delete your own active account.', null, 400);
    }

    $stmt = $conn->prepare('DELETE FROM Users WHERE UserID = ?');
    $stmt->execute([$userId]);

    if ($stmt->affected_rows > 0) {
        sendJsonResponse(true, 'User deleted successfully.');
    } else {
        sendJsonResponse(false, 'User not found or already deleted.', null, 404);
    }
}

sendJsonResponse(false, 'Invalid request method.', null, 405);


