<?php
/**
 * Role-based permissions for PIMS.
 */

/**
 * Ensure permissions tables exist and default permission catalog is seeded.
 */
function ensurePermissionSchema(mysqli $conn): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }

    $conn->query("CREATE TABLE IF NOT EXISTS permissions (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT DEFAULT NULL,
        module VARCHAR(50) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS role_permissions (
        id INT(11) NOT NULL AUTO_INCREMENT,
        role VARCHAR(50) NOT NULL,
        permission_id INT(11) NOT NULL,
        can_create TINYINT(1) DEFAULT 0,
        can_read TINYINT(1) DEFAULT 1,
        can_update TINYINT(1) DEFAULT 0,
        can_delete TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_role_permission (role, permission_id),
        KEY permission_id (permission_id),
        CONSTRAINT role_permissions_ibfk_1 FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $roleColumn = $conn->query("SHOW COLUMNS FROM role_permissions LIKE 'role'");
    if ($roleColumn && ($roleRow = $roleColumn->fetch_assoc())) {
        if (stripos($roleRow['Type'], 'enum') !== false) {
            $conn->query("ALTER TABLE role_permissions MODIFY role VARCHAR(50) NOT NULL");
        }
    }

    seedMissingPermissions($conn);
    seedDefaultRolePermissions($conn);
    $initialized = true;
}

/**
 * @return array<int, array{name:string,description:string,module:string}>
 */
function getDefaultPermissionCatalog(): array
{
    return [
        ['users.create', 'Create new users', 'users'],
        ['users.read', 'View users list', 'users'],
        ['users.update', 'Edit user information', 'users'],
        ['users.delete', 'Delete users', 'users'],
        ['users.activate', 'Activate or deactivate users', 'users'],
        ['assets.create', 'Add new assets and asset items', 'assets'],
        ['assets.read', 'View assets and inventory', 'assets'],
        ['assets.update', 'Edit asset information', 'assets'],
        ['assets.delete', 'Delete assets and asset items', 'assets'],
        ['inventory.create', 'Add inventory records', 'inventory'],
        ['inventory.read', 'View inventory records', 'inventory'],
        ['inventory.update', 'Edit inventory records', 'inventory'],
        ['inventory.delete', 'Delete inventory records', 'inventory'],
        ['inventory.transaction.in', 'Record stock in transactions', 'inventory'],
        ['inventory.transaction.out', 'Record stock out transactions', 'inventory'],
        ['forms.create', 'Create forms (ICS, PAR, ITR, etc.)', 'forms'],
        ['forms.read', 'View forms and entries', 'forms'],
        ['forms.update', 'Edit forms and entries', 'forms'],
        ['forms.delete', 'Delete forms and entries', 'forms'],
        ['categories.create', 'Create categories and subcategories', 'categories'],
        ['categories.read', 'View categories', 'categories'],
        ['categories.update', 'Edit categories', 'categories'],
        ['categories.delete', 'Delete categories', 'categories'],
        ['tags.create', 'Create inventory tags', 'tags'],
        ['tags.read', 'View inventory tags', 'tags'],
        ['tags.update', 'Edit inventory tags', 'tags'],
        ['tags.delete', 'Delete inventory tags', 'tags'],
        ['consumables.read', 'View consumables', 'consumables'],
        ['consumables.manage', 'Manage consumable stock and releases', 'consumables'],
        ['fuel.read', 'View fuel records', 'fuel'],
        ['fuel.manage', 'Manage fuel in and out', 'fuel'],
        ['reports.view', 'View system reports', 'reports'],
        ['reports.export', 'Export reports', 'reports'],
        ['system.settings', 'Access system settings', 'system'],
        ['system.logs', 'View system logs', 'system'],
        ['system.backup', 'Create system backups', 'system'],
        ['system.audit', 'Access security audit', 'system'],
        
        // Added missing modules
        ['employees.create', 'Add employees', 'employees'],
        ['employees.read', 'View employees', 'employees'],
        ['employees.update', 'Edit employees', 'employees'],
        ['employees.delete', 'Delete employees', 'employees'],
        
        ['infrastructure.create', 'Add infrastructure records', 'infrastructure'],
        ['infrastructure.read', 'View infrastructure records', 'infrastructure'],
        ['infrastructure.update', 'Edit infrastructure records', 'infrastructure'],
        ['infrastructure.delete', 'Delete infrastructure records', 'infrastructure'],
        
        ['software.create', 'Add software records', 'software'],
        ['software.read', 'View software records', 'software'],
        ['software.update', 'Edit software records', 'software'],
        ['software.delete', 'Delete software records', 'software'],
        
        ['borrowing.create', 'Create borrow requests', 'borrowing'],
        ['borrowing.read', 'View borrow requests', 'borrowing'],
        ['borrowing.update', 'Update borrow requests', 'borrowing'],
        ['borrowing.delete', 'Delete borrow requests', 'borrowing'],
    ];
}

function seedMissingPermissions(mysqli $conn): void
{
    $stmt = $conn->prepare('INSERT IGNORE INTO permissions (name, description, module) VALUES (?, ?, ?)');
    if (!$stmt) {
        return;
    }

    foreach (getDefaultPermissionCatalog() as $permission) {
        $stmt->bind_param('sss', $permission[0], $permission[1], $permission[2]);
        $stmt->execute();
    }
    $stmt->close();
}

/**
 * Seed default "all enabled" permissions for the admin role if not already present.
 */
function seedDefaultRolePermissions(mysqli $conn): void
{
    // Check if admin role already has any permissions seeded
    $check = $conn->query("SELECT 1 FROM role_permissions WHERE role = 'admin' LIMIT 1");
    if ($check && $check->num_rows > 0) {
        return; // already seeded/configured
    }

    // Seed all default permissions for 'admin' with all rights enabled
    $permissions = [];
    $res = $conn->query("SELECT id FROM permissions");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $permissions[] = (int)$row['id'];
        }
    }

    if (empty($permissions)) {
        return;
    }

    $stmt = $conn->prepare("
        INSERT IGNORE INTO role_permissions (role, permission_id, can_create, can_read, can_update, can_delete)
        VALUES ('admin', ?, 1, 1, 1, 1)
    ");
    if (!$stmt) {
        return;
    }

    foreach ($permissions as $pid) {
        $stmt->bind_param('i', $pid);
        $stmt->execute();
    }
    $stmt->close();
}

