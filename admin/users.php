<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    // Check if user has reservations
    $has_reservations = fetchOne("SELECT COUNT(*) as count FROM reservations WHERE user_id = ?", [$user_id])['count'];
    
    if ($has_reservations > 0) {
        showAlert('Cannot delete user with existing reservations', 'error');
    } else {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = executeQuery($sql, [$user_id]);
        
        if ($stmt) {
            showAlert('User deleted successfully', 'success');
        } else {
            showAlert('Failed to delete user', 'error');
        }
    }
    
    redirect('users.php');
}

// Get search and pagination parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Build WHERE clause
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get total users count
$count_sql = "SELECT COUNT(*) as total FROM users $where";
$total_result = fetchOne($count_sql, $params);
$total_users = $total_result['total'];

// Get pagination info
$pagination = getPagination($total_users, $per_page, $page);

// Get users for current page
$sql = "SELECT * FROM users $where ORDER BY created_at DESC LIMIT {$pagination['offset']}, $per_page";
$users = fetchAll($sql, $params);

// Get alert message
$alert = getAlert();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
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
            <li><a href="users.php" class="active">👥 Users</a></li>
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
                    <h1>Manage Users</h1>
                    <p>Total Users: <?php echo $total_users; ?></p>
                </div>
                <div>
                    <button onclick="exportUsers()" class="btn btn-success">📥 Export Users</button>
                </div>
            </div>

            <!-- Alert Message -->
            <?php if ($alert): ?>
                <div class="alert alert-<?php echo $alert['type']; ?>">
                    <?php echo $alert['message']; ?>
                </div>
            <?php endif; ?>

            <!-- Search Form -->
            <div class="card" style="margin-bottom: 2rem;">
                <div class="card-body">
                    <form method="GET">
                        <div style="display: flex; gap: 1rem; align-items: center;">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search by name or email..." 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   style="flex: 1;">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <?php if ($search): ?>
                                <a href="users.php" class="btn btn-outline">Clear</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header">
                    <h3>Users List</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <div class="text-center" style="padding: 3rem;">
                            <h4>No users found</h4>
                            <p><?php echo $search ? 'Try adjusting your search criteria.' : 'No users have registered yet.'; ?></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Address</th>
                                        <th>Joined</th>
                                        <th>Reservations</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <?php 
                                        $reservation_count = fetchOne("SELECT COUNT(*) as count FROM reservations WHERE user_id = ?", [$user['id']])['count'];
                                        $initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
                                        ?>
                                        <tr>
                                            <td>#<?php echo $user['id']; ?></td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                                    <div class="user-avatar"><?php echo $initials; ?></div>
                                                    <div>
                                                        <strong><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo $user['email']; ?></td>
                                            <td><?php echo $user['phone'] ?: 'Not provided'; ?></td>
                                            <td><?php echo $user['address'] ? substr($user['address'], 0, 30) . '...' : 'Not provided'; ?></td>
                                            <td><?php echo formatDate($user['created_at']); ?></td>
                                            <td>
                                                <span class="badge" style="background-color: var(--info-color); color: white; padding: 0.25rem 0.5rem; border-radius: 12px;">
                                                    <?php echo $reservation_count; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div style="display: flex; gap: 0.5rem;">
                                                    <a href="javascript:void(0)" onclick="viewUser(<?php echo $user['id']; ?>)" 
                                                       class="btn btn-sm btn-outline">View</a>
                                                    <?php if ($reservation_count == 0): ?>
                                                        <a href="users.php?delete=<?php echo $user['id']; ?>" 
                                                           class="btn btn-sm btn-danger" 
                                                           onclick="return confirm('Are you sure you want to delete this user?')">
                                                            Delete
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-danger" disabled title="User has reservations">
                                                            Delete
                                                        </button>
                                                    <?php endif; ?>
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

    <!-- User Details Modal -->
    <div id="userModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000;">
        <div class="modal-content" style="position: relative; background-color: white; margin: 5% auto; padding: 2rem; width: 90%; max-width: 600px; border-radius: var(--border-radius);">
            <span class="close" onclick="closeModal()" style="position: absolute; top: 1rem; right: 1rem; font-size: 2rem; cursor: pointer;">&times;</span>
            <div id="userDetails"></div>
        </div>
    </div>

    <script>
        // Toggle sidebar for mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // View user details
        function viewUser(userId) {
            fetch(`users.php?action=view&id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('userDetails').innerHTML = `
                        <h3>User Details</h3>
                        <div style="display: grid; gap: 1rem;">
                            <div><strong>ID:</strong> #${data.id}</div>
                            <div><strong>Name:</strong> ${data.first_name} ${data.last_name}</div>
                            <div><strong>Email:</strong> ${data.email}</div>
                            <div><strong>Phone:</strong> ${data.phone || 'Not provided'}</div>
                            <div><strong>Address:</strong> ${data.address || 'Not provided'}</div>
                            <div><strong>Member Since:</strong> ${new Date(data.created_at).toLocaleDateString()}</div>
                            <div><strong>Total Reservations:</strong> ${data.reservation_count}</div>
                        </div>
                    `;
                    document.getElementById('userModal').style.display = 'block';
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to load user details', 'error');
                });
        }

        // Close modal
        function closeModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        // Export users
        function exportUsers() {
            window.location.href = 'users.php?action=export';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
    <script src="../js/script.js"></script>
</body>
</html>

<?php
// Handle AJAX requests for user details
if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    $user = fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);
    
    if ($user) {
        $reservation_count = fetchOne("SELECT COUNT(*) as count FROM reservations WHERE user_id = ?", [$user_id])['count'];
        $user['reservation_count'] = $reservation_count;
        
        header('Content-Type: application/json');
        echo json_encode($user);
    }
    exit;
}

// Handle export functionality
if (isset($_GET['action']) && $_GET['action'] == 'export') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="users_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV header
    fputcsv($output, ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Address', 'Joined Date']);
    
    // Get all users
    $all_users = fetchAll("SELECT * FROM users ORDER BY created_at DESC");
    
    foreach ($all_users as $user) {
        fputcsv($output, [
            $user['id'],
            $user['first_name'],
            $user['last_name'],
            $user['email'],
            $user['phone'],
            $user['address'],
            $user['created_at']
        ]);
    }
    
    fclose($output);
    exit;
}
?>
