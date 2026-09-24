<?php
/**
 * Visitors Endpoint: Get Visitors List
 * Supports Search by Name, NIC, Host, Department Filter & Pagination
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireLogin();

$conn = getDbConnection();

$search       = trim($_GET['search'] ?? $_GET['q'] ?? '');
$departmentId = isset($_GET['department_id']) && $_GET['department_id'] !== '' ? (int) $_GET['department_id'] : null;
$status       = trim($_GET['status'] ?? '');
$page         = max(1, (int) ($_GET['page'] ?? 1));
$limit        = max(1, min(100, (int) ($_GET['limit'] ?? 20)));
$offset       = ($page - 1) * $limit;

$whereClauses = [];
$params       = [];

if ($search !== '') {
    $whereClauses[] = '(v.Name LIKE ? OR v.NIC LIKE ? OR v.Host LIKE ?)';
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($departmentId !== null && $departmentId > 0) {
    $whereClauses[] = 'v.DepartmentID = ?';
    $params[] = $departmentId;
}

if ($status !== '') {
    // Support both 'checked-in' and 'checked-out' (or underscore equivalent)
    $normalizedStatus = str_replace('_', '-', $status);
    $whereClauses[] = 'latest_visit.Status = ?';
    $params[] = $normalizedStatus;
}

// User Portal: Filter visitors for current customer/user if requested or non-admin
$myVisits = isset($_GET['my_visits']) || (!isAdmin() && isset($_GET['mine']));
if ($myVisits) {
    $currentUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    $currentName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? '';
    if ($currentUserId > 0) {
        $whereClauses[] = '(v.UserID = ? OR v.Name = ? OR v.Name LIKE ?)';
        $params[] = $currentUserId;
        $params[] = $currentName;
        $params[] = "%{$currentName}%";
    } elseif ($currentName !== '') {
        $whereClauses[] = '(v.Name = ? OR v.Name LIKE ?)';
        $params[] = $currentName;
        $params[] = "%{$currentName}%";
    }
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Count query
$countSql = "
    SELECT COUNT(*)
    FROM Visitors v
    LEFT JOIN (
        SELECT v1.VisitorID, v1.Status
        FROM Visits v1
        INNER JOIN (
            SELECT VisitorID, MAX(VisitID) as max_id
            FROM Visits
            GROUP BY VisitorID
        ) v2 ON v1.VisitID = v2.max_id
    ) latest_visit ON v.VisitorID = latest_visit.VisitorID
    LEFT JOIN Departments d ON v.DepartmentID = d.DepartmentID
    {$whereSql}
";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int) $countStmt->get_result()->fetch_row()[0];
$totalPages   = (int) ceil($totalRecords / $limit);

// Data query
$dataSql = "
    SELECT 
        v.VisitorID,
        v.UserID,
        v.Name,
        v.NIC,
        v.Phone,
        v.Email,
        v.Purpose,
        v.Host,
        v.DepartmentID,
        v.CreatedAt,
        d.Name AS DepartmentName,
        latest_visit.VisitID AS CurrentVisitID,
        latest_visit.CheckIn,
        latest_visit.CheckOut,
        latest_visit.VisitDate,
        COALESCE(latest_visit.Status, 'none') AS VisitStatus
    FROM Visitors v
    LEFT JOIN (
        SELECT v1.VisitID, v1.VisitorID, v1.CheckIn, v1.CheckOut, v1.VisitDate, v1.Status
        FROM Visits v1
        INNER JOIN (
            SELECT VisitorID, MAX(VisitID) as max_id
            FROM Visits
            GROUP BY VisitorID
        ) v2 ON v1.VisitID = v2.max_id
    ) latest_visit ON v.VisitorID = latest_visit.VisitorID
    LEFT JOIN Departments d ON v.DepartmentID = d.DepartmentID
    {$whereSql}
    ORDER BY v.VisitorID DESC
    LIMIT {$limit} OFFSET {$offset}
";

$dataStmt = $conn->prepare($dataSql);
$dataStmt->execute($params);
$visitors = $dataStmt->get_result()->fetch_all(MYSQLI_ASSOC);

sendResponse(true, 'Visitors retrieved successfully.', [
    'visitors'   => $visitors,
    'pagination' => [
        'total_records' => $totalRecords,
        'total_pages'   => $totalPages,
        'current_page'  => $page,
        'limit'         => $limit
    ]
]);


