<?php
require_once '../config.php';
require_once 'auth_check.php';

$db = Database::getInstance()->getConnection();

// Get date range from filters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Date filter condition
$date_condition = "DATE(t.submitted_at) BETWEEN ? AND ?";
$date_params = [$start_date, $end_date];

// Department filter for non-admin users
$dept_condition = '';
if ($admin_role !== 'admin' && $admin_department) {
    $dept_condition = " AND t.id IN (SELECT ticket_id FROM ticket_departments WHERE department_id = {$admin_department})";
}

// Overall Statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tickets,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tickets,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_tickets,
        SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_tickets,
        SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_tickets,
        AVG(CASE 
            WHEN resolved_at IS NOT NULL 
            THEN TIMESTAMPDIFF(HOUR, submitted_at, resolved_at) 
            ELSE NULL 
        END) as avg_resolution_hours
    FROM tickets t
    WHERE {$date_condition} {$dept_condition}
";
$stmt = $db->prepare($stats_query);
$stmt->execute($date_params);
$stats = $stmt->fetch();

// Tickets by Department
$dept_query = "
    SELECT 
        d.name,
        COUNT(td.ticket_id) as total,
        SUM(CASE WHEN t.status = 'open' THEN 1 ELSE 0 END) as open,
        SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN t.status = 'resolved' THEN 1 ELSE 0 END) as resolved
    FROM departments d
    LEFT JOIN ticket_departments td ON d.id = td.department_id
    LEFT JOIN tickets t ON td.ticket_id = t.id AND {$date_condition}
    WHERE d.is_active = 1 {$dept_condition}
    GROUP BY d.id, d.name
    ORDER BY total DESC
";
$stmt = $db->prepare($dept_query);
$stmt->execute($date_params);
$dept_stats = $stmt->fetchAll();

// Tickets by Priority
$priority_query = "
    SELECT 
        priority,
        COUNT(*) as count
    FROM tickets t
    WHERE {$date_condition} {$dept_condition}
    GROUP BY priority
    ORDER BY FIELD(priority, 'urgent', 'high', 'normal', 'low')
";
$stmt = $db->prepare($priority_query);
$stmt->execute($date_params);
$priority_stats = $stmt->fetchAll();

// Daily ticket trend (last 30 days)
$trend_query = "
    SELECT 
        DATE(submitted_at) as date,
        COUNT(*) as count
    FROM tickets t
    WHERE DATE(submitted_at) BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE() 
    {$dept_condition}
    GROUP BY DATE(submitted_at)
    ORDER BY date
";
$trend_data = $db->query($trend_query)->fetchAll();

// Top concerns by location
$location_query = "
    SELECT 
        location,
        COUNT(*) as count
    FROM tickets t
    WHERE location IS NOT NULL 
    AND location != '' 
    AND {$date_condition} 
    {$dept_condition}
    GROUP BY location
    ORDER BY count DESC
    LIMIT 10
