<?php
/**
 * Reports API Endpoint
 * Daily & Monthly Reporting with Statistics
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin(true);

$conn = getDbConnection();
$type = $_GET['type'] ?? 'daily';

// ----------------------------------------------------
// DAILY REPORT
// ----------------------------------------------------
if ($type === 'daily') {
    $dateInput = trim($_GET['date'] ?? '');
    if (!empty($dateInput) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateInput)) {
        $selectedDate = $dateInput;
    } else {
        $selectedDate = date('Y-m-d');
    }

    // Summary stats for the day
    $stmtSummary = $conn->prepare('
        SELECT 
            COUNT(*) AS total_visits,
            SUM(CASE WHEN Status = "checked_in" THEN 1 ELSE 0 END) AS checked_in_count,
            SUM(CASE WHEN Status = "checked_out" THEN 1 ELSE 0 END) AS checked_out_count
        FROM Visits
        WHERE VisitDate = ?
    ');
    $stmtSummary->execute([$selectedDate]);
    $summary = $stmtSummary->get_result()->fetch_assoc();

    // Detailed visits table for the day
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

    sendJsonResponse(true, 'Daily report generated', [
        'type'          => 'daily',
        'selected_date' => $selectedDate,
        'summary'       => [
            'total_visits'       => (int) ($summary['total_visits'] ?? 0),
            'checked_in_count'   => (int) ($summary['checked_in_count'] ?? 0),
            'checked_out_count'  => (int) ($summary['checked_out_count'] ?? 0),
        ],
        'visits'        => $visits
    ]);
}

// ----------------------------------------------------
// MONTHLY REPORT
// ----------------------------------------------------
if ($type === 'monthly') {
    $monthInput = trim($_GET['month'] ?? '');
    if (!empty($monthInput) && preg_match('/^\d{4}-\d{2}$/', $monthInput)) {
        $selectedMonth = $monthInput;
    } else {
        $selectedMonth = date('Y-m');
    }

    $startDate = $selectedMonth . '-01';
    $endDate = date('Y-m-t', strtotime($startDate));

    // Summary counts for the month
    $stmtSummary = $conn->prepare('
        SELECT 
            COUNT(*) AS total_visits,
            SUM(CASE WHEN Status = "checked_in" THEN 1 ELSE 0 END) AS checked_in_count,
            SUM(CASE WHEN Status = "checked_out" THEN 1 ELSE 0 END) AS checked_out_count,
            COUNT(DISTINCT VisitorID) AS unique_visitors
        FROM Visits
        WHERE VisitDate BETWEEN ? AND ?
    ');
    $stmtSummary->execute([$startDate, $endDate]);
    $summary = $stmtSummary->get_result()->fetch_assoc();

    // Daily breakdown for the month
    $stmtDaily = $conn->prepare('
        SELECT 
            VisitDate,
            COUNT(*) AS total_visits,
            SUM(CASE WHEN Status = "checked_in" THEN 1 ELSE 0 END) AS checked_in,
            SUM(CASE WHEN Status = "checked_out" THEN 1 ELSE 0 END) AS checked_out
        FROM Visits
        WHERE VisitDate BETWEEN ? AND ?
        GROUP BY VisitDate
        ORDER BY VisitDate ASC
    ');
    $stmtDaily->execute([$startDate, $endDate]);
    $dailyBreakdown = $stmtDaily->get_result()->fetch_all(MYSQLI_ASSOC);

    // Department breakdown
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

    // Top Hosts visited
    $stmtHost = $conn->prepare('
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
    $stmtHost->execute([$startDate, $endDate]);
    $topHosts = $stmtHost->get_result()->fetch_all(MYSQLI_ASSOC);

    sendJsonResponse(true, 'Monthly report generated', [
        'type'                => 'monthly',
        'selected_month'      => $selectedMonth,
        'start_date'          => $startDate,
        'end_date'            => $endDate,
        'summary'             => [
            'total_visits'      => (int) ($summary['total_visits'] ?? 0),
            'checked_in_count'  => (int) ($summary['checked_in_count'] ?? 0),
            'checked_out_count' => (int) ($summary['checked_out_count'] ?? 0),
            'unique_visitors'   => (int) ($summary['unique_visitors'] ?? 0),
        ],
        'daily_breakdown'     => $dailyBreakdown,
        'department_breakdown'=> $departmentBreakdown,
        'top_hosts'           => $topHosts
    ]);
}

sendJsonResponse(false, 'Invalid report type specified. Use "daily" or "monthly".', null, 400);


