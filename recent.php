<?php
require_once 'includes/auth.php';
require_once 'config.php';

// Require authentication for recent files page
requireAuth('recent.php');

// Get user-specific session key
$userId = getCurrentUserId();
$recentKey = 'user_' . $userId . '_recent_files';

// Initialize recent files array in session if not exists
if (!isset($_SESSION[$recentKey])) {
    $_SESSION[$recentKey] = [];
}

// Handle file deletion from recent list
if (isset($_POST['remove_file']) && isset($_POST['file_index'])) {
    $index = (int)$_POST['file_index'];
    if (isset($_SESSION[$recentKey][$index])) {
        array_splice($_SESSION[$recentKey], $index, 1);
    }
    header('Location: recent.php');
    exit;
}

// Handle clear all
if (isset($_POST['clear_all'])) {
    $_SESSION[$recentKey] = [];
    header('Location: recent.php');
    exit;
}

$recentFiles = $_SESSION[$recentKey] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recent Files - EchoDoc</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" type="image/png" href="https://img.icons8.com/fluency/48/pdf.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Outfit:wght@100..900&family=Ovo&family=Pacifico&display=swap" rel="stylesheet">
    <style>
        .recent-files-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .recent-file-card {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .recent-file-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .file-icon {
            flex-shrink: 0;
        }
        
        .file-icon img {
            width: 48px;
            height: 48px;
        }
        
        .file-details {
            flex-grow: 1;
            min-width: 0;
        }
        
        .file-name {
            font-family: var(--font-heading);
            font-size: 1.3rem;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .file-meta {
            display: flex;
            gap: 1.5rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .file-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .file-meta img {
            width: 16px;
            height: 16px;
        }
        
        .file-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }
        
        .file-actions button,
        .file-actions a {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        
        .file-actions img {
            width: 20px;
            height: 20px;
        }
        
        .btn-open {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-open:hover {
            background: var(--gray-700);
        }
        
        .btn-remove {
            background: var(--gray-200);
            color: var(--gray-700);
        }
        
        .btn-remove:hover {
            background: #dc3545;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .empty-state img {
            width: 96px;
            height: 96px;
            opacity: 0.5;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: var(--gray-500);
            margin-bottom: 1.5rem;
        }
        
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .file-count {
            font-size: 1rem;
            color: var(--gray-600);
        }
        
        .btn-clear-all {
            background: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            transition: background-color 0.2s;
        }
        
        .btn-clear-all:hover {
            background: #c82333;
        }
        
        .btn-clear-all img {
            width: 20px;
            height: 20px;
        }
        
        .text-preview {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-top: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
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
                <li><a href="recent.php" class="nav-link active"><img src="https://img.icons8.com/fluency/48/time-machine.png" alt="Recent"> Recent</a></li>
                <li><a href="about.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/info.png" alt="About"> About</a></li>
                <li><a href="help.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/help.png" alt="Help"> Help</a></li>
                <li><a href="contact.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/email.png" alt="Contact"> Contact</a></li>
            </ul>
            <button class="nav-toggle" id="navToggle">
                <img src="https://img.icons8.com/fluency/48/menu.png" alt="Menu" style="width:24px;height:24px;">
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <div class="hero-content">
            <h1><img src="https://img.icons8.com/fluency/48/time-machine.png" alt="Recent" style="width: 48px; height: 48px; vertical-align: middle;"> Recent Files</h1>
            <p>Access your recently opened PDF documents</p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
        <section class="recent-files-container">
            <?php if (empty($recentFiles)): ?>
                <div class="empty-state">
                    <img src="https://img.icons8.com/fluency/96/folder-invoices.png" alt="No files">
                    <h3>No Recent Files</h3>
                    <p>PDF files you open will appear here for quick access.</p>
                    <a href="index.php" class="btn btn-primary">
                        <img src="https://img.icons8.com/fluency/48/upload--v1.png" alt="Upload" style="width: 20px; height: 20px;"> Upload a PDF
                    </a>
                </div>
            <?php else: ?>
                <div class="header-actions">
                    <span class="file-count">
                        <img src="https://img.icons8.com/fluency/48/pdf.png" alt="Files" style="width: 20px; height: 20px; vertical-align: middle;">
                        <?php echo count($recentFiles); ?> recent file<?php echo count($recentFiles) !== 1 ? 's' : ''; ?>
                    </span>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to clear all recent files?');">
                        <button type="submit" name="clear_all" class="btn-clear-all">
                            <img src="https://img.icons8.com/fluency/48/trash--v1.png" alt="Clear"> Clear All
                        </button>
                    </form>
                </div>
                
                <?php foreach ($recentFiles as $index => $file): 
                    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $fileIcon = $fileExt === 'docx' ? 'https://img.icons8.com/fluency/96/microsoft-word-2019.png' : 'https://img.icons8.com/fluency/96/pdf.png';
                ?>
                    <div class="recent-file-card">
                        <div class="file-icon">
                            <img src="<?php echo $fileIcon; ?>" alt="<?php echo strtoupper($fileExt); ?>">
                        </div>
                        <div class="file-details">
                            <div class="file-name" title="<?php echo htmlspecialchars($file['name']); ?>">
                                <?php echo htmlspecialchars($file['name']); ?>
                            </div>
                            <div class="file-meta">
                                <span>
                                    <img src="https://img.icons8.com/fluency/48/clock--v1.png" alt="Time">
                                    <?php echo htmlspecialchars($file['accessed']); ?>
                                </span>
                                <?php if (isset($file['char_count'])): ?>
                                <span>
                                    <img src="https://img.icons8.com/fluency/48/text.png" alt="Characters">
                                    <?php echo number_format($file['char_count']); ?> characters
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php if (isset($file['preview'])): ?>
                            <div class="text-preview">
                                <?php echo htmlspecialchars($file['preview']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="file-actions">
                            <?php if (isset($file['has_text']) && $file['has_text']): ?>
                            <a href="index.php?load_recent=<?php echo $index; ?>" class="btn-open">
                                <img src="https://img.icons8.com/fluency/48/play--v1.png" alt="Open"> Open
                            </a>
                            <?php endif; ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="file_index" value="<?php echo $index; ?>">
                                <button type="submit" name="remove_file" class="btn-remove">
                                    <img src="https://img.icons8.com/fluency/48/trash--v1.png" alt="Remove">
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><img src="https://img.icons8.com/fluency/48/pdf.png" alt="EchoDoc"> EchoDoc</h3>
                    <p>Transform your PDF documents into audio with natural-sounding voices powered by YarnGPT.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php"><img src="https://img.icons8.com/fluency/48/home.png" alt="Home" style="width: 16px; height: 16px;"> Home</a></li>
                        <li><a href="recent.php"><img src="https://img.icons8.com/fluency/48/time-machine.png" alt="Recent" style="width: 16px; height: 16px;"> Recent</a></li>
                        <li><a href="about.php"><img src="https://img.icons8.com/fluency/48/info.png" alt="About" style="width: 16px; height: 16px;"> About</a></li>
                        <li><a href="help.php"><img src="https://img.icons8.com/fluency/48/help.png" alt="Help" style="width: 16px; height: 16px;"> Help</a></li>
                        <li><a href="contact.php"><img src="https://img.icons8.com/fluency/48/email.png" alt="Contact" style="width: 16px; height: 16px;"> Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Technology</h3>
                    <ul>
                        <li><img src="https://img.icons8.com/color/48/html-5--v1.png" alt="HTML" style="width: 16px; height: 16px;"> HTML5 & CSS3</li>
                        <li><img src="https://img.icons8.com/officel/48/php-logo.png" alt="PHP" style="width: 16px; height: 16px;"> PHP 8+</li>
                        <li><img src="https://img.icons8.com/color/48/javascript--v1.png" alt="JS" style="width: 16px; height: 16px;"> JavaScript</li>
                        <li><img src="https://img.icons8.com/fluency/48/high-volume--v1.png" alt="Audio" style="width: 16px; height: 16px;"> YarnGPT API</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> EchoDoc. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>