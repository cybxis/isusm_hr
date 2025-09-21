<?php
/**
 * Reusable header for HR Management System
 */

// Ensure we have the required User object passed from parent
if (!isset($currentUser) || !($currentUser instanceof User)) {
    // If no user object provided, try to create one
    if (!isset($db)) {
        require_once __DIR__ . '/../config/database.php';
        $database = new Database();
        $db = $database->getConnection();
    }
    
    if (!class_exists('User')) {
        require_once __DIR__ . '/../classes/User.php';
    }
    
    $currentUser = User::fromSession($db);
    if (!$currentUser) {
        header('Location: /hr2/login.php');
        exit;
    }
}
?>

<!-- Header -->
<header class="bg-white shadow-sm border-b border-gray-200 p-4">
    <div class="flex items-center justify-between">
<link rel="stylesheet" href="/hr2/assets/theme.css">
<script src="/hr2/assets/theme.js"></script>
        <h2 id="page-title" class="text-2xl font-semibold text-gray-800">Dashboard</h2>
        <div class="flex items-center space-x-4">
            <div class="text-sm text-gray-500 text-right">
                <div class="flex items-center">
                    <i class="fas fa-calendar mr-1"></i>
                    <?php echo date('F j, Y'); ?>
                </div>
                <div class="text-xs text-gray-400 mt-1">
                    <?php echo htmlspecialchars($_SESSION['office_name'] ?? 'No Office'); ?> • 
                    <?php echo htmlspecialchars($_SESSION['designation_name'] ?? 'No Designation'); ?>
                </div>
            </div>
            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center">
                <span class="text-sm font-bold text-white">
                    <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
                </span>
            </div>
        </div>
    </div>
</header>