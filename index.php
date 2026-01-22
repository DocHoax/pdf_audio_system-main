<?php
/**
 * EchoDoc
 * Main Interface for uploading and converting PDF documents to audio
 * 
 * Developed using HTML, CSS, PHP and JavaScript
 * Uses YarnGPT API for Text-to-Speech
 */

require_once 'includes/auth.php';
require_once 'config.php';

// Configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB max file size

// Create uploads directory if not exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

$message = '';
$messageType = '';
$extractedText = '';
$fileName = '';

// Handle file upload - Only allow for logged in users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdfFile'])) {
    // Check if user is logged in
    if (!isLoggedIn()) {
        $message = 'Please log in to upload and convert documents.';
        $messageType = 'error';
    } else {
        $file = $_FILES['pdfFile'];
    
        // Validate file
        if ($file['error'] === UPLOAD_ERR_OK) {
            $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedTypes = ['pdf', 'docx'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $message = 'Error: Only PDF and DOCX files are allowed.';
                $messageType = 'error';
            } elseif ($file['size'] > MAX_FILE_SIZE) {
                $message = 'Error: File size exceeds 10MB limit.';
                $messageType = 'error';
            } else {
                $fileName = basename($file['name']);
                $targetPath = UPLOAD_DIR . time() . '_' . $fileName;
                
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    // Extract text based on file type
                    if ($fileType === 'pdf') {
                        require_once 'includes/pdf_extractor.php';
                        $extractor = new PDFTextExtractor();
                    } else {
                        require_once 'includes/docx_extractor.php';
                        $extractor = new DOCXTextExtractor();
                    }
                    $extractedText = $extractor->extractText($targetPath);
                    
                    if (!empty($extractedText)) {
                        $message = 'Document uploaded and text extracted successfully!';
                        $messageType = 'success';
                        
                        // Use user-specific session keys
                        $userId = getCurrentUserId();
                        $_SESSION['user_' . $userId . '_extracted_text'] = $extractedText;
                        $_SESSION['user_' . $userId . '_file_name'] = $fileName;
                        
                        // Save to user-specific recent files
                        $recentKey = 'user_' . $userId . '_recent_files';
                        if (!isset($_SESSION[$recentKey])) {
                            $_SESSION[$recentKey] = [];
                        }
                        
                        // Add to beginning of recent files array
                        $recentEntry = [
                            'name' => $fileName,
                            'accessed' => date('M j, Y g:i A'),
                            'char_count' => strlen($extractedText),
                            'preview' => substr($extractedText, 0, 150) . '...',
                            'has_text' => true,
                            'text' => $extractedText
                        ];
                        
                        // Remove duplicate if exists
                        foreach ($_SESSION[$recentKey] as $key => $file) {
                            if ($file['name'] === $fileName) {
                                unset($_SESSION[$recentKey][$key]);
                            }
                        }
                        $_SESSION[$recentKey] = array_values($_SESSION[$recentKey]);
                        
                        // Add new entry at beginning
                        array_unshift($_SESSION[$recentKey], $recentEntry);
                        
                        // Keep only last 10 files
                        $_SESSION[$recentKey] = array_slice($_SESSION[$recentKey], 0, 10);
                    } else {
                        $message = 'Document uploaded but no text could be extracted. The file might be image-based or empty.';
                        $messageType = 'warning';
                    }
                } else {
                    $message = 'Error: Failed to upload file.';
                    $messageType = 'error';
                }
            }
        } else {
            $message = 'Error: ' . getUploadErrorMessage($file['error']);
            $messageType = 'error';
        }
    }
}

// Get previously extracted text from session - only for logged in users
if (isLoggedIn() && empty($extractedText)) {
    $userId = getCurrentUserId();
    $textKey = 'user_' . $userId . '_extracted_text';
    $fileKey = 'user_' . $userId . '_file_name';
    if (isset($_SESSION[$textKey])) {
        $extractedText = $_SESSION[$textKey];
        $fileName = $_SESSION[$fileKey] ?? '';
    }
}

