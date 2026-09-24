<?php
/**
 * Dashboard Endpoint: Summary Statistics & Recent Visitors
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireLogin();

$conn = getDbConnection();
$admin = isAdmin();

// 1. Total Visitors
$totalVisitors = (int) $conn->query('SELECT COUNT(*) FROM Visitors')->fetch_row()[0];

// 2. Today's Visits
$todayVisits = (int) $conn->query('SELECT COUNT(*) FROM Visits WHERE VisitDate = CURRENT_DATE')->fetch_row()[0];

// 3. Currently Checked In
$checkedIn = (int) $conn->query('SELECT COUNT(*) FROM Visits WHERE Status = "checked-in"')->fetch_row()[0];

// 4. Completed Visits
$completedVisits = (int) $conn->query('SELECT COUNT(*) FROM Visits WHERE Status = "checked-out"')->fetch_row()[0];

// 5. Total Users (Admin only)
$totalUsers = $admin ? (int) $conn->query('SELECT COUNT(*) FROM Users')->fetch_row()[0] : null;

// 6. Recent Visitors (Last 6 visits)
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
$recentVisits = $recentStmt->fetch_all(MYSQLI_ASSOC);

// 7. 7-Day Trend
$trendStmt = $conn->query('
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
$trend = $trendStmt->fetch_all(MYSQLI_ASSOC);

sendResponse(true, 'Dashboard statistics loaded successfully.', [
    'total_visitors'   => $totalVisitors,
    'today_visits'     => $todayVisits,
    'checked_in'       => $checkedIn,
    'completed_visits' => $completedVisits,
    'total_users'      => $totalUsers,
    'is_admin'         => $admin,
    'recent_visitors'  => $recentVisits,
    'trend'            => $trend
]);


