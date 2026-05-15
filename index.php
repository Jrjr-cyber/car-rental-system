<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Get featured cars
$featured_cars = fetchAll("SELECT * FROM cars WHERE available = 1 ORDER BY created_at DESC LIMIT 6");

// Get statistics
$total_cars = fetchOne("SELECT COUNT(*) as count FROM cars WHERE available = 1")['count'];
$total_users = fetchOne("SELECT COUNT(*) as count FROM users")['count'];
$total_reservations = fetchOne("SELECT COUNT(*) as count FROM reservations")['count'];

// Get alert message
$alert = getAlert();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Premium Car Rental Services</title>
    <meta name="description" content="Rent premium cars at affordable prices. Choose from our wide selection of vehicles.">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="logo"><?php echo SITE_NAME; ?></div>
            <ul class="nav-links">
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="cars.php">Cars</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#contact">Contact</a></li>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Premium Car Rental Services</h1>
                <p>Drive your dream car with our affordable and reliable rental services</p>
                <div class="hero-buttons">
                    <a href="cars.php" class="btn btn-primary btn-lg">Browse Cars</a>
                    <a href="#about" class="btn btn-outline btn-lg">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="section" style="background-color: #f8f9fa; padding: 3rem 0;">
        <div class="container">
            <div class="row row-cols-3">
                <div class="text-center">
                    <h2 style="color: var(--primary-color); font-size: 2.5rem;"><?php echo $total_cars; ?>+</h2>
                    <p>Available Cars</p>
                </div>
                <div class="text-center">
                    <h2 style="color: var(--primary-color); font-size: 2.5rem;"><?php echo $total_users; ?>+</h2>
                    <p>Happy Customers</p>
                </div>
                <div class="text-center">
                    <h2 style="color: var(--primary-color); font-size: 2.5rem;"><?php echo $total_reservations; ?>+</h2>
                    <p>Successful Rentals</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Cars Section -->
    <section class="section">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Featured Cars</h2>
                <p>Check out our most popular rental vehicles</p>
            </div>
            
            <div class="row row-cols-3">
                <?php foreach ($featured_cars as $car): ?>
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
                            <a href="car-details.php?id=<?php echo $car['id']; ?>" class="btn btn-primary" style="width: 100%;">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="cars.php" class="btn btn-outline btn-lg">View All Cars</a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="section" style="background-color: #f8f9fa;">
        <div class="container">
            <div class="row" style="align-items: center;">
                <div>
                    <h2>About <?php echo SITE_NAME; ?></h2>
                    <p>Welcome to <?php echo SITE_NAME; ?>, your trusted partner for premium car rental services. With years of experience in the industry, we pride ourselves on offering top-quality vehicles at competitive prices.</p>
                    <p>Our fleet includes the latest models from leading brands, ensuring you have a safe, comfortable, and enjoyable driving experience. Whether you need a car for business travel, vacation, or special occasions, we have the perfect vehicle for you.</p>
                    <ul style="list-style: none; padding: 0;">
                        <li>✓ Wide selection of premium vehicles</li>
                        <li>✓ Competitive pricing with no hidden fees</li>
                        <li>✓ 24/7 customer support</li>
                        <li>✓ Easy online booking system</li>
                        <li>✓ Flexible rental periods</li>
                    </ul>
                </div>
                <div>
                    <img src="images/about-image.jpg" alt="About Us" style="width: 100%; border-radius: var(--border-radius);">
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="section">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Our Services</h2>
                <p>Comprehensive car rental solutions for every need</p>
            </div>
            
            <div class="row row-cols-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🚗</div>
                        <h3>Daily Rentals</h3>
                        <p>Perfect for short trips and daily commuting needs with flexible pickup and return options.</p>
                    </div>
                </div>
                <div class="card text-center">
                    <div class="card-body">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">💼</div>
                        <h3>Corporate Services</h3>
                        <p>Special rates and services for businesses with billing options and dedicated account management.</p>
                    </div>
                </div>
                <div class="card text-center">
                    <div class="card-body">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">🎉</div>
                        <h3>Special Events</h3>
                        <p>Luxury vehicles for weddings, parties, and special occasions with professional chauffeur service available.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="section" style="background-color: #f8f9fa;">
        <div class="container">
            <div class="text-center mb-5">
                <h2>What Our Customers Say</h2>
                <p>Real reviews from satisfied customers</p>
            </div>
            
            <div class="row row-cols-3">
                <div class="card">
                    <div class="card-body">
                        <div class="stars" style="color: var(--accent-color); margin-bottom: 1rem;">
                            ⭐⭐⭐⭐⭐
                        </div>
                        <p>"Excellent service! The car was clean and well-maintained. Booking process was smooth and hassle-free."</p>
                        <div style="margin-top: 1rem;">
                            <strong>John Doe</strong>
                            <p style="margin: 0; color: var(--gray); font-size: 0.875rem;">Business Traveler</p>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="stars" style="color: var(--accent-color); margin-bottom: 1rem;">
                            ⭐⭐⭐⭐⭐
                        </div>
                        <p>"Great selection of cars at reasonable prices. Customer service was exceptional throughout my rental period."</p>
                        <div style="margin-top: 1rem;">
                            <strong>Jane Smith</strong>
                            <p style="margin: 0; color: var(--gray); font-size: 0.875rem;">Vacationer</p>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="stars" style="color: var(--accent-color); margin-bottom: 1rem;">
                            ⭐⭐⭐⭐⭐
                        </div>
                        <p>"I've been using their services for years. Always reliable, professional, and the cars are always in top condition."</p>
                        <div style="margin-top: 1rem;">
                            <strong>Mike Johnson</strong>
                            <p style="margin: 0; color: var(--gray); font-size: 0.875rem;">Regular Customer</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="section">
        <div class="container">
            <div class="text-center mb-5">
                <h2>Get In Touch</h2>
                <p>Have questions? We're here to help</p>
            </div>
            
            <div class="row" style="max-width: 800px; margin: 0 auto;">
                <div class="card">
                    <div class="card-body">
                        <div class="row row-cols-2">
                            <div>
                                <h3>Contact Information</h3>
                                <p><strong>Phone:</strong> +1 (555) 123-4567</p>
                                <p><strong>Email:</strong> info@carrental.com</p>
                                <p><strong>Address:</strong> 123 Main St, City, State 12345</p>
                                <p><strong>Hours:</strong> Mon-Sat: 8AM-8PM, Sun: 9AM-6PM</p>
                            </div>
                            <div>
                                <h3>Follow Us</h3>
                                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                                    <a href="#" style="font-size: 1.5rem;">📘</a>
                                    <a href="#" style="font-size: 1.5rem;">🐦</a>
                                    <a href="#" style="font-size: 1.5rem;">📷</a>
                                    <a href="#" style="font-size: 1.5rem;">💼</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" style="background-color: #343a40; color: white; padding: 3rem 0 1rem;">
        <div class="container">
            <div class="row row-cols-4">
                <div>
                    <h3><?php echo SITE_NAME; ?></h3>
                    <p>Your trusted partner for premium car rental services. Quality vehicles at competitive prices.</p>
                </div>
                <div>
                    <h4>Quick Links</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li><a href="index.php" style="color: white;">Home</a></li>
                        <li><a href="cars.php" style="color: white;">Cars</a></li>
                        <li><a href="auth/login.php" style="color: white;">Login</a></li>
                        <li><a href="auth/register.php" style="color: white;">Register</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Services</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li><a href="#" style="color: white;">Daily Rentals</a></li>
                        <li><a href="#" style="color: white;">Corporate Services</a></li>
                        <li><a href="#" style="color: white;">Special Events</a></li>
                        <li><a href="#" style="color: white;">Long-term Rentals</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Contact</h4>
                    <p>📍 123 Main St, City, State 12345</p>
                    <p>📞 +1 (555) 123-4567</p>
                    <p>✉️ info@carrental.com</p>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.1); margin: 2rem 0;">
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Theme Toggle Button -->
    <button id="themeToggle" class="theme-toggle" onclick="toggleTheme()">🌙</button>

    <script src="js/script.js"></script>
</body>
</html>
