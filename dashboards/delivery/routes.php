<?php
// Start session and check delivery authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection and functions
require_once '../../admin/includes/config.php';

// Check if user is logged in and is delivery personnel
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'delivery' && $_SESSION['user_role'] !== 'driver')) {
    header('Location: ../../auth/login.php');
    exit();
}

// Set page title
$page_title = 'Delivery Routes';

// Initialize variables
$currentRoutes = [];
$completedRoutes = [];
$routeStats = [];
$error = null;

// Get route data
try {
    global $pdo;

    // Get current active routes (simplified - could be based on delivery areas)
    $currentRoutesStmt = $pdo->prepare("
        SELECT
            'Nairobi CBD' as route_name,
            COUNT(*) as order_count,
            SUM(total) as total_value,
            GROUP_CONCAT(DISTINCT o.delivery_address SEPARATOR ', ') as addresses
        FROM orders o
        WHERE o.status = 'out_for_delivery'
        GROUP BY LEFT(o.delivery_address, 20) -- Group by area
        ORDER BY order_count DESC
        LIMIT 5
    ");
    $currentRoutesStmt->execute();
    $currentRoutes = $currentRoutesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get completed routes today
    $completedRoutesStmt = $pdo->prepare("
        SELECT
            'Nairobi CBD' as route_name,
            COUNT(*) as order_count,
            SUM(total) as total_value,
            AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_time
        FROM orders o
        WHERE o.status = 'delivered' AND DATE(o.updated_at) = CURDATE()
        GROUP BY LEFT(o.delivery_address, 20)
        ORDER BY order_count DESC
        LIMIT 5
    ");
    $completedRoutesStmt->execute();
    $completedRoutes = $completedRoutesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get route statistics
    $statsStmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_deliveries,
            AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_delivery_time,
            SUM(total) as total_earnings
        FROM orders
        WHERE status = 'delivered' AND DATE(updated_at) = CURDATE()
    ");
    $statsStmt->execute();
    $routeStats = $statsStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Delivery routes page error: " . $e->getMessage());
}

// Handle route optimization
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['optimize_route'])) {
    // This would typically call a route optimization algorithm
    $_SESSION['message'] = ['type' => 'success', 'text' => 'Route optimized successfully'];
    header('Location: routes.php');
    exit();
}

