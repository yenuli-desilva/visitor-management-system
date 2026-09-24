<?php
/**
 * Hosts Endpoint: Remove or Deactivate Host Officer
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/response.php';

requireRole('admin');

$input = getJsonInput();
$hostId = isset($input['host_id']) ? (int) $input['host_id'] : (int) ($_GET['id'] ?? 0);

if ($hostId <= 0) {
    sendResponse(false, 'Valid Host ID is required.');
}

$conn = getDbConnection();

// Check if host exists
$stmt = $conn->prepare('SELECT HostID, Name FROM Hosts WHERE HostID = ?');
$stmt->execute([$hostId]);
$host = $stmt->get_result()->fetch_assoc();

if (!$host) {
    sendResponse(false, 'Host officer not found.');
}

// Delete or deactivate
$deleteStmt = $conn->prepare('DELETE FROM Hosts WHERE HostID = ?');
$deleteStmt->execute([$hostId]);

sendResponse(true, "Host officer '{$host['Name']}' removed successfully.");


