<?php
/**
 * Visits Endpoint: Check-In Visitor
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
$visitorId = (int) ($data['visitor_id'] ?? 0);

if ($visitorId <= 0) {
    sendResponse(false, 'Valid visitor ID is required.', null, 400);
}

$conn = getDbConnection();

// Verify visitor exists
$vCheck = $conn->prepare('SELECT VisitorID, Name FROM Visitors WHERE VisitorID = ? LIMIT 1');
$vCheck->execute([$visitorId]);
$visitor = $vCheck->get_result()->fetch_assoc();

if (!$visitor) {
    sendResponse(false, 'Visitor does not exist.', null, 404);
}

// Check if already checked in currently
$activeCheck = $conn->prepare('
    SELECT VisitID FROM Visits 
    WHERE VisitorID = ? AND Status = "checked-in" 
    ORDER BY VisitID DESC LIMIT 1
');
$activeCheck->execute([$visitorId]);
if ($activeCheck->get_result()->fetch_assoc()) {
    sendResponse(false, 'Visitor is already marked as checked-in.', null, 409);
}

$stmt = $conn->prepare('
    INSERT INTO Visits (VisitorID, CheckIn, CheckOut, VisitDate, Status, CreatedAt)
    VALUES (?, NOW(), NULL, CURRENT_DATE, "checked-in", NOW())
');
$stmt->execute([$visitorId]);
$visitId = (int) $conn->insert_id;

sendResponse(true, 'Visitor ' . $visitor['Name'] . ' checked in successfully.', [
    'visit_id'   => $visitId,
    'visitor_id' => $visitorId,
    'status'     => 'checked-in'
], 201);