// Handle loading from recent files - only for logged in users
if (isLoggedIn() && isset($_GET['load_recent'])) {
    $userId = getCurrentUserId();
    $recentKey = 'user_' . $userId . '_recent_files';
    if (isset($_SESSION[$recentKey])) {
        $recentIndex = (int)$_GET['load_recent'];
        if (isset($_SESSION[$recentKey][$recentIndex])) {
            $recentFile = $_SESSION[$recentKey][$recentIndex];
            if (isset($recentFile['text']) && !empty($recentFile['text'])) {
                $extractedText = $recentFile['text'];
                $fileName = $recentFile['name'];
                $_SESSION['user_' . $userId . '_extracted_text'] = $extractedText;
                $_SESSION['user_' . $userId . '_file_name'] = $fileName;
                
                // Update accessed time
                $_SESSION[$recentKey][$recentIndex]['accessed'] = date('M j, Y g:i A');
            
                // Move to top of recent list
                $item = $_SESSION[$recentKey][$recentIndex];
                unset($_SESSION[$recentKey][$recentIndex]);
                $_SESSION[$recentKey] = array_values($_SESSION[$recentKey]);
                array_unshift($_SESSION[$recentKey], $item);
                
                $message = 'Loaded "' . htmlspecialchars($fileName) . '" from recent files.';
                $messageType = 'success';
            }
        }
    }
}

