<?php
require_once '../config.php';
require_once 'auth_check.php';

$db = Database::getInstance()->getConnection();

// Get ticket ID
$ticket_id = $_GET['id'] ?? 0;

// Get ticket details
$stmt = $db->prepare("
    SELECT 
        t.*,
        c.full_name as citizen_name,
        c.phone as citizen_phone,
        c.email as citizen_email,
        c.address as citizen_address,
        c.barangay as citizen_barangay
    FROM tickets t
    INNER JOIN citizens c ON t.citizen_id = c.id
    WHERE t.id = ?
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    handleError('Ticket not found', 'tickets.php');
    exit;
}

// Get assigned departments
$dept_stmt = $db->prepare("
    SELECT d.*, td.is_primary
    FROM ticket_departments td
    INNER JOIN departments d ON td.department_id = d.id
    WHERE td.ticket_id = ?
    ORDER BY td.is_primary DESC
");
$dept_stmt->execute([$ticket_id]);
$departments = $dept_stmt->fetchAll();

// Get updates
$updates_stmt = $db->prepare("
    SELECT * FROM ticket_updates
    WHERE ticket_id = ?
    ORDER BY created_at DESC
");
$updates_stmt->execute([$ticket_id]);
$updates = $updates_stmt->fetchAll();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $new_status = sanitize($_POST['status']);
        $update_message = sanitize($_POST['message']);
        
        try {
            $db->beginTransaction();
            
            // Update ticket status
            $update_ticket = $db->prepare("UPDATE tickets SET status = ? WHERE id = ?");
            $update_ticket->execute([$new_status, $ticket_id]);
            
            // Add update log
            $add_log = $db->prepare("
                INSERT INTO ticket_updates (ticket_id, message, status, updated_by)
                VALUES (?, ?, ?, ?)
            ");
            $add_log->execute([$ticket_id, $update_message, $new_status, $admin_name]);
            
            // If resolved, set resolved_at
            if ($new_status === 'resolved') {
                $set_resolved = $db->prepare("UPDATE tickets SET resolved_at = NOW() WHERE id = ?");
                $set_resolved->execute([$ticket_id]);
            }
            
            $db->commit();
            handleSuccess('Ticket status updated successfully', "ticket_details.php?id={$ticket_id}");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            handleError('Failed to update ticket status', "ticket_details.php?id={$ticket_id}");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details - <?php echo $ticket['ticket_number']; ?></title>
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

            <!-- Header -->
            <div class="mb-6">
                <a href="tickets.php" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium mb-2 inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Tickets
                </a>
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">
                            Ticket <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                        </h1>
                        <p class="text-gray-600 mt-1">
                            Submitted <?php echo formatDateTime($ticket['submitted_at']); ?>
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <?php echo getStatusBadge($ticket['status']); ?>
                        <?php echo getPriorityBadge($ticket['priority']); ?>
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Ticket Information -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">
                            <i class="fas fa-info-circle mr-2 text-indigo-600"></i>Ticket Information
                        </h2>

                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Title</label>
                                <p class="text-gray-800 font-medium mt-1">
                                    <?php echo htmlspecialchars($ticket['title']); ?>
                                </p>
                            </div>

                            <div>
                                <label class="text-sm font-medium text-gray-500">Description</label>
                                <p class="text-gray-800 mt-1 whitespace-pre-wrap">
                                    <?php echo htmlspecialchars($ticket['description']); ?>
                                </p>
                            </div>

                            <?php if ($ticket['location']): ?>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Location</label>
                                <p class="text-gray-800 mt-1">
                                    <i class="fas fa-map-marker-alt mr-2 text-red-500"></i>
                                    <?php echo htmlspecialchars($ticket['location']); ?>
                                </p>
                            </div>
                            <?php endif; ?>

                            <div>
                                <label class="text-sm font-medium text-gray-500">Assigned Departments</label>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <?php foreach ($departments as $dept): ?>
                                    <span class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm font-medium">
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                        <?php if ($dept['is_primary']): ?>
                                        <i class="fas fa-star ml-1 text-yellow-500"></i>
                                        <?php endif; ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <?php if ($ticket['ai_suggested_depts']): ?>
                            <?php $ai_suggestions = json_decode($ticket['ai_suggested_depts'], true); ?>
                            <?php if (!empty($ai_suggestions)): ?>
                            <div class="p-3 bg-indigo-50 rounded-lg border border-indigo-200">
                                <label class="text-sm font-medium text-indigo-900">
                                    <i class="fas fa-robot mr-2"></i>AI Suggested Departments
                                </label>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <?php foreach ($ai_suggestions as $suggestion): ?>
                                    <span class="px-2 py-1 bg-white text-indigo-700 rounded text-xs border border-indigo-300">
                                        <?php echo htmlspecialchars($suggestion['name']); ?>
                                        (<?php echo number_format($suggestion['score'], 1); ?>)
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Update Status Form -->
                    <div class="bg-white rounded-xl shadow-md p-6" x-data="{ showForm: false }">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-800">
                                <i class="fas fa-edit mr-2 text-indigo-600"></i>Update Status
                            </h2>
                            <button 
                                @click="showForm = !showForm"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                            >
                                <i class="fas fa-plus mr-2"></i>Add Update
                            </button>
                        </div>

                        <form 
                            method="POST" 
                            action="" 
                            x-show="showForm" 
                            x-cloak
                            class="space-y-4 p-4 bg-gray-50 rounded-lg"
                        >
                            <input type="hidden" name="action" value="update_status">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    New Status <span class="text-red-500">*</span>
                                </label>
                                <select 
                                    name="status" 
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                    <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Update Message <span class="text-red-500">*</span>
                                </label>
                                <textarea 
                                    name="message" 
                                    rows="4" 
                                    required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Describe what action was taken or current status..."
                                ></textarea>
                            </div>

                            <div class="flex space-x-2">
                                <button 
                                    type="submit"
                                    class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                                >
                                    <i class="fas fa-save mr-2"></i>Save Update
                                </button>
                                <button 
                                    type="button"
                                    @click="showForm = false"
                                    class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>

                        <!-- Timeline -->
                        <div class="mt-6 space-y-4">
                            <h3 class="font-semibold text-gray-800">
                                <i class="fas fa-history mr-2"></i>Timeline
                            </h3>
                            
                            <?php foreach ($updates as $index => $update): ?>
                            <div class="flex items-start space-x-3 <?php echo $index < count($updates) - 1 ? 'pb-4 border-l-2 border-gray-200 ml-3' : ''; ?>">
                                <div class="flex-shrink-0 -ml-4">
                                    <?php if ($update['status'] === 'resolved'): ?>
                                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-check text-green-600"></i>
                                    </div>
                                    <?php elseif ($update['status'] === 'in_progress'): ?>
                                    <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-spinner text-yellow-600"></i>
                                    </div>
                                    <?php else: ?>
                                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-info text-blue-600"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 ml-2">
                                    <div class="bg-gray-50 rounded-lg p-3">
                                        <p class="text-sm text-gray-800"><?php echo htmlspecialchars($update['message']); ?></p>
                                        <div class="flex items-center justify-between mt-2 text-xs text-gray-500">
                                            <span>
                                                <?php if ($update['updated_by']): ?>
                                                <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($update['updated_by']); ?>
                                                <?php endif; ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-clock mr-1"></i>
                                                <?php echo formatDateTime($update['created_at']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Citizen Information -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">
                            <i class="fas fa-user mr-2 text-indigo-600"></i>Citizen Information
                        </h2>
                        <div class="space-y-3">
                            <div>
                                <label class="text-xs font-medium text-gray-500">Full Name</label>
                                <p class="text-sm text-gray-800 font-medium">
                                    <?php echo htmlspecialchars($ticket['citizen_name']); ?>
                                </p>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Phone Number</label>
                                <p class="text-sm text-gray-800">
                                    <i class="fas fa-phone mr-2 text-green-500"></i>
                                    <a href="tel:<?php echo htmlspecialchars($ticket['citizen_phone']); ?>" 
                                       class="text-indigo-600 hover:text-indigo-700">
                                        <?php echo htmlspecialchars($ticket['citizen_phone']); ?>
                                    </a>
                                </p>
                            </div>
                            <?php if ($ticket['citizen_email']): ?>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Email</label>
                                <p class="text-sm text-gray-800">
                                    <i class="fas fa-envelope mr-2 text-blue-500"></i>
                                    <a href="mailto:<?php echo htmlspecialchars($ticket['citizen_email']); ?>" 
                                       class="text-indigo-600 hover:text-indigo-700">
                                        <?php echo htmlspecialchars($ticket['citizen_email']); ?>
                                    </a>
                                </p>
                            </div>
                            <?php endif; ?>
                            <?php if ($ticket['citizen_address']): ?>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Address</label>
                                <p class="text-sm text-gray-800">
                                    <?php echo htmlspecialchars($ticket['citizen_address']); ?>
                                </p>
                            </div>
                            <?php endif; ?>
                            <?php if ($ticket['citizen_barangay']): ?>
                            <div>
                                <label class="text-xs font-medium text-gray-500">Barangay</label>
                                <p class="text-sm text-gray-800">
                                    <?php echo htmlspecialchars($ticket['citizen_barangay']); ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-4">
                            <i class="fas fa-bolt mr-2 text-indigo-600"></i>Quick Actions
                        </h2>
                        <div class="space-y-2">
                            <button 
                                onclick="window.print()"
                                class="w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition text-left text-sm"
                            >
                                <i class="fas fa-print mr-2 text-gray-600"></i>Print Ticket
                            </button>
                            <a 
                                href="mailto:<?php echo $ticket['citizen_email'] ?: ''; ?>" 
                                class="block w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition text-left text-sm"
                            >
                                <i class="fas fa-envelope mr-2 text-gray-600"></i>Email Citizen
                            </a>
                            <a 
                                href="tel:<?php echo $ticket['citizen_phone']; ?>" 
                                class="block w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition text-left text-sm"
                            >
                                <i class="fas fa-phone mr-2 text-gray-600"></i>Call Citizen
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>