/**
 * @return string[]
 */
function getAssignableRoles(): array
{
    return ['system_admin', 'admin', 'office_admin', 'user', 'main_user', 'fuel'];
}

function isValidAssignableRole(string $role): bool
{
    return in_array($role, getAssignableRoles(), true);
}

/**
 * @return array<int, array<string, mixed>>
 */
function getRolePermissionsForManagement(mysqli $conn, string $role): array
{
    ensurePermissionSchema($conn);

    if (!isValidAssignableRole($role)) {
        throw new InvalidArgumentException('Invalid role selected.');
    }

    $stmt = $conn->prepare("
        SELECT
            p.id,
            p.name,
            p.description,
            p.module,
            COALESCE(rp.can_create, 0) AS can_create,
            COALESCE(rp.can_read, 0) AS can_read,
            COALESCE(rp.can_update, 0) AS can_update,
            COALESCE(rp.can_delete, 0) AS can_delete
        FROM permissions p
        LEFT JOIN role_permissions rp ON p.id = rp.permission_id AND rp.role = ?
        ORDER BY p.module, p.name
    ");
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $result = $stmt->get_result();

    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'module' => $row['module'],
            'can_create' => (bool) $row['can_create'],
            'can_read' => (bool) $row['can_read'],
            'can_update' => (bool) $row['can_update'],
            'can_delete' => (bool) $row['can_delete'],
        ];
    }
    $stmt->close();

    return $permissions;
}

