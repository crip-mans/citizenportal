<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Admin Sidebar -->
<aside 
    id="adminSidebar"
    class="w-64 bg-white shadow-lg fixed lg:sticky top-0 h-screen transition-transform -translate-x-full lg:translate-x-0 z-40"
>
    <nav class="p-4 space-y-2 overflow-y-auto h-full">
        <!-- Dashboard -->
        <a 
            href="dashboard.php" 
            class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo $current_page === 'dashboard.php' ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>"
        >
            <i class="fas fa-chart-line w-5"></i>
            <span class="font-medium">Dashboard</span>
        </a>

        <!-- Tickets -->
        <a 
            href="tickets.php" 
            class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo $current_page === 'tickets.php' ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>"
        >
            <i class="fas fa-ticket-alt w-5"></i>
            <span class="font-medium">Tickets</span>
            <?php
            // Get open tickets count
            $db = Database::getInstance()->getConnection();
            $count_query = "SELECT COUNT(*) as count FROM tickets WHERE status = 'open'";
            if ($admin_role !== 'admin' && $admin_department) {
                $count_query .= " AND id IN (SELECT ticket_id FROM ticket_departments WHERE department_id = {$admin_department})";
            }
            $count = $db->query($count_query)->fetch()['count'];
            if ($count > 0):
            ?>
            <span class="ml-auto px-2 py-1 bg-red-500 text-white text-xs rounded-full">
                <?php echo $count; ?>
            </span>
            <?php endif; ?>
        </a>

        <!-- Reports -->
        <a 
            href="reports.php" 
            class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo $current_page === 'reports.php' ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>"
        >
            <i class="fas fa-chart-bar w-5"></i>
            <span class="font-medium">Reports</span>
        </a>

        <!-- Map View -->
        <a 
            href="map.php" 
            class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo $current_page === 'map.php' ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>"
        >
            <i class="fas fa-map-marked-alt w-5"></i>
            <span class="font-medium">Map View</span>
        </a>

        <div class="border-t border-gray-200 my-4"></div>

        <!-- Admin Only Sections -->
        <?php if ($admin_role === 'admin'): ?>
        <div class="space-y-2">
            <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Administration</p>
            
            <a 
                href="departments.php" 
                class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo $current_page === 'departments.php' ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>"
            >
                <i class="fas fa-building w-5"></i>
                <span class="font-medium">Departments</span>
            </a>

            <a 
                href="users.php" 
                class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo $current_page === 'users.php' ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>"
            >
                <i class="fas fa-users w-5"></i>
                <span class="font-medium">Admin Users</span>
            </a>

            <a 
                href="faq.php" 
                class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo $current_page === 'faq.php' ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>"
            >
                <i class="fas fa-question-circle w-5"></i>
                <span class="font-medium">FAQ Management</span>
            </a>

            <a 
                href="ai_keywords.php" 
                class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo $current_page === 'ai_keywords.php' ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>"
            >
                <i class="fas fa-robot w-5"></i>
                <span class="font-medium">AI Keywords</span>
            </a>
        </div>

        <div class="border-t border-gray-200 my-4"></div>
        <?php endif; ?>

        <!-- System -->
        <div class="space-y-2">
            <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">System</p>
            
            <a 
                href="settings.php" 
                class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo $current_page === 'settings.php' ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>"
            >
                <i class="fas fa-cog w-5"></i>
                <span class="font-medium">Settings</span>
            </a>

            <a 
                href="activity_log.php" 
                class="flex items-center space-x-3 px-4 py-3 rounded-lg transition <?php echo $current_page === 'activity_log.php' ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-gray-100'; ?>"
            >
                <i class="fas fa-history w-5"></i>
                <span class="font-medium">Activity Log</span>
            </a>
        </div>

        <div class="border-t border-gray-200 my-4"></div>

        <!-- Back to Portal -->
        <a 
            href="../index.php" 
            target="_blank"
            class="flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition"
        >
            <i class="fas fa-external-link-alt w-5"></i>
            <span class="font-medium">Citizen Portal</span>
        </a>

        <!-- Logout -->
        <a 
            href="logout.php" 
            class="flex items-center space-x-3 px-4 py-3 rounded-lg text-red-600 hover:bg-red-50 transition"
        >
            <i class="fas fa-sign-out-alt w-5"></i>
            <span class="font-medium">Logout</span>
        </a>
    </nav>
</aside>

<!-- Overlay for mobile -->
<div 
    id="sidebarOverlay"
    class="fixed inset-0 bg-black bg-opacity-50 z-30 lg:hidden hidden"
    onclick="toggleSidebar()"
></div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');
}
</script>