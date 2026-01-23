<?php
/**
 * EchoDoc - Admin Analytics Dashboard
 */

require_once 'includes/auth.php';
require_once 'includes/analytics.php';
require_once 'config.php';

// Check if user is logged in AND is admin
requireAdmin();

// Get analytics data
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
$analytics = getAnalyticsSummary($days);

// Language code to name mapping
$languageNames = [
    'yo' => 'Yoruba',
    'ha' => 'Hausa',
    'ig' => 'Igbo',
    'en' => 'English',
    'unknown' => 'Unknown'
];

// SEO - noindex for admin-only page
$noIndex = true;
$metaTitle = 'Analytics Dashboard - EchoDoc';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - EchoDoc</title>
    <link rel="icon" type="image/png" href="https://img.icons8.com/fluency/48/pdf.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/pages.css">
    <?php include 'includes/seo.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #495057;
            margin-bottom: 6px;
        }
        .stat-card .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-card .stat-icon {
            width: 40px;
            height: 40px;
            margin-bottom: 10px;
        }
        .stat-card.highlight {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stat-card.highlight .stat-value,
        .stat-card.highlight .stat-label {
            color: white;
        }
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 16px;
            color: #343a40;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .data-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        .data-table tr:hover {
            background: #f8f9fa;
        }
        .data-table td {
            font-size: 0.95rem;
        }
        .period-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .period-btn {
            padding: 8px 16px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            color: #495057;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .period-btn:hover {
            background: #f8f9fa;
        }
        .period-btn.active {
            background: #495057;
            color: white;
            border-color: #495057;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        @media (max-width: 1024px) {
            .grid-3 {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 768px) {
            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
            }
        }
        .voice-badge {
            display: inline-block;
            padding: 4px 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .lang-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .lang-badge.yoruba { background: #fef3c7; color: #92400e; }
        .lang-badge.hausa { background: #dbeafe; color: #1e40af; }
        .lang-badge.igbo { background: #dcfce7; color: #166534; }
        .lang-badge.english { background: #f3e8ff; color: #6b21a8; }
        .progress-bar-mini {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 4px;
        }
        .progress-bar-mini .fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            margin-top: 30px;
        }
        .section-header h2 {
            font-size: 1.3rem;
            color: #343a40;
            margin: 0;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        .empty-state img {
            width: 64px;
            height: 64px;
            opacity: 0.5;
            margin-bottom: 12px;
        }
        .rank-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            background: #e9ecef;
            border-radius: 50%;
            font-size: 0.75rem;
            font-weight: 600;
            color: #495057;
        }
        .rank-number.gold { background: #fef3c7; color: #92400e; }
        .rank-number.silver { background: #e5e7eb; color: #374151; }
        .rank-number.bronze { background: #fed7aa; color: #9a3412; }
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
                <li><a href="analytics.php" class="nav-link active"><img src="https://img.icons8.com/fluency/48/analytics.png" alt="Analytics"> Analytics</a></li>
                <li><a href="profile.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/user-male-circle.png" alt="Profile"> Profile</a></li>
            </ul>
        </div>
    </nav>

    <main class="main-content">
        <div class="analytics-container">
            <h1 style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                <img src="https://img.icons8.com/fluency/48/analytics.png" alt="Analytics" style="width: 36px; height: 36px;">
                Analytics Dashboard
            </h1>
            <p style="color: #6c757d; margin-bottom: 20px;">Monitor how users interact with EchoDoc features</p>
            
            <!-- Period Selector -->
            <div class="period-selector">
                <a href="?days=7" class="period-btn <?php echo $days === 7 ? 'active' : ''; ?>">Last 7 Days</a>
                <a href="?days=30" class="period-btn <?php echo $days === 30 ? 'active' : ''; ?>">Last 30 Days</a>
                <a href="?days=90" class="period-btn <?php echo $days === 90 ? 'active' : ''; ?>">Last 90 Days</a>
                <a href="?days=365" class="period-btn <?php echo $days === 365 ? 'active' : ''; ?>">Last Year</a>
            </div>

            <?php if ($analytics): ?>
            
            <!-- Primary Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <img src="https://img.icons8.com/fluency/48/user-group-man-man.png" alt="Users" class="stat-icon">
                    <div class="stat-value"><?php echo number_format($analytics['total_users']); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <img src="https://img.icons8.com/fluency/48/add-user-male.png" alt="New Users" class="stat-icon">
                    <div class="stat-value"><?php echo number_format($analytics['new_users']); ?></div>
                    <div class="stat-label">New Users</div>
                </div>
                <div class="stat-card">
                    <img src="https://img.icons8.com/fluency/48/activity-feed.png" alt="Active" class="stat-icon">
                    <div class="stat-value"><?php echo number_format($analytics['active_users']); ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-card">
                    <img src="https://img.icons8.com/fluency/48/upload-to-ftp.png" alt="Uploads" class="stat-icon">
                    <div class="stat-value"><?php echo number_format($analytics['total_uploads']); ?></div>
                    <div class="stat-label">Uploads</div>
                </div>
                <div class="stat-card highlight">
                    <img src="https://img.icons8.com/fluency/48/microphone.png" alt="TTS" class="stat-icon" style="filter: brightness(10);">
                    <div class="stat-value"><?php echo number_format($analytics['total_tts_generations'] ?? 0); ?></div>
                    <div class="stat-label">TTS Generations</div>
                </div>
                <div class="stat-card">
                    <img src="https://img.icons8.com/fluency/48/download--v1.png" alt="Downloads" class="stat-icon">
                    <div class="stat-value"><?php echo number_format($analytics['total_downloads']); ?></div>
                    <div class="stat-label">MP3 Downloads</div>
                </div>
                <div class="stat-card">
                    <img src="https://img.icons8.com/fluency/48/translation.png" alt="Translations" class="stat-icon">
                    <div class="stat-value"><?php echo number_format($analytics['total_translations'] ?? 0); ?></div>
                    <div class="stat-label">Translations</div>
                </div>
                <div class="stat-card">
                    <img src="https://img.icons8.com/fluency/48/play--v1.png" alt="Plays" class="stat-icon">
                    <div class="stat-value"><?php echo number_format($analytics['total_plays']); ?></div>
                    <div class="stat-label">Audio Plays</div>
                </div>
            </div>

            <!-- Characters Processed Info -->
            <?php if (isset($analytics['total_tts_characters']) && $analytics['total_tts_characters'] > 0): ?>
            <div class="chart-container" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center; padding: 30px;">
                <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 8px;">Total Characters Processed by TTS</div>
                <div style="font-size: 2.5rem; font-weight: 700;">
                    <?php 
                    $chars = $analytics['total_tts_characters'];
                    if ($chars >= 1000000) {
                        echo number_format($chars / 1000000, 1) . 'M';
                    } elseif ($chars >= 1000) {
                        echo number_format($chars / 1000, 1) . 'K';
                    } else {
                        echo number_format($chars);
                    }
                    ?>
                </div>
                <div style="font-size: 0.85rem; opacity: 0.8; margin-top: 8px;">
                    ≈ <?php echo number_format(ceil($analytics['total_tts_characters'] / 1500)); ?> pages worth of text
                </div>
            </div>
            <?php endif; ?>

            <!-- Activity Over Time Chart -->
            <div class="chart-container">
                <div class="chart-title">📈 Activity Over Time</div>
                <canvas id="activityChart" height="80"></canvas>
            </div>

            <!-- Voice & Language Section -->
            <div class="section-header">
                <h2>🎤 Voice & Language Analytics</h2>
            </div>

            <div class="grid-3">
                <!-- Popular Voices -->
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="https://img.icons8.com/fluency/24/microphone.png" alt="">
                        Voice Usage
                    </div>
                    <?php if (!empty($analytics['popular_voices'])): ?>
                    <canvas id="voiceChart" height="200"></canvas>
                    <table class="data-table" style="margin-top: 16px;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Voice</th>
                                <th>Uses</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics['popular_voices'] as $i => $voice): ?>
                            <tr>
                                <td>
                                    <span class="rank-number <?php echo $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : '')); ?>">
                                        <?php echo $i + 1; ?>
                                    </span>
                                </td>
                                <td><span class="voice-badge"><?php echo htmlspecialchars($voice['voice'] ?: 'Unknown'); ?></span></td>
                                <td><strong><?php echo number_format($voice['use_count']); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <img src="https://img.icons8.com/fluency/64/microphone.png" alt="">
                        <p>No voice usage data yet</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Popular Languages -->
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="https://img.icons8.com/fluency/24/translation.png" alt="">
                        Translation Languages
                    </div>
                    <?php if (!empty($analytics['popular_languages'])): ?>
                    <canvas id="languageChart" height="200"></canvas>
                    <table class="data-table" style="margin-top: 16px;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Language</th>
                                <th>Translations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics['popular_languages'] as $i => $lang): 
                                $langCode = $lang['language'] ?: 'unknown';
                                $langName = $languageNames[$langCode] ?? ucfirst($langCode);
                                $langClass = strtolower($langName);
                            ?>
                            <tr>
                                <td>
                                    <span class="rank-number <?php echo $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : '')); ?>">
                                        <?php echo $i + 1; ?>
                                    </span>
                                </td>
                                <td><span class="lang-badge <?php echo $langClass; ?>"><?php echo htmlspecialchars($langName); ?></span></td>
                                <td><strong><?php echo number_format($lang['use_count']); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <img src="https://img.icons8.com/fluency/64/translation.png" alt="">
                        <p>No translation data yet</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- File Types -->
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="https://img.icons8.com/fluency/24/document.png" alt="">
                        Upload File Types
                    </div>
                    <?php if (!empty($analytics['file_type_breakdown'])): ?>
                    <canvas id="fileTypeChart" height="200"></canvas>
                    <table class="data-table" style="margin-top: 16px;">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Uploads</th>
                                <th>Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalUploads = array_sum(array_column($analytics['file_type_breakdown'], 'upload_count'));
                            foreach ($analytics['file_type_breakdown'] as $type): 
                                $percent = $totalUploads > 0 ? round(($type['upload_count'] / $totalUploads) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td>
                                    <strong style="text-transform: uppercase;"><?php echo htmlspecialchars($type['file_type'] ?: 'Unknown'); ?></strong>
                                </td>
                                <td><?php echo number_format($type['upload_count']); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div class="progress-bar-mini" style="width: 60px;">
                                            <div class="fill" style="width: <?php echo $percent; ?>%; background: <?php echo strtolower($type['file_type']) === 'pdf' ? '#ef4444' : '#2563eb'; ?>;"></div>
                                        </div>
                                        <span><?php echo $percent; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <img src="https://img.icons8.com/fluency/64/document.png" alt="">
                        <p>No upload data yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Usage Patterns Section -->
            <div class="section-header">
                <h2>⏰ Usage Patterns</h2>
            </div>

            <div class="grid-2">
                <!-- Hourly Usage -->
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="https://img.icons8.com/fluency/24/clock.png" alt="">
                        Activity by Hour of Day
                    </div>
                    <?php if (!empty($analytics['hourly_usage'])): ?>
                    <canvas id="hourlyChart" height="150"></canvas>
                    <?php else: ?>
                    <div class="empty-state">
                        <img src="https://img.icons8.com/fluency/64/clock.png" alt="">
                        <p>No hourly data yet</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Voice Usage Over Time -->
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="https://img.icons8.com/fluency/24/microphone.png" alt="">
                        Voice Popularity Trend
                    </div>
                    <?php if (!empty($analytics['voice_usage_over_time'])): ?>
                    <canvas id="voiceTrendChart" height="150"></canvas>
                    <?php else: ?>
                    <div class="empty-state">
                        <img src="https://img.icons8.com/fluency/64/microphone.png" alt="">
                        <p>No voice trend data yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Documents & Users Section -->
            <div class="section-header">
                <h2>📊 Top Content & Users</h2>
            </div>

            <div class="grid-2">
                <!-- Top Documents -->
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="https://img.icons8.com/fluency/24/document.png" alt="">
                        Most Played Documents
                    </div>
                    <?php if (!empty($analytics['top_documents'])): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Document</th>
                                <th>Plays</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics['top_documents'] as $i => $doc): ?>
                            <tr>
                                <td>
                                    <span class="rank-number <?php echo $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : '')); ?>">
                                        <?php echo $i + 1; ?>
                                    </span>
                                </td>
                                <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($doc['document'] ?: 'Unknown'); ?>
                                </td>
                                <td><strong><?php echo number_format($doc['play_count']); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <img src="https://img.icons8.com/fluency/64/document.png" alt="">
                        <p>No document data yet</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Most Active Users -->
                <div class="chart-container">
                    <div class="chart-title">
                        <img src="https://img.icons8.com/fluency/24/user-male-circle.png" alt="">
                        Most Active Users
                    </div>
                    <?php if (!empty($analytics['most_active_users'])): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Events</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics['most_active_users'] as $i => $user): ?>
                            <tr>
                                <td>
                                    <span class="rank-number <?php echo $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : '')); ?>">
                                        <?php echo $i + 1; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username'] ?: 'Anonymous'); ?></strong>
                                    <?php if ($user['email']): ?>
                                    <br><span style="font-size: 0.8rem; color: #6c757d;"><?php echo htmlspecialchars($user['email']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo number_format($user['event_count']); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <img src="https://img.icons8.com/fluency/64/user-male-circle.png" alt="">
                        <p>No user activity data yet</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
            <div class="chart-container">
                <div class="empty-state">
                    <img src="https://img.icons8.com/fluency/96/info.png" alt="Info">
                    <h3>Analytics data not available</h3>
                    <p>Make sure the database tables are set up correctly and events are being tracked.</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php if ($analytics): ?>
    <script>
        // Color palette
        const colors = {
            primary: '#667eea',
            secondary: '#764ba2',
            success: '#10b981',
            warning: '#f59e0b',
            danger: '#ef4444',
            info: '#3b82f6',
            purple: '#8b5cf6',
            pink: '#ec4899',
            teal: '#14b8a6',
            orange: '#f97316'
        };

        const chartColors = [
            colors.primary, colors.success, colors.warning, colors.danger,
            colors.info, colors.purple, colors.pink, colors.teal, colors.orange, colors.secondary
        ];

        // Daily Activity Chart
        <?php if (!empty($analytics['daily_stats'])): ?>
        const dailyData = <?php echo json_encode($analytics['daily_stats']); ?>;
        
        new Chart(document.getElementById('activityChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: dailyData.map(d => d.date),
                datasets: [
                    {
                        label: 'Page Views',
                        data: dailyData.map(d => parseInt(d.page_views)),
                        borderColor: colors.info,
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'TTS Plays',
                        data: dailyData.map(d => parseInt(d.plays)),
                        borderColor: colors.success,
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Downloads',
                        data: dailyData.map(d => parseInt(d.downloads)),
                        borderColor: colors.primary,
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Uploads',
                        data: dailyData.map(d => parseInt(d.uploads)),
                        borderColor: colors.warning,
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        <?php endif; ?>

        // Voice Usage Pie Chart
        <?php if (!empty($analytics['popular_voices'])): ?>
        const voiceData = <?php echo json_encode($analytics['popular_voices']); ?>;
        
        new Chart(document.getElementById('voiceChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: voiceData.map(v => v.voice || 'Unknown'),
                datasets: [{
                    data: voiceData.map(v => parseInt(v.use_count)),
                    backgroundColor: chartColors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } }
                }
            }
        });
        <?php endif; ?>

        // Language Usage Pie Chart
        <?php if (!empty($analytics['popular_languages'])): ?>
        const langData = <?php echo json_encode($analytics['popular_languages']); ?>;
        const langNames = <?php echo json_encode($languageNames); ?>;
        
        new Chart(document.getElementById('languageChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: langData.map(l => langNames[l.language] || l.language || 'Unknown'),
                datasets: [{
                    data: langData.map(l => parseInt(l.use_count)),
                    backgroundColor: [colors.warning, colors.info, colors.success, colors.purple],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } }
                }
            }
        });
        <?php endif; ?>

        // File Type Pie Chart
        <?php if (!empty($analytics['file_type_breakdown'])): ?>
        const fileTypeData = <?php echo json_encode($analytics['file_type_breakdown']); ?>;
        
        new Chart(document.getElementById('fileTypeChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: fileTypeData.map(f => (f.file_type || 'Unknown').toUpperCase()),
                datasets: [{
                    data: fileTypeData.map(f => parseInt(f.upload_count)),
                    backgroundColor: [colors.danger, colors.info, colors.success],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } }
                }
            }
        });
        <?php endif; ?>

        // Hourly Usage Bar Chart
        <?php if (!empty($analytics['hourly_usage'])): ?>
        const hourlyData = <?php echo json_encode($analytics['hourly_usage']); ?>;
        
        // Fill in missing hours with 0
        const hourlyFull = Array.from({length: 24}, (_, i) => {
            const found = hourlyData.find(h => parseInt(h.hour) === i);
            return found ? parseInt(found.event_count) : 0;
        });
        
        new Chart(document.getElementById('hourlyChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: Array.from({length: 24}, (_, i) => i.toString().padStart(2, '0') + ':00'),
                datasets: [{
                    label: 'Events',
                    data: hourlyFull,
                    backgroundColor: hourlyFull.map((_, i) => {
                        // Highlight peak hours
                        const max = Math.max(...hourlyFull);
                        const val = hourlyFull[i];
                        if (val === max) return colors.primary;
                        if (val >= max * 0.7) return colors.info;
                        return 'rgba(102, 126, 234, 0.4)';
                    }),
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true },
                    x: { 
                        ticks: { 
                            maxRotation: 45, 
                            minRotation: 45,
                            callback: function(val, index) {
                                return index % 3 === 0 ? this.getLabelForValue(val) : '';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Voice Trend Line Chart
        <?php if (!empty($analytics['voice_usage_over_time'])): ?>
        const voiceTrendData = <?php echo json_encode($analytics['voice_usage_over_time']); ?>;
        
        // Group by date and voice
        const dateVoiceMap = {};
        const allVoices = new Set();
        const allDates = new Set();
        
        voiceTrendData.forEach(item => {
            if (!item.voice) return;
            allVoices.add(item.voice);
            allDates.add(item.date);
            if (!dateVoiceMap[item.date]) dateVoiceMap[item.date] = {};
            dateVoiceMap[item.date][item.voice] = parseInt(item.use_count);
        });
        
        const sortedDates = Array.from(allDates).sort();
        const voiceArray = Array.from(allVoices);
        
        const voiceTrendDatasets = voiceArray.slice(0, 5).map((voice, i) => ({
            label: voice,
            data: sortedDates.map(date => dateVoiceMap[date]?.[voice] || 0),
            borderColor: chartColors[i],
            backgroundColor: chartColors[i] + '20',
            fill: false,
            tension: 0.4
        }));
        
        new Chart(document.getElementById('voiceTrendChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: sortedDates,
                datasets: voiceTrendDatasets
            },
            options: {
                responsive: true,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12 } }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        <?php endif; ?>
    </script>
    <?php endif; ?>
</body>
</html>
