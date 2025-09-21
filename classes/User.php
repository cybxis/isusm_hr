<?php
/**
 * User Class
 * Handles user authentication and data access
 */

class User {
    private $db;
    private $user_id;
    private $first_name;
    private $last_name;
    private $email;
    private $office_id;
    private $designation_id;
    private $role_id;
    private $is_active;
    private $full_name;
    private $office_name;
    private $designation_name;
    private $role_name;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Configure secure session settings
     */
    private static function configureSecureSession() {
        if (session_status() == PHP_SESSION_NONE) {
            // Configure session settings for security
            ini_set('session.cookie_lifetime', 86400); // 1 day (24 hours)
            ini_set('session.cookie_httponly', 1); // HTTP only - prevents JavaScript access
            ini_set('session.cookie_secure', 1); // Secure - requires HTTPS (set to 0 for development HTTP)
            ini_set('session.cookie_samesite', 'Strict'); // Strict SameSite policy
            ini_set('session.use_strict_mode', 1); // Prevent session fixation attacks
            ini_set('session.gc_maxlifetime', 86400); // Session garbage collection after 1 day
            session_start();
        }
    }

    /**
     * Load user from session
     */
    public static function fromSession($db) {
        // Configure secure session before accessing session data
        self::configureSecureSession();
        
        if (!isset($_SESSION['user_id']) || $_SESSION['logged_in'] !== true) {
            return null;
        }

        $user = new self($db);
        if ($user->loadById($_SESSION['user_id'])) {
            return $user;
        }
        return null;
    }

    /**
     * Load user by ID from database
     */
    public function loadById($user_id) {
        try {
            $query = "
                SELECT 
                    u.user_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.office_id,
                    u.designation_id,
                    u.role_id,
                    u.is_active,
                    o.office_name,
                    d.designation_name,
                    r.role_name,
                    CONCAT(u.first_name, ' ', u.last_name) as full_name
                FROM tbl_users u
                LEFT JOIN tbl_offices o ON u.office_id = o.office_id
                LEFT JOIN tbl_designations d ON u.designation_id = d.designation_id
                LEFT JOIN tbl_roles r ON u.role_id = r.role_id
                WHERE u.user_id = ?
            ";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData) {
                $this->user_id = $userData['user_id'];
                $this->first_name = $userData['first_name'];
                $this->last_name = $userData['last_name'];
                $this->email = $userData['email'];
                $this->office_id = $userData['office_id'];
                $this->designation_id = $userData['designation_id'];
                $this->role_id = $userData['role_id'];
                $this->is_active = $userData['is_active'];
                $this->full_name = $userData['full_name'];
                $this->office_name = $userData['office_name'];
                $this->designation_name = $userData['designation_name'];
                $this->role_name = $userData['role_name'];
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error loading user: " . $e->getMessage());
            return false;
        }
    }

    // Getters
    public function getId() { return $this->user_id; }
    public function getFirstName() { return $this->first_name; }
    public function getLastName() { return $this->last_name; }
    public function getEmail() { return $this->email; }
    public function getOfficeId() { return $this->office_id; }
    public function getDesignationId() { return $this->designation_id; }
    public function getRoleId() { return $this->role_id; }
    public function getIsActive() { return $this->is_active; }
    public function getFullName() { return $this->full_name; }
    public function getOfficeName() { return $this->office_name; }
    public function getDesignationName() { return $this->designation_name; }
    public function getRoleName() { return $this->role_name; }

    // Role-based permissions
    public function canViewAllEmployees() {
        return $this->designation_id == 2;
    }

    public function canAccessLeaves() {
        return $this->designation_id != 6;
    }

    public function getInitials() {
        return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
    }

    /**
     * Get employees query based on user permissions
     */
    public function getEmployeesQuery() {
        $baseQuery = "
            SELECT 
                u.user_id,
                u.first_name,
                u.middle_name,
                u.last_name,
                u.email,
                o.office_name,
                d.designation_name,
                r.role_name,
                u.is_active,
                u.leave_count,
                u.created_at,
                CONCAT(u.first_name, ' ', 
                       CASE WHEN u.middle_name IS NOT NULL THEN CONCAT(u.middle_name, ' ') ELSE '' END,
                       u.last_name) AS full_name
            FROM tbl_users u
            LEFT JOIN tbl_offices o ON u.office_id = o.office_id
            LEFT JOIN tbl_designations d ON u.designation_id = d.designation_id
            LEFT JOIN tbl_roles r ON u.role_id = r.role_id";

        if ($this->canViewAllEmployees()) {
            return $baseQuery . " ORDER BY u.first_name, u.last_name";
        } else {
            return $baseQuery . " WHERE u.office_id = ? ORDER BY u.first_name, u.last_name";
        }
    }

