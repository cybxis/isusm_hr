<?php
/**
 * Sidebar Component
 * Reusable sidebar for HR Management System
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

<!-- Sidebar -->
<div class="bg-white text-gray-800 w-64 min-h-screen p-4 flex flex-col border-r border-gray-200 shadow-sm">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-center text-gray-800">HR System</h1>
        <p class="text-gray-500 text-center text-sm mt-2">Management Dashboard</p>
    </div>
    
    <!-- User Info -->
    <div class="mb-6 p-3 bg-gray-100 rounded-lg">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                <span class="text-sm font-bold text-white">
                    <?php echo $currentUser->getInitials(); ?>
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">
                    <?php 
                    // Display name with middle initial and dot only
                    $displayName = $currentUser->getFirstName();
                    if (!empty($currentUser->getMiddleName())) {
                        $displayName .= ' ' . $currentUser->getMiddleInitial() . '.';
                    }
                    $displayName .= ' ' . $currentUser->getLastName();
                    echo htmlspecialchars($displayName);
                    ?>
                </p>
                <p class="text-xs text-gray-500 truncate">
                    <?php echo htmlspecialchars($currentUser->getRoleName() ?? 'Employee'); ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="space-y-2 flex-1">
        <button 
            onclick="loadSection('employees')" 
            id="employees-btn"
            class="nav-btn w-full flex items-center space-x-3 text-left p-3 rounded-lg hover:bg-blue-50 hover:text-blue-700 transition-colors duration-200 bg-blue-100 text-blue-700">
            <i class="fas fa-users"></i>
            <span>Employees</span>
        </button>
        
        <?php if ($currentUser->canAccessLeaves()): ?>
        <button 
            onclick="loadSection('leaves')" 
            id="leaves-btn"
            class="nav-btn w-full flex items-center space-x-3 text-left p-3 rounded-lg hover:bg-blue-50 hover:text-blue-700 transition-colors duration-200 text-gray-700">
            <i class="fas fa-calendar-alt"></i>
            <span>Leave Table</span>
        </button>
        <?php else: ?>
        <div class="w-full flex items-center space-x-3 text-left p-3 rounded-lg bg-gray-200 cursor-not-allowed opacity-50 text-gray-500">
            <i class="fas fa-calendar-alt"></i>
            <span>Leave Table</span>
            <i class="fas fa-ban text-red-500 ml-auto"></i>
        </div>
        <?php endif; ?>
        
        <!-- Add more navigation items here as needed -->
        <!-- Example for future features:
        <button 
            onclick="loadSection('reports')" 
            id="reports-btn"
            class="nav-btn w-full flex items-center space-x-3 text-left p-3 rounded-lg hover:bg-gray-700 transition-colors duration-200">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </button>
        
        <button 
            onclick="loadSection('settings')" 
            id="settings-btn"
            class="nav-btn w-full flex items-center space-x-3 text-left p-3 rounded-lg hover:bg-gray-700 transition-colors duration-200">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </button>
        -->
    </nav>
    
    <!-- Logout Button -->
    <div class="mt-4 pt-4 border-t border-gray-200">
        <button 
            onclick="confirmLogout()"
            class="w-full flex items-center space-x-3 text-left p-3 rounded-lg bg-red-50 text-red-700 hover:bg-red-600 hover:text-white transition-colors duration-200">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </button>
    </div>
    
    <!-- Footer -->
    <div class="mt-4">
        <div class="text-xs text-gray-500 text-center">
            <p>&copy; 2025 HR Management System</p>
            <p class="mt-1">Version 1.0</p>
        </div>
    </div>
</div>

<script>
// Sidebar JavaScript functions (if not already included in main file)
if (typeof loadSection !== 'function') {
    // Navigation functionality
    function loadSection(section) {
        // Update active button
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.classList.remove('bg-blue-100', 'text-blue-700');
            btn.classList.add('text-gray-700');
        });
        const activeBtn = document.getElementById(section + '-btn');
        if (activeBtn) {
            activeBtn.classList.remove('text-gray-700');
            activeBtn.classList.add('bg-blue-100', 'text-blue-700');
        }
        
        // Update page title
        const titles = {
            'employees': 'Employees',
            'leaves': 'Leave Table',
            'reports': 'Reports',
            'settings': 'Settings'
        };
        
        if (document.getElementById('page-title')) {
            document.getElementById('page-title').textContent = titles[section] || section;
        }
        
        // Show loading
        if (document.getElementById('content-area')) {
            document.getElementById('content-area').innerHTML = `
                <div class="p-6">
                    <div class="text-center py-12">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
                        <p class="mt-4 text-gray-500">Loading ${titles[section]?.toLowerCase() || section}...</p>
                    </div>
                </div>
            `;
        }
        
        // Load content
        fetch(`?section=${section}`)
            .then(response => response.text())
            .then(html => {
                if (document.getElementById('content-area')) {
                    document.getElementById('content-area').innerHTML = html;
                    
                    // Initialize functionality based on section
                    if (section === 'employees') {
                        // Wait a bit for DOM to be ready, then initialize pagination
                        setTimeout(() => {
                            if (typeof initializePagination === 'function') {
                                initializePagination();
                            }
                        }, 100);
                    }
                    
                    if (section === 'leaves') {
                        setTimeout(() => {
                            if (typeof initializePagination === 'function') {
                                initializePagination();
                            }
                            if (typeof initializeLeaveFilters === 'function') {
                                initializeLeaveFilters();
                            }
                        }, 100);
                    }
                }
            })
            .catch(error => {
                console.error('Error loading section:', error);
                if (document.getElementById('content-area')) {
                    document.getElementById('content-area').innerHTML = `
                        <div class="p-6">
                            <div class="text-center py-12 text-red-500">
                                <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                                <p>Error loading content. Please try again.</p>
                            </div>
                        </div>
                    `;
                }
            });
    }
}

// Logout confirmation
if (typeof confirmLogout !== 'function') {
    function confirmLogout() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = '?logout=1';
        }
    }
}
</script>