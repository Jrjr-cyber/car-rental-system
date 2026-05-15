<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isAdmin()) {
    redirect('../auth/login.php');
}

// Handle car deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $car_id = (int)$_GET['delete'];
    
    // Get car images before deletion
    $car = fetchOne("SELECT image1, image2, image3 FROM cars WHERE id = ?", [$car_id]);
    
    if ($car) {
        // Check if car has reservations
        $has_reservations = fetchOne("SELECT COUNT(*) as count FROM reservations WHERE car_id = ?", [$car_id])['count'];
        
        if ($has_reservations > 0) {
            showAlert('Cannot delete car with existing reservations', 'error');
        } else {
            // Delete images
            if ($car['image1']) deleteFile($car['image1']);
            if ($car['image2']) deleteFile($car['image2']);
            if ($car['image3']) deleteFile($car['image3']);
            
            // Delete car from database
            $sql = "DELETE FROM cars WHERE id = ?";
            $stmt = executeQuery($sql, [$car_id]);
            
            if ($stmt) {
                showAlert('Car deleted successfully', 'success');
            } else {
                showAlert('Failed to delete car', 'error');
            }
        }
    }
    
    redirect('cars.php');
}

// Handle car addition/editing
$car_success = '';
$car_errors = [];
$editing_car = null;