    /**
     * Get employees count query based on user permissions
     */
    public function getEmployeesCountQuery() {
        if ($this->canViewAllEmployees()) {
            return "SELECT COUNT(*) as total FROM tbl_users";
        } else {
            return "SELECT COUNT(*) as total FROM tbl_users WHERE office_id = ?";
        }
    }

    /**
     * Get employees statistics query based on user permissions
     */
    public function getEmployeesStatsQuery() {
        if ($this->canViewAllEmployees()) {
            return "
                SELECT 
                    COUNT(*) as total_employees,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_employees,
                    COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_employees,
                    COUNT(DISTINCT office_id) as total_offices
                FROM tbl_users
            ";
        } else {
            return "
                SELECT 
                    COUNT(*) as total_employees,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_employees,
                    COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_employees,
                    1 as total_offices
                FROM tbl_users
                WHERE office_id = ?
            ";
        }
    }

    /**
     * Execute query with appropriate parameters
     */
    public function executeQuery($query) {
        $stmt = $this->db->prepare($query);
        if ($this->canViewAllEmployees()) {
            $stmt->execute();
        } else {
            $stmt->execute([$this->office_id]);
        }
        return $stmt;
    }

    /**
     * Get leaves query based on user permissions
     */
    public function getLeavesQuery() {
        $baseQuery = "
            SELECT 
                l.leave_id,
                l.filed_at,
                l.start_date,
                l.end_date,
                l.reason,
                l.status3 as status,
                u.first_name,
                u.middle_name,
                u.last_name,
                u.email,
                o.office_name,
                d.designation_name,
                lt.leave_name,
                lt.leave_duration,
                CONCAT(u.first_name, ' ', 
                       CASE WHEN u.middle_name IS NOT NULL THEN CONCAT(u.middle_name, ' ') ELSE '' END,
                       u.last_name) AS full_name,
                DATEDIFF(l.end_date, l.start_date) + 1 AS days_requested
            FROM tbl_leaves l
            INNER JOIN tbl_users u ON l.user_id = u.user_id
            LEFT JOIN tbl_offices o ON u.office_id = o.office_id
            LEFT JOIN tbl_designations d ON u.designation_id = d.designation_id
            INNER JOIN tbl_leave_type lt ON l.leave_type_id = lt.leave_type_id";

        if ($this->canViewAllEmployees()) {
            return $baseQuery . " ORDER BY l.filed_at DESC, l.leave_id DESC";
        } else {
            return $baseQuery . " WHERE u.office_id = ? ORDER BY l.filed_at DESC, l.leave_id DESC";
        }
    }

    /**
     * Get leaves statistics query based on user permissions
     */
    public function getLeavesStatsQuery() {
        $baseQuery = "
            SELECT 
                COUNT(*) as total_leaves,
                COUNT(CASE WHEN l.status3 = 'PENDING' THEN 1 END) as pending_leaves,
                COUNT(CASE WHEN l.status3 = 'APPROVED' THEN 1 END) as approved_leaves,
                COUNT(CASE WHEN l.status3 = 'REJECTED' THEN 1 END) as rejected_leaves,
                COUNT(CASE WHEN MONTH(l.start_date) = MONTH(CURDATE()) AND YEAR(l.start_date) = YEAR(CURDATE()) THEN 1 END) as this_month_leaves
            FROM tbl_leaves l";

        if ($this->canViewAllEmployees()) {
            return $baseQuery;
        } else {
            return $baseQuery . " INNER JOIN tbl_users u ON l.user_id = u.user_id WHERE u.office_id = ?";
        }
    }

    /**
     * Execute leaves query with appropriate parameters
     */
    public function executeLeavesQuery($query) {
        $stmt = $this->db->prepare($query);
        if ($this->canViewAllEmployees()) {
            $stmt->execute();
        } else {
            $stmt->execute([$this->office_id]);
        }
        return $stmt;
    }
}
?>