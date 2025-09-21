<?php
/**
 * Leaves Section
 * Displays leave data with JOINs to related tables
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
    echo '<p class="text-gray-500">You do not have permission to access the leaves section.</p>';
    echo '</div>';
    echo '</div>';
    return;
}

try {
    // Get total count for pagination info
    $statsQuery = $currentUser->getLeavesStatsQuery();
    $statsStmt = $currentUser->executeLeavesQuery($statsQuery);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    $totalRecords = $stats['total_leaves'];
    
    // Get leaves based on user permissions
    $leavesQuery = $currentUser->getLeavesQuery();
    $leavesStmt = $currentUser->executeLeavesQuery($leavesQuery);
    $leaves = $leavesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Client-side pagination settings
    $recordsPerPage = 15;
    $totalPages = ceil($totalRecords / $recordsPerPage);
    
    error_log("Current user: " . $currentUser->getFullName() . " (ID: " . $currentUser->getId() . ")");
    error_log("Can view all leaves: " . ($currentUser->canViewAllEmployees() ? 'Yes' : 'No'));
    error_log("Found " . count($leaves) . " leaves");
    
} catch (PDOException $e) {
    error_log("Error in leaves.php: " . $e->getMessage());
    echo '<div class="p-6">';
    echo '<div class="text-center py-12">';
    echo '<i class="fas fa-exclamation-triangle text-red-400 text-6xl mb-4"></i>';
    echo '<h3 class="text-lg font-medium text-gray-900 mb-2">Error</h3>';
    echo '<p class="text-gray-500">Unable to load leave data. Please try again later.</p>';
    echo '</div>';
    echo '</div>';
    return;
}

function getStatusBadge($status) {
    switch ($status) {
        case 'APPROVED':
            return 'bg-green-100 text-green-800';
        case 'REJECTED':
            return 'bg-red-100 text-red-800';
        case 'PENDING':
        default:
            return 'bg-yellow-100 text-yellow-800';
    }
}

function getStatusIcon($status) {
    switch ($status) {
        case 'APPROVED':
            return 'fas fa-check-circle';
        case 'REJECTED':
            return 'fas fa-times-circle';
        case 'PENDING':
        default:
            return 'fas fa-clock';
    }
}
?>

<div class="p-6">
    <!-- Statistics Cards -->
    <?php 
    // Calculate the number of cards to display
    $cardCount = 3; // Base cards: Total, Approved, Rejected
    if ($stats['pending_leaves'] > 0) {
        $cardCount++;
    }
    
    // Set appropriate grid columns based on card count
    $gridCols = $cardCount == 3 ? 'md:grid-cols-3' : 'md:grid-cols-4';
    ?>
    <div class="grid grid-cols-1 <?php echo $gridCols; ?> gap-4 mb-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-calendar-alt text-blue-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-blue-600">Total Leaves</p>
                    <p class="text-2xl font-bold text-blue-900"><?php echo number_format($stats['total_leaves']); ?></p>
                </div>
            </div>
        </div>
        
        <?php if ($stats['pending_leaves'] > 0): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-yellow-600">Pending</p>
                    <p class="text-2xl font-bold text-yellow-900"><?php echo number_format($stats['pending_leaves']); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-green-600">Approved</p>
                    <p class="text-2xl font-bold text-green-900"><?php echo number_format($stats['approved_leaves']); ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-times-circle text-red-600 text-2xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-red-600">Rejected</p>
                    <p class="text-2xl font-bold text-red-900"><?php echo number_format($stats['rejected_leaves']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="mb-6 bg-gray-50 p-4 rounded-lg">
        <div class="flex-1">
            <label for="leaveSearch" class="block text-sm font-medium text-gray-700 mb-1">Search Leaves</label>
            <div class="relative">
                <input 
                    type="text" 
                    id="leaveSearch" 
                    placeholder="Search by employee name, leave type, reason..."
                    class="w-full pl-10 pr-20 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                <button 
                    onclick="clearLeaveFilters()" 
                    class="absolute right-2 top-2 px-3 py-1 text-xs bg-gray-200 hover:bg-gray-300 text-gray-600 rounded transition-colors duration-200"
                    title="Clear search">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Leaves Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200" id="leavesTable">
            <thead class="bg-gray-50">
                <tr>
                    <th onclick="sortLeaveTable(0)" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        Employee
                        <i id="leave-sort-icon-0" class="fas fa-sort text-gray-400 ml-1"></i>
                    </th>
                    <th onclick="sortLeaveTable(1)" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        Leave Type
                        <i id="leave-sort-icon-1" class="fas fa-sort text-gray-400 ml-1"></i>
                    </th>
                    <th onclick="sortLeaveTable(2)" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        Duration
                        <i id="leave-sort-icon-2" class="fas fa-sort text-gray-400 ml-1"></i>
                    </th>
                    <th onclick="sortLeaveTable(3)" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        Dates
                        <i id="leave-sort-icon-3" class="fas fa-sort text-gray-400 ml-1"></i>
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Reason
                    </th>
                    <th onclick="sortLeaveTable(5)" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        Status
                        <i id="leave-sort-icon-5" class="fas fa-sort text-gray-400 ml-1"></i>
                    </th>
                    <th onclick="sortLeaveTable(6)" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                        Filed Date
                        <i id="leave-sort-icon-6" class="fas fa-sort text-gray-400 ml-1"></i>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($leaves as $leave): ?>
                <tr class="hover:bg-gray-50 transition-colors duration-200">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-r from-indigo-400 to-indigo-600 flex items-center justify-center">
                                    <span class="text-sm font-medium text-white">
                                        <?php echo strtoupper(substr($leave['first_name'], 0, 1) . substr($leave['last_name'], 0, 1)); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($leave['full_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($leave['office_name'] ?? 'No Office'); ?> • 
                                    <?php echo htmlspecialchars($leave['designation_name'] ?? 'No Designation'); ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($leave['leave_name']); ?>
                        </div>
                        <div class="text-sm text-gray-500">
                            Max: <?php echo $leave['leave_duration']; ?> days
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-day text-gray-400 mr-2"></i>
                            <span class="text-sm font-medium text-gray-900">
                                <?php echo $leave['days_requested']; ?> day<?php echo $leave['days_requested'] > 1 ? 's' : ''; ?>
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            <div class="font-medium">
                                <i class="fas fa-play text-green-500 mr-1"></i>
                                <?php echo date('M j, Y', strtotime($leave['start_date'])); ?>
                            </div>
                            <div class="text-gray-500">
                                <i class="fas fa-stop text-red-500 mr-1"></i>
                                <?php echo date('M j, Y', strtotime($leave['end_date'])); ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 max-w-xs truncate" title="<?php echo htmlspecialchars($leave['reason']); ?>">
                            <?php echo htmlspecialchars($leave['reason']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getStatusBadge($leave['status']); ?>">
                            <i class="<?php echo getStatusIcon($leave['status']); ?> mr-1"></i>
                            <?php echo ucfirst(strtolower($leave['status'])); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div>
                            <?php echo date('M j, Y', strtotime($leave['filed_at'])); ?>
                        </div>
                        <div class="text-xs text-gray-400">
                            <?php echo date('g:i A', strtotime($leave['filed_at'])); ?>
                        </div>
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
                    (<span id="total-records"><?php echo number_format($totalRecords); ?></span> total leaves, showing <span id="visible-records">15</span> on this page)
                </span>
                <span class="font-medium" id="pagination-info" style="display: none;">
                    Showing 1 to 15 of <?php echo number_format($totalRecords); ?> results
                </span>
            </div>
        </div>
    </div>

    <?php if (empty($leaves)): ?>
    <div class="text-center py-12">
        <i class="fas fa-calendar-alt text-gray-300 text-6xl mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No leave requests found</h3>
        <p class="text-gray-500">There are no leave requests in the database.</p>
    </div>
    <?php endif; ?>
</div>