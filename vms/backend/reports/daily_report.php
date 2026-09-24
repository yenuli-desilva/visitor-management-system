<?php
/**
 * Reports Endpoint: Daily Visitor Report
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireLogin();

$conn = getDbConnection();

$dateInput = trim($_GET['date'] ?? '');
if (!empty($dateInput) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateInput)) {
    $selectedDate = $dateInput;
} else {
    $selectedDate = date('Y-m-d');
}

// 1. Summary totals for the day
$stmtSummary = $conn->prepare('
    SELECT 
        COUNT(*) AS total_visits,
        SUM(CASE WHEN Status = "checked-in" THEN 1 ELSE 0 END) AS checked_in_count,
        SUM(CASE WHEN Status = "checked-out" THEN 1 ELSE 0 END) AS checked_out_count
    FROM Visits
    WHERE VisitDate = ?
');
$stmtSummary->execute([$selectedDate]);
$summary = $stmtSummary->get_result()->fetch_assoc();

// 2. Detailed visitor entries for the day
$stmtList = $conn->prepare('
    SELECT 
        v.VisitID,
        v.CheckIn,
        v.CheckOut,
        v.VisitDate,
        v.Status,
        vis.VisitorID,
        vis.Name AS VisitorName,
        vis.NIC,
        vis.Phone,
        vis.Email,
        vis.Purpose,
        vis.Host,
        d.Name AS DepartmentName
    FROM Visits v
    JOIN Visitors vis ON v.VisitorID = vis.VisitorID
    LEFT JOIN Departments d ON vis.DepartmentID = d.DepartmentID
    WHERE v.VisitDate = ?
    ORDER BY v.CheckIn ASC
');
$stmtList->execute([$selectedDate]);
$visits = $stmtList->get_result()->fetch_all(MYSQLI_ASSOC);

sendResponse(true, 'Daily visitor report generated successfully.', [
    'date'              => $selectedDate,
    'total_visits'      => (int) ($summary['total_visits'] ?? 0),
    'checked_in_count'  => (int) ($summary['checked_in_count'] ?? 0),
    'checked_out_count' => (int) ($summary['checked_out_count'] ?? 0),
    'visitor_details'   => $visits
]);


