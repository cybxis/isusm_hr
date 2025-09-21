<?php
/**
 * Login Page
 * HR Management System
 */

require_once 'config/database.php';
require_once 'classes/User.php';

$database = new Database();
$db = $database->getConnection();

// Check if user is already logged in using User class
$currentUser = User::fromSession($db);
if ($currentUser) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';
$success_message = '';

// Check for logout message
if (isset($_GET['message']) && $_GET['message'] === 'logged_out') {
    $success_message = 'You have been successfully logged out.';
}

// Check for access denied message
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error_message = 'Please fill in all fields.';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Check user credentials
            $query = "
                SELECT 
                    u.user_id,
                    u.email,
                    u.password,
                    u.first_name,
                    u.middle_name,
                    u.last_name,
                    u.is_active,
                    u.designation_id,
                    o.office_name,
                    d.designation_name,
                    r.role_name
                FROM tbl_users u
                LEFT JOIN tbl_offices o ON u.office_id = o.office_id
                LEFT JOIN tbl_designations d ON u.designation_id = d.designation_id
                LEFT JOIN tbl_roles r ON u.role_id = r.role_id
                WHERE u.email = ? AND u.is_active = 1
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['full_name'] = trim($user['first_name'] . ' ' . ($user['middle_name'] ? $user['middle_name'] . ' ' : '') . $user['last_name']);
                $_SESSION['office_name'] = $user['office_name'];
                $_SESSION['designation_name'] = $user['designation_name'];
                $_SESSION['designation_id'] = $user['designation_id'];
                $_SESSION['role_name'] = $user['role_name'];
                $_SESSION['logged_in'] = true;
                
                // Log the login activity
                $logQuery = "UPDATE tbl_users SET created_at = created_at WHERE user_id = ?";
                $logStmt = $db->prepare($logQuery);
                $logStmt->execute([$user['user_id']]);
                
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error_message = 'Invalid email or password.';
            }
            
        } catch (PDOException $e) {
            $error_message = 'Database connection error. Please try again later.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HR Management System</title>
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
<body class="bg-gradient-to-br from-blue-50 via-white to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-blue-600/5 to-indigo-600/10"></div>
    
    <div class="relative z-10 w-full max-w-md mx-4">
        <!-- Login Card -->
        <div class="bg-white shadow-2xl rounded-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-12 text-center">
                <div class="w-20 h-20 mx-auto bg-white/20 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-users text-3xl text-white"></i>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">HR System</h1>
                <p class="text-blue-100">Management Dashboard Login</p>
            </div>
            
            <!-- Login Form -->
            <div class="p-8">
                <?php if ($error_message): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                        <span class="text-red-800 text-sm"><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-2"></i>
                        <span class="text-green-800 text-sm"><?php echo htmlspecialchars($success_message); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="space-y-6">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Email Address
                        </label>
                        <div class="relative">
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                required
                                class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                placeholder="Enter your email address">
                            <i class="fas fa-envelope absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>
                    
                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                class="w-full pl-12 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                                placeholder="Enter your password">
                            <i class="fas fa-lock absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <button 
                                type="button" 
                                onclick="togglePassword()"
                                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Login Button -->
                    <button 
                        type="submit" 
                        class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 px-4 rounded-lg font-medium hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-200 transform hover:scale-[1.02]">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Sign In
                    </button>
                </form>
                
                <!-- Demo Credentials -->
                <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Demo Credentials
                    </h3>
                    <div class="space-y-2 text-xs text-gray-600">
                        <div class="flex justify-between">
                            <span class="font-medium">Admin:</span>
                            <span>admin@admin.com</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">HR:</span>
                            <span>hr@hr.com</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Employee:</span>
                            <span>ca@ca.com</span>
                        </div>
                        <div class="mt-2 pt-2 border-t border-gray-200">
                            <span class="font-medium">Default Password:</span>
                            <span class="font-mono bg-gray-200 px-1 rounded">admin123</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-8">
            <p class="text-sm text-gray-500">
                &copy; 2025 HR Management System. All rights reserved.
            </p>
            <p class="text-xs text-gray-400 mt-1">
                Secure login powered by PHP 8+ and MySQL
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Auto-focus email field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });

        // Add loading state to button on form submit
        document.querySelector('form').addEventListener('submit', function() {
            const button = document.querySelector('button[type="submit"]');
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Signing In...';
            button.disabled = true;
        });
    </script>
</body>
</html>