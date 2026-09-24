<?php
/**
 * Dashboard Statistics & Recent Activity API
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin(true);

$conn = getDbConnection();
$adminUser = isAdmin();

// 1. Total Visitors
$stmtTotalVisitors = $conn->query('SELECT COUNT(*) FROM Visitors');
$totalVisitors = (int) $stmtTotalVisitors->get_result()->fetch_row()[0];

// 2. Today's Visits
$stmtTodayVisits = $conn->query('SELECT COUNT(*) FROM Visits WHERE VisitDate = CURRENT_DATE');
$todayVisits = (int) $stmtTodayVisits->get_result()->fetch_row()[0];

// 3. Currently Checked In
$stmtCheckedIn = $conn->query('SELECT COUNT(*) FROM Visits WHERE Status = "checked_in"');
$currentlyCheckedIn = (int) $stmtCheckedIn->get_result()->fetch_row()[0];

// 4. Total Users (Admin only)
$totalUsers = null;
if ($adminUser) {
    $stmtUsers = $conn->query('SELECT COUNT(*) FROM Users');
    $totalUsers = (int) $stmtUsers->get_result()->fetch_row()[0];
}

// 5. 7-Day Visit Trend (Last 7 days ending today)
$trendStmt = $conn->prepare('
    SELECT 
        d.date_val AS visit_date,
        COALESCE(COUNT(v.VisitID), 0) AS visit_count
    FROM (
        SELECT CURDATE() - INTERVAL 6 DAY AS date_val UNION ALL
        SELECT CURDATE() - INTERVAL 5 DAY UNION ALL
        SELECT CURDATE() - INTERVAL 4 DAY UNION ALL
        SELECT CURDATE() - INTERVAL 3 DAY UNION ALL
        SELECT CURDATE() - INTERVAL 2 DAY UNION ALL
        SELECT CURDATE() - INTERVAL 1 DAY UNION ALL
        SELECT CURDATE()
    ) d
    LEFT JOIN Visits v ON v.VisitDate = d.date_val
    GROUP BY d.date_val
    ORDER BY d.date_val ASC
');
$trendStmt->execute();
$trendData = $trendStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 6. Recent Visitors / Visits (Latest 6)
$recentStmt = $conn->query('
    SELECT 
        v.VisitID,
        v.VisitorID,
        v.CheckIn,
        v.CheckOut,
        v.VisitDate,
        v.Status,
        vis.Name AS VisitorName,
        vis.NIC,
        vis.Phone,
        vis.Host,
        vis.Purpose,
        d.Name AS DepartmentName
    FROM Visits v
    JOIN Visitors vis ON v.VisitorID = vis.VisitorID
    LEFT JOIN Departments d ON vis.DepartmentID = d.DepartmentID
    ORDER BY v.VisitID DESC
    LIMIT 6
');
$recentVisits = $recentStmt->get_result()->fetch_all(MYSQLI_ASSOC);

sendJsonResponse(true, 'Dashboard statistics loaded', [
    'stats' => [
        'total_visitors'        => $totalVisitors,
        'today_visitors'        => $todayVisits,
        'currently_checked_in'  => $currentlyCheckedIn,
        'total_users'           => $totalUsers,
        'is_admin'              => $adminUser
    ],
    'trend'         => $trendData,
    'recent_visits' => $recentVisits
]);