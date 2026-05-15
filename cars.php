<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Get filter parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$brand = isset($_GET['brand']) ? sanitizeInput($_GET['brand']) : '';
$price_range = isset($_GET['price_range']) ? sanitizeInput($_GET['price_range']) : '';
$fuel_type = isset($_GET['fuel_type']) ? sanitizeInput($_GET['fuel_type']) : '';
$transmission = isset($_GET['transmission']) ? sanitizeInput($_GET['transmission']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 9;

// Build WHERE clause
$where = "WHERE available = 1";
$params = [];

if ($search) {
    $where .= " AND (brand LIKE ? OR model LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($brand) {
    $where .= " AND brand = ?";
    $params[] = $brand;
}

if ($price_range) {
    list($min_price, $max_price) = explode('-', $price_range);
    $where .= " AND price_per_day BETWEEN ? AND ?";
    $params[] = $min_price;
    $params[] = $max_price;
}

if ($fuel_type) {
    $where .= " AND fuel_type = ?";
    $params[] = $fuel_type;
}

if ($transmission) {
    $where .= " AND transmission = ?";
    $params[] = $transmission;
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

// Get unique brands for filter
$brands = fetchAll("SELECT DISTINCT brand FROM cars WHERE available = 1 ORDER BY brand");

// Get alert message
$alert = getAlert();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cars Catalog - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="logo"><?php echo SITE_NAME; ?></div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="cars.php" class="active">Cars</a></li>
                <li><a href="index.php#about">About</a></li>
                <li><a href="index.php#contact">Contact</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="account/index.php">My Account</a></li>
                    <li><a href="auth/logout.php" class="btn btn-outline">Logout</a></li>
                <?php else: ?>
                    <li><a href="auth/login.php">Login</a></li>
                    <li><a href="auth/register.php" class="btn btn-primary">Register</a></li>
                <?php endif; ?>
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

    <!-- Page Header -->
    <section class="section" style="background-color: var(--primary-color); color: white; padding: 3rem 0;">
        <div class="container text-center">
            <h1>Our Cars</h1>
            <p>Choose from our wide selection of premium vehicles</p>
        </div>
    </section>

    <!-- Search and Filters -->
    <section class="section" style="background-color: #f8f9fa; padding: 2rem 0;">
        <div class="container">
            <form method="GET" class="card">
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" id="searchInput" class="form-control" 
                                   placeholder="Search by brand or model..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Brand</label>
                            <select name="brand" id="brandFilter" class="form-control" onchange="this.form.submit()">
                                <option value="">All Brands</option>
                                <?php foreach ($brands as $b): ?>
                                    <option value="<?php echo $b['brand']; ?>" <?php echo $brand === $b['brand'] ? 'selected' : ''; ?>>
                                        <?php echo $b['brand']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Price Range</label>
                            <select name="price_range" id="priceRange" class="form-control" onchange="this.form.submit()">
                                <option value="">All Prices</option>
                                <option value="0-50" <?php echo $price_range === '0-50' ? 'selected' : ''; ?>>Under $50/day</option>
                                <option value="50-100" <?php echo $price_range === '50-100' ? 'selected' : ''; ?>>$50 - $100/day</option>
                                <option value="100-150" <?php echo $price_range === '100-150' ? 'selected' : ''; ?>>$100 - $150/day</option>
                                <option value="150-999" <?php echo $price_range === '150-999' ? 'selected' : ''; ?>>$150+/day</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fuel Type</label>
                            <select name="fuel_type" id="fuelTypeFilter" class="form-control" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                <option value="Petrol" <?php echo $fuel_type === 'Petrol' ? 'selected' : ''; ?>>Petrol</option>
                                <option value="Diesel" <?php echo $fuel_type === 'Diesel' ? 'selected' : ''; ?>>Diesel</option>
                                <option value="Electric" <?php echo $fuel_type === 'Electric' ? 'selected' : ''; ?>>Electric</option>
                                <option value="Hybrid" <?php echo $fuel_type === 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Transmission</label>
                            <select name="transmission" id="transmissionFilter" class="form-control" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                <option value="Manual" <?php echo $transmission === 'Manual' ? 'selected' : ''; ?>>Manual</option>
                                <option value="Automatic" <?php echo $transmission === 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                            </select>
                        </div>
                    </div>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <button type="submit" class="btn btn-primary">Search Cars</button>
                        <a href="cars.php" class="btn btn-outline">Clear Filters</a>
                        <span style="margin-left: auto; color: var(--gray);">
                            Found <?php echo $total_cars; ?> cars
                        </span>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Cars Grid -->
    <section class="section">
        <div class="container">
            <?php if (empty($cars)): ?>
                <div class="text-center">
                    <h3>No cars found</h3>
                    <p>Try adjusting your search criteria or browse all available cars.</p>
                    <a href="cars.php" class="btn btn-primary">View All Cars</a>
                </div>
            <?php else: ?>
                <div class="row row-cols-3">
                    <?php foreach ($cars as $car): ?>
                        <div class="car-card">
                            <img src="uploads/<?php echo $car['image1']; ?>" alt="<?php echo $car['brand'] . ' ' . $car['model']; ?>">
                            <div class="card-body">
                                <h3><?php echo $car['brand'] . ' ' . $car['model']; ?></h3>
                                <div class="price"><?php echo formatPrice($car['price_per_day']); ?>/day</div>
                                <div class="specs">
                                    <div class="spec-item">
                                        <span>⛽</span>
                                        <span><?php echo $car['fuel_type']; ?></span>
                                    </div>
                                    <div class="spec-item">
                                        <span>⚙️</span>
                                        <span><?php echo $car['transmission']; ?></span>
                                    </div>
                                    <div class="spec-item">
                                        <span>👥</span>
                                        <span><?php echo $car['seats']; ?> Seats</span>
                                    </div>
                                </div>
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="car-details.php?id=<?php echo $car['id']; ?>" class="btn btn-primary" style="flex: 1;">
                                        View Details
                                    </a>
                                    <?php if (isLoggedIn()): ?>
                                        <a href="car-details.php?id=<?php echo $car['id']; ?>#booking" class="btn btn-success">
                                            Book Now
                                        </a>
                                    <?php else: ?>
                                        <a href="auth/login.php" class="btn btn-success">
                                            Book Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="pagination" style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 3rem;">
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
    </section>

    <!-- Footer -->
    <footer class="footer" style="background-color: #343a40; color: white; padding: 2rem 0;">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Theme Toggle Button -->
    <button id="themeToggle" class="theme-toggle" onclick="toggleTheme()">🌙</button>

    <script src="js/script.js"></script>
</body>
</html>
