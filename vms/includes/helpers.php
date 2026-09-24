<?php
/**
 * Global Helper Functions
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

/**
 * Sends a standardized JSON response and halts script execution.
 *
 * @param bool $success
 * @param string $message
 * @param mixed $data
 * @param int $statusCode
 */
function sendJsonResponse(bool $success, string $message, $data = null, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');

    $response = [
        'success' => $success,
        'message' => $message,
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Reads and parses request payload from JSON or standard form-data.
 *
 * @return array
 */
function getRequestPayload(): array
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

    // Merge $_POST and php://input parsing for PUT/DELETE requests
    if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            parse_str($raw, $parsed);
            if (is_array($parsed)) {
                return array_merge($_POST, $parsed);
            }
        }
    }

    return $_POST;
}

/**
 * Sanitizes string for secure display.
 *
 * @param string|null $string
 * @return string
 */
function escapeHtml(?string $string): string
{
    if ($string === null) {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Formats a SQL DATETIME string to a friendly readable format.
 *
 * @param string|null $datetime
 * @param string $format
 * @return string
 */
function formatDateTime(?string $datetime, string $format = 'M d, Y h:i A'): string
{
    if (empty($datetime)) {
        return '-';
    }
    try {
        $dt = new DateTime($datetime);
        return $dt->format($format);
    } catch (Exception $e) {
        return $datetime;
    }
}

/**
 * Formats a SQL DATE string.
 *
 * @param string|null $date
 * @param string $format
 * @return string
 */
function formatDate(?string $date, string $format = 'M d, Y'): string
{
    if (empty($date)) {
        return '-';
    }
    try {
        $dt = new DateTime($date);
        return $dt->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

