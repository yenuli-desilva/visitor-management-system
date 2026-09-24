<?php
/**
 * Authentication Endpoint: Login
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method. POST expected.', null, 405);
}

$data = getRequestData();
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

if (empty($username) || empty($password)) {
    sendResponse(false, 'Username and password are required.', null, 400);
}

$conn = getDbConnection();

$stmt = $conn->prepare('
    SELECT UserID, Username, Password, FullName, Role, Status 
    FROM Users 
    WHERE Username = ? 
    LIMIT 1
');
$stmt->execute([$username]);
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($password, $user['Password'])) {
    sendResponse(false, 'Invalid username or password.', null, 401);
}

if ($user['Status'] === 'blocked') {
    sendResponse(false, 'Your account has been blocked. Please contact an administrator.', null, 403);
}

// Regenerate session ID to prevent session fixation
session_regenerate_id(true);

$_SESSION['user_id']   = (int) $user['UserID'];
$_SESSION['username']  = $user['Username'];
$_SESSION['full_name'] = $user['FullName'];
$_SESSION['role']      = $user['Role'];

// Compatibility keys for app UI
$_SESSION['vms_user_id']   = (int) $user['UserID'];
$_SESSION['vms_username']  = $user['Username'];
$_SESSION['vms_full_name'] = $user['FullName'];
$_SESSION['vms_role']      = $user['Role'];

sendResponse(true, 'Login successful. Welcome, ' . $user['FullName'] . '!', [
    'user_id'   => (int) $user['UserID'],
    'username'  => $user['Username'],
    'full_name' => $user['FullName'],
    'role'      => $user['Role']
]);


