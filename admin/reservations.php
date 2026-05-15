<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Handle reservation status update
if (isset($_POST['update_status']) && isset($_POST['reservation_id']) && isset($_POST['status'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    $status = sanitizeInput($_POST['status']);
    
    if (in_array($status, ['Pending', 'Confirmed', 'Cancelled', 'Completed'])) {
        $sql = "UPDATE reservations SET status = ? WHERE id = ?";
        $stmt = executeQuery($sql, [$status, $reservation_id]);
        
        if ($stmt) {
            showAlert('Reservation status updated successfully', 'success');
        } else {
            showAlert('Failed to update reservation status', 'error');
        }
    }
    
    redirect('reservations.php');
}

// Handle reservation deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $reservation_id = (int)$_GET['delete'];
    
    $sql = "DELETE FROM reservations WHERE id = ?";
    $stmt = executeQuery($sql, [$reservation_id]);
    
    if ($stmt) {
        showAlert('Reservation deleted successfully', 'success');
    } else {
        showAlert('Failed to delete reservation', 'error');
    }
    
    redirect('reservations.php');
}

// Get filter and pagination parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$date_filter = isset($_GET['date']) ? sanitizeInput($_GET['date']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Build WHERE clause
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR c.brand LIKE ? OR c.model LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where .= " AND r.status = ?";
    $params[] = $status_filter;
}

if ($date_filter) {
    $where .= " AND DATE(r.created_at) = ?";
    $params[] = $date_filter;
}

// Get total reservations count
$count_sql = "SELECT COUNT(*) as total FROM reservations r 
              JOIN users u ON r.user_id = u.id 
              JOIN cars c ON r.car_id = c.id $where";
$total_result = fetchOne($count_sql, $params);
$total_reservations = $total_result['total'];

// Get pagination info
$pagination = getPagination($total_reservations, $per_page, $page);

// Get reservations for current page
$sql = "SELECT r.*, u.first_name, u.last_name, u.email, c.brand, c.model, c.image1 
        FROM reservations r 
        JOIN users u ON r.user_id = u.id 
        JOIN cars c ON r.car_id = c.id 
        $where 
        ORDER BY r.created_at DESC 
        LIMIT {$pagination['offset']}, $per_page";
$reservations = fetchAll($sql, $params);

// Get statistics
$pending_count = fetchOne("SELECT COUNT(*) as count FROM reservations WHERE status = 'Pending'")['count'];
$confirmed_count = fetchOne("SELECT COUNT(*) as count FROM reservations WHERE status = 'Confirmed'")['count'];
$cancelled_count = fetchOne("SELECT COUNT(*) as count FROM reservations WHERE status = 'Cancelled'")['count'];
$completed_count = fetchOne("SELECT COUNT(*) as count FROM reservations WHERE status = 'Completed'")['count'];

