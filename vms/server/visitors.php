<?php
/**
 * Visitors CRUD & Search API Endpoint
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin(true);

$conn = getDbConnection();
$method = $_SERVER['REQUEST_METHOD'];
$payload = getRequestPayload();

// Support _method override for forms
if ($method === 'POST' && isset($payload['_method'])) {
    $method = strtoupper($payload['_method']);
}

// ----------------------------------------------------
// GET: List Visitors or Get Single Visitor
// ----------------------------------------------------
if ($method === 'GET') {
    $visitorId = isset($_GET['id']) ? (int) $_GET['id'] : null;

    if ($visitorId) {
        // Fetch single visitor
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
            sendJsonResponse(false, 'Visitor not found', null, 404);
        }

        // Fetch their visit history
        $visitsStmt = $conn->prepare('
            SELECT VisitID, CheckIn, CheckOut, VisitDate, Status, CreatedAt
            FROM Visits
            WHERE VisitorID = ?
            ORDER BY VisitID DESC
        ');
        $visitsStmt->execute([$visitorId]);
        $visits = $visitsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $visitor['visits'] = $visits;

        sendJsonResponse(true, 'Visitor retrieved successfully', [
            'visitor' => $visitor
        ]);
    }

    // List visitors with filters, search, and pagination
    $search = trim($_GET['q'] ?? '');
    $departmentId = isset($_GET['department_id']) && $_GET['department_id'] !== '' ? (int) $_GET['department_id'] : null;
    $status = trim($_GET['status'] ?? '');
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = max(5, min(100, (int) ($_GET['limit'] ?? 15)));
    $offset = ($page - 1) * $limit;

    $whereClauses = [];
    $params = [];

    if ($search !== '') {
        $whereClauses[] = '(vis.Name LIKE ? OR vis.NIC LIKE ? OR vis.Phone LIKE ? OR vis.Host LIKE ? OR vis.Purpose LIKE ?)';
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($departmentId !== null && $departmentId > 0) {
        $whereClauses[] = 'vis.DepartmentID = ?';
        $params[] = $departmentId;
    }

    if ($status !== '' && in_array($status, ['checked_in', 'checked_out', 'scheduled'], true)) {
        $whereClauses[] = 'latest_v.Status = ?';
        $params[] = $status;
    }

    $whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    // Count query with subquery for latest visit status
    $countSql = "
        SELECT COUNT(*)
        FROM Visitors vis
        LEFT JOIN (
            SELECT v1.VisitorID, v1.Status
            FROM Visits v1
            INNER JOIN (
                SELECT VisitorID, MAX(VisitID) AS max_id
                FROM Visits
                GROUP BY VisitorID
            ) v2 ON v1.VisitID = v2.max_id
        ) latest_v ON vis.VisitorID = latest_v.VisitorID
        LEFT JOIN Departments d ON vis.DepartmentID = d.DepartmentID
        {$whereSql}
    ";

    $countStmt = $conn->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = (int) $countStmt->get_result()->fetch_row()[0];
    $totalPages = (int) ceil($totalRecords / $limit);

    // Data query
    $dataSql = "
        SELECT 
            vis.VisitorID,
            vis.Name,
            vis.NIC,
            vis.Phone,
            vis.Email,
            vis.Purpose,
            vis.Host,
            vis.DepartmentID,
            vis.CreatedAt,
            d.Name AS DepartmentName,
            latest_v.VisitID AS CurrentVisitID,
            latest_v.CheckIn,
            latest_v.CheckOut,
            latest_v.VisitDate,
            COALESCE(latest_v.Status, 'none') AS VisitStatus
        FROM Visitors vis
        LEFT JOIN (
            SELECT v1.VisitID, v1.VisitorID, v1.CheckIn, v1.CheckOut, v1.VisitDate, v1.Status
            FROM Visits v1
            INNER JOIN (
                SELECT VisitorID, MAX(VisitID) AS max_id
                FROM Visits
                GROUP BY VisitorID
            ) v2 ON v1.VisitID = v2.max_id
        ) latest_v ON vis.VisitorID = latest_v.VisitorID
        LEFT JOIN Departments d ON vis.DepartmentID = d.DepartmentID
        {$whereSql}
        ORDER BY vis.VisitorID DESC
        LIMIT {$limit} OFFSET {$offset}
    ";

    $dataStmt = $conn->prepare($dataSql);
    $dataStmt->execute($params);
    $visitors = $dataStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    sendJsonResponse(true, 'Visitors list retrieved', [
        'visitors'     => $visitors,
        'pagination'   => [
            'total_records' => $totalRecords,
            'total_pages'   => $totalPages,
            'current_page'  => $page,
            'limit'         => $limit
        ]
    ]);
}

// ----------------------------------------------------
// POST: Add New Visitor (+ optional auto check-in)
// ----------------------------------------------------
if ($method === 'POST') {
    $name         = trim($payload['name'] ?? '');
    $nic          = trim($payload['nic'] ?? '');
    $phone        = trim($payload['phone'] ?? '');
    $email        = trim($payload['email'] ?? '');
    $purpose      = trim($payload['purpose'] ?? '');
    $host         = trim($payload['host'] ?? '');
    $departmentId = (int) ($payload['department_id'] ?? 0);
    $checkInNow   = !isset($payload['check_in_now']) || $payload['check_in_now'] == '1' || $payload['check_in_now'] === true;

    if (empty($name) || empty($nic) || empty($phone) || empty($purpose) || empty($host) || $departmentId <= 0) {
        sendJsonResponse(false, 'Please fill in all required fields (Name, NIC, Phone, Purpose, Host, Department).', null, 400);
    }

    // Verify department exists
    $deptCheck = $conn->prepare('SELECT DepartmentID FROM Departments WHERE DepartmentID = ? LIMIT 1');
    $deptCheck->execute([$departmentId]);
    if (!$deptCheck->get_result()->fetch_assoc()) {
        sendJsonResponse(false, 'Selected department does not exist.', null, 400);
    }

    $conn->begin_transaction();

    try {
        $insertVisitor = $conn->prepare('
            INSERT INTO Visitors (Name, NIC, Phone, Email, Purpose, Host, DepartmentID, CreatedAt)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        $insertVisitor->execute([$name, $nic, $phone, !empty($email) ? $email : null, $purpose, $host, $departmentId]);
        $visitorId = (int) $conn->insert_id;

        $visitId = null;
        if ($checkInNow) {
            $insertVisit = $conn->prepare('
                INSERT INTO Visits (VisitorID, CheckIn, CheckOut, VisitDate, Status, CreatedAt)
                VALUES (?, NOW(), NULL, CURRENT_DATE, "checked_in", NOW())
            ');
            $insertVisit->execute([$visitorId]);
            $visitId = (int) $conn->insert_id;
        }

        $conn->commit();

        sendJsonResponse(true, 'Visitor registered successfully' . ($checkInNow ? ' and checked in.' : '.'), [
            'visitor_id' => $visitorId,
            'visit_id'   => $visitId
        ], 201);
    } catch (Exception $e) {
        $conn->rollback();
        sendJsonResponse(false, 'Failed to register visitor: ' . $e->getMessage(), null, 500);
    }
}

// ----------------------------------------------------
// PUT: Update Existing Visitor
// ----------------------------------------------------
if ($method === 'PUT') {
    $visitorId    = (int) ($payload['visitor_id'] ?? 0);
    $name         = trim($payload['name'] ?? '');
    $nic          = trim($payload['nic'] ?? '');
    $phone        = trim($payload['phone'] ?? '');
    $email        = trim($payload['email'] ?? '');
    $purpose      = trim($payload['purpose'] ?? '');
    $host         = trim($payload['host'] ?? '');
    $departmentId = (int) ($payload['department_id'] ?? 0);

    if ($visitorId <= 0 || empty($name) || empty($nic) || empty($phone) || empty($purpose) || empty($host) || $departmentId <= 0) {
        sendJsonResponse(false, 'All required fields must be filled.', null, 400);
    }

    $stmt = $conn->prepare('
        UPDATE Visitors 
        SET Name = ?, NIC = ?, Phone = ?, Email = ?, Purpose = ?, Host = ?, DepartmentID = ?
        WHERE VisitorID = ?
    ');
    $stmt->execute([$name, $nic, $phone, !empty($email) ? $email : null, $purpose, $host, $departmentId, $visitorId]);

    if ($stmt->affected_rows >= 0) {
        sendJsonResponse(true, 'Visitor information updated successfully.');
    } else {
        sendJsonResponse(false, 'No visitor found with that ID or no changes made.', null, 404);
    }
}

// ----------------------------------------------------
// DELETE: Delete Visitor (Admin Only)
// ----------------------------------------------------
if ($method === 'DELETE') {
    requireAdmin(true);

    $visitorId = (int) ($payload['visitor_id'] ?? $_GET['id'] ?? 0);

    if ($visitorId <= 0) {
        sendJsonResponse(false, 'Invalid visitor ID provided.', null, 400);
    }

    $stmt = $conn->prepare('DELETE FROM Visitors WHERE VisitorID = ?');
    $stmt->execute([$visitorId]);

    if ($stmt->affected_rows > 0) {
        sendJsonResponse(true, 'Visitor and all associated visit records deleted successfully.');
    } else {
        sendJsonResponse(false, 'Visitor not found or already deleted.', null, 404);
    }
}

sendJsonResponse(false, 'Invalid request method.', null, 405);


