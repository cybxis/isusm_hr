<?php
/**
 * Database Setup Script
 * Creates proper password hashes for user authentication
 * Run this once after importing the database
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Default password for all demo accounts
    $defaultPassword = 'admin123';
    $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
    
    // Update all user passwords with the hashed version
    $updateQuery = "UPDATE tbl_users SET password = ?";
    $stmt = $db->prepare($updateQuery);
    $stmt->execute([$hashedPassword]);
    
    $affectedRows = $stmt->rowCount();
    
    echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Setup - HR Management System</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body class='bg-gray-100 min-h-screen flex items-center justify-center'>
    <div class='max-w-md mx-auto bg-white rounded-lg shadow-lg p-8'>
        <div class='text-center'>
            <div class='w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4'>
                <i class='fas fa-check text-green-600 text-2xl'></i>
            </div>
            <h2 class='text-2xl font-bold text-gray-800 mb-4'>Setup Complete!</h2>
            <p class='text-gray-600 mb-6'>
                Database setup successful. Updated {$affectedRows} user passwords with proper hashing.
            </p>
            <div class='bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-left'>
                <h3 class='font-semibold text-blue-800 mb-2'>Demo Login Credentials:</h3>
                <div class='space-y-1 text-sm text-blue-700'>
                    <div><strong>Admin:</strong> admin@admin.com</div>
                    <div><strong>HR:</strong> hr@hr.com</div>
                    <div><strong>Employee:</strong> ca@ca.com</div>
                    <div class='pt-2 border-t border-blue-200'><strong>Password:</strong> admin123</div>
                </div>
            </div>
            <a href='login.php' class='inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors'>
                <i class='fas fa-sign-in-alt mr-2'></i>
                Go to Login
            </a>
        </div>
    </div>
</body>
</html>";
    
} catch (PDOException $e) {
    echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Database Setup Error - HR Management System</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body class='bg-gray-100 min-h-screen flex items-center justify-center'>
    <div class='max-w-md mx-auto bg-white rounded-lg shadow-lg p-8'>
        <div class='text-center'>
            <div class='w-16 h-16 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4'>
                <i class='fas fa-times text-red-600 text-2xl'></i>
            </div>
            <h2 class='text-2xl font-bold text-gray-800 mb-4'>Setup Failed!</h2>
            <p class='text-gray-600 mb-4'>Database connection error:</p>
            <div class='bg-red-50 border border-red-200 rounded-lg p-4 mb-6'>
                <p class='text-red-800 text-sm'>" . htmlspecialchars($e->getMessage()) . "</p>
            </div>
            <p class='text-sm text-gray-500'>
                Please check your database configuration in config/database.php and ensure the database is imported.
            </p>
        </div>
    </div>
</body>
</html>";
}
?>