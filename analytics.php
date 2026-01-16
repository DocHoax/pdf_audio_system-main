<?php
/**
 * EchoDoc - Admin Analytics Dashboard
 */

require_once 'includes/auth.php';
require_once 'includes/analytics.php';
require_once 'config.php';

// Check if user is logged in and is admin (for now, just check logged in)
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get analytics data
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
$analytics = getAnalyticsSummary($days);
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #495057;
            margin-bottom: 8px;
        }
        .stat-card .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            margin-bottom: 12px;
        }
        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .chart-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #343a40;
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
        }
        .data-table tr:hover {
            background: #f8f9fa;
        }
        .period-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .period-btn {
            padding: 8px 16px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            color: #495057;
        }
        .period-btn.active {
            background: #495057;
            color: white;
            border-color: #495057;
        }
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .two-columns {
                grid-template-columns: 1fr;
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
                <li><a href="analytics.php" class="nav-link active"><img src="https://img.icons8.com/fluency/48/analytics.png" alt="Analytics"> Analytics</a></li>
                <li><a href="profile.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/user-male-circle.png" alt="Profile"> Profile</a></li>
            </ul>
        </div>
    </nav>

    <main class="main-content">
        <div class="analytics-container">
            <h1 style="margin-bottom: 10px;"><img src="https://img.icons8.com/fluency/48/analytics.png" alt="Analytics" style="vertical-align: middle;"> Analytics Dashboard</h1>
            
            <!-- Period Selector -->
            <div class="period-selector">
                <a href="?days=7" class="period-btn <?php echo $days === 7 ? 'active' : ''; ?>">Last 7 Days</a>
                <a href="?days=30" class="period-btn <?php echo $days === 30 ? 'active' : ''; ?>">Last 30 Days</a>
                <a href="?days=90" class="period-btn <?php echo $days === 90 ? 'active' : ''; ?>">Last 90 Days</a>
            </div>

            <?php if ($analytics): ?>
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <img src="https://img.icons8.com/fluency/48/user-group-man-man.png" alt="Users" class="stat-icon">
                    <div class="stat-value"><?php echo number_format($analytics['total_users']); ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <img src="https://img.icons8.com/fluency/48/add-user-male.png" alt="New Users" class="stat-icon">
                    <div class="stat-value"><?php echo number_format($analytics['new_users']); ?></div>
                    <div class="stat-label">New Users (<?php echo $days; ?>d)</div>
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
                <div class="stat-card">
                    <img src="https://img.icons8.com/fluency/48/play--v1.png" alt="Plays" class="stat-icon">
                    <div class="stat-value"><?php echo number_format($analytics['total_plays']); ?></div>
                    <div class="stat-label">Audio Plays</div>
                </div>
                <div class="stat-card">
                    <img src="https://img.icons8.com/fluency/48/download--v1.png" alt="Downloads" class="stat-icon">
                    <div class="stat-value"><?php echo number_format($analytics['total_downloads']); ?></div>
                    <div class="stat-label">MP3 Downloads</div>
                </div>
            </div>

            <!-- Activity Chart -->
            <div class="chart-container">
                <div class="chart-title">📈 Activity Over Time</div>
                <canvas id="activityChart" height="100"></canvas>
            </div>

            <!-- Two Column Layout -->
            <div class="two-columns">
                <!-- Top Documents -->
                <div class="chart-container">
                    <div class="chart-title">📄 Top Documents</div>
                    <?php if (!empty($analytics['top_documents'])): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Plays</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics['top_documents'] as $doc): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doc['document'] ?: 'Unknown'); ?></td>
                                <td><?php echo number_format($doc['play_count']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="color: #6c757d; text-align: center; padding: 20px;">No data yet</p>
                    <?php endif; ?>
                </div>

                <!-- Popular Voices -->
                <div class="chart-container">
                    <div class="chart-title">🎤 Popular Voices</div>
                    <?php if (!empty($analytics['popular_voices'])): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Voice</th>
                                <th>Uses</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics['popular_voices'] as $voice): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($voice['voice'] ?: 'Unknown'); ?></td>
                                <td><?php echo number_format($voice['use_count']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="color: #6c757d; text-align: center; padding: 20px;">No data yet</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
            <div class="chart-container">
                <p style="text-align: center; padding: 40px; color: #6c757d;">
                    <img src="https://img.icons8.com/fluency/96/info.png" alt="Info" style="display: block; margin: 0 auto 20px;"><br>
                    Analytics data not available. Make sure the database tables are set up correctly.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <?php if ($analytics && !empty($analytics['daily_stats'])): ?>
    <script>
        const dailyData = <?php echo json_encode($analytics['daily_stats']); ?>;
        
        const ctx = document.getElementById('activityChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dailyData.map(d => d.date),
                datasets: [
                    {
                        label: 'Page Views',
                        data: dailyData.map(d => d.page_views),
                        borderColor: '#6c757d',
                        backgroundColor: 'rgba(108, 117, 125, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Uploads',
                        data: dailyData.map(d => d.uploads),
                        borderColor: '#495057',
                        backgroundColor: 'rgba(73, 80, 87, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Audio Plays',
                        data: dailyData.map(d => d.plays),
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Downloads',
                        data: dailyData.map(d => d.downloads),
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
