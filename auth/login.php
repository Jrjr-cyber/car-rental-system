<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('../index.php');
}

if (isAdmin()) {
    redirect('../admin/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identifier = sanitizeInput($_POST['email']); // Can be email or username
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    // Validation
    if (empty($identifier)) {
        $errors[] = 'Email or username is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // If no errors, attempt login
    if (empty($errors)) {
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $loginSuccess = false;
        
        if ($isEmail) {
            // Try user login with email
            $sql = "SELECT * FROM users WHERE email = ?";
            $user = fetchOne($sql, [$identifier]);
            
            if ($user && verifyPassword($password, $user['password'])) {
                // User login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                
                // Set remember me cookie if checked
                if ($remember) {
                    setcookie('remember_email', $identifier, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                }
                
                // Redirect to intended page or dashboard
                $redirect = isset($_SESSION['redirect']) ? $_SESSION['redirect'] : '../index.php';
                unset($_SESSION['redirect']);
                redirect($redirect);
                $loginSuccess = true;
            }
        } else {
            // Try admin login with username
            $sql = "SELECT * FROM admins WHERE username = ?";
            $admin = fetchOne($sql, [$identifier]);
            
            if ($admin && verifyPassword($password, $admin['password'])) {
                // Admin login successful
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];
                
                redirect('../admin/index.php');
                $loginSuccess = true;
            }
        }
        
        if (!$loginSuccess) {
            $errors[] = 'Invalid email/username or password';
        }
    }
}

// Get remembered email if exists
$remembered_email = isset($_COOKIE['remember_email']) ? $_COOKIE['remember_email'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="logo"><?php echo SITE_NAME; ?></div>
            <ul class="nav-links">
                <li><a href="../index.php">Home</a></li>
                <li><a href="../cars.php">Cars</a></li>
                <li><a href="login.php" class="btn btn-primary">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </div>
    </nav>

    <!-- Login Section -->
    <section class="section" style="padding: 4rem 0;">
        <div class="container">
            <div class="row" style="justify-content: center;">
                <div class="card" style="max-width: 400px; width: 100%;">
                    <div class="card-header text-center">
                        <h2>Welcome Back</h2>
                        <p>Login as User or Admin</p>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="form-group">
                                <label class="form-label">Email Address or Username</label>
                                <input type="text" name="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($remembered_email); ?>" 
                                       placeholder="Enter your email or username"
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input type="checkbox" name="remember" 
                                           <?php echo $remembered_email ? 'checked' : ''; ?>>
                                    Remember me
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                Login
                            </button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p><a href="forgot-password.php">Forgot Password?</a></p>
                            <p>Don't have an account? <a href="register.php">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" style="background-color: #343a40; color: white; padding: 2rem 0; margin-top: 4rem;">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
        </div>
    </footer>

    <script src="../js/script.js"></script>
</body>
</html>
