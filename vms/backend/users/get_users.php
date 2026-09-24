<?php
/**
 * Users Endpoint: Get All Users (Admin Only)
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireAdmin();

$conn = getDbConnection();
$stmt = $conn->query('
    SELECT UserID, Username, FullName, Role, Status, CreatedAt 
    FROM Users 
    ORDER BY UserID ASC
');
$users = $stmt->fetch_all(MYSQLI_ASSOC);

sendResponse(true, 'Users list retrieved successfully.', [
    'users' => $users
]);


