<?php
/**
 * Visitors Endpoint: Update Visitor Information
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    sendResponse(false, 'Invalid request method. POST or PUT expected.', null, 405);
}

$data         = getRequestData();
$visitorId    = (int) ($data['visitor_id'] ?? $data['id'] ?? 0);
$name         = trim($data['name'] ?? '');
$nic          = trim($data['nic'] ?? '');
$phone        = trim($data['phone'] ?? '');
$email        = trim($data['email'] ?? '');
$purpose      = trim($data['purpose'] ?? '');
$host         = trim($data['host'] ?? '');
$departmentId = isset($data['department_id']) && $data['department_id'] !== '' ? (int) $data['department_id'] : null;

if ($visitorId <= 0) {
    sendResponse(false, 'Valid visitor ID is required.', null, 400);
}

if (empty($name) || empty($nic)) {
    sendResponse(false, 'Visitor name and NIC cannot be empty.', null, 400);
}

$conn = getDbConnection();

// Check visitor existence
$checkStmt = $conn->prepare('SELECT VisitorID FROM Visitors WHERE VisitorID = ? LIMIT 1');
$checkStmt->execute([$visitorId]);
if (!$checkStmt->get_result()->fetch_assoc()) {
    sendResponse(false, 'Visitor record not found.', null, 404);
}

// Validate department if provided
if ($departmentId !== null && $departmentId > 0) {
    $deptCheck = $conn->prepare('SELECT DepartmentID FROM Departments WHERE DepartmentID = ? LIMIT 1');
    $deptCheck->execute([$departmentId]);
    if (!$deptCheck->get_result()->fetch_assoc()) {
        sendResponse(false, 'Selected department does not exist.', null, 400);
    }
} else {
    $departmentId = null;
}

$updateStmt = $conn->prepare('
    UPDATE Visitors 
    SET Name = ?, NIC = ?, Phone = ?, Email = ?, Purpose = ?, Host = ?, DepartmentID = ? 
    WHERE VisitorID = ?
');
$updateStmt->execute([
    $name,
    $nic,
    !empty($phone) ? $phone : null,
    !empty($email) ? $email : null,
    !empty($purpose) ? $purpose : null,
    !empty($host) ? $host : null,
    $departmentId,
    $visitorId
]);

sendResponse(true, 'Visitor information updated successfully.');


