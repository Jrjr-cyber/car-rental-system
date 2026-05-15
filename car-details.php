<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Check if car ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('cars.php');
}

$car_id = (int)$_GET['id'];

// Get car details
$car = fetchOne("SELECT * FROM cars WHERE id = ? AND available = 1", [$car_id]);

if (!$car) {
    showAlert('Car not found or not available', 'error');
    redirect('cars.php');
}

// Get similar cars
$similar_cars = fetchAll("SELECT * FROM cars WHERE id != ? AND brand = ? AND available = 1 LIMIT 3", 
                        [$car_id, $car['brand']]);

// Handle reservation form submission
$reservation_success = '';
$reservation_errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['make_reservation'])) {
    // Check if user is logged in
    if (!isLoggedIn()) {
        $_SESSION['redirect'] = $_SERVER['REQUEST_URI'];
        redirect('auth/login.php');
    }
    
    $pickup_date = $_POST['pickup_date'];
    $return_date = $_POST['return_date'];
    $total_amount = $_POST['total_amount'];
    
    // Validation
    if (empty($pickup_date)) {
        $reservation_errors[] = 'Pickup date is required';
    }
    
    if (empty($return_date)) {
        $reservation_errors[] = 'Return date is required';
    }
    
    if (strtotime($pickup_date) < strtotime(date('Y-m-d'))) {
        $reservation_errors[] = 'Pickup date cannot be in the past';
    }
    
    if (strtotime($return_date) <= strtotime($pickup_date)) {
        $reservation_errors[] = 'Return date must be after pickup date';
    }
    
    // Check car availability
    if (empty($reservation_errors)) {
        if (!isCarAvailable($car_id, $pickup_date, $return_date)) {
            $reservation_errors[] = 'Car is not available for the selected dates';
        }
    }
    
    // Create reservation if no errors
    if (empty($reservation_errors)) {
        $sql = "INSERT INTO reservations (user_id, car_id, pickup_date, return_date, total_amount, status) 
                VALUES (?, ?, ?, ?, ?, 'Pending')";
        
        $params = [
            $_SESSION['user_id'],
            $car_id,
            $pickup_date,
            $return_date,
            $total_amount
        ];
        
        $stmt = executeQuery($sql, $params);
        
        if ($stmt) {
            $reservation_success = 'Reservation created successfully! We will contact you soon.';
            // Clear form
            $_POST = [];
        } else {
            $reservation_errors[] = 'Failed to create reservation. Please try again.';
        }
    }
}

