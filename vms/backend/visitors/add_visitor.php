<?php
/**
 * Visitors Endpoint: Add New Visitor & Create Check-In Record
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method. POST expected.', null, 405);
}

$data         = getRequestData();
$name         = trim($data['name'] ?? '');
$nic          = trim($data['nic'] ?? '');
$phone        = trim($data['phone'] ?? '');
$email        = trim($data['email'] ?? '');
$purpose      = trim($data['purpose'] ?? '');
$host         = trim($data['host'] ?? '');
$departmentId = isset($data['department_id']) && $data['department_id'] !== '' ? (int) $data['department_id'] : null;

// Validation
if (empty($name) || empty($nic)) {
    sendResponse(false, 'Visitor name and NIC are required.', null, 400);
}

$conn = getDbConnection();

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

$conn->begin_transaction();

try {
    $currentUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

    // 1. Insert visitor
    $insertVisitor = $conn->prepare('
        INSERT INTO Visitors (UserID, Name, NIC, Phone, Email, Purpose, Host, DepartmentID, CreatedAt)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ');
    $insertVisitor->execute([
        $currentUserId,
        $name,
        $nic,
        !empty($phone) ? $phone : null,
        !empty($email) ? $email : null,
        !empty($purpose) ? $purpose : null,
        !empty($host) ? $host : null,
        $departmentId
    ]);
    $visitorId = (int) $conn->insert_id;

    // 2. Automatically create initial visit check-in record
    $insertVisit = $conn->prepare('
        INSERT INTO Visits (VisitorID, CheckIn, CheckOut, VisitDate, Status, CreatedAt)
        VALUES (?, NOW(), NULL, CURRENT_DATE, "checked-in", NOW())
    ');
    $insertVisit->execute([$visitorId]);
    $visitId = (int) $conn->insert_id;

    $conn->commit();

    sendResponse(true, 'Visitor added successfully and checked in.', [
        'visitor_id' => $visitorId,
        'visit_id'   => $visitId,
        'status'     => 'checked-in'
    ], 201);
} catch (Exception $e) {
    $conn->rollback();
    sendResponse(false, 'Failed to add visitor: ' . $e->getMessage(), null, 500);
}


