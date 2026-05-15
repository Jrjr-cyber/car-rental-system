<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Get date range filter
$start_date = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : date('Y-m-d');

// Get comprehensive statistics
$stats = [
    'total_users' => fetchOne("SELECT COUNT(*) as count FROM users")['count'],
    'total_cars' => fetchOne("SELECT COUNT(*) as count FROM cars")['count'],
    'total_reservations' => fetchOne("SELECT COUNT(*) as count FROM reservations")['count'],
    'available_cars' => fetchOne("SELECT COUNT(*) as count FROM cars WHERE available = 1")['count'],
    'revenue' => fetchOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM reservations WHERE status IN ('Confirmed', 'Completed') AND created_at BETWEEN ? AND ?", [$start_date, $end_date])['total'],
    'period_reservations' => fetchOne("SELECT COUNT(*) as count FROM reservations WHERE created_at BETWEEN ? AND ?", [$start_date, $end_date])['count']
];

// Get monthly revenue data
$monthly_revenue = fetchAll("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COALESCE(SUM(total_amount), 0) as revenue 
                            FROM reservations 
                            WHERE status IN ('Confirmed', 'Completed') AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                            GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                            ORDER BY month");

// Get car popularity data
$car_popularity = fetchAll("SELECT c.brand, c.model, COUNT(r.id) as reservation_count 
                            FROM cars c 
                            LEFT JOIN reservations r ON c.id = r.car_id 
                            WHERE r.created_at BETWEEN ? AND ? 
                            GROUP BY c.id, c.brand, c.model 
                            ORDER BY reservation_count DESC 
                            LIMIT 10", [$start_date, $end_date]);

// Get fuel type distribution
$fuel_distribution = fetchAll("SELECT fuel_type, COUNT(*) as count FROM cars GROUP BY fuel_type");

// Get reservation status distribution
$status_distribution = fetchAll("SELECT status, COUNT(*) as count FROM reservations GROUP BY status");

// Get user registration trends
$user_registrations = fetchAll("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                               FROM users 
                               WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                               GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                               ORDER BY month");

// Get top customers by revenue
$top_customers = fetchAll("SELECT u.first_name, u.last_name, u.email, COALESCE(SUM(r.total_amount), 0) as total_spent, COUNT(r.id) as reservation_count 
                          FROM users u 
                          LEFT JOIN reservations r ON u.id = r.user_id 
                          WHERE r.status IN ('Confirmed', 'Completed') 
                          GROUP BY u.id, u.first_name, u.last_name, u.email 
                          ORDER BY total_spent DESC 
                          LIMIT 10");

// Prepare chart data
$revenue_labels = [];
$revenue_data = [];
foreach ($monthly_revenue as $data) {
    $revenue_labels[] = date('M Y', strtotime($data['month'] . '-01'));
    $revenue_data[] = $data['revenue'];
}

$user_labels = [];
$user_data = [];
foreach ($user_registrations as $data) {
    $user_labels[] = date('M Y', strtotime($data['month'] . '-01'));
    $user_data[] = $data['count'];
}

$car_labels = [];
$car_data = [];
foreach ($car_popularity as $car) {
    $car_labels[] = $car['brand'] . ' ' . $car['model'];
    $car_data[] = $car['reservation_count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-align: center;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding: 1rem !important;
            }
            
            .chart-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">☰</button>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <h2><?php echo SITE_NAME; ?></h2>
            <p>Admin Panel</p>
        </div>
        
        <ul class="sidebar-menu">
            <li><a href="index.php">📊 Dashboard</a></li>
            <li><a href="users.php">👥 Users</a></li>
            <li><a href="cars.php">🚗 Cars</a></li>
            <li><a href="reservations.php">📅 Reservations</a></li>
            <li><a href="reports.php" class="active">📈 Reports</a></li>
            <li><a href="../index.php" target="_blank">🌐 View Website</a></li>
            <li><a href="../auth/logout.php">🚪 Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="container">
            <!-- Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h1>Reports & Analytics</h1>
                    <p>Comprehensive business insights and performance metrics</p>
                </div>
                <div>
                    <button onclick="exportReport()" class="btn btn-success">📥 Export Report</button>
                </div>
            </div>

            <!-- Date Range Filter -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-body">
                    <form method="GET">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary">Apply Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Key Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_cars']; ?></div>
                    <div class="stat-label">Total Cars</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_reservations']; ?></div>
                    <div class="stat-label">Total Reservations</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['available_cars']; ?></div>
                    <div class="stat-label">Available Cars</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo formatPrice($stats['revenue']); ?></div>
                    <div class="stat-label">Period Revenue</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['period_reservations']; ?></div>
                    <div class="stat-label">Period Reservations</div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="chart-grid">
                <!-- Revenue Chart -->
                <div class="chart-container">
                    <h3>Monthly Revenue Trend</h3>
                    <canvas id="revenueChart" width="400" height="200"></canvas>
                </div>

                <!-- User Registration Chart -->
                <div class="chart-container">
                    <h3>User Registration Trend</h3>
                    <canvas id="userChart" width="400" height="200"></canvas>
                </div>

                <!-- Car Popularity Chart -->
                <div class="chart-container">
                    <h3>Most Popular Cars</h3>
                    <canvas id="carPopularityChart" width="400" height="200"></canvas>
                </div>

                <!-- Fuel Type Distribution -->
                <div class="chart-container">
                    <h3>Fuel Type Distribution</h3>
                    <canvas id="fuelChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Top Customers Table -->
            <div class="card">
                <div class="card-header">
                    <h3>Top Customers by Revenue</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Email</th>
                                    <th>Total Spent</th>
                                    <th>Reservations</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_customers as $customer): ?>
                                    <tr>
                                        <td><strong><?php echo $customer['first_name'] . ' ' . $customer['last_name']; ?></strong></td>
                                        <td><?php echo $customer['email']; ?></td>
                                        <td><strong><?php echo formatPrice($customer['total_spent']); ?></strong></td>
                                        <td><?php echo $customer['reservation_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Additional Statistics -->
            <div class="row" style="margin-top: 2rem;">
                <!-- Reservation Status Distribution -->
                <div class="card">
                    <div class="card-header">
                        <h3>Reservation Status Distribution</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                            <?php foreach ($status_distribution as $status): ?>
                                <div style="text-align: center; padding: 1rem; background-color: #f8f9fa; border-radius: var(--border-radius);">
                                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--primary-color);">
                                        <?php echo $status['count']; ?>
                                    </div>
                                    <div style="color: var(--gray); font-size: 0.875rem;">
                                        <?php echo $status['status']; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Insights -->
                <div class="card">
                    <div class="card-header">
                        <h3>Quick Insights</h3>
                    </div>
                    <div class="card-body">
                        <ul style="list-style: none; padding: 0;">
                            <li style="margin-bottom: 1rem;">
                                <strong>📈 Average Revenue per Reservation:</strong> 
                                <?php echo formatPrice($stats['period_reservations'] > 0 ? $stats['revenue'] / $stats['period_reservations'] : 0); ?>
                            </li>
                            <li style="margin-bottom: 1rem;">
                                <strong>🚗 Car Utilization Rate:</strong> 
                                <?php echo $stats['total_cars'] > 0 ? round((($stats['total_cars'] - $stats['available_cars']) / $stats['total_cars']) * 100, 1) : 0; ?>%
                            </li>
                            <li style="margin-bottom: 1rem;">
                                <strong>👥 User Conversion Rate:</strong> 
                                <?php echo $stats['total_users'] > 0 ? round(($stats['period_reservations'] / $stats['total_users']) * 100, 1) : 0; ?>%
                            </li>
                            <li>
                                <strong>⭐ Most Popular Fuel Type:</strong> 
                                <?php 
                                $most_popular_fuel = array_reduce($fuel_distribution, function($carry, $item) {
                                    return $carry && $carry['count'] > $item['count'] ? $carry : $item;
                                });
                                echo $most_popular_fuel['fuel_type'] ?? 'N/A';
                                ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Toggle sidebar for mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($revenue_labels); ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?php echo json_encode($revenue_data); ?>,
                    borderColor: 'rgb(40, 167, 69)',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // User Registration Chart
        const userCtx = document.getElementById('userChart').getContext('2d');
        new Chart(userCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($user_labels); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode($user_data); ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.8)',
                    borderColor: 'rgb(220, 53, 69)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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

        // Car Popularity Chart
        const carCtx = document.getElementById('carPopularityChart').getContext('2d');
        new Chart(carCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($car_labels); ?>,
                datasets: [{
                    label: 'Reservations',
                    data: <?php echo json_encode($car_data); ?>,
                    backgroundColor: 'rgba(23, 162, 184, 0.8)',
                    borderColor: 'rgb(23, 162, 184)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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

        // Fuel Type Distribution Chart
        const fuelCtx = document.getElementById('fuelChart').getContext('2d');
        new Chart(fuelCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($fuel_distribution, 'fuel_type')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($fuel_distribution, 'count')); ?>,
                    backgroundColor: [
                        'rgba(255, 193, 7, 0.8)',
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(220, 53, 69, 0.8)'
                    ],
                    borderColor: [
                        'rgb(255, 193, 7)',
                        'rgb(40, 167, 69)',
                        'rgb(23, 162, 184)',
                        'rgb(220, 53, 69)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Export report function
        function exportReport() {
            const startDate = document.querySelector('input[name="start_date"]').value;
            const endDate = document.querySelector('input[name="end_date"]').value;
            window.location.href = `reports.php?action=export&start_date=${startDate}&end_date=${endDate}`;
        }
    </script>
    <script src="../js/script.js"></script>
</body>
</html>

<?php
// Handle export functionality
if (isset($_GET['action']) && $_GET['action'] == 'export') {
    $export_start_date = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : date('Y-m-01');
    $export_end_date = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : date('Y-m-d');
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="car_rental_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Write header
    fputcsv($output, ['Car Rental System Report']);
    fputcsv($output, ['Report Period:', $export_start_date . ' to ' . $export_end_date]);
    fputcsv($output, ['Generated:', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Write statistics
    fputcsv($output, ['KEY STATISTICS']);
    fputcsv($output, ['Total Users', fetchOne("SELECT COUNT(*) as count FROM users")['count']]);
    fputcsv($output, ['Total Cars', fetchOne("SELECT COUNT(*) as count FROM cars")['count']]);
    fputcsv($output, ['Available Cars', fetchOne("SELECT COUNT(*) as count FROM cars WHERE available = 1")['count']]);
    fputcsv($output, ['Total Reservations', fetchOne("SELECT COUNT(*) as count FROM reservations")['count']]);
    
    $revenue_data = fetchOne("SELECT COALESCE(SUM(total_amount), 0) as total FROM reservations WHERE status IN ('Confirmed', 'Completed') AND created_at BETWEEN ? AND ?", [$export_start_date, $export_end_date]);
    fputcsv($output, ['Period Revenue', $revenue_data['total']]);
    
    fputcsv($output, []);
    
    // Write reservations data
    fputcsv($output, ['RESERVATIONS DETAIL']);
    fputcsv($output, ['ID', 'Customer', 'Email', 'Car', 'Pickup Date', 'Return Date', 'Total Amount', 'Status', 'Created Date']);
    
    $reservations_sql = "SELECT r.*, u.first_name, u.last_name, u.email, c.brand, c.model 
                        FROM reservations r 
                        JOIN users u ON r.user_id = u.id 
                        JOIN cars c ON r.car_id = c.id 
                        WHERE r.created_at BETWEEN ? AND ? 
                        ORDER BY r.created_at DESC";
    
    $reservations = fetchAll($reservations_sql, [$export_start_date, $export_end_date]);
    
    foreach ($reservations as $reservation) {
        fputcsv($output, [
            $reservation['id'],
            $reservation['first_name'] . ' ' . $reservation['last_name'],
            $reservation['email'],
            $reservation['brand'] . ' ' . $reservation['model'],
            $reservation['pickup_date'],
            $reservation['return_date'],
            $reservation['total_amount'],
            $reservation['status'],
            $reservation['created_at']
        ]);
    }
    
    fclose($output);
    exit;
}
?>