function getUploadErrorMessage($errorCode) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit.',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit.',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server temporary folder missing.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.'
    ];
    return $errors[$errorCode] ?? 'Unknown upload error.';
}
?>
<!DOCTYPE html>
<html lang="en"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="Hs65gDOL_s4YG9yZLSttXykvcasVtI-YmrziXxL7pWs" />
    <meta name="description" content="EchoDoc - Transform PDF documents into natural-sounding audio with AI-powered voice synthesis">
    <meta name="keywords" content="PDF, Audio, Text to Speech, Accessibility, Reading Aid">
    <title>EchoDoc - Transform PDF to Audio</title>
    <link rel="icon" type="image/png" href="https://img.icons8.com/fluency/48/pdf.png">
    <link rel="apple-touch-icon" href="https://img.icons8.com/fluency/48/pdf.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=7">
    <link rel="stylesheet" href="assets/css/auth.css?v=2">
    <?php include 'includes/seo.php'; ?>
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
                <li><a href="index.php" class="nav-link active"><img src="https://img.icons8.com/fluency/48/home.png" alt="Home"> Home</a></li>
                <li><a href="recent.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/time-machine.png" alt="Recent"> Recent</a></li>
                <li><a href="about.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/info.png" alt="About"> About</a></li>
                <li><a href="help.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/help.png" alt="Help"> Help</a></li>
                <li><a href="contact.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/email.png" alt="Contact"> Contact</a></li>
                <?php if (isLoggedIn()): 
                    $currentUser = getCurrentUser();
                    $userAvatar = (!empty($currentUser['avatar']) && file_exists($currentUser['avatar'])) 
                        ? htmlspecialchars($currentUser['avatar']) 
                        : 'https://img.icons8.com/fluency/48/user-male-circle.png';
                ?>
                <li class="user-menu">
                    <button class="user-menu-toggle" onclick="toggleUserMenu()">
                        <img src="<?php echo $userAvatar; ?>" alt="User" class="user-avatar-nav">
                        <span><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="profile.php"><img src="<?php echo $userAvatar; ?>" alt="Profile"> My Profile</a>
                        <a href="recent.php"><img src="https://img.icons8.com/fluency/48/time-machine.png" alt="Documents"> My Documents</a>
                        <a href="logout.php" class="logout-link"><img src="https://img.icons8.com/fluency/48/logout-rounded.png" alt="Logout"> Logout</a>
                    </div>
                </li>
                <?php else: ?>
                <li><a href="login.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/login-rounded-right.png" alt="Login"> Login</a></li>
                <li><a href="signup.php" class="nav-link btn-signup"><img src="https://img.icons8.com/fluency/48/add-user-male.png" alt="Sign Up"> Sign Up</a></li>
                <?php endif; ?>
            </ul>
            <button class="nav-toggle" id="navToggle">
                <img src="https://img.icons8.com/fluency/48/menu.png" alt="Menu" style="width:24px;height:24px;">
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <div class="hero-content">
            <h1><img src="https://img.icons8.com/fluency/96/pdf.png" alt="EchoDoc"> EchoDoc</h1>
            <p>Transform your PDF and Word documents into natural-sounding audio with AI-powered voice synthesis</p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Message Display -->
            <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                <?php if ($messageType === 'success'): ?>
                <img src="https://img.icons8.com/fluency/48/checkmark--v1.png" alt="Success">
                <?php elseif ($messageType === 'warning'): ?>
                <img src="https://img.icons8.com/fluency/48/error--v1.png" alt="Warning">
                <?php else: ?>
                <img src="https://img.icons8.com/fluency/48/cancel--v1.png" alt="Error">
                <?php endif; ?>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <!-- Upload Section -->
            <section class="upload-section">
                <div class="card">
                    <div class="card-header">
                        <h2><img src="https://img.icons8.com/fluency/48/upload-to-ftp.png" alt="Upload"> Upload Document</h2>
                    </div>
                    <div class="card-body">
                        <?php if (isLoggedIn()): ?>
                        <form action="index.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                            <div class="upload-area" id="dropZone">
                                <div class="upload-icon">
                                    <img src="https://img.icons8.com/fluency/96/document--v1.png" alt="Document">
                                </div>
                                <h3>Drag & Drop your PDF or Word document here</h3>
                                <p>or click to browse files (PDF, DOCX)</p>
                                <input type="file" name="pdfFile" id="pdfFile" accept=".pdf,.docx" required>
                                <div class="file-info" id="fileInfo"></div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block" id="uploadBtn">
                                <img src="https://img.icons8.com/fluency/48/upload--v1.png" alt="Upload"> Upload & Extract Text
                            </button>
                        </form>
                        <?php else: ?>
                        <!-- Login Required Message -->
                        <div class="auth-required">
                            <div class="auth-required-icon">
                                <img src="https://img.icons8.com/fluency/96/lock--v1.png" alt="Login Required">
                            </div>
                            <h3>Sign In Required</h3>
                            <p>Please sign in or create an account to upload documents and convert them to audio.</p>
                            <div class="auth-required-buttons">
                                <a href="login.php" class="btn btn-primary">
                                    <img src="https://img.icons8.com/fluency/48/login-rounded-right.png" alt="Login"> Sign In
                                </a>
                                <a href="signup.php" class="btn btn-secondary">
                                    <img src="https://img.icons8.com/fluency/48/add-user-male.png" alt="Sign Up"> Create Account
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Text Display & Audio Controls - Only show for logged in users with extracted text -->
            <?php if (isLoggedIn() && !empty($extractedText)): 
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $fileIcon = $fileExt === 'docx' ? 'https://img.icons8.com/fluency/48/microsoft-word-2019.png' : 'https://img.icons8.com/fluency/48/pdf.png';
            ?>
            <section class="audio-section">
                <div class="card">
                    <div class="card-header">
                        <h2><img src="https://img.icons8.com/fluency/48/document--v1.png" alt="Document"> Extracted Text</h2>
                        <?php if (!empty($fileName)): ?>
                        <span class="file-badge"><img src="<?php echo $fileIcon; ?>" alt="<?php echo strtoupper($fileExt); ?>"> <?php echo htmlspecialchars($fileName); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <!-- Text Edit Controls -->
                        <div class="text-edit-controls">
                            <button type="button" id="editTextBtn" class="btn btn-secondary btn-sm" onclick="toggleEditMode()">
                                <img src="https://img.icons8.com/fluency/48/edit.png" alt="Edit"> Edit Text
                            </button>
                            <button type="button" id="saveTextBtn" class="btn btn-success btn-sm" onclick="saveEditedText()" style="display: none;">
                                <img src="https://img.icons8.com/fluency/48/save.png" alt="Save"> Save Changes
                            </button>
                            <button type="button" id="cancelEditBtn" class="btn btn-secondary btn-sm" onclick="cancelEdit()" style="display: none;">
                                <img src="https://img.icons8.com/fluency/48/cancel.png" alt="Cancel"> Cancel
                            </button>
                            <span id="editStatus" class="edit-status"></span>
                        </div>
                        
                        <!-- Text Display (Read-only mode) -->
                        <div class="text-display" id="textDisplay">
                            <?php echo nl2br(htmlspecialchars($extractedText)); ?>
                        </div>
                        
                        <!-- Text Editor (Edit mode) -->
                        <textarea id="textEditor" class="text-editor" style="display: none;"><?php echo htmlspecialchars($extractedText); ?></textarea>
                    </div>
                </div>

                <!-- Audio Controls -->
                <div class="card">
                    <div class="card-header">
                        <h2><img src="https://img.icons8.com/fluency/48/headphones--v1.png" alt="Audio"> Audio Controls</h2>
                    </div>
                    <div class="card-body">
                        <div class="audio-controls">
                            <!-- Voice Selection -->
                            <div class="control-group">
                                <label for="voiceSelect"><img src="https://img.icons8.com/fluency/48/user-male-circle--v1.png" alt="Voice"> Voice:</label>
                                <select id="voiceSelect" class="form-control">
                                    <?php foreach (YARNGPT_VOICES as $name => $description): ?>
                                    <option value="<?php echo htmlspecialchars($name); ?>" <?php echo $name === 'Idera' ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($name); ?> - <?php echo htmlspecialchars($description); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Translation Controls -->
                            <div class="control-group">
                                <label for="languageSelect"><img src="https://img.icons8.com/fluency/48/language.png" alt="Language"> Translate:</label>
                                <div class="translate-row">
                                    <select id="languageSelect" class="form-control">
                                        <option value="en" selected>English (Original)</option>
                                        <option value="yo">Yoruba</option>
                                        <option value="ha">Hausa</option>
                                        <option value="ig">Igbo</option>
                                    </select>
                                    <button type="button" id="translateBtn" class="btn btn-secondary">
                                        <img src="https://img.icons8.com/fluency/48/translation.png" alt="Translate"> Translate
                                    </button>
                                </div>
                                <div id="translationStatus" class="translation-status"></div>
                            </div>

                            <!-- Volume Control -->
                            <div class="control-group">
                                <label for="volumeRange"><img src="https://img.icons8.com/fluency/48/high-volume--v1.png" alt="Volume"> Volume: <span id="volumeValue">100</span>%</label>
                                <input type="range" id="volumeRange" min="0" max="1" step="0.1" value="1" class="range-slider">
                            </div>
                        </div>

                        <!-- Playback Buttons -->
                        <div class="playback-controls">
                            <button class="btn btn-success" id="playBtn" onclick="speakText()">
                                <img src="https://img.icons8.com/fluency/48/play--v1.png" alt="Play"> Play
                            </button>
                            <button class="btn btn-warning" id="pauseBtn" onclick="pauseSpeech()" disabled>
                                <img src="https://img.icons8.com/fluency/48/pause--v1.png" alt="Pause"> Pause
                            </button>
                            <button class="btn btn-info" id="resumeBtn" onclick="resumeSpeech()" disabled>
                                <img src="https://img.icons8.com/fluency/48/play--v1.png" alt="Resume"> Resume
                            </button>
                            <button class="btn btn-danger" id="stopBtn" onclick="stopSpeech()" disabled>
                                <img src="https://img.icons8.com/fluency/48/stop--v1.png" alt="Stop"> Stop
                            </button>
                            <button class="btn btn-primary" id="downloadBtn" onclick="downloadAudio()">
                                <img src="https://img.icons8.com/fluency/48/download--v1.png" alt="Download"> Download MP3
                            </button>
                        </div>
                        
                        <!-- Download Progress -->
                        <div class="download-progress" id="downloadProgress" style="display: none;">
                            <label><img src="https://img.icons8.com/fluency/48/download--v1.png" alt="Download"> Download Progress:</label>
                            <div class="progress-bar">
                                <div class="progress-fill" id="downloadProgressFill"></div>
                            </div>
                            <span id="downloadProgressText">0%</span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress-container">
                            <label><img src="https://img.icons8.com/fluency/48/bar-chart--v1.png" alt="Progress"> Reading Progress:</label>
                            <div class="progress-bar">
                                <div class="progress-fill" id="progressFill"></div>
                            </div>
                            <span id="progressText">0%</span>
                        </div>

                        <!-- Status -->
                        <div class="status-display">
                            <span class="status-label">Status:</span>
                            <span class="status-value" id="statusValue">Ready</span>
                        </div>
                        
                        <!-- Text Highlighting Toggle -->
                        <div class="highlight-toggle">
                            <button type="button" id="highlightBtn" class="btn btn-secondary btn-sm active" onclick="toggleHighlight()">
                                <img src="https://img.icons8.com/fluency/48/marker-pen.png" alt="Highlight"> Text Highlighting
                            </button>
                            <span class="toggle-hint">Highlights current sentence while reading</span>
                        </div>
                    </div>
                </div>

                <!-- Clear Session Button -->
                <div class="text-center">
                    <a href="clear_session.php" class="btn btn-secondary">
                        <img src="https://img.icons8.com/fluency/48/trash--v1.png" alt="Clear"> Clear & Upload New PDF
                    </a>
                </div>
            </section>
            <?php endif; ?>

            <!-- Features Section -->
            <section class="features-section">
                <h2 class="section-title"><img src="https://img.icons8.com/fluency/48/star--v1.png" alt="Features"> Key Features</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <img src="https://img.icons8.com/fluency/96/pdf.png" alt="PDF Upload">
                        </div>
                        <h3>PDF Upload</h3>
                        <p>Upload any PDF document and extract text content automatically</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <img src="https://img.icons8.com/fluency/96/speaker.png" alt="Text to Speech">
                        </div>
                        <h3>Text to Speech</h3>
                        <p>Convert extracted text to natural sounding speech</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <img src="https://img.icons8.com/fluency/96/microphone.png" alt="Multiple Voices">
                        </div>
                        <h3>Multiple Voices</h3>
                        <p>Choose from 16 different voice characters</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <img src="https://img.icons8.com/fluency/96/audio-wave--v1.png" alt="Customizable">
                        </div>
                        <h3>Customizable</h3>
                        <p>Adjust volume to your preference</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <img src="https://img.icons8.com/fluency/96/accessibility2.png" alt="Accessibility">
                        </div>
                        <h3>Accessibility</h3>
                        <p>Designed to assist users with reading disabilities</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">
                            <img src="https://img.icons8.com/fluency/96/graduation-cap.png" alt="Educational">
                        </div>
                        <h3>Educational</h3>
                        <p>Improve reading comprehension for students</p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><img src="https://img.icons8.com/fluency/48/pdf.png" alt="EchoDoc"> EchoDoc</h3>
                    <p>Transform your documents into audio with AI-powered voice synthesis for accessibility and convenience.</p>
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
                        <li><img src="https://img.icons8.com/fluency/48/high-volume--v1.png" alt="Audio" style="width: 16px; height: 16px;"> YarnGPT TTS API</li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Connect</h3>
                    <ul>
                        <li><a href="https://x.com/echodoc" target="_blank" rel="noopener noreferrer"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle;"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg> @echodoc</a></li>
                        <li><a href="contact.php?subject=Bug%20Report" rel="noopener" onclick="if(typeof EchoAnalytics!=='undefined'){EchoAnalytics.track('report_bug_click');}"><img src="https://img.icons8.com/fluency/48/email.png" alt="Report" style="width: 16px; height: 16px;"> Report a bug</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> EchoDoc. All rights reserved. — <a href="mailto:infoechodoc@gmail.com?subject=EchoDoc%20Bug%20Report" rel="noopener">Report a bug</a></p>
            </div>
        </div>
    </footer>

    <!-- Hidden text area for speech -->
    <textarea id="hiddenText" style="display: none;"><?php echo htmlspecialchars($extractedText); ?></textarea>

    <!-- Must Read Modal (shows after signup) -->
    <?php if (isset($_GET['welcome']) && $_GET['welcome'] == '1'): ?>
    <div class="modal-overlay" id="mustReadModal">
        <div class="modal-container must-read-modal">
            <div class="modal-header">
                <div class="modal-icon">
                    <img src="https://img.icons8.com/fluency/96/open-book--v1.png" alt="Must Read">
                </div>
                <h2>Welcome to EchoDoc!</h2>
                <p class="modal-subtitle">Please read this important information before you start</p>
            </div>
            <div class="modal-body">
                <div class="must-read-section">
                    <h3><img src="https://img.icons8.com/fluency/48/info.png" alt="Info"> API Limitations</h3>
                    <ul class="must-read-list">
                        <li>
                            <span class="list-icon"><img src="https://img.icons8.com/fluency/48/error--v1.png" alt="Limit"></span>
                            <div>
                                <strong>2,000 Character Limit</strong>
                                <p>Each API call can process a maximum of 2,000 characters. Longer texts are automatically split into chunks.</p>
                            </div>
                        </li>
                        <li>
                            <span class="list-icon"><img src="https://img.icons8.com/fluency/48/bar-chart.png" alt="Stats"></span>
                            <div>
                                <strong>80 Calls Per Day</strong>
                                <p>The API allows up to 80 requests per day. Use wisely for longer documents.</p>
                            </div>
                        </li>
                        <li>
                            <span class="list-icon"><img src="https://img.icons8.com/fluency/48/link--v1.png" alt="Chunks"></span>
                            <div>
                                <strong>Chunked Processing</strong>
                                <p>Large documents are processed in chunks and the audio is merged automatically for seamless playback.</p>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <div class="must-read-section">
                    <h3><img src="https://img.icons8.com/fluency/48/speed.png" alt="Performance"> Performance Tips</h3>
                    <ul class="must-read-list">
                        <li>
                            <span class="list-icon"><img src="https://img.icons8.com/fluency/48/save--v1.png" alt="Cache"></span>
                            <div>
                                <strong>Smart Audio Caching</strong>
                                <p>Audio is cached using SHA-256 hash (text + voice) for instant playback on repeated content.</p>
                            </div>
                        </li>
                        <li>
                            <span class="list-icon"><img src="https://img.icons8.com/fluency/48/flash-on.png" alt="Fast"></span>
                            <div>
                                <strong>Faster Playback</strong>
                                <p>Previously converted text plays instantly from cache without additional API calls.</p>
                            </div>
                        </li>
                        <li>
                            <span class="list-icon"><img src="https://img.icons8.com/fluency/48/goal--v1.png" alt="Optimize"></span>
                            <div>
                                <strong>Minimize API Calls</strong>
                                <p>Edit text before converting to reduce unnecessary API usage.</p>
                            </div>
                        </li>
                    </ul>
                </div>
                
                <div class="must-read-section">
                    <h3><img src="https://img.icons8.com/fluency/48/light-on.png" alt="Tips"> Quick Tips</h3>
                    <ul class="must-read-list tips-list">
                        <li>
                            <span class="list-icon"><img src="https://img.icons8.com/fluency/48/edit--v1.png" alt="Edit"></span>
                            <div>
                                <strong>Edit Extracted Text</strong>
                                <p>Click "Edit Text" to correct any extraction errors before converting to audio.</p>
                            </div>
                        </li>
                        <li>
                            <span class="list-icon"><img src="https://img.icons8.com/fluency/48/globe--v1.png" alt="Translate"></span>
                            <div>
                                <strong>Translation Available</strong>
                                <p>Translate your text to Yoruba, Hausa, or Igbo before audio conversion.</p>
                            </div>
                        </li>
                        <li>
                            <span class="list-icon"><img src="https://img.icons8.com/fluency/48/microphone--v1.png" alt="Voices"></span>
                            <div>
                                <strong>16 Voice Options</strong>
                                <p>Choose from 16 different AI voices with unique styles and tones.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-block" onclick="closeMustReadModal()">
                    <img src="https://img.icons8.com/fluency/48/checkmark--v1.png" alt="OK"> Got it, Let's Start!
                </button>
            </div>
        </div>
    </div>
    
    <style>
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 1rem;
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-container {
            background: #fff;
            border-radius: 20px;
            max-width: 600px;
            width: 100%;
            max-height: 85vh;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s ease;
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .must-read-modal .modal-header {
            background: linear-gradient(135deg, #3d5a80, #98c1d9);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .modal-header .modal-icon img {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
        }
        
        .modal-header h2 {
            font-size: 1.75rem;
            margin: 0 0 0.5rem 0;
            font-weight: 700;
        }
        
        .modal-subtitle {
            opacity: 0.9;
            font-size: 1rem;
            margin: 0;
        }
        
        .modal-body {
            padding: 1.5rem;
            max-height: 45vh;
            overflow-y: auto;
        }
        
        .must-read-section {
            margin-bottom: 1.5rem;
        }
        
        .must-read-section:last-child {
            margin-bottom: 0;
        }
        
        .must-read-section h3 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
            color: #343a40;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .must-read-section h3 img {
            width: 24px;
            height: 24px;
        }
        
        .must-read-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .must-read-list li {
            display: flex;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .must-read-list li:hover {
            background: #e9ecef;
        }
        
        .must-read-list li:last-child {
            margin-bottom: 0;
        }
        
        .list-icon {
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .list-icon img {
            width: 28px;
            height: 28px;
        }
        
        .must-read-list li strong {
            display: block;
            color: #343a40;
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }
        
        .must-read-list li p {
            color: #6c757d;
            font-size: 0.85rem;
            margin: 0;
            line-height: 1.4;
        }
        
        .modal-footer {
            padding: 1.25rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .modal-footer .btn {
            padding: 0.875rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .modal-footer .btn img {
            width: 20px;
            height: 20px;
        }
        
        @media (max-width: 576px) {
            .modal-container {
                max-height: 90vh;
                border-radius: 16px;
            }
            
            .modal-header {
                padding: 1.5rem;
            }
            
            .modal-header h2 {
                font-size: 1.4rem;
            }
            
            .modal-body {
                padding: 1rem;
                max-height: 50vh;
            }
            
            .must-read-list li {
                padding: 0.625rem;
            }
        }
    </style>
    
    <script>
        function closeMustReadModal() {
            const modal = document.getElementById('mustReadModal');
            modal.style.animation = 'fadeOut 0.3s ease forwards';
            setTimeout(() => {
                modal.remove();
                // Remove ?welcome=1 from URL without refresh
                const url = new URL(window.location);
                url.searchParams.delete('welcome');
                window.history.replaceState({}, '', url);
            }, 300);
        }
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('mustReadModal');
                if (modal) closeMustReadModal();
            }
        });
        
        // Add fadeOut animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
    <?php endif; ?>

    <!-- JavaScript -->
    <script src="assets/js/analytics.js?v=1"></script>
    <script src="assets/js/speech.js?v=14"></script>
    <script src="assets/js/main.js?v=15"></script>
</body>
</html>
