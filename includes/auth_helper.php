<?php
/**
 * Senior Architect Auth & RBAC Helper
 * Handles unified session management and granular permission checks.
 */
class Auth {
    /**
     * Get the current logged-in user's role
     * @return string|null Role name (admin, nurse, pharmacist, accountant, lab_tech, records, patient)
     */
    public static function getRole() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (isset($_SESSION['patient_id'])) return 'patient';
        if (isset($_SESSION['admin_logged_in']) || isset($_SESSION['admin_id'])) return 'admin';
        if (isset($_SESSION['nurse_id'])) return 'nurse';
        if (isset($_SESSION['pharmacist_id'])) return 'pharmacist';
        if (isset($_SESSION['accountant_id'])) return 'accountant';
        if (isset($_SESSION['lab_tech_id'])) return 'lab_tech';
        if (isset($_SESSION['records_staff_id'])) return 'records';
        
        return null;
    }

    /**
     * Check if the current user has permission for a specific module/action
     * @param string $module The module being accessed (e.g., 'clinical_notes', 'billing', 'inventory')
     * @param string $action The action (view, edit, delete, manage)
     * @return bool
     */
    public static function can($module, $action = 'view') {
        $role = self::getRole();
        if (!$role) return false;

        // Admin has full access to everything
        if ($role === 'admin') return true;

        // RBAC Policy Matrix
        $policies = [
            'clinical_notes' => [
                'view' => ['nurse', 'lab_tech', 'patient'], // Patient can see their own notes
                'edit' => ['nurse'],
                'manage' => []
            ],
            'billing' => [
                'view' => ['accountant', 'patient'],
                'edit' => ['accountant'],
                'manage' => ['accountant']
            ],
            'inventory' => [
                'view' => ['pharmacist', 'nurse'],
                'edit' => ['pharmacist'],
                'manage' => ['pharmacist']
            ],
            'lab_results' => [
                'view' => ['lab_tech', 'nurse', 'patient'],
                'edit' => ['lab_tech'],
                'manage' => ['lab_tech']
            ],
            'patient_records' => [
                'view' => ['records', 'nurse', 'lab_tech', 'pharmacist', 'accountant'],
                'edit' => ['records', 'nurse'],
                'manage' => ['records']
            ],
            'system_settings' => [
                'view' => [],
                'edit' => [],
                'manage' => []
            ]
        ];

        if (!isset($policies[$module])) return false;
        if (!isset($policies[$module][$action])) return false;

        return in_array($role, $policies[$module][$action]);
    }

    /**
     * Enforce a permission check. Redirects or dies if unauthorized.
     */
    public static function guard($module, $action = 'view') {
        if (!self::can($module, $action)) {
            if (self::isApiRequest()) {
                header('Content-Type: application/json');
                die(json_encode(['success' => false, 'message' => 'RBAC: Unauthorized access to ' . $module]));
            } else {
                die("Access Denied: You do not have permission to $action $module.");
            }
        }
    }

    private static function isApiRequest() {
        return (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) || 
               (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
    }

    public static function getUserId() {
        $role = self::getRole();
        if (!$role) return null;
        return $_SESSION[$role . '_id'] ?? $_SESSION['admin_id'] ?? null;
    }
}
?>