<?php
/**
 * Reports Page Example
 * Demonstrating how to use the reusable components
 */

require_once 'config/database.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->getConnection();

// Get current user
$currentUser = User::fromSession($db);
if (!$currentUser) {
    header('Location: login.php');
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Handle AJAX requests for different sections
if (isset($_GET['section'])) {
    $section = $_GET['section'];
    
    if ($section === 'employees') {
        include 'sections/employees.php';
        exit;
    } elseif ($section === 'leaves') {
        include 'sections/leaves.php';
        exit;
    }
    // Add more sections as needed
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - HR Management System</title>
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
        // Load employees section by default
        document.addEventListener('DOMContentLoaded', function() {
            loadSection('employees');
        });
    </script>
</body>
</html>