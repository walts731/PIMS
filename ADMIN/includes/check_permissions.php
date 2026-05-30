<?php
/**
 * Central permission check for the ADMIN directory.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/permissions.php';

// Ensure database connection is available
if (!isset($conn)) {
    require_once __DIR__ . '/../../config.php';
}

// Initialize schema if not done
ensurePermissionSchema($conn);

/**
 * Check if the logged-in user's role has permission for a specific module action.
 *
 * @param string $permissionName
 * @param string $action 'can_create'|'can_read'|'can_update'|'can_delete'
 * @return bool
 */
function adminHasPermission(string $permissionName, string $action = 'can_read'): bool
{
    global $conn;
    $role = $_SESSION['role'] ?? null;
    return hasPermission($conn, $role, $permissionName, $action);
}

/**
 * Enforce permissions on the server side. Blocks access and returns 403 or redirects.
 *
 * @param string $permissionName
 * @param string $action 'can_create'|'can_read'|'can_update'|'can_delete'
 * @param string $redirectUrl
 */
function adminRequirePermission(string $permissionName, string $action = 'can_read', string $redirectUrl = 'dashboard.php'): void
{
    global $conn;
    $role = $_SESSION['role'] ?? null;
    requirePermission($conn, $permissionName, $action, $redirectUrl);
}
