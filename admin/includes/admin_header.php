<!-- Admin Header -->
<header class="bg-white shadow-md sticky top-0 z-50">
    <div class="px-6 py-4">
        <div class="flex items-center justify-between">
            <!-- Logo and Title -->
            <div class="flex items-center space-x-4">
                <button 
                    onclick="toggleSidebar()" 
                    class="lg:hidden p-2 text-gray-600 hover:bg-gray-100 rounded-lg"
                >
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="flex items-center space-x-3">
                    <i class="fas fa-shield-alt text-indigo-600 text-2xl"></i>
                    <div>
                        <h1 class="text-lg font-bold text-gray-800">Admin Portal</h1>
                        <p class="text-xs text-gray-500"><?php echo SITE_TAGLINE; ?></p>
                    </div>
                </div>
            </div>

            <!-- Right Section -->
            <div class="flex items-center space-x-4">
                <!-- Real-time indicator -->
                <div class="hidden md:flex items-center space-x-2 px-3 py-1 bg-green-50 rounded-full">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="text-xs text-green-700 font-medium">Live</span>
                </div>

                <!-- User Menu -->
                <div class="relative" x-data="{ open: false }">
                    <button 
                        @click="open = !open"
                        class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-gray-100 transition"
                    >
                        <div class="text-right hidden md:block">
                            <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($admin_name); ?></p>
                            <p class="text-xs text-gray-500"><?php echo ucfirst($admin_role); ?></p>
                        </div>
                        <div class="w-10 h-10 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold">
                            <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <div 
                        x-show="open" 
                        @click.away="open = false"
                        x-cloak
                        class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-2"
                    >
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($admin_name); ?></p>
                            <p class="text-xs text-gray-500"><?php echo ucfirst($admin_role); ?></p>
                            <?php if ($admin_department_name): ?>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-building mr-1"></i><?php echo htmlspecialchars($admin_department_name); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-user mr-2 w-5"></i>My Profile
                        </a>
                        <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-cog mr-2 w-5"></i>Settings
                        </a>
                        <div class="border-t border-gray-100 my-2"></div>
                        <a href="../index.php" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-external-link-alt mr-2 w-5"></i>Citizen Portal
                        </a>
                        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            <i class="fas fa-sign-out-alt mr-2 w-5"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    sidebar.classList.toggle('-translate-x-full');
}
</script>