<?php
require_once 'config.php';
require_once 'AICategorizer.php';

$db = Database::getInstance()->getConnection();

// Get statistics
$stats_stmt = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved
    FROM tickets
");
$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - <?php echo SITE_TAGLINE; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-building text-indigo-600 text-3xl"></i>
                    <div>
                        <h1 class="text-xl font-bold text-gray-800"><?php echo SITE_NAME; ?></h1>
                        <p class="text-xs text-gray-500"><?php echo SITE_TAGLINE; ?></p>
                    </div>
                </div>
                <nav class="hidden md:flex space-x-4">
                    <a href="index.php" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        <i class="fas fa-home mr-2"></i>Home
                    </a>
                    <a href="submit.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                        <i class="fas fa-plus mr-2"></i>Submit Concern
                    </a>
                    <a href="assistant.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                        <i class="fas fa-robot mr-2"></i>Ask Assistant
                    </a>
                    <a href="track.php" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                        <i class="fas fa-search mr-2"></i>Track Tickets
                    </a>
                </nav>
                <button class="md:hidden" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
            
            <!-- Mobile Menu -->
            <div id="mobileMenu" class="hidden md:hidden mt-4 space-y-2">
                <a href="index.php" class="block px-4 py-2 bg-indigo-600 text-white rounded-lg">
                    <i class="fas fa-home mr-2"></i>Home
                </a>
                <a href="submit.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-plus mr-2"></i>Submit Concern
                </a>
                <a href="assistant.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-robot mr-2"></i>Ask Assistant
                </a>
                <a href="track.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-search mr-2"></i>Track Tickets
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-8">
        <?php echo getFlashMessage(); ?>
        
        <!-- Welcome Section -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-6">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Welcome to Your LGU Portal</h2>
            <p class="text-gray-600 mb-6">
                Report concerns, get instant answers, and track your requests - all in one place.
            </p>
            
            <div class="grid md:grid-cols-3 gap-4">
                <a href="submit.php" class="p-6 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                    <i class="fas fa-plus text-4xl text-indigo-600 mb-2"></i>
                    <h3 class="font-semibold text-gray-800 mt-2">Submit a Concern</h3>
                    <p class="text-sm text-gray-600 mt-1">Report issues with AI-powered routing</p>
                </a>
                
                <a href="assistant.php" class="p-6 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    <i class="fas fa-robot text-4xl text-green-600 mb-2"></i>
                    <h3 class="font-semibold text-gray-800 mt-2">Ask the Assistant</h3>
                    <p class="text-sm text-gray-600 mt-1">Get instant answers to common questions</p>
                </a>
                
                <a href="track.php" class="p-6 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                    <i class="fas fa-search text-4xl text-purple-600 mb-2"></i>
                    <h3 class="font-semibold text-gray-800 mt-2">Track Your Tickets</h3>
                    <p class="text-sm text-gray-600 mt-1">Monitor status and updates in real-time</p>
                </a>
            </div>
        </div>

        <!-- Features and Stats Grid -->
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Key Features -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-star text-indigo-600 mr-2"></i>
                    Key Features
                </h3>
                <ul class="space-y-3 text-gray-600">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                        <span>AI automatically categorizes and routes your concerns</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                        <span>Multi-department submissions for complex issues</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                        <span>Real-time tracking with status updates</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mr-3 mt-1"></i>
                        <span>Smart Q&A for instant answers</span>
                    </li>
                </ul>
            </div>

            <!-- Statistics -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-chart-bar mr-2"></i>Portal Statistics
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-sm text-gray-600">Total Tickets</span>
                        <span class="font-bold text-indigo-600 text-lg"><?php echo $stats['total']; ?></span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-sm text-gray-600">Open</span>
                        <span class="font-bold text-blue-600 text-lg"><?php echo $stats['open']; ?></span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-sm text-gray-600">In Progress</span>
                        <span class="font-bold text-yellow-600 text-lg"><?php echo $stats['in_progress']; ?></span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-sm text-gray-600">Resolved</span>
                        <span class="font-bold text-green-600 text-lg"><?php echo $stats['resolved']; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 py-6 text-center text-gray-600 text-sm">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_TAGLINE; ?> LGU - <?php echo SITE_NAME; ?></p>
            <p class="mt-1">For assistance, call: <?php echo CONTACT_PHONE; ?> | Email: <?php echo CONTACT_EMAIL; ?></p>
        </div>
    </footer>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            menu.classList.toggle('hidden');
        }
    </script>
</body>
</html>