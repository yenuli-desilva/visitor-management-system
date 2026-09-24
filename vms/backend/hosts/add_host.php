<?php
/**
 * Hosts Endpoint: Add Host Officer / Employee
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireRole('admin');

$input = getJsonInput();

$name = trim((string) ($input['name'] ?? ''));
$deptId = isset($input['department_id']) ? (int) $input['department_id'] : 0;
$designation = trim((string) ($input['designation'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$phone = trim((string) ($input['phone'] ?? ''));
$officeRoom = trim((string) ($input['office_room'] ?? ''));

if (empty($name)) {
    sendResponse(false, 'Host officer name is required.');
}

if ($deptId <= 0) {
    sendResponse(false, 'Please select a valid department for this host.');
}

if (empty($designation)) {
    sendResponse(false, 'Designation / job title is required.');
}

$conn = getDbConnection();

// Verify department exists
$deptStmt = $conn->prepare('SELECT DepartmentID, Name FROM Departments WHERE DepartmentID = ?');
$deptStmt->execute([$deptId]);
$dept = $deptStmt->get_result()->fetch_assoc();

if (!$dept) {
    sendResponse(false, 'Selected department does not exist.');
}

// Insert Host
$stmt = $conn->prepare('
    INSERT INTO Hosts (Name, DepartmentID, Designation, Email, Phone, OfficeRoom, Status, CreatedAt)
    VALUES (?, ?, ?, ?, ?, ?, "active", NOW())
');

$stmt->execute([
    $name,
    $deptId,
    $designation,
    !empty($email) ? $email : null,
    !empty($phone) ? $phone : null,
    !empty($officeRoom) ? $officeRoom : null
]);

$hostId = (int) $conn->insert_id;

sendResponse(true, "Host officer '$name' registered successfully.", [
    'host_id' => $hostId,
    'host' => [
        'HostID' => $hostId,
        'Name' => $name,
        'DepartmentID' => $deptId,
        'DepartmentName' => $dept['Name'],
        'Designation' => $designation,
        'Email' => $email,
        'Phone' => $phone,
        'OfficeRoom' => $officeRoom
    ]
]);