";
$stmt = $db->prepare($location_query);
$stmt->execute($date_params);
$location_stats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/admin_header.php'; ?>

    <div class="flex">
        <?php include 'includes/admin_sidebar.php'; ?>

        <main class="flex-1 p-6">
            <?php echo getFlashMessage(); ?>

            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-chart-bar mr-2 text-indigo-600"></i>Reports & Analytics
                </h1>
                <p class="text-gray-600 mt-1">Comprehensive insights and statistics</p>
            </div>

            <!-- Date Range Filter -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <form method="GET" action="" class="flex items-end space-x-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                        <input 
                            type="date" 
                            name="start_date" 
                            value="<?php echo $start_date; ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        >
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                        <input 
                            type="date" 
                            name="end_date" 
                            value="<?php echo $end_date; ?>"
                            max="<?php echo date('Y-m-d'); ?>"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        >
                    </div>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        <i class="fas fa-filter mr-2"></i>Apply Filter
                    </button>
                    <button type="button" onclick="window.print()" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                </form>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-md p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm opacity-90">Total Tickets</p>
                        <i class="fas fa-ticket-alt text-2xl opacity-75"></i>
                    </div>
                    <p class="text-4xl font-bold"><?php echo number_format($stats['total_tickets']); ?></p>
                    <p class="text-xs mt-2 opacity-75">
                        <?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?>
                    </p>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-md p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm opacity-90">Resolved</p>
                        <i class="fas fa-check-circle text-2xl opacity-75"></i>
                    </div>
                    <p class="text-4xl font-bold"><?php echo number_format($stats['resolved_tickets']); ?></p>
                    <p class="text-xs mt-2 opacity-75">
                        <?php 
                        $resolution_rate = $stats['total_tickets'] > 0 
                            ? round(($stats['resolved_tickets'] / $stats['total_tickets']) * 100, 1) 
                            : 0;
                        echo "{$resolution_rate}% resolution rate";
                        ?>
                    </p>
                </div>

                <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-md p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm opacity-90">Avg Resolution Time</p>
                        <i class="fas fa-clock text-2xl opacity-75"></i>
                    </div>
                    <p class="text-4xl font-bold">
                        <?php echo $stats['avg_resolution_hours'] ? round($stats['avg_resolution_hours']) : '0'; ?>
                    </p>
                    <p class="text-xs mt-2 opacity-75">hours average</p>
                </div>

                <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-md p-6 text-white">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm opacity-90">Priority Tickets</p>
                        <i class="fas fa-exclamation-triangle text-2xl opacity-75"></i>
                    </div>
                    <p class="text-4xl font-bold"><?php echo number_format($stats['urgent_tickets'] + $stats['high_tickets']); ?></p>
                    <p class="text-xs mt-2 opacity-75">
                        <?php echo number_format($stats['urgent_tickets']); ?> urgent, 
                        <?php echo number_format($stats['high_tickets']); ?> high
                    </p>
                </div>
            </div>

            <div class="grid lg:grid-cols-2 gap-6 mb-6">
                <!-- Department Performance -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-building mr-2 text-indigo-600"></i>Department Performance
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b border-gray-200">
                                <tr class="text-left">
                                    <th class="pb-3 font-semibold">Department</th>
                                    <th class="pb-3 font-semibold text-center">Total</th>
                                    <th class="pb-3 font-semibold text-center">Open</th>
                                    <th class="pb-3 font-semibold text-center">In Progress</th>
                                    <th class="pb-3 font-semibold text-center">Resolved</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dept_stats as $dept): ?>
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 font-medium"><?php echo htmlspecialchars($dept['name']); ?></td>
                                    <td class="py-3 text-center font-bold text-indigo-600"><?php echo $dept['total']; ?></td>
                                    <td class="py-3 text-center"><?php echo $dept['open']; ?></td>
                                    <td class="py-3 text-center"><?php echo $dept['in_progress']; ?></td>
                                    <td class="py-3 text-center text-green-600 font-medium"><?php echo $dept['resolved']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Priority Distribution -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-chart-pie mr-2 text-indigo-600"></i>Priority Distribution
                    </h2>
                    <canvas id="priorityChart" height="200"></canvas>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Daily Trend -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-chart-line mr-2 text-indigo-600"></i>Ticket Trend (Last 30 Days)
                    </h2>
                    <canvas id="trendChart" height="100"></canvas>
                </div>

                <!-- Top Locations -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-map-marker-alt mr-2 text-indigo-600"></i>Top Locations
                    </h2>
                    <div class="space-y-3">
                        <?php foreach ($location_stats as $loc): ?>
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm text-gray-700 truncate flex-1">
                                <?php echo htmlspecialchars($loc['location']); ?>
                            </span>
                            <span class="ml-3 px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-bold">
                                <?php echo $loc['count']; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Priority Chart
        const priorityCtx = document.getElementById('priorityChart').getContext('2d');
        new Chart(priorityCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($priority_stats, 'priority')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($priority_stats, 'count')); ?>,
                    backgroundColor: [
                        '#EF4444', // urgent - red
                        '#F59E0B', // high - orange
                        '#3B82F6', // normal - blue
                        '#6B7280'  // low - gray
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($trend_data, 'date')); ?>,
                datasets: [{
                    label: 'Tickets',
                    data: <?php echo json_encode(array_column($trend_data, 'count')); ?>,
                    borderColor: '#6366F1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
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