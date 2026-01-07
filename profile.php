<?php
/**
 * EchoDoc - User Profile Page
 */

require_once 'includes/auth.php';
require_once 'includes/User.php';
require_once 'config.php';

// Require authentication
requireAuth('profile.php');

$user = getCurrentUser();
$message = '';
$messageType = '';

// Create avatars directory if not exists
$avatarsDir = 'uploads/avatars/';
if (!file_exists($avatarsDir)) {
    mkdir($avatarsDir, 0777, true);
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid request. Please try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $userModel = new User();
        
        if ($action === 'update_profile') {
            $fullName = $_POST['full_name'] ?? '';
            $username = $_POST['username'] ?? '';
            
            $updateData = ['full_name' => $fullName];
            
            // Only include username if it changed
            if ($username !== $user['username']) {
                $updateData['username'] = $username;
            }
            
            $result = $userModel->updateProfile($user['id'], $updateData);
            
            if ($result['success']) {
                // Update session
                $_SESSION['user']['full_name'] = $fullName;
                if (isset($updateData['username'])) {
                    $_SESSION['user']['username'] = $username;
                }
                $user = getCurrentUser();
                $message = $result['message'];
                $messageType = 'success';
            } else {
                $message = $result['message'];
                $messageType = 'error';
            }
        } elseif ($action === 'update_avatar') {
            // Handle avatar upload
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $maxSize = 2 * 1024 * 1024; // 2MB
                
                // Validate file type
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                
                if (!in_array($mimeType, $allowedTypes)) {
                    $message = 'Invalid file type. Please upload a JPG, PNG, GIF, or WebP image.';
                    $messageType = 'error';
                } elseif ($file['size'] > $maxSize) {
                    $message = 'File too large. Maximum size is 2MB.';
                    $messageType = 'error';
                } else {
                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $newFilename = 'avatar_' . $user['id'] . '_' . time() . '.' . $extension;
                    $targetPath = $avatarsDir . $newFilename;
                    
                    // Delete old avatar if exists
                    $oldAvatar = $userModel->getUserById($user['id'])['avatar'] ?? null;
                    if ($oldAvatar && file_exists($oldAvatar)) {
                        unlink($oldAvatar);
                    }
                    
                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        // Update database
                        $result = $userModel->updateProfile($user['id'], ['avatar' => $targetPath]);
                        if ($result['success']) {
                            $_SESSION['user']['avatar'] = $targetPath;
                            $message = 'Profile picture updated successfully!';
                            $messageType = 'success';
                        } else {
                            $message = 'Failed to save avatar to database.';
                            $messageType = 'error';
                        }
                    } else {
                        $message = 'Failed to upload file.';
                        $messageType = 'error';
                    }
                }
            } else {
                $message = 'Please select an image to upload.';
                $messageType = 'error';
            }
        } elseif ($action === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if ($newPassword !== $confirmPassword) {
                $message = 'New passwords do not match.';
                $messageType = 'error';
            } else {
                $result = $userModel->changePassword($user['id'], $currentPassword, $newPassword);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
            }
        }
    }
}

// Get fresh user data
$userModel = new User();
$userData = $userModel->getUserById($user['id']);

