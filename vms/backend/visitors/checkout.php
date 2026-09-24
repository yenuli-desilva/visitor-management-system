<?php
/**
 * Visits Endpoint: Check-Out Visitor
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method. POST expected.', null, 405);
}

$data      = getRequestData();
$visitId   = (int) ($data['visit_id'] ?? 0);
$visitorId = (int) ($data['visitor_id'] ?? 0);

if ($visitId <= 0 && $visitorId <= 0) {
    sendResponse(false, 'Visit ID or Visitor ID is required for check-out.', null, 400);
}

$conn = getDbConnection();

if ($visitId > 0) {
    $stmt = $conn->prepare('
        UPDATE Visits 
        SET CheckOut = NOW(), Status = "checked-out" 
        WHERE VisitID = ? AND Status = "checked-in"
    ');
    $stmt->execute([$visitId]);
} else {
    $stmt = $conn->prepare('
        UPDATE Visits 
        SET CheckOut = NOW(), Status = "checked-out" 
        WHERE VisitorID = ? AND Status = "checked-in"
        ORDER BY VisitID DESC LIMIT 1
    ');
    $stmt->execute([$visitorId]);
}

if ($stmt->affected_rows > 0) {
    sendResponse(true, 'Visitor checked out successfully.', [
        'status' => 'checked-out'
    ]);
} else {
    sendResponse(false, 'No active checked-in visit found for this visitor or already checked out.', null, 404);
}


