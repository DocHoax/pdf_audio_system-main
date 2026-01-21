<?php
/**
 * EchoDoc - User Registration Page
 */

require_once 'includes/auth.php';
require_once 'includes/User.php';
require_once 'includes/google_oauth.php';
require_once 'includes/email.php';
require_once 'includes/analytics.php';
require_once 'config.php';

// Redirect if already logged in
redirectIfLoggedIn();

$message = '';
$messageType = '';
$formData = ['username' => '', 'email' => '', 'full_name' => ''];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    } else {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $fullName = $_POST['full_name'] ?? '';
        
        // Store form data for repopulation
        $formData = [
            'username' => htmlspecialchars($username),
            'email' => htmlspecialchars($email),
            'full_name' => htmlspecialchars($fullName)
        ];
        
        // Validate passwords match
        if ($password !== $confirmPassword) {
            $message = 'Passwords do not match.';
            $messageType = 'error';
        } else {
            // Attempt registration
            $user = new User();
            $result = $user->register($username, $email, $password, $fullName);
            
            if ($result['success']) {
                // Send welcome email
                sendWelcomeEmail($email, $fullName ?: $username);
                
                // Auto-login after registration
                $loginResult = $user->login($username, $password);
                if ($loginResult['success']) {
                    setUserSession($loginResult['user']);
                    header('Location: index.php?welcome=1');
                    exit;
                }
                
                // Fallback: redirect to login
                header('Location: login.php?registered=1');
                exit;
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create your EchoDoc account">
    <link rel="icon" type="image/png" href="https://img.icons8.com/fluency/48/pdf.png">
    <title>Sign Up - EchoDoc</title>
    <link rel="icon" type="image/png" href="https://img.icons8.com/fluency/48/pdf.png">
    <link rel="apple-touch-icon" href="https://img.icons8.com/fluency/48/pdf.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <link rel="stylesheet" href="assets/css/auth.css?v=3">
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
                <li><a href="login.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/login-rounded-right.png" alt="Login"> Login</a></li>
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
                    <img src="https://img.icons8.com/fluency/96/add-user-male.png" alt="Sign Up">
                    <h1>Create Account</h1>
                    <p>Join EchoDoc and start converting documents to audio</p>
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

                <form method="POST" action="signup.php" class="auth-form">
                    <?php echo csrfField(); ?>
                    
                    <div class="form-group">
                        <label for="full_name">
                            <img src="https://img.icons8.com/fluency/48/user-male-circle.png" alt="Name">
                            Full Name
                        </label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               placeholder="Enter your full name"
                               value="<?php echo $formData['full_name']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="username">
                            <img src="https://img.icons8.com/fluency/48/username.png" alt="Username">
                            Username <span class="required">*</span>
                        </label>
                        <input type="text" id="username" name="username" class="form-control" 
                               placeholder="Choose a username" required
                               value="<?php echo $formData['username']; ?>"
                               pattern="[a-zA-Z0-9_]+" minlength="3" maxlength="50">
                        <small class="form-hint">3-50 characters, letters, numbers, and underscores only</small>
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <img src="https://img.icons8.com/fluency/48/email.png" alt="Email">
                            Email Address <span class="required">*</span>
                        </label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="Enter your email" required
                               value="<?php echo $formData['email']; ?>">
                    </div>

                    <div class="form-group">
                        <label for="password">
                            <img src="https://img.icons8.com/fluency/48/password.png" alt="Password">
                            Password <span class="required">*</span>
                        </label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="Create a password" required minlength="6">
                            <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                <img src="https://img.icons8.com/fluency/48/visible.png" alt="Show" id="password-icon">
                            </button>
                        </div>
                        <small class="form-hint">At least 6 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            <img src="https://img.icons8.com/fluency/48/password.png" alt="Confirm Password">
                            Confirm Password <span class="required">*</span>
                        </label>
                        <div class="password-input">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   placeholder="Confirm your password" required minlength="6">
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                <img src="https://img.icons8.com/fluency/48/visible.png" alt="Show" id="confirm_password-icon">
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <img src="https://img.icons8.com/fluency/48/add-user-male.png" alt="Sign Up"> Create Account
                    </button>
                </form>

                <!-- Divider -->
                <div class="auth-divider">
                    <span>or continue with</span>
                </div>

                <!-- Google Sign Up -->
                <a href="<?php echo htmlspecialchars(getGoogleAuthUrl()); ?>" class="btn btn-google">
                    <img src="https://img.icons8.com/color/48/google-logo.png" alt="Google"> Sign up with Google
                </a>

                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> EchoDoc. All rights reserved. — <a href="mailto:infoechodoc@gmail.com?subject=EchoDoc%20Bug%20Report" rel="noopener">Report a bug</a></p>
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
