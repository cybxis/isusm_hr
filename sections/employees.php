<?php
/**
 * Employees Section
 * Displays employee data with JOINs to related tables
 */

// Include database connection if not already available
if (!isset($db)) {
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
}

// Include User class
require_once __DIR__ . '/../classes/User.php';

// Get current user
$currentUser = User::fromSession($db);
if (!$currentUser) {
    header('Location: /hr2/login.php');
    exit;
}

// Restrict access for users with designation_id = 6
if ($currentUser->getDesignationId() == 6) {
    echo '<div class="p-6">';
    echo '<div class="text-center py-12">';
    echo '<i class="fas fa-ban text-red-400 text-6xl mb-4"></i>';
    echo '<h3 class="text-lg font-medium text-gray-900 mb-2">Access Denied</h3>';
    echo '<p class="text-gray-500">You do not have permission to access the employees section.</p>';
    echo '</div>';
    echo '</div>';
    return;
}

try {
    // Get total count for pagination info
    $countQuery = $currentUser->getEmployeesCountQuery();
    $countStmt = $currentUser->executeQuery($countQuery);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get employees based on user permissions
    $employeesQuery = $currentUser->getEmployeesQuery();
    $employeesStmt = $currentUser->executeQuery($employeesQuery);
    $employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Current user: " . $currentUser->getFullName() . " (ID: " . $currentUser->getId() . ")");
    error_log("Can view all employees: " . ($currentUser->canViewAllEmployees() ? 'Yes' : 'No'));
    error_log("Found " . count($employees) . " employees");
    
    // Client-side pagination settings
    $recordsPerPage = 15;
    $totalPages = ceil($totalRecords / $recordsPerPage);
    
    // Get statistics based on user permissions
    $statsQuery = $currentUser->getEmployeesStatsQuery();
    $statsStmt = $currentUser->executeQuery($statsQuery);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<div class="p-6">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-users text-blue-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-blue-600">Total Employees</p>
                    <p class="text-2xl font-bold text-blue-900"><?php echo number_format($stats['total_employees']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-check text-green-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-green-600">Active</p>
                    <p class="text-2xl font-bold text-green-900"><?php echo number_format($stats['active_employees']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-user-times text-red-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-red-600">Inactive</p>
                    <p class="text-2xl font-bold text-red-900"><?php echo number_format($stats['inactive_employees']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-building text-purple-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-purple-600">Offices</p>
                    <p class="text-2xl font-bold text-purple-900"><?php echo number_format($stats['total_offices']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="mb-6 bg-gray-50 p-4 rounded-lg">
        <div class="flex-1">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">
                Search Employees 
                <span class="text-xs text-gray-500">(Searches all employees)</span>
            </label>
            <div class="relative">
                <input 
                    type="text" 
                    id="search" 
                    placeholder="Search by name, email, office..."
                    class="w-full pl-10 pr-20 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                <button 
                    onclick="clearEmployeeFilters()" 
                    class="absolute right-2 top-2 px-3 py-1 text-xs bg-gray-200 hover:bg-gray-300 text-gray-600 rounded transition-colors duration-200"
                    title="Clear search">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Employees Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200" id="employeesTable">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(0)">
                        <div class="flex items-center justify-center space-x-1">
                            <span>Employee</span>
                            <i class="fas fa-sort text-gray-400" id="sort-icon-0"></i>
                        </div>
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(1)">
                        <div class="flex items-center justify-center space-x-1">
                            <span>Email</span>
                            <i class="fas fa-sort text-gray-400" id="sort-icon-1"></i>
                        </div>
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(2)">
                        <div class="flex items-center justify-center space-x-1">
                            <span>Office</span>
                            <i class="fas fa-sort text-gray-400" id="sort-icon-2"></i>
                        </div>
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(3)">
                        <div class="flex items-center justify-center space-x-1">
                            <span>Designation</span>
                            <i class="fas fa-sort text-gray-400" id="sort-icon-3"></i>
                        </div>
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(4)">
                        <div class="flex items-center justify-center space-x-1">
                            <span>Leave Count</span>
                            <i class="fas fa-sort text-gray-400" id="sort-icon-4"></i>
                        </div>
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-200" onclick="sortTable(5)">
                        <div class="flex items-center justify-center space-x-1">
                            <span>Status</span>
                            <i class="fas fa-sort text-gray-400" id="sort-icon-5"></i>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($employees as $employee): ?>
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-400 to-blue-600 flex items-center justify-center">
                                    <span class="text-sm font-medium text-white">
                                        <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($employee['full_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    ID: <?php echo $employee['user_id']; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                        <a href="mailto:<?php echo htmlspecialchars($employee['email']); ?>" 
                           class="text-blue-600 hover:text-blue-900 hover:underline">
                            <?php echo htmlspecialchars($employee['email']); ?>
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                     <?php 
                                     $office_name = $employee['office_name'] ?? 'Not Assigned';
                                     if ($office_name === 'BSIT') {
                                         echo 'bg-purple-100 text-purple-800';
                                     } elseif ($office_name === 'BSA') {
                                         echo 'bg-green-100 text-green-800';
                                     } else {
                                         echo 'bg-blue-100 text-blue-800';
                                     }
                                     ?>">
                            <?php echo htmlspecialchars($office_name); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                        <?php echo htmlspecialchars($employee['designation_name'] ?? 'Not Assigned'); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                        <div class="flex items-center justify-center">
                            <i class="fas fa-calendar-day text-gray-400 mr-1"></i>
                            <?php echo number_format($employee['leave_count']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-center">
                        <?php if ($employee['is_active']): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>
                                Active
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>
                                Inactive
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Controls -->
    <div class="mt-6 bg-white px-4 py-3 border border-gray-200 rounded-lg" id="pagination-controls">
        <div class="flex items-center justify-between">
            <!-- Empty left space -->
            <div></div>
            
            <!-- Pagination Buttons (Centered) -->
            <div class="flex items-center space-x-2" id="pagination-buttons">
                <!-- Previous Button -->
                <button id="prev-btn" onclick="previousPage()" 
                        class="px-3 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-md cursor-not-allowed" disabled>
                    <i class="fas fa-chevron-left mr-1"></i>
                    Previous
                </button>
                
                <!-- Page Numbers -->
                <div class="flex items-center space-x-1" id="page-numbers">
                    <!-- Dynamically generated page numbers -->
                </div>
                
                <!-- Next Button -->
                <button id="next-btn" onclick="nextPage()" 
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:text-gray-900 transition-colors duration-200">
                    Next
                    <i class="fas fa-chevron-right ml-1"></i>
                </button>
            </div>

            <!-- Pagination Info (Right side) -->
            <div class="flex flex-col items-end text-sm text-gray-700">
                <span class="font-medium">Page <span id="current-page">1</span> of <span id="total-pages"><?php echo $totalPages; ?></span></span>
                <span class="text-xs text-gray-500 mt-1">
                    (<span id="total-records"><?php echo number_format($totalRecords); ?></span> total employees, showing <span id="visible-records">15</span> on this page)
                </span>
                <span class="font-medium" id="pagination-info" style="display: none;">
                    Showing 1 to 15 of <?php echo number_format($totalRecords); ?> results
                </span>
            </div>
        </div>
    </div>

    <?php if (empty($employees)): ?>
    <div class="text-center py-12">
        <i class="fas fa-users text-gray-300 text-6xl mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No employees found</h3>
        <p class="text-gray-500">There are no employees in the database.</p>
    </div>
    <?php endif; ?>
</div>