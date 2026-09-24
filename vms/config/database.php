<?php
/**
 * Database Configuration & Connection Provider
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

// Database configuration settings
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'vms_database');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a shared mysqli database instance.
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
            // Log error in production; output friendly response for JSON or page
            if (php_sapi_name() !== 'cli' && (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false || !empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Database connection failed: ' . $e->getMessage()
                ]);
                exit;
            }
            throw $e;
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
