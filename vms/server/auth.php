<?php
/**
 * Auth API Endpoint
 * Actions: login, register, logout, me
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$payload = getRequestPayload();

// ----------------------------------------------------
// Action: ME (Current Authenticated User)
// ----------------------------------------------------
if ($action === 'me' && $method === 'GET') {
    if (!isLoggedIn()) {
        sendJsonResponse(false, 'Not authenticated', null, 401);
    }
    sendJsonResponse(true, 'User session active', [
        'user' => getCurrentUser()
    ]);
}

// ----------------------------------------------------
// Action: LOGOUT
// ----------------------------------------------------
if ($action === 'logout') {
    logoutUser();
    sendJsonResponse(true, 'Logged out successfully');
}

// Only POST allowed for login & register
if ($method !== 'POST') {
    sendJsonResponse(false, 'Invalid request method. POST expected.', null, 405);
}

$conn = getDbConnection();

// ----------------------------------------------------
// Action: LOGIN
// ----------------------------------------------------
if ($action === 'login') {
    $username = trim($payload['username'] ?? '');
    $password = $payload['password'] ?? '';

    if (empty($username) || empty($password)) {
        sendJsonResponse(false, 'Please provide both username and password.', null, 400);
    }

    $stmt = $conn->prepare('SELECT UserID, Username, Password, FullName, Role, Status FROM Users WHERE Username = ? LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($password, $user['Password'])) {
        sendJsonResponse(false, 'Invalid username or password.', null, 401);
    }

    if ($user['Status'] === 'blocked') {
        sendJsonResponse(false, 'Your account has been deactivated/blocked. Please contact an administrator.', null, 403);
    }

    loginUser($user);

    sendJsonResponse(true, 'Login successful. Welcome back, ' . $user['FullName'] . '!', [
        'user' => [
            'user_id'   => (int) $user['UserID'],
            'username'  => $user['Username'],
            'full_name' => $user['FullName'],
            'role'      => $user['Role']
        ],
        'redirect' => 'dashboard.php'
    ]);
}

// ----------------------------------------------------
// Action: REGISTER
// ----------------------------------------------------
if ($action === 'register') {
    $fullName = trim($payload['full_name'] ?? '');
    $username = trim($payload['username'] ?? '');
    $password = $payload['password'] ?? '';
    $confirmPassword = $payload['confirm_password'] ?? '';

    if (empty($fullName) || empty($username) || empty($password)) {
        sendJsonResponse(false, 'All fields are required.', null, 400);
    }

    if (strlen($fullName) < 2) {
        sendJsonResponse(false, 'Full name must be at least 2 characters.', null, 400);
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        sendJsonResponse(false, 'Username must be 3-30 alphanumeric characters or underscores.', null, 400);
    }

    if (strlen($password) < 6) {
        sendJsonResponse(false, 'Password must be at least 6 characters long.', null, 400);
    }

    if ($password !== $confirmPassword) {
        sendJsonResponse(false, 'Password confirmation does not match.', null, 400);
    }

    // Check username uniqueness
    $stmt = $conn->prepare('SELECT UserID FROM Users WHERE Username = ? LIMIT 1');
    $stmt->execute([$username]);
    if ($stmt->get_result()->fetch_assoc()) {
        sendJsonResponse(false, 'Username is already taken. Please choose another one.', null, 409);
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $insertStmt = $conn->prepare('
        INSERT INTO Users (Username, Password, FullName, Role, Status, CreatedAt)
        VALUES (?, ?, ?, "user", "active", NOW())
    ');
    $insertStmt->execute([$username, $hashedPassword, $fullName]);
    $newUserId = (int) $conn->insert_id;

    $newUser = [
        'UserID'   => $newUserId,
        'Username' => $username,
        'FullName' => $fullName,
        'Role'     => 'user',
        'Status'   => 'active'
    ];

    // Log the new user in automatically
    loginUser($newUser);

    sendJsonResponse(true, 'Account created successfully! Redirecting to your dashboard...', [
        'user' => [
            'user_id'   => $newUserId,
            'username'  => $username,
            'full_name' => $fullName,
            'role'      => 'user'
        ],
        'redirect' => 'dashboard.php'
    ], 201);
}

sendJsonResponse(false, 'Unknown auth action requested.', null, 400);


