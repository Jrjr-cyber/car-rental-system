<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
    redirect('../auth/login.php');
}

// Get user information
$user_id = $_SESSION['user_id'];
$user = fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);

// Get user reservations
$reservations = fetchAll("SELECT r.*, c.brand, c.model, c.image1 
                         FROM reservations r 
                         JOIN cars c ON r.car_id = c.id 
                         WHERE r.user_id = ? 
                         ORDER BY r.created_at DESC", [$user_id]);

// Handle profile update
$update_success = '';
$update_errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    
    // Validation
    if (empty($first_name)) {
        $update_errors[] = 'First name is required';
    }
    
    if (empty($last_name)) {
        $update_errors[] = 'Last name is required';
    }
    
    // Update profile if no errors
    if (empty($update_errors)) {
        $sql = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ? WHERE id = ?";
        $params = [$first_name, $last_name, $phone, $address, $user_id];
        
        $stmt = executeQuery($sql, $params);
        
        if ($stmt) {
            $update_success = 'Profile updated successfully!';
            // Update session data
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            // Refresh user data
            $user = fetchOne("SELECT * FROM users WHERE id = ?", [$user_id]);
        } else {
            $update_errors[] = 'Failed to update profile. Please try again.';
        }
    }
}

// Handle password change
$password_success = '';
$password_errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($current_password)) {
        $password_errors[] = 'Current password is required';
    }
    
    if (empty($new_password)) {
        $password_errors[] = 'New password is required';
    } elseif (strlen($new_password) < 6) {
        $password_errors[] = 'New password must be at least 6 characters long';
    }
    
    if ($new_password !== $confirm_password) {
        $password_errors[] = 'Passwords do not match';
    }
    
    // Verify current password
    if (empty($password_errors) && !verifyPassword($current_password, $user['password'])) {
        $password_errors[] = 'Current password is incorrect';
    }
    
    // Update password if no errors
    if (empty($password_errors)) {
        $hashed_password = hashPassword($new_password);
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $params = [$hashed_password, $user_id];
        
        $stmt = executeQuery($sql, $params);
        
        if ($stmt) {
            $password_success = 'Password changed successfully!';
        } else {
            $password_errors[] = 'Failed to change password. Please try again.';
        }
    }
}

// Handle reservation cancellation
if (isset($_GET['cancel_reservation']) && is_numeric($_GET['cancel_reservation'])) {
    $reservation_id = (int)$_GET['cancel_reservation'];
    
    // Check if reservation belongs to user and is pending
    $reservation = fetchOne("SELECT * FROM reservations WHERE id = ? AND user_id = ? AND status = 'Pending'", 
                           [$reservation_id, $user_id]);
    
    if ($reservation) {
        $sql = "UPDATE reservations SET status = 'Cancelled' WHERE id = ?";
        $stmt = executeQuery($sql, [$reservation_id]);
        
        if ($stmt) {
            showAlert('Reservation cancelled successfully', 'success');
        } else {
            showAlert('Failed to cancel reservation', 'error');
        }
    }
    
    redirect('index.php');
}

