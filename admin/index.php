<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Get statistics
$total_users = fetchOne("SELECT COUNT(*) as count FROM users")['count'];
$total_cars = fetchOne("SELECT COUNT(*) as count FROM cars")['count'];
$total_reservations = fetchOne("SELECT COUNT(*) as count FROM reservations")['count'];
$available_cars = fetchOne("SELECT COUNT(*) as count FROM cars WHERE available = 1")['count'];

// Get revenue estimation
$revenue_data = fetchOne("SELECT SUM(total_amount) as total FROM reservations WHERE status IN ('Confirmed', 'Completed')");
$total_revenue = $revenue_data['total'] ?: 0;

// Get recent reservations
$recent_reservations = fetchAll("SELECT r.*, u.first_name, u.last_name, c.brand, c.model 
                                FROM reservations r 
                                JOIN users u ON r.user_id = u.id 
                                JOIN cars c ON r.car_id = c.id 
                                ORDER BY r.created_at DESC LIMIT 5");

// Get monthly reservation data for chart
$monthly_data = fetchAll("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                         FROM reservations 
                         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                         GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                         ORDER BY month");

// Prepare chart data
$chart_labels = [];
$chart_data = [];
foreach ($monthly_data as $data) {
    $chart_labels[] = date('M Y', strtotime($data['month'] . '-01'));
    $chart_data[] = $data['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
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
            background: linear-gradient(135deg, var(--primary-color), #c82333);
            color: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.3);
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, var(--success-color), #1e7e34);
        }
        
        .stat-card.info {
            background: linear-gradient(135deg, var(--info-color), #117a8b);
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, var(--warning-color), #d39e00);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .chart-container {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
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
            <li><a href="index.php" class="active">📊 Dashboard</a></li>
            <li><a href="users.php">👥 Users</a></li>
            <li><a href="cars.php">🚗 Cars</a></li>
            <li><a href="reservations.php">📅 Reservations</a></li>
            <li><a href="reports.php">📈 Reports</a></li>
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
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>
                </div>
                <div style="text-align: right;">
                    <p style="color: var(--gray); margin: 0;"><?php echo date('l, F j, Y'); ?></p>
                    <p style="color: var(--gray); margin: 0;"><?php echo date('g:i A'); ?></p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-number"><?php echo $total_cars; ?></div>
                    <div class="stat-label">Total Cars</div>
                </div>
                <div class="stat-card info">
                    <div class="stat-number"><?php echo $available_cars; ?></div>
                    <div class="stat-label">Available Cars</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-number"><?php echo $total_reservations; ?></div>
                    <div class="stat-label">Total Reservations</div>
                </div>
            </div>

            <!-- Revenue Card -->
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea, #764ba2); margin-bottom: 2rem;">
                <div class="stat-number"><?php echo formatPrice($total_revenue); ?></div>
                <div class="stat-label">Total Revenue (Confirmed & Completed)</div>
            </div>

            <!-- Charts Row -->
            <div class="row" style="margin-bottom: 2rem;">
                <!-- Reservations Chart -->
                <div class="chart-container">
                    <h3>Monthly Reservations</h3>
                    <canvas id="reservationsChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Recent Reservations -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent Reservations</h3>
                    <a href="reservations.php" class="btn btn-primary btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_reservations)): ?>
                        <p>No recent reservations found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Car</th>
                                        <th>Pickup</th>
                                        <th>Return</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_reservations as $reservation): ?>
                                        <tr>
                                            <td>#<?php echo $reservation['id']; ?></td>
                                            <td><?php echo $reservation['first_name'] . ' ' . $reservation['last_name']; ?></td>
                                            <td><?php echo $reservation['brand'] . ' ' . $reservation['model']; ?></td>
                                            <td><?php echo formatDate($reservation['pickup_date']); ?></td>
                                            <td><?php echo formatDate($reservation['return_date']); ?></td>
                                            <td><?php echo formatPrice($reservation['total_amount']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($reservation['status']); ?>">
                                                    <?php echo $reservation['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="reservations.php?view=<?php echo $reservation['id']; ?>" 
                                                   class="btn btn-sm btn-outline">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row" style="margin-top: 2rem;">
                <div class="card">
                    <div class="card-header">
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="row row-cols-2">
                            <a href="cars.php?action=add" class="btn btn-primary">➕ Add New Car</a>
                            <a href="users.php" class="btn btn-success">👥 Manage Users</a>
                            <a href="reservations.php" class="btn btn-info">📅 Manage Reservations</a>
                            <a href="reports.php" class="btn btn-warning">📊 View Reports</a>
                        </div>
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

        // Reservations Chart
        const ctx = document.getElementById('reservationsChart').getContext('2d');
        const reservationsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Number of Reservations',
                    data: <?php echo json_encode($chart_data); ?>,
                    borderColor: 'rgb(220, 53, 69)',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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

        // Auto-refresh dashboard every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
    <script src="../js/script.js"></script>
</body>
</html>