// Include header
require_once 'includes/header.php';
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-primary via-primary-dark to-green-700 text-white">
    <div class="px-6 py-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Delivery Routes</h1>
                <p class="text-lg opacity-90">Optimize your delivery routes and track performance</p>
            </div>
            <div class="mt-4 lg:mt-0">
                <form method="POST" class="inline">
                    <button type="submit" name="optimize_route"
                            class="bg-white/20 hover:bg-white/30 text-white px-6 py-3 rounded-lg font-semibold transition-all duration-200 flex items-center backdrop-blur-sm border border-white/20">
                        <i class="fas fa-route mr-2"></i>
                        Optimize Routes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="px-6 py-8">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $_SESSION['message']['type'] === 'success' ? 'bg-green-50 text-green-800 border-l-4 border-green-400' : 'bg-red-50 text-red-800 border-l-4 border-red-400'; ?>">
            <?php echo htmlspecialchars($_SESSION['message']['text']); ?>
            <?php unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-400 text-red-700 rounded-lg">
            <p class="font-semibold">Error</p>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <!-- Route Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-route text-2xl text-blue-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo count($currentRoutes); ?></h3>
                    <p class="text-gray-600 font-medium">Active Routes</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-check-circle text-2xl text-green-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo $routeStats['total_deliveries'] ?? 0; ?></h3>
                    <p class="text-gray-600 font-medium">Completed Today</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-clock text-2xl text-purple-600"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-2xl font-bold text-gray-900">
                        <?php echo isset($routeStats['avg_delivery_time']) ? round($routeStats['avg_delivery_time']) : 0; ?>m
                    </h3>
                    <p class="text-gray-600 font-medium">Avg. Delivery Time</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Route Tabs -->
    <div class="mb-8">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button onclick="showRouteTab('current')" class="route-tab-button active whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-accent text-accent">
                    Current Routes
                </button>
                <button onclick="showRouteTab('completed')" class="route-tab-button whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Completed Routes
                </button>
                <button onclick="showRouteTab('optimize')" class="route-tab-button whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Route Optimization
                </button>
            </nav>
        </div>
    </div>

    <!-- Current Routes Tab -->
    <div id="current-routes-tab" class="route-tab-content">
        <?php if (!empty($currentRoutes)): ?>
            <div class="grid gap-6">
                <?php foreach ($currentRoutes as $index => $route): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-400">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-map-marker-alt text-blue-600 text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">Route <?php echo $index + 1; ?>: <?php echo htmlspecialchars($route['route_name']); ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo $route['order_count']; ?> deliveries</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Total Value</p>
                                        <p class="font-medium text-gray-900">KES <?php echo number_format($route['total_value'], 2); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Delivery Addresses</p>
                                        <p class="font-medium text-gray-900 text-sm"><?php echo htmlspecialchars(substr($route['addresses'], 0, 100) . '...'); ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 lg:mt-0 lg:ml-6">
                                <div class="flex space-x-2">
                                    <button onclick="viewRouteMap(<?php echo $index + 1; ?>)"
                                            class="bg-accent hover:bg-blue-600 text-white px-3 py-2 rounded-lg font-medium transition-colors flex items-center text-sm">
                                        <i class="fas fa-map mr-1"></i>
                                        View Map
                                    </button>
                                    <button onclick="startRoute(<?php echo $index + 1; ?>)"
                                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg font-medium transition-colors flex items-center text-sm">
                                        <i class="fas fa-play mr-1"></i>
                                        Start Route
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-route text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-900 mb-2">No active routes</h3>
                <p class="text-gray-600">Routes will appear here when you have deliveries to make.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Completed Routes Tab -->
    <div id="completed-routes-tab" class="route-tab-content hidden">
        <?php if (!empty($completedRoutes)): ?>
            <div class="grid gap-6">
                <?php foreach ($completedRoutes as $index => $route): ?>
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-400">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-check-circle text-green-600 text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">Completed Route <?php echo $index + 1; ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo $route['order_count']; ?> deliveries completed</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Total Value</p>
                                        <p class="font-medium text-gray-900">KES <?php echo number_format($route['total_value'], 2); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Avg. Time</p>
                                        <p class="font-medium text-gray-900"><?php echo round($route['avg_time']); ?> minutes</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600 mb-1">Efficiency</p>
                                        <p class="font-medium text-green-600">95% On-time</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 lg:mt-0 lg:ml-6">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check mr-1"></i>
                                    Completed
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-check-circle text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-900 mb-2">No completed routes today</h3>
                <p class="text-gray-600">Your completed routes will appear here.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Route Optimization Tab -->
    <div id="optimize-routes-tab" class="route-tab-content hidden">
        <div class="bg-white rounded-lg shadow-md p-8">
            <div class="text-center mb-8">
                <i class="fas fa-route text-6xl text-accent mb-4"></i>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Route Optimization</h3>
                <p class="text-gray-600">Optimize your delivery routes for maximum efficiency</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Optimization Options -->
                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Optimization Settings</h4>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div>
                                <h5 class="font-medium text-gray-900">Minimize Travel Time</h5>
                                <p class="text-sm text-gray-600">Optimize for shortest route</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" checked>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-accent/25 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-accent"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div>
                                <h5 class="font-medium text-gray-900">Balance Workload</h5>
                                <p class="text-sm text-gray-600">Even distribution of deliveries</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-accent/25 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-accent"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div>
                                <h5 class="font-medium text-gray-900">Traffic Awareness</h5>
                                <p class="text-sm text-gray-600">Avoid high-traffic areas</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" checked>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-accent/25 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-accent"></div>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button onclick="optimizeRoutes()"
                                class="w-full bg-accent hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold transition-colors">
                            <i class="fas fa-magic mr-2"></i>
                            Optimize Routes
                        </button>
                    </div>
                </div>

                <!-- Route Preview -->
                <div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Route Preview</h4>

                    <div class="bg-gray-50 rounded-lg p-6 min-h-64">
                        <div class="text-center text-gray-500">
                            <i class="fas fa-map text-4xl mb-3"></i>
                            <p>Route optimization preview will appear here</p>
                            <p class="text-sm mt-2">Select optimization settings to see preview</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showRouteTab(tabName) {
    // Hide all route tabs
    document.querySelectorAll('.route-tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });

    // Remove active state from all buttons
    document.querySelectorAll('.route-tab-button').forEach(button => {
        button.classList.remove('border-accent', 'text-accent');
        button.classList.add('border-transparent', 'text-gray-500');
    });

    // Show selected tab
    document.getElementById(tabName + '-routes-tab').classList.remove('hidden');

    // Add active state to clicked button
    event.target.classList.remove('border-transparent', 'text-gray-500');
    event.target.classList.add('border-accent', 'text-accent');
}

function viewRouteMap(routeNumber) {
    // Open route map
    alert(`Opening map for Route ${routeNumber}`);
    // In a real implementation, this would open a map with the route
}

function startRoute(routeNumber) {
    // Start navigation for the route
    Swal.fire({
        title: 'Start Route?',
        text: `Begin navigation for Route ${routeNumber}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3B82F6',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Start Route'
    }).then((result) => {
        if (result.isConfirmed) {
            alert(`Starting route ${routeNumber}`);
            // In a real implementation, this would start GPS navigation
        }
    });
}

function optimizeRoutes() {
    // Show loading state
    Swal.fire({
        title: 'Optimizing Routes...',
        text: 'Please wait while we optimize your delivery routes',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading();
        }
    });

    // Simulate optimization process
    setTimeout(() => {
        Swal.close();
        Swal.fire({
            icon: 'success',
            title: 'Routes Optimized!',
            text: 'Your delivery routes have been optimized for maximum efficiency.',
            confirmButtonColor: '#3B82F6'
        });
    }, 2000);
}

// Auto-refresh every 60 seconds for route updates
setInterval(function() {
    location.reload();
}, 60000);
</script>

<?php require_once 'includes/footer.php'; ?>
