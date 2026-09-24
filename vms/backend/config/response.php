<?php
/**
 * Response Formatter, Request Parser & Auth Verification Helper
 * Visitor Management System (VMS) Backend
 */

declare(strict_types=1);

// Prevent PHP notices/warnings from polluting JSON output
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

/**
 * Initializes secure PHP session if not already active.
 */
function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 86400,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

// Auto-start session for backend endpoints
startSession();

/**
 * Sends a standardized JSON response and terminates script execution.
 *
 * @param bool $success
 * @param string $message
 * @param mixed $data
 * @param int $statusCode
 */
function sendResponse(bool $success, string $message, $data = null, int $statusCode = 200): void
{
    http_response_code($statusCode);

    $response = [
        'success' => $success,
        'message' => $message
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Parses request payload from JSON body or standard form POST/PUT.
 *
 * @return array
 */
function getRequestData(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
    }

    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        parse_str($raw, $parsed);
        if (is_array($parsed) && !empty($parsed)) {
            return array_merge($_POST, $parsed);
        }
    }

    return $_POST;
}

/**
 * Checks if a user is currently authenticated via session.
 *
 * @return bool
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['username']);
}

/**
 * Checks if the current user has the 'admin' role.
 *
 * @return bool
 */
function isAdmin(): bool
{
    return isLoggedIn() && (($_SESSION['role'] ?? '') === 'admin');
}

/**
 * Returns current authenticated session details.
 *
 * @return array|null
 */
function getCurrentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'user_id'   => (int) $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'] ?? '',
        'role'      => $_SESSION['role'] ?? 'user'
    ];
}

/**
 * Enforces authentication. Halts with 401 if unauthenticated.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        sendResponse(false, 'Unauthorized. Please log in to continue.', null, 401);
    }
}

/**
 * Enforces Administrator authorization. Halts with 403 if not an admin.
 */
function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {
        sendResponse(false, 'Forbidden. Administrator privileges required.', null, 403);
    }
}

/**
 * Sanitizes input string to prevent XSS.
 *
 * @param string|null $str
 * @return string
 */
function escapeString(?string $str): string
{
    if ($str === null) {
        return '';
    }
    return htmlspecialchars(trim($str), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

