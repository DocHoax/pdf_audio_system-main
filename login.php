<?php
/**
 * EchoDoc - User Login Page
 */

require_once 'includes/auth.php';
require_once 'includes/User.php';
require_once 'includes/google_oauth.php';
require_once 'config.php';

// Redirect if already logged in
redirectIfLoggedIn();

$message = '';
$messageType = '';

// Check for registration success
if (isset($_GET['registered'])) {
    $message = 'Registration successful! Please login.';
    $messageType = 'success';
}

// Check for Google OAuth errors
if (isset($_GET['error'])) {
    $detail = isset($_GET['detail']) ? ' (' . htmlspecialchars(urldecode($_GET['detail'])) . ')' : '';
    $errorMessages = [
        'google_denied' => 'Google sign-in was cancelled.',
        'google_failed' => 'Google sign-in failed. Please try again.',
        'google_token_failed' => 'Failed to authenticate with Google.' . $detail,
        'google_user_failed' => 'Could not get user info from Google.',
        'database_error' => 'Database error.' . $detail
    ];
    $message = $errorMessages[$_GET['error']] ?? 'An error occurred. Please try again.';
    $messageType = 'error';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    } else {
        $identifier = $_POST['identifier'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // Attempt login
        $user = new User();
        $result = $user->login($identifier, $password);
        
        if ($result['success']) {
            setUserSession($result['user']);
            
            // Handle redirect
            $redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? 'index.php';
            // Sanitize redirect URL
            if (!preg_match('/^[a-zA-Z0-9_\-\.\/\?=&]+$/', $redirect)) {
                $redirect = 'index.php';
            }
            
            header('Location: ' . $redirect);
            exit;
        } else {
            $message = $result['message'];
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to your EchoDoc account">
    <link rel="icon" type="image/png" href="https://img.icons8.com/fluency/48/pdf.png">
    <title>Login - EchoDoc</title>
    <link rel="icon" type="image/png" href="https://img.icons8.com/fluency/48/pdf.png">
    <link rel="apple-touch-icon" href="https://img.icons8.com/fluency/48/pdf.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <link rel="stylesheet" href="assets/css/auth.css?v=2">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <img src="https://img.icons8.com/fluency/48/pdf.png" alt="EchoDoc">
                <span>EchoDoc</span>
            </a>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/home.png" alt="Home"> Home</a></li>
                <li><a href="about.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/info.png" alt="About"> About</a></li>
                <li><a href="help.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/help.png" alt="Help"> Help</a></li>
                <li><a href="signup.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/add-user-male.png" alt="Sign Up"> Sign Up</a></li>
            </ul>
            <button class="nav-toggle" id="navToggle">
                <img src="https://img.icons8.com/fluency/48/menu.png" alt="Menu" style="width:24px;height:24px;">
            </button>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="auth-main">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <img src="https://img.icons8.com/fluency/96/login-rounded-right.png" alt="Login">
                    <h1>Welcome Back</h1>
                    <p>Login to your EchoDoc account</p>
                </div>

                <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php if ($messageType === 'success'): ?>
                    <img src="https://img.icons8.com/fluency/48/checkmark--v1.png" alt="Success">
                    <?php else: ?>
                    <img src="https://img.icons8.com/fluency/48/cancel--v1.png" alt="Error">
                    <?php endif; ?>
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" class="auth-form">
                    <?php echo csrfField(); ?>
                    <?php if (isset($_GET['redirect'])): ?>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="identifier">
                            <img src="https://img.icons8.com/fluency/48/user-male-circle.png" alt="User">
                            Username or Email
                        </label>
                        <input type="text" id="identifier" name="identifier" class="form-control" 
                               placeholder="Enter your username or email" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <img src="https://img.icons8.com/fluency/48/password.png" alt="Password">
                            Password
                        </label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="Enter your password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <img src="https://img.icons8.com/fluency/48/visible.png" alt="Show" id="password-icon">
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember">
                            <span>Remember me</span>
                        </label>
                        <!-- <a href="forgot-password.php" class="forgot-link">Forgot password?</a> -->
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <img src="https://img.icons8.com/fluency/48/login-rounded-right.png" alt="Login"> Login
                    </button>
                </form>

                <!-- Divider -->
                <div class="auth-divider">
                    <span>or continue with</span>
                </div>

                <!-- Google Sign In -->
                <a href="<?php echo htmlspecialchars(getGoogleAuthUrl()); ?>" class="btn btn-google">
                    <img src="https://img.icons8.com/color/48/google-logo.png" alt="Google"> Sign in with Google
                </a>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> EchoDoc. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js?v=11"></script>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '-icon');
            if (field.type === 'password') {
                field.type = 'text';
                icon.src = 'https://img.icons8.com/fluency/48/invisible.png';
            } else {
                field.type = 'password';
                icon.src = 'https://img.icons8.com/fluency/48/visible.png';
            }
        }
    </script>
</body>
</html>
