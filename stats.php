<?php
/**
 * EchoDoc - Public Stats Page
 * Shows live statistics about users, voices, and languages
 */

require_once 'env.php';
require_once 'includes/db_config.php';
require_once 'config.php';

// Get public stats (no admin required)
$dbError = '';

function getPublicStats() {
    global $dbError;
    
    $pdo = getDbConnection();
    if (!$pdo) {
        $dbError = 'Database connection failed. Please check your database settings.';
        return null;
    }
    
    try {
        $stats = [];
        
        // Total users - this should always work
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $stats['total_users'] = $stmt->fetch()['total'];
        
        // Check if user_analytics table has the expected columns
        $hasEventType = false;
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM user_analytics LIKE 'event_type'");
            $hasEventType = $checkStmt->rowCount() > 0;
        } catch (PDOException $e) {
            // Table might not exist or have different structure
        }
        
        if ($hasEventType) {
            // Total audio plays (all time)
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_analytics WHERE event_type IN ('play_audio', 'tts_generate', 'tts')");
            $stats['total_plays'] = $stmt->fetch()['total'];
            
            // Total downloads (all time)
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM user_analytics WHERE event_type IN ('download_mp3', 'download')");
            $stats['total_downloads'] = $stmt->fetch()['total'];
            
            // Top 5 voices (all time)
            $stmt = $pdo->query("
                SELECT 
                    JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.voice')) as voice,
                    COUNT(*) as use_count
                FROM user_analytics
                WHERE event_type IN ('play_audio', 'tts_generate', 'tts')
                AND JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.voice')) IS NOT NULL
                GROUP BY voice
                ORDER BY use_count DESC
                LIMIT 5
            ");
            $stats['top_voices'] = $stmt->fetchAll();
            
            // Top 5 languages (all time)
            $stmt = $pdo->query("
                SELECT 
                    JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.target_lang')) as language,
                    COUNT(*) as use_count
                FROM user_analytics
                WHERE event_type = 'translate'
                AND JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.target_lang')) IS NOT NULL
                GROUP BY language
                ORDER BY use_count DESC
                LIMIT 5
            ");
            $stats['top_languages'] = $stmt->fetchAll();
        } else {
            // Fallback: set defaults when analytics table structure is different
            $stats['total_plays'] = 0;
            $stats['total_downloads'] = 0;
            $stats['top_voices'] = [];
            $stats['top_languages'] = [];
        }
        
        return $stats;
        
    } catch (PDOException $e) {
        $dbError = 'Database error: ' . $e->getMessage();
        error_log("Public stats error: " . $e->getMessage());
        return null;
    }
}

$stats = getPublicStats();

// Language code to name mapping
$languageNames = [
    'yo' => 'Yoruba',
    'ha' => 'Hausa',
    'ig' => 'Igbo',
    'en' => 'English',
    'unknown' => 'Unknown'
];

// Page SEO
$metaTitle = 'EchoDoc Stats - Live User & Voice Statistics';
$metaDescription = 'View real-time statistics about EchoDoc usage including total users, most popular AI voices, and top languages used for PDF to audio conversion.';
$metaKeywords = 'EchoDoc stats, PDF reader statistics, Nigerian language usage, Yoruba TTS stats';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/performance.php'; ?>
    <link rel="icon" type="image/png" href="https://img.icons8.com/fluency/48/pdf.png">
    <title><?php echo htmlspecialchars($metaTitle); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/pages.css">
    <?php include 'includes/seo.php'; ?>
    <style>
        .stats-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 4rem 1rem;
            text-align: center;
            color: #fff;
        }
        
        .stats-hero h1 {
            font-size: 2.5rem;
            margin: 0 0 0.75rem;
            font-weight: 800;
        }
        
        .stats-hero p {
            font-size: 1.1rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .stats-container {
            max-width: 1100px;
            margin: -3rem auto 3rem;
            padding: 0 1rem;
        }
        
        /* Main Stats Cards */
        .main-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon {
            width: 64px;
            height: 64px;
            margin-bottom: 1rem;
        }
        
        .stat-value {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        /* Leaderboard Section */
        .leaderboards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }
        
        .leaderboard-card {
            background: #fff;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
        
        .leaderboard-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .leaderboard-header img {
            width: 32px;
            height: 32px;
        }
        
        .leaderboard-header h2 {
            font-size: 1.25rem;
            color: #1a1a2e;
            margin: 0;
        }
        
        .leaderboard-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .leaderboard-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
            transition: all 0.2s ease;
        }
        
        .leaderboard-item:hover {
            background: #e9ecef;
            transform: translateX(4px);
        }
        
        .rank {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        
        .rank-1 {
            background: linear-gradient(135deg, #ffd700 0%, #ffb700 100%);
            color: #000;
        }
        
        .rank-2 {
            background: linear-gradient(135deg, #c0c0c0 0%, #a0a0a0 100%);
            color: #000;
        }
        
        .rank-3 {
            background: linear-gradient(135deg, #cd7f32 0%, #b87333 100%);
            color: #fff;
        }
        
        .rank-default {
            background: #e9ecef;
            color: #495057;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: #1a1a2e;
            font-size: 1rem;
        }
        
        .item-count {
            color: #6c757d;
            font-size: 0.85rem;
        }
        
        .item-bar {
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .item-bar-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 3px;
            transition: width 0.5s ease;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }
        
        .empty-state img {
            width: 64px;
            height: 64px;
            opacity: 0.5;
            margin-bottom: 1rem;
        }
        
        /* Live Indicator */
        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        
        .live-dot {
            width: 8px;
            height: 8px;
            background: #2ecc71;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stats-hero h1 {
                font-size: 1.75rem;
            }
            
            .main-stats {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
            
            .stat-value {
                font-size: 2.25rem;
            }
            
            .stat-icon {
                width: 48px;
                height: 48px;
            }
            
            .leaderboards {
                grid-template-columns: 1fr;
            }
            
            .leaderboard-card {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .stats-hero {
                padding: 3rem 1rem;
            }
            
            .stats-hero h1 {
                font-size: 1.5rem;
            }
            
            .stats-container {
                margin-top: -2rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            .stat-label {
                font-size: 0.85rem;
            }
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
                <li><a href="about.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/info.png" alt="About"> About</a></li>
                <li><a href="stats.php" class="nav-link active"><img src="https://img.icons8.com/fluency/48/combo-chart--v1.png" alt="Stats"> Stats</a></li>
                <li><a href="help.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/help.png" alt="Help"> Help</a></li>
            </ul>
            <button class="nav-toggle" id="navToggle">
                <img src="https://img.icons8.com/fluency/48/menu.png" alt="Menu" style="width:24px;height:24px;">
            </button>
        </div>
    </nav>

    <!-- Stats Hero -->
    <header class="stats-hero">
        <div class="live-badge">
            <span class="live-dot"></span>
            Live Statistics
        </div>
        <h1>📊 EchoDoc Stats</h1>
        <p>Real-time usage statistics showing our growing community and most popular features</p>
    </header>

    <!-- Stats Content -->
    <main class="stats-container">
        <?php if ($stats): ?>
        
        <!-- Main Stats Cards -->
        <div class="main-stats">
            <div class="stat-card">
                <img src="https://img.icons8.com/fluency/96/user-group-man-man.png" alt="Users" class="stat-icon">
                <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            
            <div class="stat-card">
                <img src="https://img.icons8.com/fluency/96/audio-wave--v1.png" alt="Plays" class="stat-icon">
                <div class="stat-value"><?php echo number_format($stats['total_plays']); ?></div>
                <div class="stat-label">Audio Generations</div>
            </div>
            
            <div class="stat-card">
                <img src="https://img.icons8.com/fluency/96/download--v1.png" alt="Downloads" class="stat-icon">
                <div class="stat-value"><?php echo number_format($stats['total_downloads']); ?></div>
                <div class="stat-label">MP3 Downloads</div>
            </div>
        </div>
        
        <!-- Leaderboards -->
        <div class="leaderboards">
            <!-- Top Voices -->
            <div class="leaderboard-card">
                <div class="leaderboard-header">
                    <img src="https://img.icons8.com/fluency/48/microphone--v1.png" alt="Voices">
                    <h2>Top AI Voices</h2>
                </div>
                
                <?php if (!empty($stats['top_voices'])): ?>
                    <?php $maxVoiceCount = $stats['top_voices'][0]['use_count']; ?>
                    <ul class="leaderboard-list">
                        <?php foreach ($stats['top_voices'] as $index => $voice): ?>
                            <?php 
                                $rank = $index + 1;
                                $rankClass = $rank <= 3 ? "rank-{$rank}" : "rank-default";
                                $percentage = $maxVoiceCount > 0 ? ($voice['use_count'] / $maxVoiceCount) * 100 : 0;
                            ?>
                            <li class="leaderboard-item">
                                <div class="rank <?php echo $rankClass; ?>"><?php echo $rank; ?></div>
                                <div class="item-info">
                                    <div class="item-name"><?php echo htmlspecialchars($voice['voice'] ?: 'Unknown'); ?></div>
                                    <div class="item-count"><?php echo number_format($voice['use_count']); ?> uses</div>
                                    <div class="item-bar">
                                        <div class="item-bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state">
                        <img src="https://img.icons8.com/fluency/96/microphone--v1.png" alt="No data">
                        <p>No voice data available yet</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Top Languages -->
            <div class="leaderboard-card">
                <div class="leaderboard-header">
                    <img src="https://img.icons8.com/fluency/48/globe--v1.png" alt="Languages">
                    <h2>Top Languages</h2>
                </div>
                
                <?php if (!empty($stats['top_languages'])): ?>
                    <?php $maxLangCount = $stats['top_languages'][0]['use_count']; ?>
                    <ul class="leaderboard-list">
                        <?php foreach ($stats['top_languages'] as $index => $lang): ?>
                            <?php 
                                $rank = $index + 1;
                                $rankClass = $rank <= 3 ? "rank-{$rank}" : "rank-default";
                                $percentage = $maxLangCount > 0 ? ($lang['use_count'] / $maxLangCount) * 100 : 0;
                                $langName = $languageNames[$lang['language']] ?? ucfirst($lang['language'] ?: 'Unknown');
                            ?>
                            <li class="leaderboard-item">
                                <div class="rank <?php echo $rankClass; ?>"><?php echo $rank; ?></div>
                                <div class="item-info">
                                    <div class="item-name"><?php echo htmlspecialchars($langName); ?></div>
                                    <div class="item-count"><?php echo number_format($lang['use_count']); ?> translations</div>
                                    <div class="item-bar">
                                        <div class="item-bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="empty-state">
                        <img src="https://img.icons8.com/fluency/96/globe--v1.png" alt="No data">
                        <p>No language data available yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php else: ?>
        <div class="empty-state" style="padding: 4rem;">
            <img src="https://img.icons8.com/fluency/96/database.png" alt="Error">
            <h3>Unable to load stats</h3>
            <p><?php echo htmlspecialchars($dbError ?: 'Please check the database connection and try again.'); ?></p>
        </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><img src="https://img.icons8.com/fluency/48/pdf.png" alt="EchoDoc" style="width: 24px; height: 24px; vertical-align: middle;"> EchoDoc</h3>
                    <p>Transform your documents into audio with AI-powered voice synthesis.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php"><img src="https://img.icons8.com/fluency/48/home.png" alt="Home" style="width: 16px; height: 16px;"> Home</a></li>
                        <li><a href="about.php"><img src="https://img.icons8.com/fluency/48/info.png" alt="About" style="width: 16px; height: 16px;"> About</a></li>
                        <li><a href="stats.php"><img src="https://img.icons8.com/fluency/48/combo-chart--v1.png" alt="Stats" style="width: 16px; height: 16px;"> Stats</a></li>
                        <li><a href="contact.php"><img src="https://img.icons8.com/fluency/48/email.png" alt="Contact" style="width: 16px; height: 16px;"> Contact</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> EchoDoc. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
</body>
</html>
