<?php
require_once '../config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT 
            au.*, 
            d.name as department_name 
        FROM admin_users au
        LEFT JOIN departments d ON au.department_id = d.id
        WHERE au.username = ? AND au.is_active = 1
    ");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        // Login successful
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_name'] = $admin['full_name'];
        $_SESSION['admin_role'] = $admin['role'];
        $_SESSION['admin_department'] = $admin['department_id'];
        $_SESSION['admin_department_name'] = $admin['department_name'];
        
        // Update last login
        $update = $db->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
        $update->execute([$admin['id']]);
        
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-indigo-600 to-purple-700 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md">
        <!-- Logo and Title -->
        <div class="text-center mb-8">
            <div class="bg-white w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="fas fa-shield-alt text-indigo-600 text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">Admin Portal</h1>
            <p class="text-indigo-200"><?php echo SITE_NAME; ?></p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-xl shadow-2xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
                <i class="fas fa-lock mr-2"></i>Sign In
            </h2>

            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-user mr-2"></i>Username
                    </label>
                    <input 
                        type="text" 
                        name="username" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="Enter your username"
                    >
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-2">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <input 
                        type="password" 
                        name="password" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        placeholder="Enter your password"
                    >
                </div>

                <button 
                    type="submit"
                    class="w-full bg-indigo-600 text-white py-3 rounded-lg hover:bg-indigo-700 transition font-medium"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Default Login: <code class="bg-gray-100 px-2 py-1 rounded">admin</code> / 
                    <code class="bg-gray-100 px-2 py-1 rounded">admin123</code>
                </p>
            </div>
        </div>

        <!-- Back to Portal -->
        <div class="text-center mt-6">
            <a href="../index.php" class="text-white hover:text-indigo-200 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Citizen Portal
            </a>
        </div>
    </div>
</body>
</html>