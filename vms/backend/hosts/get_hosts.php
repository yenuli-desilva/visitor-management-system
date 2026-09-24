<?php
/**
 * Hosts Endpoint: Retrieve Host Officers & Employees
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireLogin();

$conn = getDbConnection();

$deptId = isset($_GET['department_id']) && is_numeric($_GET['department_id']) ? (int) $_GET['department_id'] : null;
$includeAll = isset($_GET['all']) && $_GET['all'] === '1' && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

$sql = '
    SELECT 
        h.HostID,
        h.Name,
        h.DepartmentID,
        d.Name AS DepartmentName,
        h.Designation,
        h.Email,
        h.Phone,
        h.OfficeRoom,
        h.Status,
        h.CreatedAt
    FROM Hosts h
    JOIN Departments d ON h.DepartmentID = d.DepartmentID
';

$conditions = [];
$params = [];

if (!$includeAll) {
    $conditions[] = 'h.Status = "active"';
}

if ($deptId !== null) {
    $conditions[] = 'h.DepartmentID = ?';
    $params[] = $deptId;
}

if (!empty($conditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY d.Name ASC, h.Name ASC';

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$hosts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

sendResponse(true, 'Hosts retrieved successfully.', [
    'hosts' => $hosts,
    'total' => count($hosts)
]);