/**
 * @param array<string|int, array<string, bool|int|string>> $permissionsById
 */
function updateRolePermissions(mysqli $conn, string $role, array $permissionsById): void
{
    ensurePermissionSchema($conn);

    if (!isValidAssignableRole($role)) {
        throw new InvalidArgumentException('Invalid role selected.');
    }

    $validPermissionIds = [];
    $idResult = $conn->query('SELECT id FROM permissions');
    if ($idResult) {
        while ($idRow = $idResult->fetch_assoc()) {
            $validPermissionIds[(int) $idRow['id']] = true;
        }
    }

    $conn->begin_transaction();

    $stmt = $conn->prepare("
        INSERT INTO role_permissions (role, permission_id, can_create, can_read, can_update, can_delete)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            can_create = VALUES(can_create),
            can_read = VALUES(can_read),
            can_update = VALUES(can_update),
            can_delete = VALUES(can_delete)
    ");

    foreach ($permissionsById as $permissionId => $perms) {
        $permissionId = (int) $permissionId;
        if ($permissionId <= 0 || !isset($validPermissionIds[$permissionId])) {
            continue;
        }

        if (!is_array($perms)) {
            continue;
        }

        $canCreate = !empty($perms['can_create']) ? 1 : 0;
        $canRead = !empty($perms['can_read']) ? 1 : 0;
        $canUpdate = !empty($perms['can_update']) ? 1 : 0;
        $canDelete = !empty($perms['can_delete']) ? 1 : 0;

        $stmt->bind_param('siiiii', $role, $permissionId, $canCreate, $canRead, $canUpdate, $canDelete);
        $stmt->execute();
    }

    $stmt->close();
    $conn->commit();
}

/**
 * Get all permissions for a role as an associative array.
 */
function getPermissionsForRole(mysqli $conn, string $role): array
{
    static $cache = [];
    if (isset($cache[$role])) {
        return $cache[$role];
    }

    ensurePermissionSchema($conn);

    if ($role === 'system_admin') {
        return [];
    }

    $sql = "
        SELECT p.name, rp.can_create, rp.can_read, rp.can_update, rp.can_delete
        FROM role_permissions rp
        INNER JOIN permissions p ON p.id = rp.permission_id
        WHERE rp.role = ?
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('s', $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $perms = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $perms[$row['name']] = [
                'can_create' => (bool)$row['can_create'],
                'can_read' => (bool)$row['can_read'],
                'can_update' => (bool)$row['can_update'],
                'can_delete' => (bool)$row['can_delete'],
            ];
        }
    }
    $stmt->close();

    $cache[$role] = $perms;
    return $perms;
}

/**
 * Check whether a role has a permission action.
 * system_admin always has full access.
 */
function hasPermission(mysqli $conn, ?string $role, string $permissionName, string $action = 'can_read'): bool
{
    if ($role === 'system_admin') {
        return true;
    }

    if (empty($role)) {
        return false;
    }

    $allowedActions = ['can_create', 'can_read', 'can_update', 'can_delete'];
    if (!in_array($action, $allowedActions, true)) {
        $action = 'can_read';
    }

    $perms = getPermissionsForRole($conn, $role);
    if (isset($perms[$permissionName])) {
        return !empty($perms[$permissionName][$action]);
    }

    return false;
}

/**
 * Require permission or stop request.
 */
function requirePermission(mysqli $conn, string $permissionName, string $action = 'can_read', string $redirectUrl = '../index.php'): void
{
    $role = $_SESSION['role'] ?? null;
    if (!hasPermission($conn, $role, $permissionName, $action)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You do not have permission to perform this action.']);
            exit();
        }

        $_SESSION['error'] = 'You do not have permission to perform this action.';
        header('Location: ' . $redirectUrl);
        exit();
    }
}

function decodePermissionsPayload($raw): array
{
    if (is_array($raw)) {
        return $raw;
    }

    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}
