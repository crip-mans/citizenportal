<?php
require_once '../config.php';
require_once 'auth_check.php';

$db = Database::getInstance()->getConnection();

// Get tickets with location data
$query = "
    SELECT 
        t.*,
        c.full_name as citizen_name,
        c.phone as citizen_phone,
        GROUP_CONCAT(d.name SEPARATOR ', ') as departments
    FROM tickets t
    INNER JOIN citizens c ON t.citizen_id = c.id
    LEFT JOIN ticket_departments td ON t.id = td.ticket_id
    LEFT JOIN departments d ON td.department_id = d.id
    WHERE t.location IS NOT NULL AND t.location != ''
";

if ($admin_role !== 'admin' && $admin_department) {
    $query .= " AND t.id IN (SELECT ticket_id FROM ticket_departments WHERE department_id = {$admin_department})";
}

$query .= " GROUP BY t.id ORDER BY t.submitted_at DESC LIMIT 100";

$tickets = $db->query($query)->fetchAll();

// Group tickets by location
$location_groups = [];
foreach ($tickets as $ticket) {
    $location = $ticket['location'];
    if (!isset($location_groups[$location])) {
        $location_groups[$location] = [];
    }
    $location_groups[$location][] = $ticket;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map View - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        #map {
            height: calc(100vh - 200px);
            min-height: 500px;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'includes/admin_header.php'; ?>

    <div class="flex">
        <?php include 'includes/admin_sidebar.php'; ?>

        <main class="flex-1 p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-map-marked-alt mr-2 text-indigo-600"></i>Real-Time Map View
                </h1>
                <p class="text-gray-600 mt-1">Geographical distribution of citizen concerns</p>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <p class="text-2xl font-bold text-indigo-600"><?php echo count($tickets); ?></p>
                    <p class="text-xs text-gray-600 mt-1">Total Locations</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600">
                        <?php echo count(array_filter($tickets, fn($t) => $t['status'] === 'open')); ?>
                    </p>
                    <p class="text-xs text-gray-600 mt-1">Open</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <p class="text-2xl font-bold text-yellow-600">
                        <?php echo count(array_filter($tickets, fn($t) => $t['status'] === 'in_progress')); ?>
                    </p>
                    <p class="text-xs text-gray-600 mt-1">In Progress</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">
                        <?php echo count(array_filter($tickets, fn($t) => $t['status'] === 'resolved')); ?>
                    </p>
                    <p class="text-xs text-gray-600 mt-1">Resolved</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <p class="text-2xl font-bold text-red-600">
                        <?php echo count(array_filter($tickets, fn($t) => $t['priority'] === 'urgent')); ?>
                    </p>
                    <p class="text-xs text-gray-600 mt-1">Urgent</p>
                </div>
            </div>

            <!-- Map and List -->
            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Location List -->
                <div class="bg-white rounded-xl shadow-md p-6 overflow-y-auto" style="max-height: calc(100vh - 300px);">
                    <h2 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-list mr-2"></i>Locations (<?php echo count($location_groups); ?>)
                    </h2>
                    
                    <div class="space-y-3">
                        <?php foreach ($location_groups as $location => $location_tickets): ?>
                        <div class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 cursor-pointer" 
                             onclick="showLocationDetails('<?php echo htmlspecialchars($location); ?>')">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-800 text-sm">
                                        <i class="fas fa-map-marker-alt mr-2 text-red-500"></i>
                                        <?php echo htmlspecialchars($location); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <?php echo count($location_tickets); ?> ticket(s)
                                    </p>
                                </div>
                                <div class="flex items-center space-x-1">
                                    <?php
                                    $status_counts = [
                                        'open' => 0,
                                        'in_progress' => 0,
                                        'resolved' => 0
                                    ];
                                    foreach ($location_tickets as $lt) {
                                        if (isset($status_counts[$lt['status']])) {
                                            $status_counts[$lt['status']]++;
                                        }
                                    }
                                    ?>
                                    <?php if ($status_counts['open'] > 0): ?>
                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs">
                                        <?php echo $status_counts['open']; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($status_counts['in_progress'] > 0): ?>
                                    <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded text-xs">
                                        <?php echo $status_counts['in_progress']; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($status_counts['resolved'] > 0): ?>
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">
                                        <?php echo $status_counts['resolved']; ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Map Visualization (Placeholder) -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-800">
                            <i class="fas fa-map mr-2"></i>Geographic Distribution
                        </h2>
                        <div class="flex items-center space-x-2">
                            <button class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded">
                                <i class="fas fa-circle mr-1"></i>Open
                            </button>
                            <button class="px-3 py-1 text-xs bg-yellow-100 text-yellow-700 rounded">
                                <i class="fas fa-circle mr-1"></i>In Progress
                            </button>
                            <button class="px-3 py-1 text-xs bg-green-100 text-green-700 rounded">
                                <i class="fas fa-circle mr-1"></i>Resolved
                            </button>
                        </div>
                    </div>

                    <!-- Map Container -->
                    <div id="map" class="bg-gray-100 rounded-lg border-2 border-gray-300 flex items-center justify-center">
                        <div class="text-center p-8">
                            <i class="fas fa-map-marked-alt text-6xl text-gray-300 mb-4"></i>
                            <p class="text-gray-600 font-medium">Interactive Map View</p>
                            <p class="text-sm text-gray-500 mt-2">
                                To enable full map functionality, integrate with:
                            </p>
                            <div class="mt-4 space-y-2">
                                <p class="text-xs text-gray-600">
                                    • Google Maps API
                                </p>
                                <p class="text-xs text-gray-600">
                                    • OpenStreetMap / Leaflet.js
                                </p>
                                <p class="text-xs text-gray-600">
                                    • Mapbox GL JS
                                </p>
                            </div>
                            <div class="mt-6 p-4 bg-blue-50 rounded-lg text-left">
                                <p class="text-sm font-medium text-blue-900 mb-2">Location Data Available:</p>
                                <div class="grid grid-cols-2 gap-2 text-xs text-blue-700">
                                    <?php 
                                    $sample_locations = array_slice(array_keys($location_groups), 0, 6);
                                    foreach ($sample_locations as $loc): 
                                    ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-map-pin mr-2"></i>
                                        <?php echo htmlspecialchars($loc); ?>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php if (count($location_groups) > 6): ?>
                                    <div class="col-span-2 text-center text-gray-500">
                                        ... and <?php echo count($location_groups) - 6; ?> more locations
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Implementation Note -->
                    <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                        <p class="text-sm text-amber-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Implementation Note:</strong> To display tickets on an interactive map, 
                            add geocoding to convert location text to coordinates (latitude/longitude), 
                            then integrate a mapping library like Leaflet.js or Google Maps.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function showLocationDetails(location) {
            alert('Location: ' + location + '\n\nClick on individual tickets in the tickets page to view full details.');
        }

        // Placeholder for future map integration
        // Example using Leaflet.js:
        /*
        const map = L.map('map').setView([14.6760, 121.0437], 12); // Quezon City coordinates
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Add markers for each ticket location
        <?php foreach ($tickets as $ticket): ?>
        L.marker([lat, lng]).addTo(map)
            .bindPopup('<?php echo htmlspecialchars($ticket['title']); ?>');
        <?php endforeach; ?>
        */
    </script>
</body>
</html>