<?php
require_once '../config.php';
require_once 'auth_check.php';

$db = Database::getInstance()->getConnection();

// Get filters
$status_filter = $_GET['status'] ?? 'all';
$priority_filter = $_GET['priority'] ?? 'all';
$department_filter = $_GET['department'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
}

if ($priority_filter !== 'all') {
    $where_conditions[] = "t.priority = ?";
    $params[] = $priority_filter;
}

if ($department_filter !== 'all') {
    $where_conditions[] = "t.id IN (SELECT ticket_id FROM ticket_departments WHERE department_id = ?)";
    $params[] = $department_filter;
} elseif ($admin_role !== 'admin' && $admin_department) {
    $where_conditions[] = "t.id IN (SELECT ticket_id FROM ticket_departments WHERE department_id = ?)";
    $params[] = $admin_department;
}

if ($search) {
    $where_conditions[] = "(t.ticket_number LIKE ? OR t.title LIKE ? OR t.description LIKE ? OR c.full_name LIKE ?)";
    $search_term = "%{$search}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get tickets
$sql = "
    SELECT 
        t.*,
        c.full_name as citizen_name,
        c.phone as citizen_phone,
        c.email as citizen_email,
        GROUP_CONCAT(DISTINCT d.name SEPARATOR ', ') as departments,
        (SELECT COUNT(*) FROM ticket_updates WHERE ticket_id = t.id) as update_count
    FROM tickets t
    INNER JOIN citizens c ON t.citizen_id = c.id
    LEFT JOIN ticket_departments td ON t.id = td.ticket_id
    LEFT JOIN departments d ON td.department_id = d.id
    {$where_sql}
    GROUP BY t.id
    ORDER BY 
        FIELD(t.priority, 'urgent', 'high', 'normal', 'low'),
        FIELD(t.status, 'open', 'in_progress', 'resolved', 'closed'),
        t.submitted_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Get departments for filter
$departments = $db->query("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">
    <?php include 'includes/admin_header.php'; ?>

    <div class="flex">
        <?php include 'includes/admin_sidebar.php'; ?>

        <main class="flex-1 p-6">
            <?php echo getFlashMessage(); ?>

            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-ticket-alt mr-2 text-indigo-600"></i>Ticket Management
                </h1>
                <p class="text-gray-600 mt-1">View and manage all citizen tickets</p>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <form method="GET" action="" class="grid md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                        <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="all" <?php echo $priority_filter === 'all' ? 'selected' : ''; ?>>All Priority</option>
                            <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                            <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="normal" <?php echo $priority_filter === 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>Low</option>
                        </select>
                    </div>

                    <?php if ($admin_role === 'admin'): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                        <select name="department" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="all">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo $department_filter == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="<?php echo $admin_role === 'admin' ? '' : 'md:col-span-2'; ?>">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input 
                            type="text" 
                            name="search" 
                            value="<?php echo htmlspecialchars($search); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                            placeholder="Ticket #, title, citizen..."
                        >
                    </div>

                    <div class="flex items-end space-x-2">
                        <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                        <a href="tickets.php" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Tickets List -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-list mr-2"></i>
                            Tickets (<?php echo count($tickets); ?>)
                        </h2>
                        <button onclick="window.print()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">
                            <i class="fas fa-print mr-2"></i>Print Report
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticket</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Citizen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Departments</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2"></i>
                                    <p>No tickets found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm font-bold text-indigo-600">
                                        <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                    </span>
                                    <?php if ($ticket['update_count'] > 1): ?>
                                    <span class="ml-2 text-xs text-gray-500">
                                        <i class="fas fa-comment"></i> <?php echo $ticket['update_count']; ?>
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($ticket['citizen_name']); ?></p>
                                        <p class="text-gray-500"><?php echo htmlspecialchars($ticket['citizen_phone']); ?></p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="max-w-xs">
                                        <p class="text-sm font-medium text-gray-800 truncate">
                                            <?php echo htmlspecialchars($ticket['title']); ?>
                                        </p>
                                        <?php if ($ticket['location']): ?>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <i class="fas fa-map-marker-alt mr-1"></i>
                                            <?php echo htmlspecialchars($ticket['location']); ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-gray-600 max-w-xs truncate">
                                        <?php echo htmlspecialchars($ticket['departments'] ?: 'N/A'); ?>
                                    </p>
                                </td>
                                <td class="px-6 py-4"><?php echo getStatusBadge($ticket['status']); ?></td>
                                <td class="px-6 py-4"><?php echo getPriorityBadge($ticket['priority']); ?></td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <?php echo date('M d, Y', strtotime($ticket['submitted_at'])); ?>
                                    <br>
                                    <span class="text-xs text-gray-500">
                                        <?php echo date('h:i A', strtotime($ticket['submitted_at'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="ticket_details.php?id=<?php echo $ticket['id']; ?>" 
                                       class="inline-flex items-center px-3 py-1 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>