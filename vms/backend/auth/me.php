<?php
/**
 * Authentication Endpoint: Current Session (Me)
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/response.php';

if (!isLoggedIn()) {
    sendResponse(false, 'Not authenticated', null, 401);
}

sendResponse(true, 'Authenticated', [
    'user' => getCurrentUser()
]);

