<?php
/**
 * Database Connection Provider
 * Database: vms_database
 */

declare(strict_types=1);

// Disable error display in production/API responses
ini_set('display_errors', '0');
error_reporting(E_ALL);

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'vms_database');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton mysqli connection to MySQL.
 *
 * @return mysqli
 */
function getDbConnection(): mysqli
{
    static $conn = null;

    if ($conn === null) {
        try {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
            $conn->set_charset(DB_CHARSET);
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    return $conn;
}

function dbExecute(mysqli_stmt $stmt, array $params = []): void
{
    if ($params !== []) {
        $types = '';
        $values = [];
        foreach ($params as $param) {
            $types .= is_int($param) ? 'i' : (is_float($param) ? 'd' : 's');
            $values[] = $param;
        }
        $references = [$types];
        foreach ($values as $key => $value) {
            $references[] = &$values[$key];
        }
        $stmt->bind_param(...$references);
    }
    $stmt->execute();
}

function dbFetchOne(mysqli_stmt $stmt): ?array
{
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

function dbFetchAll(mysqli_stmt $stmt): array
{
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function dbFetchColumn(mysqli_stmt $stmt): mixed
{
    $row = $stmt->get_result()->fetch_row();
    return $row[0] ?? null;
}
