<?php
/**
 * Departments API Endpoint
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin(true);

$conn = getDbConnection();

$stmt = $conn->query('
    SELECT DepartmentID, Name, Status 
    FROM Departments 
    WHERE Status = "active" 
    ORDER BY Name ASC
');

$departments = $stmt->fetch_all(MYSQLI_ASSOC);

sendJsonResponse(true, 'Departments retrieved successfully', [
    'departments' => $departments
]);


