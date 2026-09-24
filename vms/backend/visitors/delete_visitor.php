<?php
/**
 * Visitors Endpoint: Delete Visitor (Admin Only)
 * Cascades Safely to Associated Visit Records
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireAdmin();

$data      = getRequestData();
$visitorId = (int) ($data['visitor_id'] ?? $data['id'] ?? $_GET['id'] ?? 0);

if ($visitorId <= 0) {
    sendResponse(false, 'Valid visitor ID is required.', null, 400);
}

$conn = getDbConnection();
$stmt = $conn->prepare('DELETE FROM Visitors WHERE VisitorID = ?');
$stmt->execute([$visitorId]);

if ($stmt->affected_rows > 0) {
    sendResponse(true, 'Visitor and all associated visit logs deleted successfully.');
} else {
    sendResponse(false, 'Visitor not found or already deleted.', null, 404);
}


