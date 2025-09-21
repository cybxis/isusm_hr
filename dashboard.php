<?php
/**
 * HR Management System Dashboard
 * Main dashboard with sidebar navigation and session management
 */

require_once 'config/database.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->getConnection();

// Get current user (this will configure secure session automatically)
$currentUser = User::fromSession($db);
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

// Restrict access for users with designation_id = 6
if ($currentUser->getDesignationId() == 6) {
    session_destroy();
    header('Location: login.php?error=' . urlencode('Access denied: Insufficient privileges'));
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Handle AJAX requests
if (isset($_GET['section'])) {
    $section = $_GET['section'];
    
    if ($section === 'employees') {
        include 'sections/employees.php';
        exit;
    } elseif ($section === 'leaves') {
        // Additional check for leaves section access using User class
        if ($currentUser->getDesignationId() == 6) {
            echo '<div class="p-6">';
            echo '<div class="text-center py-12">';
            echo '<i class="fas fa-ban text-red-400 text-6xl mb-4"></i>';
            echo '<h3 class="text-lg font-medium text-gray-900 mb-2">Access Denied</h3>';
            echo '<p class="text-gray-500">You do not have permission to access the leaves section.</p>';
            echo '</div>';
            echo '</div>';
            exit;
        }
        include 'sections/leaves.php';
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management System - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2563eb',
                        secondary: '#64748b',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Include Sidebar Component -->
        <?php include 'components/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Include Header Component -->
            <?php include 'components/header.php'; ?>
            
            <!-- Content Area -->
            <main class="flex-1 p-6 overflow-auto">
                <div id="content-area" class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <!-- Default content will be loaded here -->
                    <div class="p-6">
                        <div class="text-center py-12">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto"></div>
                            <p class="mt-4 text-gray-500">Loading dashboard...</p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Employee search functionality using event delegation
        document.addEventListener('input', function(e) {
            if (e.target && e.target.id === 'search') {
                console.log('Search input detected:', e.target.value);
                filterEmployeeTable();
            }
            if (e.target && e.target.id === 'leaveSearch') {
                console.log('Leave search input detected:', e.target.value);
                filterLeaveTable();
            }
        });

        document.addEventListener('change', function(e) {
            // Handle table sorting clicks - no filter dropdowns needed
        });

        document.addEventListener('click', function(e) {
            // Handle table sorting clicks
            if (e.target && e.target.hasAttribute && e.target.hasAttribute('onclick') && e.target.getAttribute('onclick').includes('sortTable')) {
                console.log('Sort click detected');
                // The onclick attribute will handle the sorting
            }
        });

        // Filter function for employee table
        function filterEmployeeTable() {
            const searchInput = document.getElementById('search');
            const statusFilter = document.getElementById('statusFilter');
            const table = document.getElementById('employeesTable');
            
            if (!searchInput || !statusFilter || !table) {
                console.log('Elements not found for filtering');
                return;
            }

            const searchTerm = searchInput.value.toLowerCase().trim();
            const statusValue = statusFilter.value;
            const tbody = table.querySelector('tbody');
            const rows = tbody.querySelectorAll('tr');
            
            console.log('Filtering with:', searchTerm, 'Status:', statusValue);
            console.log('Found rows:', rows.length);

            let visibleCount = 0;

            rows.forEach((row) => {
                const text = row.textContent.toLowerCase();
                const statusCell = row.querySelector('td:nth-child(6) span');
                const isActive = statusCell && statusCell.textContent.toLowerCase().includes('active');
                
                const matchesSearch = searchTerm === '' || text.includes(searchTerm);
                const matchesStatus = statusValue === '' || 
                                    (statusValue === '1' && isActive) || 
                                    (statusValue === '0' && !isActive);

                if (matchesSearch && matchesStatus) {
                    row.style.display = 'table-row';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            console.log('Visible rows:', visibleCount);
        }

        // Clear filters function for employees
        function clearEmployeeFilters() {
            console.log('Clearing employee filters');
            const searchInput = document.getElementById('search');
            const statusFilter = document.getElementById('statusFilter');
            
            if (searchInput) searchInput.value = '';
            if (statusFilter) statusFilter.value = '';
            
            // Reset to page 1 and reload
            loadEmployeesPage(1);
        }

        // Filter function for leave table
        function filterLeaveTable() {
            const searchInput = document.getElementById('leaveSearch');
            const table = document.getElementById('leavesTable');
            
            if (!searchInput || !table) {
                console.log('Leave filter elements not found');
                return;
            }

            const searchTerm = searchInput.value.toLowerCase().trim();
            const tbody = table.querySelector('tbody');
            const rows = tbody.querySelectorAll('tr');
            
            console.log('Filtering leaves with:', searchTerm);
            console.log('Found leave rows:', rows.length);

            let visibleCount = 0;

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                
                const matchesSearch = searchTerm === '' || text.includes(searchTerm);

                if (matchesSearch) {
                    row.style.display = 'table-row';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            console.log('Visible leave rows:', visibleCount);
        }

        // Clear filters function for leaves
        function clearLeaveFilters() {
            console.log('Clearing leave filters');
            const searchInput = document.getElementById('leaveSearch');
            
            if (searchInput) searchInput.value = '';
            
            filterLeaveTable();
        }

        // Table sorting functionality for employees
        let sortDirection = {};

        function sortTable(columnIndex) {
            console.log('Sorting column:', columnIndex);
            const table = document.getElementById('employeesTable');
            if (!table) return;
            
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Initialize sort direction for column if not exists
            if (!sortDirection[columnIndex]) {
                sortDirection[columnIndex] = 'asc';
            }
            
            // Toggle sort direction
            sortDirection[columnIndex] = sortDirection[columnIndex] === 'asc' ? 'desc' : 'asc';
            
            // Update all sort icons
            for (let i = 0; i <= 5; i++) {
                const icon = document.getElementById(`sort-icon-${i}`);
                if (icon) {
                    if (i === columnIndex) {
                        if (sortDirection[columnIndex] === 'asc') {
                            icon.className = 'fas fa-sort-up text-blue-500';
                        } else {
                            icon.className = 'fas fa-sort-down text-blue-500';
                        }
                    } else {
                        icon.className = 'fas fa-sort text-gray-400';
                    }
                }
            }
            
            // Sort rows
            rows.sort((a, b) => {
                let aValue = '';
                let bValue = '';
                
                try {
                    switch (columnIndex) {
                        case 0: // Employee name
                            const aNameEl = a.querySelector('td:nth-child(1) .text-sm.font-medium');
                            const bNameEl = b.querySelector('td:nth-child(1) .text-sm.font-medium');
                            aValue = aNameEl ? aNameEl.textContent.trim() : '';
                            bValue = bNameEl ? bNameEl.textContent.trim() : '';
                            break;
                        case 1: // Email
                            const aEmailEl = a.querySelector('td:nth-child(2) a');
                            const bEmailEl = b.querySelector('td:nth-child(2) a');
                            aValue = aEmailEl ? aEmailEl.textContent.trim() : '';
                            bValue = bEmailEl ? bEmailEl.textContent.trim() : '';
                            break;
                        case 2: // Office
                            const aOfficeEl = a.querySelector('td:nth-child(3) span');
                            const bOfficeEl = b.querySelector('td:nth-child(3) span');
                            aValue = aOfficeEl ? aOfficeEl.textContent.trim() : '';
                            bValue = bOfficeEl ? bOfficeEl.textContent.trim() : '';
                            break;
                        case 3: // Designation
                            aValue = a.querySelector('td:nth-child(4)') ? a.querySelector('td:nth-child(4)').textContent.trim() : '';
                            bValue = b.querySelector('td:nth-child(4)') ? b.querySelector('td:nth-child(4)').textContent.trim() : '';
                            break;
                        case 4: // Leave Count
                            const aLeaveText = a.querySelector('td:nth-child(5)') ? a.querySelector('td:nth-child(5)').textContent : '0';
                            const bLeaveText = b.querySelector('td:nth-child(5)') ? b.querySelector('td:nth-child(5)').textContent : '0';
                            aValue = parseInt(aLeaveText.replace(/\D/g, '')) || 0;
                            bValue = parseInt(bLeaveText.replace(/\D/g, '')) || 0;
                            break;
                        case 5: // Status
                            const aStatusEl = a.querySelector('td:nth-child(6) span');
                            const bStatusEl = b.querySelector('td:nth-child(6) span');
                            aValue = aStatusEl ? aStatusEl.textContent.trim() : '';
                            bValue = bStatusEl ? bStatusEl.textContent.trim() : '';
                            break;
                    }
                } catch (error) {
                    console.error('Error sorting column', columnIndex, error);
                    return 0;
                }
                
                // Handle numeric and date comparisons
                if (columnIndex === 4) { // Leave Count
                    return sortDirection[columnIndex] === 'asc' ? aValue - bValue : bValue - aValue;
                } else {
                    // String comparison
                    if (sortDirection[columnIndex] === 'asc') {
                        return aValue.localeCompare(bValue);
                    } else {
                        return bValue.localeCompare(aValue);
                    }
                }
            });
            
            // Clear tbody and re-append sorted rows
            tbody.innerHTML = '';
            rows.forEach(row => tbody.appendChild(row));
        }

        // Table sorting functionality for leaves
        let leaveSortDirection = {};

        function sortLeaveTable(columnIndex) {
            console.log('Sorting leave column:', columnIndex);
            const table = document.getElementById('leavesTable');
            if (!table) return;
            
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            // Initialize sort direction for column if not exists
            if (!leaveSortDirection[columnIndex]) {
                leaveSortDirection[columnIndex] = 'asc';
            }
            
            // Toggle sort direction
            leaveSortDirection[columnIndex] = leaveSortDirection[columnIndex] === 'asc' ? 'desc' : 'asc';
            
            // Update all sort icons
            for (let i = 0; i <= 6; i++) {
                const icon = document.getElementById(`leave-sort-icon-${i}`);
                if (icon) {
                    if (i === columnIndex) {
                        if (leaveSortDirection[columnIndex] === 'asc') {
                            icon.className = 'fas fa-sort-up text-blue-500';
                        } else {
                            icon.className = 'fas fa-sort-down text-blue-500';
                        }
                    } else {
                        icon.className = 'fas fa-sort text-gray-400';
                    }
                }
            }
            
            // Sort rows
            rows.sort((a, b) => {
                let aValue = '';
                let bValue = '';
                
                try {
                    switch (columnIndex) {
                        case 0: // Employee name
                            const aNameEl = a.querySelector('td:nth-child(1) .text-sm.font-medium');
                            const bNameEl = b.querySelector('td:nth-child(1) .text-sm.font-medium');
                            aValue = aNameEl ? aNameEl.textContent.trim() : '';
                            bValue = bNameEl ? bNameEl.textContent.trim() : '';
                            break;
                        case 1: // Leave Type
                            const aTypeEl = a.querySelector('td:nth-child(2) span');
                            const bTypeEl = b.querySelector('td:nth-child(2) span');
                            aValue = aTypeEl ? aTypeEl.textContent.trim() : '';
                            bValue = bTypeEl ? bTypeEl.textContent.trim() : '';
                            break;
                        case 2: // Duration
                            const aDurationText = a.querySelector('td:nth-child(3)') ? a.querySelector('td:nth-child(3)').textContent : '0';
                            const bDurationText = b.querySelector('td:nth-child(3)') ? b.querySelector('td:nth-child(3)').textContent : '0';
                            aValue = parseInt(aDurationText.replace(/\D/g, '')) || 0;
                            bValue = parseInt(bDurationText.replace(/\D/g, '')) || 0;
                            break;
                        case 3: // Start Date
                            const aDateText = a.querySelector('td:nth-child(4)') ? a.querySelector('td:nth-child(4)').textContent.trim() : '';
                            const bDateText = b.querySelector('td:nth-child(4)') ? b.querySelector('td:nth-child(4)').textContent.trim() : '';
                            // Extract start date from the date range
                            const aDateMatch = aDateText.match(/(\w+)\s+(\d+),\s+(\d+)/);
                            const bDateMatch = bDateText.match(/(\w+)\s+(\d+),\s+(\d+)/);
                            aValue = aDateMatch ? new Date(`${aDateMatch[1]} ${aDateMatch[2]}, ${aDateMatch[3]}`) : new Date(0);
                            bValue = bDateMatch ? new Date(`${bDateMatch[1]} ${bDateMatch[2]}, ${bDateMatch[3]}`) : new Date(0);
                            break;
                        case 5: // Status
                            const aStatusEl = a.querySelector('td:nth-child(6) span');
                            const bStatusEl = b.querySelector('td:nth-child(6) span');
                            aValue = aStatusEl ? aStatusEl.textContent.trim() : '';
                            bValue = bStatusEl ? bStatusEl.textContent.trim() : '';
                            break;
                        case 6: // Filed Date
                            const aFiledText = a.querySelector('td:nth-child(7)') ? a.querySelector('td:nth-child(7)').textContent.trim() : '';
                            const bFiledText = b.querySelector('td:nth-child(7)') ? b.querySelector('td:nth-child(7)').textContent.trim() : '';
                            const aFiledMatch = aFiledText.match(/(\w+)\s+(\d+),\s+(\d+)/);
                            const bFiledMatch = bFiledText.match(/(\w+)\s+(\d+),\s+(\d+)/);
                            aValue = aFiledMatch ? new Date(`${aFiledMatch[1]} ${aFiledMatch[2]}, ${aFiledMatch[3]}`) : new Date(0);
                            bValue = bFiledMatch ? new Date(`${bFiledMatch[1]} ${bFiledMatch[2]}, ${bFiledMatch[3]}`) : new Date(0);
                            break;
                    }
                } catch (error) {
                    console.error('Error sorting leave column', columnIndex, error);
                    return 0;
                }
                
                // Handle numeric and date comparisons
                if (columnIndex === 2) { // Duration (numeric)
                    return leaveSortDirection[columnIndex] === 'asc' ? aValue - bValue : bValue - aValue;
                } else if (columnIndex === 3 || columnIndex === 6) { // Dates
                    return leaveSortDirection[columnIndex] === 'asc' ? aValue - bValue : bValue - aValue;
                } else {
                    // String comparison
                    if (leaveSortDirection[columnIndex] === 'asc') {
                        return aValue.localeCompare(bValue);
                    } else {
                        return bValue.localeCompare(aValue);
                    }
                }
            });
            
            // Clear tbody and re-append sorted rows
            tbody.innerHTML = '';
            rows.forEach(row => tbody.appendChild(row));
        }

        // Load employees section by default
        document.addEventListener('DOMContentLoaded', function() {
            loadSection('employees');
        });
    </script>
</body>
</html>