// Get alert message
$alert = getAlert();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            padding: 1.5rem;
            border-radius: var(--border-radius);
            text-align: center;
            color: white;
        }
        
        .stat-card.pending { background: linear-gradient(135deg, #ffc107, #e0a800); }
        .stat-card.confirmed { background: linear-gradient(135deg, #28a745, #1e7e34); }
        .stat-card.cancelled { background: linear-gradient(135deg, #dc3545, #c82333); }
        .stat-card.completed { background: linear-gradient(135deg, #17a2b8, #117a8b); }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .status-select {
            padding: 0.25rem 0.5rem;
            border-radius: var(--border-radius);
            border: 1px solid #ddd;
            font-size: 0.875rem;
        }
        
        .car-image-preview {
            width: 60px;
            height: 45px;
            object-fit: cover;
            border-radius: var(--border-radius);
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
            <li><a href="index.php">📊 Dashboard</a></li>
            <li><a href="users.php">👥 Users</a></li>
            <li><a href="cars.php">🚗 Cars</a></li>
            <li><a href="reservations.php" class="active">📅 Reservations</a></li>
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
                    <h1>Manage Reservations</h1>
                    <p>Total Reservations: <?php echo $total_reservations; ?></p>
                </div>
                <div>
                    <button onclick="exportReservations()" class="btn btn-success">📥 Export</button>
                </div>
            </div>

            <!-- Alert Message -->
            <?php if ($alert): ?>
                <div class="alert alert-<?php echo $alert['type']; ?>">
                    <?php echo $alert['message']; ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card pending">
                    <div class="stat-number"><?php echo $pending_count; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card confirmed">
                    <div class="stat-number"><?php echo $confirmed_count; ?></div>
                    <div class="stat-label">Confirmed</div>
                </div>
                <div class="stat-card cancelled">
                    <div class="stat-number"><?php echo $cancelled_count; ?></div>
                    <div class="stat-label">Cancelled</div>
                </div>
                <div class="stat-card completed">
                    <div class="stat-number"><?php echo $completed_count; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>

            <!-- Search and Filter Form -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-body">
                    <form method="GET">
                        <div class="form-row">
                            <div class="form-group">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by customer, car..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="form-group">
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Confirmed" <?php echo $status_filter === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="Cancelled" <?php echo $status_filter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    <option value="Completed" <?php echo $status_filter === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="date" name="date" class="form-control" 
                                       value="<?php echo htmlspecialchars($date_filter); ?>">
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="reservations.php" class="btn btn-outline">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Reservations Table -->
            <div class="card">
                <div class="card-header">
                    <h3>Reservations List</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($reservations)): ?>
                        <div class="text-center" style="padding: 3rem;">
                            <h4>No reservations found</h4>
                            <p><?php echo ($search || $status_filter || $date_filter) ? 'Try adjusting your search criteria.' : 'No reservations have been made yet.'; ?></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Car</th>
                                        <th>Dates</th>
                                        <th>Duration</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservations as $reservation): ?>
                                        <tr>
                                            <td>#<?php echo $reservation['id']; ?></td>
                                            <td>
                                                <div>
                                                    <strong><?php echo $reservation['first_name'] . ' ' . $reservation['last_name']; ?></strong>
                                                    <br>
                                                    <small style="color: var(--gray);"><?php echo $reservation['email']; ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                    <?php if ($reservation['image1']): ?>
                                                        <img src="../uploads/<?php echo $reservation['image1']; ?>" 
                                                             alt="<?php echo $reservation['brand'] . ' ' . $reservation['model']; ?>"
                                                             class="car-image-preview">
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong><?php echo $reservation['brand'] . ' ' . $reservation['model']; ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong>Pickup:</strong> <?php echo formatDate($reservation['pickup_date']); ?>
                                                    <br>
                                                    <strong>Return:</strong> <?php echo formatDate($reservation['return_date']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                $days = calculateDays($reservation['pickup_date'], $reservation['return_date']);
                                                echo $days . ' day' . ($days > 1 ? 's' : '');
                                                ?>
                                            </td>
                                            <td><strong><?php echo formatPrice($reservation['total_amount']); ?></strong></td>
                                            <td>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to change the status?')">
                                                    <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                    <select name="status" class="status-select" onchange="this.form.submit()">
                                                        <option value="Pending" <?php echo $reservation['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="Confirmed" <?php echo $reservation['status'] === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                        <option value="Cancelled" <?php echo $reservation['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                        <option value="Completed" <?php echo $reservation['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                    </select>
                                                    <input type="hidden" name="update_status" value="1">
                                                </form>
                                            </td>
                                            <td><?php echo formatDate($reservation['created_at'], 'M j, Y H:i'); ?></td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <a href="javascript:void(0)" onclick="viewReservation(<?php echo $reservation['id']; ?>)" 
                                                       class="btn btn-sm btn-outline">View</a>
                                                    <a href="reservations.php?delete=<?php echo $reservation['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this reservation?')">
                                                        Delete
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($pagination['total_pages'] > 1): ?>
                            <div class="pagination" style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
                                <?php if ($pagination['has_prev']): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['prev_page']])); ?>" 
                                       class="btn btn-outline">← Previous</a>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                    <?php if ($i == $page): ?>
                                        <span class="btn btn-primary"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                           class="btn btn-outline"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($pagination['has_next']): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])); ?>" 
                                       class="btn btn-outline">Next →</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Reservation Details Modal -->
    <div id="reservationModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000;">
        <div class="modal-content" style="position: relative; background-color: white; margin: 5% auto; padding: 2rem; width: 90%; max-width: 700px; border-radius: var(--border-radius); max-height: 80vh; overflow-y: auto;">
            <span class="close" onclick="closeModal()" style="position: absolute; top: 1rem; right: 1rem; font-size: 2rem; cursor: pointer;">&times;</span>
            <div id="reservationDetails"></div>
        </div>
    </div>

    <script>
        // Toggle sidebar for mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // View reservation details
        function viewReservation(reservationId) {
            fetch(`reservations.php?action=view&id=${reservationId}`)
                .then(response => response.json())
                .then(data => {
                    const statusColor = {
                        'Pending': '#ffc107',
                        'Confirmed': '#28a745',
                        'Cancelled': '#dc3545',
                        'Completed': '#17a2b8'
                    };
                    
                    document.getElementById('reservationDetails').innerHTML = `
                        <h3>Reservation Details</h3>
                        <div style="display: grid; gap: 1rem;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div>
                                    <h4>Reservation Information</h4>
                                    <div><strong>ID:</strong> #${data.id}</div>
                                    <div><strong>Status:</strong> <span style="background-color: ${statusColor[data.status]}; color: white; padding: 0.25rem 0.5rem; border-radius: 12px;">${data.status}</span></div>
                                    <div><strong>Created:</strong> ${new Date(data.created_at).toLocaleString()}</div>
                                    <div><strong>Pickup Date:</strong> ${new Date(data.pickup_date).toLocaleDateString()}</div>
                                    <div><strong>Return Date:</strong> ${new Date(data.return_date).toLocaleDateString()}</div>
                                    <div><strong>Total Amount:</strong> ${formatPrice(data.total_amount)}</div>
                                </div>
                                <div>
                                    <h4>Customer Information</h4>
                                    <div><strong>Name:</strong> ${data.first_name} ${data.last_name}</div>
                                    <div><strong>Email:</strong> ${data.email}</div>
                                    <div><strong>Phone:</strong> ${data.phone || 'Not provided'}</div>
                                    <div><strong>Address:</strong> ${data.address || 'Not provided'}</div>
                                </div>
                            </div>
                            <div>
                                <h4>Car Information</h4>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    ${data.image1 ? `<img src="../uploads/${data.image1}" alt="${data.brand} ${data.model}" style="width: 100px; height: 75px; object-fit: cover; border-radius: var(--border-radius);">` : ''}
                                    <div>
                                        <div><strong>Brand:</strong> ${data.brand}</div>
                                        <div><strong>Model:</strong> ${data.model}</div>
                                        <div><strong>Year:</strong> ${data.year}</div>
                                        <div><strong>Fuel Type:</strong> ${data.fuel_type}</div>
                                        <div><strong>Transmission:</strong> ${data.transmission}</div>
                                        <div><strong>Seats:</strong> ${data.seats}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    document.getElementById('reservationModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to load reservation details', 'error');
                });
        }

        // Close modal
        function closeModal() {
            document.getElementById('reservationModal').style.display = 'none';
        }

        // Export reservations
        function exportReservations() {
            window.location.href = 'reservations.php?action=export';
        }

        // Format price helper
        function formatPrice(price) {
            return '$' + parseFloat(price).toFixed(2);
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('reservationModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    <script src="../js/script.js"></script>
</body>
</html>

<?php
// Handle AJAX requests for reservation details
if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id'])) {
    $reservation_id = (int)$_GET['id'];
    
    $sql = "SELECT r.*, u.first_name, u.last_name, u.email, u.phone, u.address, c.brand, c.model, c.year, c.fuel_type, c.transmission, c.seats, c.image1 
            FROM reservations r 
            JOIN users u ON r.user_id = u.id 
            JOIN cars c ON r.car_id = c.id 
            WHERE r.id = ?";
    
    $reservation = fetchOne($sql, [$reservation_id]);
    
    if ($reservation) {
        header('Content-Type: application/json');
        echo json_encode($reservation);
    }
    exit;
}

// Handle export functionality
if (isset($_GET['action']) && $_GET['action'] == 'export') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="reservations_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV header
    fputcsv($output, ['ID', 'Customer', 'Email', 'Car', 'Pickup Date', 'Return Date', 'Total Amount', 'Status', 'Created Date']);
    
    // Get all reservations
    $sql = "SELECT r.*, u.first_name, u.last_name, u.email, c.brand, c.model 
            FROM reservations r 
            JOIN users u ON r.user_id = u.id 
            JOIN cars c ON r.car_id = c.id 
            ORDER BY r.created_at DESC";
    
    $all_reservations = fetchAll($sql);
    
    foreach ($all_reservations as $reservation) {
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
