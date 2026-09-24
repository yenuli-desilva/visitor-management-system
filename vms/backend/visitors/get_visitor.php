<?php
/**
 * Visitors Endpoint: Get Single Visitor Details & Visit History
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireLogin();

$visitorId = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_GET['visitor_id']) ? (int) $_GET['visitor_id'] : 0);

if ($visitorId <= 0) {
    sendResponse(false, 'Valid visitor ID is required.', null, 400);
}

$conn = getDbConnection();

$stmt = $conn->prepare('
    SELECT 
        v.VisitorID,
        v.Name,
        v.NIC,
        v.Phone,
        v.Email,
        v.Purpose,
        v.Host,
        v.DepartmentID,
        v.CreatedAt,
        d.Name AS DepartmentName
    FROM Visitors v
    LEFT JOIN Departments d ON v.DepartmentID = d.DepartmentID
    WHERE v.VisitorID = ?
    LIMIT 1
');
$stmt->execute([$visitorId]);
$visitor = $stmt->get_result()->fetch_assoc();

if (!$visitor) {
    sendResponse(false, 'Visitor record not found.', null, 404);
}

// Fetch visit history for this visitor
$visitsStmt = $conn->prepare('
    SELECT VisitID, CheckIn, CheckOut, VisitDate, Status, CreatedAt
    FROM Visits
    WHERE VisitorID = ?
    ORDER BY VisitID DESC
');
$visitsStmt->execute([$visitorId]);
$visitor['visits'] = $visitsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

sendResponse(true, 'Visitor details retrieved successfully.', [
    'visitor' => $visitor
]);


