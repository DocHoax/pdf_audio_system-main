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
        
        // Check if user_analytics table has the expected columns by trying a query
        $hasEventType = false;
        $debugError = '';
        try {
            // Debug: check which database we're connected to
            $dbCheck = $pdo->query("SELECT DATABASE()")->fetchColumn();
            $debugError = "DB: " . $dbCheck . " | ";
            
            // Debug: check table structure
            $colsQuery = $pdo->query("SHOW COLUMNS FROM user_analytics");
            $cols = $colsQuery->fetchAll(PDO::FETCH_COLUMN);
            $debugError .= "Columns: " . implode(", ", $cols) . " | ";
            
            if (in_array('event_type', $cols)) {
                $hasEventType = true;
                $debugError = ''; // Clear if successful
            } else {
                $debugError .= "event_type not in list!";
            }
        } catch (PDOException $e) {
            $debugError .= "Error: " . $e->getMessage();
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
        
        // Include flag for debugging
        $stats['analytics_enabled'] = $hasEventType;
        $stats['debug_error'] = $debugError;
        
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
        /* Stats Page - Clean Modern Design */
        :root {
            --stats-accent: #3d5a80;
            --stats-accent-light: #e8f0f7;
            --stats-text-dark: #1a1a2e;
            --stats-text-muted: #6c757d;
            --stats-border: #e9ecef;
            --stats-bg: #f8f9fa;
        }
        
        .stats-hero {
            background: var(--stats-bg);
            padding: 3rem 1rem 5rem;
            text-align: center;
            border-bottom: 1px solid var(--stats-border);
        }
        
        .stats-hero h1 {
            font-size: 2.25rem;
            margin: 0 0 0.5rem;
            font-weight: 700;
            color: var(--stats-text-dark);
        }
        
        .stats-hero p {
            font-size: 1rem;
            color: var(--stats-text-muted);
            max-width: 500px;
            margin: 0 auto;
        }
        
        .stats-container {
            max-width: 1000px;
            margin: -2.5rem auto 3rem;
            padding: 0 1rem;
        }
        
        /* Main Stats Cards */
        .main-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
            margin-bottom: 2.5rem;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.75rem;
            text-align: center;
            border: 1px solid var(--stats-border);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: all 0.2s ease;
        }
        
        .stat-card:hover {
            border-color: var(--stats-accent);
            box-shadow: 0 4px 16px rgba(61, 90, 128, 0.1);
        }
        
        .stat-icon {
            width: 52px;
            height: 52px;
            margin-bottom: 0.75rem;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--stats-accent);
            margin-bottom: 0.25rem;
            line-height: 1.1;
        }
        
        .stat-label {
            color: var(--stats-text-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        /* Leaderboard Section */
        .leaderboards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .leaderboard-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid var(--stats-border);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .leaderboard-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            padding-bottom: 0.875rem;
            border-bottom: 1px solid var(--stats-border);
        }
        
        .leaderboard-header img {
            width: 28px;
            height: 28px;
        }
        
        .leaderboard-header h2 {
            font-size: 1.1rem;
            color: var(--stats-text-dark);
            margin: 0;
            font-weight: 600;
        }
        
        .leaderboard-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .leaderboard-item {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            padding: 0.875rem;
            border-radius: 10px;
            margin-bottom: 0.375rem;
            background: var(--stats-bg);
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }
        
        .leaderboard-item:last-child {
            margin-bottom: 0;
        }
        
        .leaderboard-item:hover {
            border-color: var(--stats-accent);
            background: var(--stats-accent-light);
        }
        
        .rank {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        
        .rank-1 {
            background: #fef3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }
        
        .rank-2 {
            background: #e9ecef;
            color: #495057;
            border: 1px solid #adb5bd;
        }
        
        .rank-3 {
            background: #f5e6d3;
            color: #8b4513;
            border: 1px solid #deb887;
        }
        
        .rank-default {
            background: #fff;
            color: var(--stats-text-muted);
            border: 1px solid var(--stats-border);
        }
        
        .item-info {
            flex: 1;
            min-width: 0;
        }
        
        .item-name {
            font-weight: 600;
            color: var(--stats-text-dark);
            font-size: 0.95rem;
        }
        
        .item-count {
            color: var(--stats-text-muted);
            font-size: 0.8rem;
        }
        
        .item-bar {
            height: 4px;
            background: var(--stats-border);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .item-bar-fill {
            height: 100%;
            background: var(--stats-accent);
            border-radius: 2px;
            transition: width 0.5s ease;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2rem 1rem;
            color: var(--stats-text-muted);
        }
        
        .empty-state img {
            width: 56px;
            height: 56px;
            opacity: 0.4;
            margin-bottom: 0.75rem;
        }
        
        .empty-state h3 {
            color: var(--stats-text-dark);
            margin-bottom: 0.5rem;
        }
        
        /* Live Indicator */
        .live-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: #fff;
            border: 1px solid var(--stats-border);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--stats-text-muted);
            margin-bottom: 1rem;
        }
        
        .live-dot {
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.15); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stats-hero {
                padding: 2.5rem 1rem 4rem;
            }
            
            .stats-hero h1 {
                font-size: 1.75rem;
            }
            
            .main-stats {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.25rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            .stat-icon {
                width: 44px;
                height: 44px;
            }
            
            .leaderboards {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .leaderboard-card {
                padding: 1.25rem;
            }
        }
        
        @media (max-width: 480px) {
            .stats-hero {
                padding: 2rem 1rem 3rem;
            }
            
            .stats-hero h1 {
                font-size: 1.5rem;
            }
            
            .stats-container {
                margin-top: -1.5rem;
            }
            
            .stat-value {
                font-size: 1.75rem;
            }
            
            .stat-label {
                font-size: 0.75rem;
            }
            
            .leaderboard-item {
                padding: 0.75rem;
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
        
        <?php if (isset($stats['analytics_enabled']) && !$stats['analytics_enabled']): ?>
        <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 10px; padding: 1rem; margin-bottom: 1.5rem; text-align: center;">
            <strong>⚠️ Analytics tracking not enabled.</strong><br>
            <small>Run <code>database/stats_migration.sql</code> in phpMyAdmin to enable audio & download tracking.</small>
            <?php if (!empty($stats['debug_error'])): ?>
            <br><small style="color:#666;">Debug: <?php echo htmlspecialchars($stats['debug_error']); ?></small>
            <?php endif; ?>
        </div>
        <?php endif; ?>
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
