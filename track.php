<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();

// Get search parameters
$search_ticket = $_GET['ticket'] ?? '';
$search_phone = $_GET['phone'] ?? '';
$search_query = $_GET['q'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($search_ticket) {
    $where_conditions[] = "t.ticket_number = ?";
    $params[] = $search_ticket;
}

if ($search_phone) {
    $where_conditions[] = "c.phone = ?";
    $params[] = $search_phone;
}

if ($search_query) {
    $where_conditions[] = "(t.ticket_number LIKE ? OR t.title LIKE ? OR t.description LIKE ?)";
    $search_term = "%{$search_query}%";
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
        GROUP_CONCAT(d.name SEPARATOR ', ') as department_names
    FROM tickets t
    INNER JOIN citizens c ON t.citizen_id = c.id
    LEFT JOIN ticket_departments td ON t.id = td.ticket_id
    LEFT JOIN departments d ON td.department_id = d.id
    {$where_sql}
    GROUP BY t.id
    ORDER BY t.submitted_at DESC
    LIMIT 50
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Tickets - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    
    <?php include 'includes/header.php'; ?>

    <main class="max-w-6xl mx-auto px-4 py-8">
        <?php echo getFlashMessage(); ?>
        
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 md:mb-0">
                    <i class="fas fa-search mr-2 text-indigo-600"></i>
                    Track Your Tickets
                </h2>
            </div>

            <!-- Search Form -->
            <form method="GET" action="track.php" class="grid md:grid-cols-3 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ticket Number</label>
                    <input 
                        type="text" 
                        name="ticket"
                        value="<?php echo htmlspecialchars($search_ticket); ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        placeholder="TKT-XXXXXX"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                    <input 
                        type="tel" 
                        name="phone"
                        value="<?php echo htmlspecialchars($search_phone); ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        placeholder="09XXXXXXXXX"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search Keywords</label>
                    <input 
                        type="text" 
                        name="q"
                        value="<?php echo htmlspecialchars($search_query); ?>"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                        placeholder="Search by title or description"
                    >
                </div>
                <div class="md:col-span-3 flex space-x-2">
                    <button 
                        type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                    >
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <a 
                        href="track.php"
                        class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
                    >
                        <i class="fas fa-undo mr-2"></i>Clear
                    </a>
                </div>
            </form>

            <!-- Tickets List -->
            <?php if (empty($tickets)): ?>
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500">
                        <?php if ($search_ticket || $search_phone || $search_query): ?>
                            No tickets found matching your search criteria.
                        <?php else: ?>
                            No tickets found. Submit your first concern to get started!
                        <?php endif; ?>
                    </p>
                    <a href="submit.php" class="inline-block mt-4 px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Submit a Concern
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($tickets as $ticket): ?>
                    <div class="border border-gray-200 rounded-lg overflow-hidden" x-data="{ expanded: <?php echo ($search_ticket === $ticket['ticket_number']) ? 'true' : 'false'; ?> }">
                        <!-- Ticket Header -->
                        <div class="p-4 bg-gray-50">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2 flex-wrap">
                                        <span class="font-mono text-sm font-bold text-indigo-600">
                                            <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                        </span>
                                        <?php echo getStatusBadge($ticket['status']); ?>
                                        <?php echo getPriorityBadge($ticket['priority']); ?>
                                    </div>
                                    <h3 class="font-semibold text-gray-800 mb-1">
                                        <?php echo htmlspecialchars($ticket['title']); ?>
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-building mr-1"></i>
                                        Departments: <?php echo htmlspecialchars($ticket['department_names'] ?: 'N/A'); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <i class="fas fa-clock mr-1"></i>
                                        Submitted: <?php echo formatDateTime($ticket['submitted_at']); ?>
                                    </p>
                                </div>
                                <button 
                                    @click="expanded = !expanded"
                                    class="ml-4 p-2 hover:bg-gray-200 rounded transition"
                                >
                                    <i class="fas" :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Ticket Details -->
                        <div x-show="expanded" x-cloak class="p-4 bg-white border-t border-gray-200">
                            <!-- Description -->
                            <div class="mb-4">
                                <h4 class="font-semibold text-gray-800 mb-2">
                                    <i class="fas fa-file-alt mr-2 text-indigo-600"></i>Description:
                                </h4>
                                <p class="text-gray-600 text-sm whitespace-pre-wrap">
                                    <?php echo htmlspecialchars($ticket['description']); ?>
                                </p>
                            </div>

                            <?php if ($ticket['location']): ?>
                            <div class="mb-4">
                                <h4 class="font-semibold text-gray-800 mb-2">
                                    <i class="fas fa-map-marker-alt mr-2 text-indigo-600"></i>Location:
                                </h4>
                                <p class="text-gray-600 text-sm">
                                    <?php echo htmlspecialchars($ticket['location']); ?>
                                </p>
                            </div>
                            <?php endif; ?>

                            <!-- Citizen Info -->
                            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                                <h4 class="font-semibold text-gray-800 mb-2">
                                    <i class="fas fa-user mr-2 text-indigo-600"></i>Contact Information:
                                </h4>
                                <p class="text-sm text-gray-600">
                                    <strong>Name:</strong> <?php echo htmlspecialchars($ticket['citizen_name']); ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <strong>Phone:</strong> <?php echo htmlspecialchars($ticket['citizen_phone']); ?>
                                </p>
                                <?php if ($ticket['citizen_email']): ?>
                                <p class="text-sm text-gray-600">
                                    <strong>Email:</strong> <?php echo htmlspecialchars($ticket['citizen_email']); ?>
                                </p>
                                <?php endif; ?>
                            </div>

                            <!-- Status Updates Timeline -->
                            <?php
                            $updates_stmt = $db->prepare("
                                SELECT * FROM ticket_updates 
                                WHERE ticket_id = ? 
                                ORDER BY created_at DESC
                            ");
                            $updates_stmt->execute([$ticket['id']]);
                            $updates = $updates_stmt->fetchAll();
                            ?>
                            <div class="mb-4">
                                <h4 class="font-semibold text-gray-800 mb-3">
                                    <i class="fas fa-history mr-2 text-indigo-600"></i>Status Updates:
                                </h4>
                                <div class="space-y-3">
                                    <?php foreach ($updates as $update): ?>
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0 mt-1">
                                            <?php if ($update['status'] === 'resolved'): ?>
                                                <i class="fas fa-check-circle text-green-500 text-lg"></i>
                                            <?php elseif ($update['status'] === 'in_progress'): ?>
                                                <i class="fas fa-clock text-yellow-500 text-lg"></i>
                                            <?php else: ?>
                                                <i class="fas fa-info-circle text-blue-500 text-lg"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-sm text-gray-800">
                                                <?php echo htmlspecialchars($update['message']); ?>
                                            </p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <?php echo formatDateTime($update['created_at']); ?>
                                                <?php if ($update['updated_by']): ?>
                                                    - by <?php echo htmlspecialchars($update['updated_by']); ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex justify-end space-x-2 pt-4 border-t border-gray-200">
                                <button 
                                    onclick="window.print()"
                                    class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
                                >
                                    <i class="fas fa-print mr-2"></i>Print
                                </button>
                                <a 
                                    href="mailto:<?php echo CONTACT_EMAIL; ?>?subject=Ticket <?php echo $ticket['ticket_number']; ?>"
                                    class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
                                >
                                    <i class="fas fa-envelope mr-2"></i>Email LGU
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>