// Get alert message
$alert = getAlert();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .dashboard-tabs {
            display: flex;
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 2rem;
        }
        
        .tab-button {
            padding: 1rem 2rem;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            color: var(--gray);
            border-bottom: 3px solid transparent;
            transition: var(--transition);
        }
        
        .tab-button.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .reservation-card {
            border: 1px solid #dee2e6;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: var(--transition);
        }
        
        .reservation-card:hover {
            box-shadow: var(--box-shadow);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-completed {
            background-color: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="logo"><?php echo SITE_NAME; ?></div>
            <ul class="nav-links">
                <li><a href="../index.php">Home</a></li>
                <li><a href="../cars.php">Cars</a></li>
                <li><a href="../index.php#about">About</a></li>
                <li><a href="../index.php#contact">Contact</a></li>
                <li><a href="index.php" class="active">My Account</a></li>
                <li><a href="../auth/logout.php" class="btn btn-outline">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Alert Message -->
    <?php if ($alert): ?>
        <div class="alert alert-<?php echo $alert['type']; ?>" style="margin: 0; border-radius: 0;">
            <div class="container">
                <?php echo $alert['message']; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Dashboard Header -->
    <section class="section" style="background-color: var(--primary-color); color: white; padding: 3rem 0;">
        <div class="container">
            <h1>My Account</h1>
            <p>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
        </div>
    </section>

    <!-- Dashboard Content -->
    <section class="section">
        <div class="container">
            <div class="row" style="align-items: start;">
                <!-- Sidebar -->
                <div style="flex: 1; min-width: 250px;">
                    <div class="card">
                        <div class="card-header text-center">
                            <div style="width: 80px; height: 80px; background-color: var(--primary-color); color: white; 
                                        border-radius: 50%; display: flex; align-items: center; justify-content: center; 
                                        margin: 0 auto 1rem; font-size: 2rem;">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </div>
                            <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                            <p style="color: var(--gray);"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        <div class="card-body">
                            <div style="text-align: left;">
                                <p><strong>Member Since:</strong><br><?php echo formatDate($user['created_at']); ?></p>
                                <p><strong>Total Reservations:</strong><br><?php echo count($reservations); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Main Content -->
                <div style="flex: 3; margin-left: 2rem;">
                    <!-- Tabs -->
                    <div class="dashboard-tabs">
                        <button class="tab-button active" onclick="showTab('reservations')">Reservations</button>
                        <button class="tab-button" onclick="showTab('profile')">Profile</button>
                        <button class="tab-button" onclick="showTab('security')">Security</button>
                    </div>
                    
                    <!-- Reservations Tab -->
                    <div id="reservations" class="tab-content active">
                        <h3>My Reservations</h3>
                        
                        <?php if (empty($reservations)): ?>
                            <div class="text-center" style="padding: 3rem;">
                                <h4>No reservations yet</h4>
                                <p>Start by browsing our available cars and making your first reservation.</p>
                                <a href="../cars.php" class="btn btn-primary">Browse Cars</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($reservations as $reservation): ?>
                                <div class="reservation-card">
                                    <div class="row" style="align-items: center;">
                                        <div style="flex: 1;">
                                            <img src="../uploads/<?php echo $reservation['image1']; ?>" 
                                                 alt="<?php echo $reservation['brand'] . ' ' . $reservation['model']; ?>"
                                                 style="width: 120px; height: 80px; object-fit: cover; border-radius: var(--border-radius);">
                                        </div>
                                        <div style="flex: 2;">
                                            <h4><?php echo $reservation['brand'] . ' ' . $reservation['model']; ?></h4>
                                            <p>Pickup: <?php echo formatDate($reservation['pickup_date']); ?></p>
                                            <p>Return: <?php echo formatDate($reservation['return_date']); ?></p>
                                            <p><strong>Total:</strong> <?php echo formatPrice($reservation['total_amount']); ?></p>
                                        </div>
                                        <div style="flex: 1; text-align: center;">
                                            <span class="status-badge status-<?php echo strtolower($reservation['status']); ?>">
                                                <?php echo $reservation['status']; ?>
                                            </span>
                                            <div style="margin-top: 1rem;">
                                                <?php if ($reservation['status'] == 'Pending'): ?>
                                                    <a href="?cancel_reservation=<?php echo $reservation['id']; ?>" 
                                                       class="btn btn-danger btn-sm" 
                                                       onclick="return confirm('Are you sure you want to cancel this reservation?')">
                                                        Cancel
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Profile Tab -->
                    <div id="profile" class="tab-content">
                        <h3>Profile Information</h3>
                        
                        <?php if ($update_success): ?>
                            <div class="alert alert-success">
                                <?php echo $update_success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($update_errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($update_errors as $error): ?>
                                    <p><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" data-validate>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="first_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" 
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                <small>Email address cannot be changed</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                Update Profile
                            </button>
                        </form>
                    </div>
                    
                    <!-- Security Tab -->
                    <div id="security" class="tab-content">
                        <h3>Change Password</h3>
                        
                        <?php if ($password_success): ?>
                            <div class="alert alert-success">
                                <?php echo $password_success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($password_errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($password_errors as $error): ?>
                                    <p><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" data-validate>
                            <div class="form-group">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required>
                                <small>Password must be at least 6 characters long</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary">
                                Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" style="background-color: #343a40; color: white; padding: 2rem 0;">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Theme Toggle Button -->
    <button id="themeToggle" class="theme-toggle" onclick="toggleTheme()">🌙</button>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
    </script>
    <script src="../js/script.js"></script>
</body>
</html>
