<?php
/**
 * Departments Endpoint: Get Active Departments
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireLogin();

$conn = getDbConnection();
$stmt = $conn->query('
    SELECT DepartmentID, Name, Status, CreatedAt 
    FROM Departments 
    WHERE Status = "active" 
    ORDER BY Name ASC
');
$departments = $stmt->fetch_all(MYSQLI_ASSOC);

sendResponse(true, 'Active departments retrieved successfully.', [
    'departments' => $departments
]);


