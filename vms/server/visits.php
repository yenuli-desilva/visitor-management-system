<?php
/**
 * Visits & Check-In / Check-Out API Endpoint
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin(true);

$conn = getDbConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$payload = getRequestPayload();

if ($method !== 'POST') {
    sendJsonResponse(false, 'Invalid request method. POST expected.', null, 405);
}

// ----------------------------------------------------
// Action: CHECK-IN
// ----------------------------------------------------
if ($action === 'checkin') {
    $visitorId = (int) ($payload['visitor_id'] ?? 0);

    if ($visitorId <= 0) {
        sendJsonResponse(false, 'Valid Visitor ID is required for check-in.', null, 400);
    }

    // Verify visitor exists
    $vCheck = $conn->prepare('SELECT VisitorID, Name FROM Visitors WHERE VisitorID = ? LIMIT 1');
    $vCheck->execute([$visitorId]);
    $visitor = $vCheck->get_result()->fetch_assoc();

    if (!$visitor) {
        sendJsonResponse(false, 'Visitor does not exist.', null, 404);
    }

    // Check if visitor is already checked in currently
    $activeVisitStmt = $conn->prepare('
        SELECT VisitID FROM Visits 
        WHERE VisitorID = ? AND Status = "checked_in" 
        ORDER BY VisitID DESC LIMIT 1
    ');
    $activeVisitStmt->execute([$visitorId]);
    if ($activeVisitStmt->get_result()->fetch_assoc()) {
        sendJsonResponse(false, 'This visitor is already checked in.', null, 409);
    }

    $stmt = $conn->prepare('
        INSERT INTO Visits (VisitorID, CheckIn, CheckOut, VisitDate, Status, CreatedAt)
        VALUES (?, NOW(), NULL, CURRENT_DATE, "checked_in", NOW())
    ');
    $stmt->execute([$visitorId]);
    $visitId = (int) $conn->insert_id;

    sendJsonResponse(true, 'Visitor ' . $visitor['Name'] . ' checked in successfully.', [
        'visit_id'   => $visitId,
        'visitor_id' => $visitorId,
        'status'     => 'checked_in'
    ]);
}

// ----------------------------------------------------
// Action: CHECK-OUT
// ----------------------------------------------------
if ($action === 'checkout') {
    $visitId   = (int) ($payload['visit_id'] ?? 0);
    $visitorId = (int) ($payload['visitor_id'] ?? 0);

    if ($visitId <= 0 && $visitorId <= 0) {
        sendJsonResponse(false, 'Visit ID or Visitor ID is required to perform check-out.', null, 400);
    }

    if ($visitId > 0) {
        $stmt = $conn->prepare('
            UPDATE Visits 
            SET CheckOut = NOW(), Status = "checked_out" 
            WHERE VisitID = ? AND Status = "checked_in"
        ');
        $stmt->execute([$visitId]);
    } else {
        // If only visitorId provided, checkout their latest active visit
        $stmt = $conn->prepare('
            UPDATE Visits 
            SET CheckOut = NOW(), Status = "checked_out" 
            WHERE VisitorID = ? AND Status = "checked_in"
            ORDER BY VisitID DESC LIMIT 1
        ');
        $stmt->execute([$visitorId]);
    }

    if ($stmt->affected_rows > 0) {
        sendJsonResponse(true, 'Visitor checked out successfully.', [
            'status' => 'checked_out'
        ]);
    } else {
        sendJsonResponse(false, 'No active check-in found for this visitor or already checked out.', null, 404);
    }
}

sendJsonResponse(false, 'Unknown visit action requested.', null, 400);


