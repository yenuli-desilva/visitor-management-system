<?php
/**
 * Reports Endpoint: Monthly Visitor Report
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireLogin();

$conn = getDbConnection();

// Extract month input
$monthInput = trim($_GET['month'] ?? '');
$yearInput  = trim($_GET['year'] ?? '');

if (!empty($monthInput) && preg_match('/^\d{4}-\d{2}$/', $monthInput)) {
    $selectedMonth = $monthInput;
} elseif (!empty($yearInput) && !empty($_GET['month'])) {
    $selectedMonth = sprintf('%04d-%02d', (int) $yearInput, (int) $_GET['month']);
} else {
    $selectedMonth = date('Y-m');
}

$startDate = $selectedMonth . '-01';
$endDate   = date('Y-m-t', strtotime($startDate));

// 1. Overall monthly totals
$stmtSummary = $conn->prepare('
    SELECT 
        COUNT(*) AS total_visits,
        SUM(CASE WHEN Status = "checked-in" THEN 1 ELSE 0 END) AS checked_in_count,
        SUM(CASE WHEN Status = "checked-out" THEN 1 ELSE 0 END) AS checked_out_count,
        COUNT(DISTINCT VisitorID) AS unique_visitors
    FROM Visits
    WHERE VisitDate BETWEEN ? AND ?
');
$stmtSummary->execute([$startDate, $endDate]);
$summary = $stmtSummary->get_result()->fetch_assoc();

// 2. Daily breakdown counts
$stmtDaily = $conn->prepare('
    SELECT 
        VisitDate,
        COUNT(*) AS total_visits,
        SUM(CASE WHEN Status = "checked-in" THEN 1 ELSE 0 END) AS checked_in,
        SUM(CASE WHEN Status = "checked-out" THEN 1 ELSE 0 END) AS checked_out
    FROM Visits
    WHERE VisitDate BETWEEN ? AND ?
    GROUP BY VisitDate
    ORDER BY VisitDate ASC
');
$stmtDaily->execute([$startDate, $endDate]);
$dailyVisits = $stmtDaily->get_result()->fetch_all(MYSQLI_ASSOC);

// 3. Department breakdown summary
$stmtDept = $conn->prepare('
    SELECT 
        d.Name AS DepartmentName,
        COUNT(v.VisitID) AS visit_count
    FROM Visits v
    JOIN Visitors vis ON v.VisitorID = vis.VisitorID
    LEFT JOIN Departments d ON vis.DepartmentID = d.DepartmentID
    WHERE v.VisitDate BETWEEN ? AND ?
    GROUP BY d.DepartmentID, d.Name
    ORDER BY visit_count DESC
');
$stmtDept->execute([$startDate, $endDate]);
$departmentBreakdown = $stmtDept->get_result()->fetch_all(MYSQLI_ASSOC);

// 4. Top visited hosts
$stmtHosts = $conn->prepare('
    SELECT 
        vis.Host,
        COUNT(v.VisitID) AS visit_count
    FROM Visits v
    JOIN Visitors vis ON v.VisitorID = vis.VisitorID
    WHERE v.VisitDate BETWEEN ? AND ?
    GROUP BY vis.Host
    ORDER BY visit_count DESC
    LIMIT 5
');
$stmtHosts->execute([$startDate, $endDate]);
$topHosts = $stmtHosts->get_result()->fetch_all(MYSQLI_ASSOC);

sendResponse(true, 'Monthly visitor report generated successfully.', [
    'month'               => $selectedMonth,
    'start_date'          => $startDate,
    'end_date'            => $endDate,
    'total_visits'        => (int) ($summary['total_visits'] ?? 0),
    'daily_visit_counts'  => $dailyVisits,
    'summary_information' => [
        'unique_visitors'      => (int) ($summary['unique_visitors'] ?? 0),
        'checked_in_count'     => (int) ($summary['checked_in_count'] ?? 0),
        'checked_out_count'    => (int) ($summary['checked_out_count'] ?? 0),
        'department_breakdown' => $departmentBreakdown,
        'top_hosts'            => $topHosts
    ]
]);


