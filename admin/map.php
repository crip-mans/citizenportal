<?php
require_once '../config.php';
require_once 'auth_check.php';

$db = Database::getInstance()->getConnection();

// Get department filter
$dept_filter = getDepartmentFilter();

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
    {$dept_filter}
    GROUP BY t.id 
    ORDER BY t.submitted_at DESC 
    LIMIT 200
";

$tickets = $db->query($query)->fetchAll();

// Group tickets by location
$location_groups = [];
foreach ($tickets as $ticket) {
    $location = trim($ticket['location']);
    if (!isset($location_groups[$location])) {
        $location_groups[$location] = [
            'tickets' => [],
            'open' => 0,
            'in_progress' => 0,
            'resolved' => 0,
            'urgent' => 0
        ];
    }
    $location_groups[$location]['tickets'][] = $ticket;
    
    // Count by status
    if ($ticket['status'] === 'open') $location_groups[$location]['open']++;
    if ($ticket['status'] === 'in_progress') $location_groups[$location]['in_progress']++;
    if ($ticket['status'] === 'resolved') $location_groups[$location]['resolved']++;
    if ($ticket['priority'] === 'urgent') $location_groups[$location]['urgent']++;
}

// Get status filter
$status_filter = $_GET['status'] ?? 'all';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map View - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS (FREE - No API Key Required) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Leaflet MarkerCluster for grouped markers -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        #map {
            height: calc(100vh - 250px);
            min-height: 500px;
        }
        
        .custom-marker {
            background: none;
            border: none;
        }
        
        .marker-pin {
            width: 30px;
            height: 30px;
            border-radius: 50% 50% 50% 0;
            position: relative;
            transform: rotate(-45deg);
            left: -15px;
            top: -15px;
        }
        
        .marker-icon {
            position: absolute;
            width: 18px;
            height: 18px;
            top: 6px;
            left: 6px;
            transform: rotate(45deg);
            color: white;
            text-align: center;
            font-size: 12px;
        }
        
        .status-open { background-color: #3B82F6; }
        .status-in-progress { background-color: #F59E0B; }
        .status-resolved { background-color: #10B981; }
        .status-urgent { background-color: #EF4444; }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'includes/admin_header.php'; ?>

    <div class="flex">
        <?php include 'includes/admin_sidebar.php'; ?>

        <main class="flex-1 p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800">
                    <i class="fas fa-map-marked-alt mr-2 text-indigo-600"></i>Interactive Map View
                </h1>
                <p class="text-gray-600 mt-1">
                    <i class="fas fa-check-circle text-green-600 mr-1"></i>
                    FREE - Powered by OpenStreetMap & Leaflet.js (No API Key Required)
                </p>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <p class="text-2xl font-bold text-indigo-600"><?php echo count($location_groups); ?></p>
                    <p class="text-xs text-gray-600 mt-1">Locations</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <p class="text-2xl font-bold text-gray-800"><?php echo count($tickets); ?></p>
                    <p class="text-xs text-gray-600 mt-1">Total Tickets</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600">
                        <?php echo array_sum(array_column($location_groups, 'open')); ?>
                    </p>
                    <p class="text-xs text-gray-600 mt-1">Open</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <p class="text-2xl font-bold text-yellow-600">
                        <?php echo array_sum(array_column($location_groups, 'in_progress')); ?>
                    </p>
                    <p class="text-xs text-gray-600 mt-1">In Progress</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <p class="text-2xl font-bold text-green-600">
                        <?php echo array_sum(array_column($location_groups, 'resolved')); ?>
                    </p>
                    <p class="text-xs text-gray-600 mt-1">Resolved</p>
                </div>
                <div class="bg-white rounded-lg shadow p-4 text-center">
                    <p class="text-2xl font-bold text-red-600">
                        <?php echo array_sum(array_column($location_groups, 'urgent')); ?>
                    </p>
                    <p class="text-xs text-gray-600 mt-1">Urgent</p>
                </div>
            </div>

            <!-- Map and Controls -->
            <div class="grid lg:grid-cols-4 gap-6">
                <!-- Location List -->
                <div class="bg-white rounded-xl shadow-md p-6 overflow-y-auto" style="max-height: calc(100vh - 350px);">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-800">
                            <i class="fas fa-list mr-2"></i>Locations
                        </h2>
                        <select 
                            onchange="filterMarkers(this.value)"
                            class="text-xs border border-gray-300 rounded px-2 py-1"
                        >
                            <option value="all">All Status</option>
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                        </select>
                    </div>
                    
                    <div class="space-y-3" id="locationList">
                        <?php foreach ($location_groups as $location => $data): ?>
                        <div class="border border-gray-200 rounded-lg p-3 hover:bg-gray-50 cursor-pointer location-item" 
                             data-location="<?php echo htmlspecialchars($location); ?>"
                             onclick="focusLocation('<?php echo htmlspecialchars(addslashes($location)); ?>')">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-800 text-sm">
                                        <i class="fas fa-map-marker-alt mr-2 text-red-500"></i>
                                        <?php echo htmlspecialchars($location); ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <?php echo count($data['tickets']); ?> ticket(s)
                                    </p>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    <?php if ($data['open'] > 0): ?>
                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-medium">
                                        <?php echo $data['open']; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($data['in_progress'] > 0): ?>
                                    <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded text-xs font-medium">
                                        <?php echo $data['in_progress']; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($data['resolved'] > 0): ?>
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs font-medium">
                                        <?php echo $data['resolved']; ?>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($data['urgent'] > 0): ?>
                                    <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-medium">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Map Container -->
                <div class="lg:col-span-3 bg-white rounded-xl shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-800">
                            <i class="fas fa-map mr-2"></i>Geographic Distribution
                        </h2>
                        <div class="flex items-center space-x-2">
                            <button onclick="map.setView([14.6760, 121.0437], 12)" class="px-3 py-1 text-xs bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200">
                                <i class="fas fa-crosshairs mr-1"></i>Center Map
                            </button>
                        </div>
                    </div>

                    <!-- Map Legend -->
                    <div class="mb-3 flex flex-wrap gap-3 text-xs">
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-blue-500 mr-2"></span>
                            <span class="text-gray-600">Open</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></span>
                            <span class="text-gray-600">In Progress</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-green-500 mr-2"></span>
                            <span class="text-gray-600">Resolved</span>
                        </div>
                        <div class="flex items-center">
                            <span class="w-3 h-3 rounded-full bg-red-500 mr-2"></span>
                            <span class="text-gray-600">Urgent</span>
                        </div>
                    </div>

                    <!-- Leaflet Map -->
                    <div id="map" class="rounded-lg border-2 border-gray-200"></div>

                    <!-- Map Info -->
                    <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                        <p class="text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>FREE Map:</strong> Using OpenStreetMap & Leaflet.js. Click markers to view ticket details. 
                            Zoom and pan to explore different areas. Clustered markers show multiple tickets in the same area.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Initialize map centered on Quezon City, Philippines
        const map = L.map('map').setView([14.6760, 121.0437], 12);

        // Add FREE OpenStreetMap tiles (no API key required!)
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Create marker cluster group for better performance
        const markers = L.markerClusterGroup({
            maxClusterRadius: 50,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false
        });

        // Location data from PHP
        const locations = <?php echo json_encode($location_groups); ?>;
        const allMarkers = {};

        // Quezon City landmarks for reference (approximate coordinates)
        const qcLandmarks = {
            'diliman': [14.6537, 121.0685],
            'cubao': [14.6196, 121.0517],
            'commonwealth': [14.7138, 121.0887],
            'novaliches': [14.7289, 121.0374],
            'fairview': [14.7386, 121.0583],
            'quezon city hall': [14.6760, 121.0437],
            'eastwood': [14.6093, 121.0790],
            'sm north': [14.6567, 121.0289],
            'trinoma': [14.6550, 121.0316],
            'timog': [14.6245, 121.0327]
        };

        // Function to estimate coordinates based on location text
        function estimateCoordinates(location) {
            const loc = location.toLowerCase();
            
            // Check if location matches a landmark
            for (const [landmark, coords] of Object.entries(qcLandmarks)) {
                if (loc.includes(landmark)) {
                    // Add small random offset for multiple tickets in same area
                    return [
                        coords[0] + (Math.random() - 0.5) * 0.01,
                        coords[1] + (Math.random() - 0.5) * 0.01
                    ];
                }
            }
            
            // Default to random point within Quezon City bounds
            return [
                14.6760 + (Math.random() - 0.5) * 0.15,  // Latitude
                121.0437 + (Math.random() - 0.5) * 0.15  // Longitude
            ];
        }

        // Add markers for each location
        Object.keys(locations).forEach(location => {
            const data = locations[location];
            const coords = estimateCoordinates(location);
            
            // Determine marker color based on priority
            let markerClass = 'status-open';
            if (data.urgent > 0) {
                markerClass = 'status-urgent';
            } else if (data.open > 0) {
                markerClass = 'status-open';
            } else if (data.in_progress > 0) {
                markerClass = 'status-in-progress';
            } else if (data.resolved > 0) {
                markerClass = 'status-resolved';
            }

            // Create custom icon
            const icon = L.divIcon({
                className: 'custom-marker',
                html: `<div class="marker-pin ${markerClass}">
                         <div class="marker-icon">${data.tickets.length}</div>
                       </div>`,
                iconSize: [30, 42],
                iconAnchor: [15, 42]
            });

            // Create marker
            const marker = L.marker(coords, { icon: icon });
            
            // Create popup content
            let popupContent = `
                <div class="p-2" style="min-width: 250px;">
                    <h3 class="font-bold text-sm mb-2">
                        <i class="fas fa-map-marker-alt text-red-500 mr-1"></i>
                        ${location}
                    </h3>
                    <div class="text-xs space-y-1 mb-3">
                        <div class="flex justify-between">
                            <span>Total Tickets:</span>
                            <strong>${data.tickets.length}</strong>
                        </div>
                        <div class="flex justify-between text-blue-600">
                            <span>Open:</span>
                            <strong>${data.open}</strong>
                        </div>
                        <div class="flex justify-between text-yellow-600">
                            <span>In Progress:</span>
                            <strong>${data.in_progress}</strong>
                        </div>
                        <div class="flex justify-between text-green-600">
                            <span>Resolved:</span>
                            <strong>${data.resolved}</strong>
                        </div>
                        ${data.urgent > 0 ? `<div class="flex justify-between text-red-600">
                            <span>Urgent:</span>
                            <strong>${data.urgent}</strong>
                        </div>` : ''}
                    </div>
                    <div class="border-t pt-2 space-y-1">
                        <p class="font-semibold text-xs mb-1">Recent Tickets:</p>
            `;
            
            // Add ticket links (show first 3)
            data.tickets.slice(0, 3).forEach(ticket => {
                popupContent += `
                    <a href="ticket_details.php?id=${ticket.id}" 
                       class="block text-xs text-blue-600 hover:underline"
                       target="_blank">
                        ${ticket.ticket_number} - ${ticket.title.substring(0, 40)}...
                    </a>
                `;
            });
            
            if (data.tickets.length > 3) {
                popupContent += `<p class="text-xs text-gray-500 mt-1">+ ${data.tickets.length - 3} more tickets</p>`;
            }
            
            popupContent += `</div></div>`;
            
            marker.bindPopup(popupContent);
            markers.addLayer(marker);
            
            // Store marker reference
            allMarkers[location] = marker;
        });

        // Add markers to map
        map.addLayer(markers);

        // Function to focus on specific location
        function focusLocation(location) {
            const marker = allMarkers[location];
            if (marker) {
                map.setView(marker.getLatLng(), 15);
                marker.openPopup();
            }
        }

        // Filter markers by status
        function filterMarkers(status) {
            // This is a simplified version - you can enhance this
            console.log('Filtering by:', status);
            // Would need to recreate markers based on filter
        }

        // Auto-fit map to show all markers on load
        if (Object.keys(allMarkers).length > 0) {
            const group = new L.featureGroup(Object.values(allMarkers));
            map.fitBounds(group.getBounds().pad(0.1));
        }
    </script>
</body>
</html>