// Get alert message
$alert = getAlert();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $car['brand'] . ' ' . $car['model']; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .car-gallery {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: var(--border-radius);
        }
        
        .thumbnail-images {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }
        
        .thumbnail-images img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .thumbnail-images img:hover {
            opacity: 0.8;
        }
        
        .spec-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .spec-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
        }
        
        @media (max-width: 768px) {
            .car-gallery {
                grid-template-columns: 1fr;
            }
            
            .thumbnail-images {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="logo"><?php echo SITE_NAME; ?></div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="cars.php">Cars</a></li>
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

    <!-- Car Details -->
    <section class="section">
        <div class="container">
            <div class="row" style="align-items: start;">
                <!-- Car Information -->
                <div style="flex: 2;">
                    <h1><?php echo $car['brand'] . ' ' . $car['model']; ?></h1>
                    <div class="price" style="font-size: 2rem; color: var(--primary-color); margin-bottom: 1rem;">
                        <?php echo formatPrice($car['price_per_day']); ?>/day
                    </div>
                    
                    <!-- Car Gallery -->
                    <div class="car-gallery">
                        <img src="uploads/<?php echo $car['image1']; ?>" alt="<?php echo $car['brand'] . ' ' . $car['model']; ?>" 
                             class="main-image" id="mainImage">
                        <div class="thumbnail-images">
                            <?php if ($car['image1']): ?>
                                <img src="uploads/<?php echo $car['image1']; ?>" alt="Image 1" 
                                     onclick="changeMainImage(this.src)">
                            <?php endif; ?>
                            <?php if ($car['image2']): ?>
                                <img src="uploads/<?php echo $car['image2']; ?>" alt="Image 2" 
                                     onclick="changeMainImage(this.src)">
                            <?php endif; ?>
                            <?php if ($car['image3']): ?>
                                <img src="uploads/<?php echo $car['image3']; ?>" alt="Image 3" 
                                     onclick="changeMainImage(this.src)">
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Specifications -->
                    <h3>Specifications</h3>
                    <div class="spec-grid">
                        <div class="spec-item">
                            <span>🏭</span>
                            <div>
                                <strong>Brand</strong><br>
                                <?php echo $car['brand']; ?>
                            </div>
                        </div>
                        <div class="spec-item">
                            <span>🚗</span>
                            <div>
                                <strong>Model</strong><br>
                                <?php echo $car['model']; ?>
                            </div>
                        </div>
                        <div class="spec-item">
                            <span>📅</span>
                            <div>
                                <strong>Year</strong><br>
                                <?php echo $car['year']; ?>
                            </div>
                        </div>
                        <div class="spec-item">
                            <span>⛽</span>
                            <div>
                                <strong>Fuel Type</strong><br>
                                <?php echo $car['fuel_type']; ?>
                            </div>
                        </div>
                        <div class="spec-item">
                            <span>⚙️</span>
                            <div>
                                <strong>Transmission</strong><br>
                                <?php echo $car['transmission']; ?>
                            </div>
                        </div>
                        <div class="spec-item">
                            <span>👥</span>
                            <div>
                                <strong>Seats</strong><br>
                                <?php echo $car['seats']; ?> Persons
                            </div>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <h3>Description</h3>
                    <p><?php echo nl2br($car['description']); ?></p>
                </div>
                
                <!-- Reservation Form -->
                <div style="flex: 1;">
                    <div class="card" id="booking">
                        <div class="card-header">
                            <h3>Book This Car</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($reservation_success): ?>
                                <div class="alert alert-success">
                                    <?php echo $reservation_success; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($reservation_errors)): ?>
                                <div class="alert alert-danger">
                                    <?php foreach ($reservation_errors as $error): ?>
                                        <p><?php echo $error; ?></p>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!isLoggedIn()): ?>
                                <div class="alert alert-info">
                                    <p>Please <a href="auth/login.php">login</a> or <a href="auth/register.php">register</a> to make a reservation.</p>
                                </div>
                            <?php else: ?>
                                <form method="POST" data-validate>
                                    <input type="hidden" name="price_per_day" value="<?php echo $car['price_per_day']; ?>">
                                    <input type="hidden" name="total_amount" id="total_amount_input" value="0">
                                    
                                    <div class="form-group">
                                        <label class="form-label">Pickup Date *</label>
                                        <input type="date" name="pickup_date" id="pickup_date" class="form-control" 
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Return Date *</label>
                                        <input type="date" name="return_date" id="return_date" class="form-control" 
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    
                                    <button type="button" id="calculateBtn" class="btn btn-outline" style="width: 100%; margin-bottom: 1rem;">
                                        Calculate Total
                                    </button>
                                    
                                    <div class="card" style="background-color: #f8f9fa;">
                                        <div class="card-body">
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                                <span>Daily Rate:</span>
                                                <strong><?php echo formatPrice($car['price_per_day']); ?></strong>
                                            </div>
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                                <span>Days:</span>
                                                <strong id="total_days">0</strong>
                                            </div>
                                            <hr>
                                            <div style="display: flex; justify-content: space-between;">
                                                <span>Total Amount:</span>
                                                <strong id="total_amount" style="color: var(--primary-color); font-size: 1.25rem;">
                                                    $0.00
                                                </strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="make_reservation" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                                        Make Reservation
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Similar Cars -->
            <?php if (!empty($similar_cars)): ?>
                <div style="margin-top: 4rem;">
                    <h3>Similar Cars</h3>
                    <div class="row row-cols-3">
                        <?php foreach ($similar_cars as $similar_car): ?>
                            <div class="car-card">
                                <img src="uploads/<?php echo $similar_car['image1']; ?>" 
                                     alt="<?php echo $similar_car['brand'] . ' ' . $similar_car['model']; ?>">
                                <div class="card-body">
                                    <h4><?php echo $similar_car['brand'] . ' ' . $similar_car['model']; ?></h4>
                                    <div class="price"><?php echo formatPrice($similar_car['price_per_day']); ?>/day</div>
                                    <a href="car-details.php?id=<?php echo $similar_car['id']; ?>" class="btn btn-primary" style="width: 100%;">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
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

    <script>
        function changeMainImage(src) {
            document.getElementById('mainImage').src = src;
        }
    </script>
    <script src="js/script.js"></script>
</body>
</html>
