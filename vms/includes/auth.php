<?php
/**
 * Authentication & Role-Based Access Control (RBAC) Module
 * Visitor Management System (VMS)
 */

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * Ensures session is active with secure configurations.
 */
function startSessionIfNotStarted(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $lifetime = 86400; // 24 hours
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }
}

// Auto-start session when this file is included
startSessionIfNotStarted();

/**
 * Checks if user is authenticated.
 *
 * @return bool
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['vms_user_id']) && !empty($_SESSION['vms_username']);
}

/**
 * Returns current logged-in user data.
 *
 * @return array|null
 */
function getCurrentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'user_id'   => (int) $_SESSION['vms_user_id'],
        'username'  => $_SESSION['vms_username'],
        'full_name' => $_SESSION['vms_full_name'] ?? 'User',
        'role'      => $_SESSION['vms_role'] ?? 'user'
    ];
}

/**
 * Checks if current user has Admin role.
 *
 * @return bool
 */
function isAdmin(): bool
{
    return isLoggedIn() && (($_SESSION['vms_role'] ?? '') === 'admin');
}

/**
 * Enforces authentication. Redirects to login or responds with 401 for API.
 *
 * @param bool $isApi
 */
function requireLogin(bool $isApi = false): void
{
    if (!isLoggedIn()) {
        if ($isApi) {
            sendJsonResponse(false, 'Unauthorized. Please log in to continue.', null, 401);
        } else {
            header('Location: login.php');
            exit;
        }
    }
}

/**
 * Enforces Administrator role. Redirects or responds with 403 for API.
 *
 * @param bool $isApi
 */
function requireAdmin(bool $isApi = false): void
{
    requireLogin($isApi);

    if (!isAdmin()) {
        if ($isApi) {
            sendJsonResponse(false, 'Forbidden. Administrator privileges required.', null, 403);
        } else {
            header('Location: dashboard.php?error=unauthorized');
            exit;
        }
    }
}

/**
 * Initializes session for an authenticated user.
 *
 * @param array $user
 */
function loginUser(array $user): void
{
    // Prevent session fixation
    session_regenerate_id(true);

    $_SESSION['vms_user_id']   = (int) $user['UserID'];
    $_SESSION['vms_username']  = $user['Username'];
    $_SESSION['vms_full_name'] = $user['FullName'];
    $_SESSION['vms_role']      = $user['Role'];
    $_SESSION['vms_logged_at'] = time();
}

/**
 * Securely logs out user and terminates session.
 */
function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
}

