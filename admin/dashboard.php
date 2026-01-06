<?php
require_once '../config.php';
require_once 'auth_check.php';

$db = Database::getInstance()->getConnection();

// Department filter
$dept_filter = getDepartmentFilter();

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tickets,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tickets,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_tickets,
        SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_tickets,
        SUM(CASE WHEN DATE(submitted_at) = CURDATE() THEN 1 ELSE 0 END) as today_tickets,
        SUM(CASE WHEN DATE(submitted_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_tickets
    FROM tickets t
    WHERE 1=1 {$dept_filter}
";

$stats = $db->query($stats_query)->fetch();

// Get recent tickets
$recent_query = "
    SELECT 
        t.*,
        c.full_name as citizen_name,
        c.phone as citizen_phone,
        GROUP_CONCAT(d.name SEPARATOR ', ') as departments
    FROM tickets t
    INNER JOIN citizens c ON t.citizen_id = c.id
    LEFT JOIN ticket_departments td ON t.id = td.ticket_id
    LEFT JOIN departments d ON td.department_id = d.id
    WHERE 1=1 {$dept_filter}
    GROUP BY t.id 
    ORDER BY t.submitted_at DESC 
    LIMIT 10
";

$recent_tickets = $db->query($recent_query)->fetchAll();

// Get department statistics
$dept_stats_query = "
    SELECT 
        d.name as department_name,
        COUNT(td.ticket_id) as ticket_count,
        SUM(CASE WHEN t.status = 'open' THEN 1 ELSE 0 END) as open_count,
        SUM(CASE WHEN t.status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
    FROM departments d
    LEFT JOIN ticket_departments td ON d.id = td.department_id
    LEFT JOIN tickets t ON td.ticket_id = t.id
    WHERE d.is_active = 1
";

if ($admin_role !== 'admin' && $admin_department) {
    $dept_stats_query .= " AND d.id = {$admin_department}";
}

$dept_stats_query .= " GROUP BY d.id, d.name ORDER BY ticket_count DESC LIMIT 8";
$dept_stats = $db->query($dept_stats_query)->fetchAll();

// Get ticket trend for chart (last 7 days)
$trend_query = "
    SELECT 
        DATE(submitted_at) as date,
        COUNT(*) as count
    FROM tickets t
    WHERE DATE(submitted_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    {$dept_filter}
    GROUP BY DATE(submitted_at)
    ORDER BY date
";
$trend_data = $db->query($trend_query)->fetchAll();

// Priority breakdown
$priority_query = "
    SELECT 
        priority,
        COUNT(*) as count
    FROM tickets t
    WHERE 1=1 {$dept_filter}
    GROUP BY priority
";
$priority_data = $db->query($priority_query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <meta http-equiv="refresh" content="60"> <!-- Auto-refresh every 60 seconds -->
</head>
<body class="bg-gray-100">
    <?php include 'includes/admin_header.php'; ?>

    <div class="flex">
        <?php include 'includes/admin_sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 p-6">
            <?php echo getFlashMessage(); ?>

            <!-- Welcome Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-chart-line mr-2 text-indigo-600"></i>Dashboard
                </h1>
                <p class="text-gray-600 mt-1">
                    Welcome back, <?php echo htmlspecialchars($admin_name); ?>! 
                    <?php if ($admin_department_name): ?>
                    <span class="text-sm">
                        (<i class="fas fa-building mr-1"></i><?php echo htmlspecialchars($admin_department_name); ?>)
                    </span>
                    <?php endif; ?>
                </p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Total Tickets -->
                <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Tickets</p>
                            <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo number_format($stats['total_tickets']); ?></p>
                        </div>
                        <div class="bg-indigo-100 rounded-full p-4">
                            <i class="fas fa-ticket-alt text-indigo-600 text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm">
                        <span class="text-green-600 font-medium">
                            <i class="fas fa-calendar mr-1"></i>
                            <?php echo number_format($stats['today_tickets']); ?> today
                        </span>
                        <span class="text-gray-400 mx-2">|</span>
                        <span class="text-gray-600">
                            <?php echo number_format($stats['week_tickets']); ?> this week
                        </span>
                    </div>
                </div>

                <!-- Open Tickets -->
                <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Open Tickets</p>
                            <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo number_format($stats['open_tickets']); ?></p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-4">
                            <i class="fas fa-folder-open text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <?php if ($stats['urgent_tickets'] > 0): ?>
                        <div class="flex items-center text-sm">
                            <span class="text-red-600 font-medium">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                <?php echo number_format($stats['urgent_tickets']); ?> urgent
                            </span>
                        </div>
                        <?php else: ?>
                        <p class="text-sm text-gray-500">No urgent tickets</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- In Progress -->
                <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">In Progress</p>
                            <p class="text-3xl font-bold text-yellow-600 mt-2"><?php echo number_format($stats['in_progress_tickets']); ?></p>
                        </div>
                        <div class="bg-yellow-100 rounded-full p-4">
                            <i class="fas fa-spinner text-yellow-600 text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <?php 
                        $active_tickets = $stats['open_tickets'] + $stats['in_progress_tickets'];
                        ?>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-tasks mr-1"></i>
                            <?php echo number_format($active_tickets); ?> active tickets
                        </p>
                    </div>
                </div>

                <!-- Resolved -->
                <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Resolved</p>
                            <p class="text-3xl font-bold text-green-600 mt-2"><?php echo number_format($stats['resolved_tickets']); ?></p>
                        </div>
                        <div class="bg-green-100 rounded-full p-4">
                            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <?php 
                        $resolution_rate = $stats['total_tickets'] > 0 
                            ? round(($stats['resolved_tickets'] / $stats['total_tickets']) * 100, 1) 
                            : 0;
                        ?>
                        <p class="text-sm text-gray-600">
                            <i class="fas fa-percentage mr-1"></i>
                            <?php echo $resolution_rate; ?>% resolution rate
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Recent Tickets -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-history mr-2 text-indigo-600"></i>Recent Tickets
                        </h2>
                        <a href="tickets.php" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                            View All <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-200 text-left text-sm">
                                    <th class="pb-3 font-semibold text-gray-700">Ticket #</th>
                                    <th class="pb-3 font-semibold text-gray-700">Title</th>
                                    <th class="pb-3 font-semibold text-gray-700">Status</th>
                                    <th class="pb-3 font-semibold text-gray-700">Priority</th>
                                    <th class="pb-3 font-semibold text-gray-700">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_tickets)): ?>
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-500">
                                        <i class="fas fa-inbox text-4xl mb-2"></i>
                                        <p>No tickets yet</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($recent_tickets as $ticket): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3">
                                        <a href="ticket_details.php?id=<?php echo $ticket['id']; ?>" 
                                           class="text-indigo-600 hover:text-indigo-700 font-mono text-sm font-medium">
                                            <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                        </a>
                                    </td>
                                    <td class="py-3">
                                        <div class="max-w-xs">
                                            <p class="text-sm font-medium text-gray-800 truncate">
                                                <?php echo htmlspecialchars($ticket['title']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500 truncate">
                                                <?php echo htmlspecialchars($ticket['citizen_name']); ?>
                                            </p>
                                        </div>
                                    </td>
                                    <td class="py-3"><?php echo getStatusBadge($ticket['status']); ?></td>
                                    <td class="py-3"><?php echo getPriorityBadge($ticket['priority']); ?></td>
                                    <td class="py-3 text-sm text-gray-600">
                                        <?php echo date('M d, Y', strtotime($ticket['submitted_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Department Statistics & Charts -->
                <div class="space-y-6">
                    <!-- Department Stats -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-building mr-2 text-indigo-600"></i>Departments
                        </h2>
                        <div class="space-y-3">
                            <?php foreach ($dept_stats as $dept): ?>
                            <div class="border-b border-gray-100 pb-3 last:border-0">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-sm font-medium text-gray-800 truncate">
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </p>
                                    <span class="text-sm font-bold text-indigo-600">
                                        <?php echo number_format($dept['ticket_count']); ?>
                                    </span>
                                </div>
                                <div class="flex items-center space-x-4 text-xs text-gray-500">
                                    <span>
                                        <i class="fas fa-folder-open text-blue-500 mr-1"></i>
                                        <?php echo $dept['open_count']; ?> open
                                    </span>
                                    <span>
                                        <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                        <?php echo $dept['resolved_count']; ?> resolved
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-md p-6 text-white">
                        <h3 class="text-lg font-bold mb-4">
                            <i class="fas fa-chart-pie mr-2"></i>Quick Stats
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-sm opacity-90">Active Tickets</span>
                                <span class="font-bold text-xl">
                                    <?php echo number_format($stats['open_tickets'] + $stats['in_progress_tickets']); ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm opacity-90">Closed Tickets</span>
                                <span class="font-bold text-xl">
                                    <?php echo number_format($stats['closed_tickets']); ?>
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm opacity-90">Resolution Rate</span>
                                <span class="font-bold text-xl"><?php echo $resolution_rate; ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ticket Trend Chart -->
            <div class="mt-6 bg-white rounded-xl shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-chart-line mr-2 text-indigo-600"></i>7-Day Ticket Trend
                </h2>
                <canvas id="trendChart" height="80"></canvas>
            </div>

            <!-- Auto-refresh indicator -->
            <div class="mt-6 text-center text-sm text-gray-500">
                <i class="fas fa-sync-alt mr-2"></i>Dashboard auto-refreshes every 60 seconds
                <span class="mx-2">|</span>
                <span>Last updated: <?php echo date('h:i A'); ?></span>
            </div>
        </main>
    </div>

    <script>
        // Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($d) { 
                    return date('M d', strtotime($d['date'])); 
                }, $trend_data)); ?>,
                datasets: [{
                    label: 'Tickets Submitted',
                    data: <?php echo json_encode(array_column($trend_data, 'count')); ?>,
                    borderColor: '#6366F1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>