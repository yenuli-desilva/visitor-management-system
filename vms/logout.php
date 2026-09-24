<?php
/**
 * Secure Logout Handler
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

logoutUser();

header('Location: frontend/index.html');
exit;