// Fallback if userData is null (use session data)
if (!$userData) {
    $userData = $user;
    $userData['is_premium'] = $userData['is_premium'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Your EchoDoc Profile">
    <link rel="icon" type="image/png" href="https://img.icons8.com/fluency/48/pdf.png">
    <title>My Profile - EchoDoc</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=3">
    <link rel="stylesheet" href="assets/css/auth.css?v=1">
    <style>
        .profile-main {
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, #343a40 0%, #212529 100%);
            border-radius: 20px;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.3);
        }
        .profile-info h1 {
            font-size: 1.75rem;
            margin-bottom: 0.25rem;
            color: #ffffff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
        .profile-info p {
            opacity: 0.9;
        }
        .profile-section {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .profile-section h2 {
            font-size: 1.25rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .profile-section h2 img {
            width: 24px;
            height: 24px;
        }
        .profile-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .profile-form .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .profile-form label {
            font-weight: 600;
            color: #333;
            font-size: 0.9rem;
        }
        .profile-form .form-control {
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
        }
        .profile-form .form-control:focus {
            outline: none;
            border-color: #3d5a80;
        }
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 1.25rem;
            border-radius: 12px;
            text-align: center;
        }
        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #3d5a80;
        }
        .stat-card .stat-label {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }
        .username-hint {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }
        /* Avatar Upload Styles */
        .avatar-upload-container {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }
        .current-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #e9ecef;
            flex-shrink: 0;
        }
        .current-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .avatar-upload-info {
            flex: 1;
        }
        .avatar-upload-label {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            color: #495057;
            transition: all 0.3s ease;
        }
        .avatar-upload-label:hover {
            background: #e9ecef;
            border-color: #dee2e6;
        }
        .avatar-form input[type="file"] {
            display: none;
        }
        .avatar-hint {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }
        .avatar-form .btn {
            align-self: flex-start;
        }
    </style>
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
                <li><a href="recent.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/time-machine.png" alt="Recent"> Recent</a></li>
                <li><a href="about.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/info.png" alt="About"> About</a></li>
                <li><a href="help.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/help.png" alt="Help"> Help</a></li>
                <?php 
                $navAvatar = (!empty($userData['avatar']) && file_exists($userData['avatar'])) 
                    ? htmlspecialchars($userData['avatar']) 
                    : 'https://img.icons8.com/fluency/48/user-male-circle.png';
                ?>
                <li class="user-menu">
                    <button class="user-menu-toggle" onclick="toggleUserMenu()">
                        <img src="<?php echo $navAvatar; ?>" alt="User" class="user-avatar-nav">
                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="profile.php"><img src="<?php echo $navAvatar; ?>" alt="Profile"> My Profile</a>
                        <a href="recent.php"><img src="https://img.icons8.com/fluency/48/time-machine.png" alt="Documents"> My Documents</a>
                        <a href="logout.php" class="logout-link"><img src="https://img.icons8.com/fluency/48/logout-rounded.png" alt="Logout"> Logout</a>
                    </div>
                </li>
            </ul>
            <button class="nav-toggle" id="navToggle">
                <img src="https://img.icons8.com/fluency/48/menu.png" alt="Menu" style="width:24px;height:24px;">
            </button>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content profile-main">
        <!-- Profile Header -->
        <div class="profile-header">
            <?php 
            $avatarSrc = (!empty($userData['avatar']) && file_exists($userData['avatar'])) 
                ? htmlspecialchars($userData['avatar']) . '?v=' . time()
                : 'https://img.icons8.com/fluency/96/user-male-circle.png';
            ?>
            <img src="<?php echo $avatarSrc; ?>" alt="Avatar" class="profile-avatar">
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($userData['full_name'] ?: $userData['username']); ?></h1>
                <p>@<?php echo htmlspecialchars($userData['username']); ?></p>
                <p>Member since <?php echo date('F Y', strtotime($userData['created_at'])); ?></p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>" style="margin-bottom: 1.5rem;">
            <?php if ($messageType === 'success'): ?>
            <img src="https://img.icons8.com/fluency/48/checkmark--v1.png" alt="Success" style="width:24px;height:24px;">
            <?php else: ?>
            <img src="https://img.icons8.com/fluency/48/cancel--v1.png" alt="Error" style="width:24px;height:24px;">
            <?php endif; ?>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Update Avatar -->
        <div class="profile-section">
            <h2><img src="https://img.icons8.com/fluency/48/camera.png" alt="Avatar"> Profile Picture</h2>
            <form method="POST" enctype="multipart/form-data" class="profile-form avatar-form">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_avatar">
                
                <div class="avatar-upload-container">
                    <div class="current-avatar">
                        <img src="<?php echo $avatarSrc; ?>" alt="Current Avatar" id="avatarPreview">
                    </div>
                    <div class="avatar-upload-info">
                        <label for="avatar" class="avatar-upload-label">
                            <img src="https://img.icons8.com/fluency/48/upload.png" alt="Upload" style="width:20px;height:20px;">
                            Choose Image
                        </label>
                        <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewAvatar(this)">
                        <p class="avatar-hint">JPG, PNG, GIF or WebP. Max 2MB.</p>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <img src="https://img.icons8.com/fluency/48/save.png" alt="Save" style="width:20px;height:20px;"> Update Picture
                </button>
            </form>
        </div>

        <!-- Account Stats -->
        <div class="profile-section">
            <h2><img src="https://img.icons8.com/fluency/48/bar-chart.png" alt="Stats"> Account Statistics</h2>
            <div class="profile-stats">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($_SESSION['user_' . $user['id'] . '_recent_files'] ?? []); ?></div>
                    <div class="stat-label">Recent Documents</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $userData['last_login'] ? date('M j', strtotime($userData['last_login'])) : 'N/A'; ?></div>
                    <div class="stat-label">Last Login</div>
                </div>
            </div>
        </div>

        <!-- Update Profile -->
        <div class="profile-section">
            <h2><img src="https://img.icons8.com/fluency/48/edit.png" alt="Edit"> Update Profile</h2>
            <form method="POST" class="profile-form">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($userData['username']); ?>"
                           pattern="^[a-zA-Z0-9_]+$" minlength="3" maxlength="50" required>
                    <small class="username-hint">Letters, numbers, and underscores only. 3-50 characters.</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" class="form-control" 
                           value="<?php echo htmlspecialchars($userData['email']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           value="<?php echo htmlspecialchars($userData['full_name'] ?? ''); ?>"
                           placeholder="Enter your full name">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <img src="https://img.icons8.com/fluency/48/save.png" alt="Save" style="width:20px;height:20px;"> Save Changes
                </button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="profile-section">
            <h2><img src="https://img.icons8.com/fluency/48/password.png" alt="Password"> Change Password</h2>
            <form method="POST" class="profile-form">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" 
                           placeholder="Enter current password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" 
                           placeholder="Enter new password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                           placeholder="Confirm new password" required minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <img src="https://img.icons8.com/fluency/48/password.png" alt="Change" style="width:20px;height:20px;"> Change Password
                </button>
            </form>
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

    <script src="assets/js/main.js?v=12"></script>
    <script>
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            if (dropdown) {
                dropdown.classList.toggle('show');
            }
        }
        
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const maxSize = 2 * 1024 * 1024; // 2MB
                
                // Validate file size
                if (file.size > maxSize) {
                    alert('File too large. Maximum size is 2MB.');
                    input.value = '';
                    return;
                }
                
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Please upload a JPG, PNG, GIF, or WebP image.');
                    input.value = '';
                    return;
                }
                
                // Preview the image
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>