if (isset($_GET['action'])) {
    if ($_GET['action'] == 'add' || $_GET['action'] == 'edit') {
        if ($_GET['action'] == 'edit' && isset($_GET['id'])) {
            $editing_car = fetchOne("SELECT * FROM cars WHERE id = ?", [(int)$_GET['id']]);
            if (!$editing_car) {
                showAlert('Car not found', 'error');
                redirect('cars.php');
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $brand = sanitizeInput($_POST['brand']);
            $model = sanitizeInput($_POST['model']);
            $year = (int)$_POST['year'];
            $price_per_day = (float)$_POST['price_per_day'];
            $fuel_type = $_POST['fuel_type'];
            $transmission = $_POST['transmission'];
            $seats = (int)$_POST['seats'];
            $description = sanitizeInput($_POST['description']);
            $available = isset($_POST['available']) ? 1 : 0;
            
            // Validation
            if (empty($brand)) $car_errors[] = 'Brand is required';
            if (empty($model)) $car_errors[] = 'Model is required';
            if (empty($year) || $year < 1900 || $year > date('Y') + 1) $car_errors[] = 'Invalid year';
            if (empty($price_per_day) || $price_per_day <= 0) $car_errors[] = 'Price must be greater than 0';
            if (empty($seats) || $seats < 1 || $seats > 10) $car_errors[] = 'Seats must be between 1 and 10';
            
            // Handle image uploads
            $image1 = $editing_car['image1'] ?? '';
            $image2 = $editing_car['image2'] ?? '';
            $image3 = $editing_car['image3'] ?? '';
            
            if (isset($_FILES['image1']) && $_FILES['image1']['error'] == 0) {
                $upload_result = uploadFile($_FILES['image1'], 'cars');
                if ($upload_result['success']) {
                    if ($image1) deleteFile($image1);
                    $image1 = $upload_result['filename'];
                } else {
                    $car_errors[] = 'Image 1: ' . $upload_result['message'];
                }
            }
            
            if (isset($_FILES['image2']) && $_FILES['image2']['error'] == 0) {
                $upload_result = uploadFile($_FILES['image2'], 'cars');
                if ($upload_result['success']) {
                    if ($image2) deleteFile($image2);
                    $image2 = $upload_result['filename'];
                } else {
                    $car_errors[] = 'Image 2: ' . $upload_result['message'];
                }
            }
            
            if (isset($_FILES['image3']) && $_FILES['image3']['error'] == 0) {
                $upload_result = uploadFile($_FILES['image3'], 'cars');
                if ($upload_result['success']) {
                    if ($image3) deleteFile($image3);
                    $image3 = $upload_result['filename'];
                } else {
                    $car_errors[] = 'Image 3: ' . $upload_result['message'];
                }
            }
            
            // Save car if no errors
            if (empty($car_errors)) {
                if ($_GET['action'] == 'add') {
                    $sql = "INSERT INTO cars (brand, model, year, price_per_day, fuel_type, transmission, seats, description, image1, image2, image3, available) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $params = [$brand, $model, $year, $price_per_day, $fuel_type, $transmission, $seats, $description, $image1, $image2, $image3, $available];
                    $message = 'Car added successfully!';
                } else {
                    $sql = "UPDATE cars SET brand = ?, model = ?, year = ?, price_per_day = ?, fuel_type = ?, transmission = ?, seats = ?, description = ?, image1 = ?, image2 = ?, image3 = ?, available = ? WHERE id = ?";
                    $params = [$brand, $model, $year, $price_per_day, $fuel_type, $transmission, $seats, $description, $image1, $image2, $image3, $available, $editing_car['id']];
                    $message = 'Car updated successfully!';
                }
                
                $stmt = executeQuery($sql, $params);
                
                if ($stmt) {
                    showAlert($message, 'success');
                    redirect('cars.php');
                } else {
                    $car_errors[] = 'Failed to save car. Please try again.';
                }
            }
        }
    }
}

// Get search and pagination parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Build WHERE clause
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (brand LIKE ? OR model LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get total cars count
$count_sql = "SELECT COUNT(*) as total FROM cars $where";
$total_result = fetchOne($count_sql, $params);
$total_cars = $total_result['total'];

// Get pagination info
$pagination = getPagination($total_cars, $per_page, $page);

// Get cars for current page
$sql = "SELECT * FROM cars $where ORDER BY created_at DESC LIMIT {$pagination['offset']}, $per_page";
$cars = fetchAll($sql, $params);

// Get alert message
$alert = getAlert();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cars - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .car-image-preview {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--border-radius);
        }
        
        .image-upload-preview {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .preview-item {
            position: relative;
        }
        
        .preview-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: var(--border-radius);
        }
        
        .preview-item .remove-btn {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background: var(--danger-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .availability-toggle {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .availability-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: var(--success-color);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
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
            <li><a href="cars.php" class="active">🚗 Cars</a></li>
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
                    <h1>Manage Cars</h1>
                    <p>Total Cars: <?php echo $total_cars; ?></p>
                </div>
                <div>
                    <a href="cars.php?action=add" class="btn btn-primary">➕ Add New Car</a>
                </div>
            </div>

            <!-- Alert Message -->
            <?php if ($alert): ?>
                <div class="alert alert-<?php echo $alert['type']; ?>">
                    <?php echo $alert['message']; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['action']) && ($_GET['action'] == 'add' || $_GET['action'] == 'edit')): ?>
                <!-- Add/Edit Car Form -->
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo $_GET['action'] == 'add' ? 'Add New Car' : 'Edit Car'; ?></h3>
                        <a href="cars.php" class="btn btn-outline">← Back to Cars</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($car_errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($car_errors as $error): ?>
                                    <p><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data" data-validate>
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Brand *</label>
                                    <input type="text" name="brand" class="form-control" 
                                           value="<?php echo $editing_car['brand'] ?? ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Model *</label>
                                    <input type="text" name="model" class="form-control" 
                                           value="<?php echo $editing_car['model'] ?? ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Year *</label>
                                    <input type="number" name="year" class="form-control" 
                                           value="<?php echo $editing_car['year'] ?? date('Y'); ?>" 
                                           min="1900" max="<?php echo date('Y') + 1; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Price per Day ($) *</label>
                                    <input type="number" name="price_per_day" class="form-control" 
                                           value="<?php echo $editing_car['price_per_day'] ?? ''; ?>" 
                                           step="0.01" min="0" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Fuel Type *</label>
                                    <select name="fuel_type" class="form-control" required>
                                        <option value="">Select Fuel Type</option>
                                        <option value="Petrol" <?php echo ($editing_car['fuel_type'] ?? '') == 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                                        <option value="Diesel" <?php echo ($editing_car['fuel_type'] ?? '') == 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                                        <option value="Electric" <?php echo ($editing_car['fuel_type'] ?? '') == 'Electric' ? 'selected' : ''; ?>>Electric</option>
                                        <option value="Hybrid" <?php echo ($editing_car['fuel_type'] ?? '') == 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Transmission *</label>
                                    <select name="transmission" class="form-control" required>
                                        <option value="">Select Transmission</option>
                                        <option value="Manual" <?php echo ($editing_car['transmission'] ?? '') == 'Manual' ? 'selected' : ''; ?>>Manual</option>
                                        <option value="Automatic" <?php echo ($editing_car['transmission'] ?? '') == 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Number of Seats *</label>
                                    <input type="number" name="seats" class="form-control" 
                                           value="<?php echo $editing_car['seats'] ?? 5; ?>" 
                                           min="1" max="10" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Availability</label>
                                    <label class="availability-toggle">
                                        <input type="checkbox" name="available" <?php echo ($editing_car['available'] ?? 1) ? 'checked' : ''; ?>>
                                        <span class="slider"></span>
                                    </label>
                                    <small style="display: block; margin-top: 0.5rem;">Car is available for rental</small>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"><?php echo $editing_car['description'] ?? ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Car Images</label>
                                <small>Upload up to 3 images. First image will be the main display image.</small>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="form-label">Image 1 (Main)</label>
                                        <input type="file" name="image1" class="form-control" accept="image/*" 
                                               onchange="previewImage(this, 'preview1')">
                                        <?php if ($editing_car && $editing_car['image1']): ?>
                                            <div id="preview1" class="preview-item" style="margin-top: 0.5rem;">
                                                <img src="../uploads/<?php echo $editing_car['image1']; ?>" alt="Current image 1">
                                            </div>
                                        <?php else: ?>
                                            <div id="preview1"></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Image 2</label>
                                        <input type="file" name="image2" class="form-control" accept="image/*" 
                                               onchange="previewImage(this, 'preview2')">
                                        <?php if ($editing_car && $editing_car['image2']): ?>
                                            <div id="preview2" class="preview-item" style="margin-top: 0.5rem;">
                                                <img src="../uploads/<?php echo $editing_car['image2']; ?>" alt="Current image 2">
                                            </div>
                                        <?php else: ?>
                                            <div id="preview2"></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Image 3</label>
                                        <input type="file" name="image3" class="form-control" accept="image/*" 
                                               onchange="previewImage(this, 'preview3')">
                                        <?php if ($editing_car && $editing_car['image3']): ?>
                                            <div id="preview3" class="preview-item" style="margin-top: 0.5rem;">
                                                <img src="../uploads/<?php echo $editing_car['image3']; ?>" alt="Current image 3">
                                            </div>
                                        <?php else: ?>
                                            <div id="preview3"></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 1rem;">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $_GET['action'] == 'add' ? 'Add Car' : 'Update Car'; ?>
                                </button>
                                <a href="cars.php" class="btn btn-outline">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- Search Form -->
                <div class="card" style="margin-bottom: 2rem;">
                    <div class="card-body">
                        <form method="GET">
                            <div style="display: flex; gap: 1rem; align-items: center;">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by brand or model..." 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       style="flex: 1;">
                                <button type="submit" class="btn btn-primary">Search</button>
                                <?php if ($search): ?>
                                    <a href="cars.php" class="btn btn-outline">Clear</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Cars Table -->
                <div class="card">
                    <div class="card-header">
                        <h3>Cars List</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($cars)): ?>
                            <div class="text-center" style="padding: 3rem;">
                                <h4>No cars found</h4>
                                <p><?php echo $search ? 'Try adjusting your search criteria.' : 'No cars have been added yet.'; ?></p>
                                <a href="cars.php?action=add" class="btn btn-primary">Add Your First Car</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Image</th>
                                            <th>Car Details</th>
                                            <th>Price/Day</th>
                                            <th>Specs</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cars as $car): ?>
                                            <tr>
                                                <td>#<?php echo $car['id']; ?></td>
                                                <td>
                                                    <?php if ($car['image1']): ?>
                                                        <img src="../uploads/<?php echo $car['image1']; ?>" 
                                                             alt="<?php echo $car['brand'] . ' ' . $car['model']; ?>"
                                                             class="car-image-preview">
                                                    <?php else: ?>
                                                        <div style="width: 80px; height: 60px; background-color: #f0f0f0; border-radius: var(--border-radius); display: flex; align-items: center; justify-content: center; color: var(--gray);">
                                                            No Image
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo $car['brand'] . ' ' . $car['model']; ?></strong>
                                                    <br>
                                                    <small style="color: var(--gray);"><?php echo $car['year']; ?></small>
                                                </td>
                                                <td><?php echo formatPrice($car['price_per_day']); ?></td>
                                                <td>
                                                    <small>
                                                        ⛽ <?php echo $car['fuel_type']; ?><br>
                                                        ⚙️ <?php echo $car['transmission']; ?><br>
                                                        👥 <?php echo $car['seats']; ?> seats
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($car['available']): ?>
                                                        <span class="badge" style="background-color: var(--success-color); color: white;">Available</span>
                                                    <?php else: ?>
                                                        <span class="badge" style="background-color: var(--danger-color); color: white;">Unavailable</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div style="display: flex; gap: 0.5rem;">
                                                        <a href="cars.php?action=edit&id=<?php echo $car['id']; ?>" 
                                                           class="btn btn-sm btn-outline">Edit</a>
                                                        <a href="cars.php?delete=<?php echo $car['id']; ?>" 
                                                           class="btn btn-sm btn-danger" 
                                                           onclick="return confirm('Are you sure you want to delete this car?')">
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
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Toggle sidebar for mobile
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Preview image before upload
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 150px; object-fit: cover; border-radius: var(--border-radius);">`;
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
    <script src="../js/script.js"></script>
</body>
</html>
