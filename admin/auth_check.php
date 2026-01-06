<?php
// ============================================
// ADMIN AUTHENTICATION CHECK
// Include this file at the top of every admin page
// ============================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get admin info from session
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];
$admin_username = $_SESSION['admin_username'];
$admin_role = $_SESSION['admin_role'];
$admin_department = $_SESSION['admin_department'];
$admin_department_name = $_SESSION['admin_department_name'] ?? null;

// Verify admin is still active in database
$db = Database::getInstance()->getConnection();
$check_stmt = $db->prepare("
    SELECT is_active, role, department_id 
    FROM admin_users 
    WHERE id = ?
");
$check_stmt->execute([$admin_id]);
$user = $check_stmt->fetch();

if (!$user || !$user['is_active']) {
    // User has been deactivated
    session_destroy();
    header('Location: login.php?error=account_disabled');
    exit;
}

// Update session if role or department changed
if ($user['role'] !== $admin_role) {
    $_SESSION['admin_role'] = $user['role'];
    $admin_role = $user['role'];
}

if ($user['department_id'] != $admin_department) {
    $_SESSION['admin_department'] = $user['department_id'];
    $admin_department = $user['department_id'];
}

/**
 * Check if admin has required role
 * @param string $required_role - 'admin', 'department_head', or 'staff'
 */
function requireRole($required_role) {
    global $admin_role;
    
    $roles_hierarchy = [
        'admin' => 3,
        'department_head' => 2,
        'staff' => 1
    ];
    
    $user_level = $roles_hierarchy[$admin_role] ?? 0;
    $required_level = $roles_hierarchy[$required_role] ?? 0;
    
    if ($user_level < $required_level) {
        $_SESSION['error'] = 'You do not have permission to access this page.';
        header('Location: dashboard.php');
        exit;
    }
    
    return true;
}

/**
 * Check if admin can access specific department
 * @param int $department_id
 * @return bool
 */
function canAccessDepartment($department_id) {
    global $admin_role, $admin_department;
    
    // Admins can access everything
    if ($admin_role === 'admin') {
        return true;
    }
    
    // Others can only access their department
    return ($admin_department == $department_id);
}

/**
 * Check if admin can modify ticket
 * @param int $ticket_id
 * @return bool
 */
function canModifyTicket($ticket_id) {
    global $admin_role, $admin_department, $db;
    
    // Admins can modify everything
    if ($admin_role === 'admin') {
        return true;
    }
    
    // Check if ticket is assigned to admin's department
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM ticket_departments 
        WHERE ticket_id = ? AND department_id = ?
    ");
    $stmt->execute([$ticket_id, $admin_department]);
    $result = $stmt->fetch();
    
    return ($result['count'] > 0);
}

/**
 * Get department filter SQL for non-admin users
 * @return string SQL WHERE clause fragment
 */
function getDepartmentFilter() {
    global $admin_role, $admin_department;
    
    if ($admin_role === 'admin') {
        return '';
    }
    
    if ($admin_department) {
        return " AND t.id IN (SELECT ticket_id FROM ticket_departments WHERE department_id = {$admin_department})";
    }
    
    return " AND 1=0"; // No access if no department assigned
}

/**
 * Log admin activity
 * @param string $action
 * @param string $details
 */
function logActivity($action, $details = '') {
    global $admin_id, $admin_username, $db;
    
    try {
        $stmt = $db->prepare("
            INSERT INTO activity_log (admin_id, username, action, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $admin_id,
            $admin_username,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Check if user has specific permission
 * @param string $permission
 * @return bool
 */
function hasPermission($permission) {
    global $admin_role;
    
    $permissions = [
        'admin' => [
            'view_all_tickets',
            'modify_all_tickets',
            'view_all_reports',
            'manage_departments',
            'manage_users',
            'manage_faq',
            'manage_ai_keywords',
            'system_settings'
        ],
        'department_head' => [
            'view_department_tickets',
            'modify_department_tickets',
            'view_department_reports'
        ],
        'staff' => [
            'view_department_tickets',
            'modify_department_tickets'
        ]
    ];
    
    return in_array($permission, $permissions[$admin_role] ?? []);
}