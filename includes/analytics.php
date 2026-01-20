<?php
/**
 * EchoDoc - Analytics Helper
 * 
 * Track user activity and generate analytics
 */

require_once __DIR__ . '/db_config.php';

/**
 * Track an analytics event
 */
function trackEvent($eventType, $eventData = null, $userId = null) {
    $pdo = getDbConnection();
    if (!$pdo) return false;
    
    try {
        // Get or create session ID
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $sessionId = session_id() ?: bin2hex(random_bytes(32));
        
        // Get user ID from session if not provided
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id'];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO user_analytics (user_id, session_id, event_type, event_data, page_url, ip_address, user_agent)
            VALUES (:user_id, :session_id, :event_type, :event_data, :page_url, :ip_address, :user_agent)
        ");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':session_id' => $sessionId,
            ':event_type' => $eventType,
            ':event_data' => $eventData ? json_encode($eventData) : null,
            ':page_url' => $_SERVER['REQUEST_URI'] ?? null,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Analytics tracking error: " . $e->getMessage());
        return false;
    }
}

/**
 * Track page view
 */
function trackPageView($pageName = null) {
    $data = ['page' => $pageName ?: basename($_SERVER['PHP_SELF'])];
    return trackEvent('page_view', $data);
}

/**
 * Track document upload
 */
function trackUpload($fileName, $fileType, $fileSize) {
    return trackEvent('upload', [
        'file_name' => $fileName,
        'file_type' => $fileType,
        'file_size' => $fileSize
    ]);
}

/**
 * Track audio play
 */
function trackAudioPlay($documentName, $voice) {
    return trackEvent('play_audio', [
        'document' => $documentName,
        'voice' => $voice
    ]);
}

/**
 * Track MP3 download
 */
function trackDownload($documentName) {
    return trackEvent('download_mp3', [
        'document' => $documentName
    ]);
}

/**
 * Get analytics summary for dashboard
 */
function getAnalyticsSummary($days = 30) {
    $pdo = getDbConnection();
    if (!$pdo) return null;
    
    try {
        $summary = [];
        
        // Total users
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
        $summary['total_users'] = $stmt->fetch()['total'];
        
        // New users in period
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)");
        $stmt->execute([':days' => $days]);
        $summary['new_users'] = $stmt->fetch()['total'];
        
        // Active users (logged in during period)
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as total FROM user_analytics WHERE user_id IS NOT NULL AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)");
        $stmt->execute([':days' => $days]);
        $summary['active_users'] = $stmt->fetch()['total'];
        
        // Total uploads in period
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_analytics WHERE event_type = 'upload' AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)");
        $stmt->execute([':days' => $days]);
        $summary['total_uploads'] = $stmt->fetch()['total'];
        
        // Total audio plays
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_analytics WHERE event_type = 'play_audio' AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)");
        $stmt->execute([':days' => $days]);
        $summary['total_plays'] = $stmt->fetch()['total'];
        
        // Total downloads
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_analytics WHERE event_type = 'download_mp3' AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)");
        $stmt->execute([':days' => $days]);
        $summary['total_downloads'] = $stmt->fetch()['total'];
        
        // Total page views
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM user_analytics WHERE event_type = 'page_view' AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)");
        $stmt->execute([':days' => $days]);
        $summary['total_page_views'] = $stmt->fetch()['total'];
        
        // Daily stats for chart
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(CASE WHEN event_type = 'page_view' THEN 1 END) as page_views,
                COUNT(CASE WHEN event_type = 'upload' THEN 1 END) as uploads,
                COUNT(CASE WHEN event_type = 'play_audio' THEN 1 END) as plays,
                COUNT(CASE WHEN event_type = 'download_mp3' THEN 1 END) as downloads,
                COUNT(DISTINCT user_id) as unique_users
            FROM user_analytics
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute([':days' => $days]);
        $summary['daily_stats'] = $stmt->fetchAll();
        
        // Top documents
        $stmt = $pdo->prepare("
            SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.document')) as document,
                COUNT(*) as play_count
            FROM user_analytics
            WHERE event_type = 'play_audio' AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY document
            ORDER BY play_count DESC
            LIMIT 10
        ");
        $stmt->execute([':days' => $days]);
        $summary['top_documents'] = $stmt->fetchAll();
        
        // Popular voices (from both play_audio and tts_generate events)
        $stmt = $pdo->prepare("
            SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.voice')) as voice,
                COUNT(*) as use_count
            FROM user_analytics
            WHERE (event_type = 'play_audio' OR event_type = 'tts_generate' OR event_type = 'tts')
            AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            AND JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.voice')) IS NOT NULL
            GROUP BY voice
            ORDER BY use_count DESC
            LIMIT 10
        ");
        $stmt->execute([':days' => $days]);
        $summary['popular_voices'] = $stmt->fetchAll();
        
        // TTS generation stats
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM user_analytics 
            WHERE (event_type = 'tts_generate' OR event_type = 'tts')
            AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
        ");
        $stmt->execute([':days' => $days]);
        $summary['total_tts_generations'] = $stmt->fetch()['total'];
        
        // Total characters processed by TTS
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.text_length')) AS UNSIGNED)), 0) as total_chars
            FROM user_analytics
            WHERE (event_type = 'tts_generate' OR event_type = 'tts')
            AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
        ");
        $stmt->execute([':days' => $days]);
        $summary['total_tts_characters'] = $stmt->fetch()['total_chars'];
        
        // Translation stats - total translations
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM user_analytics 
            WHERE event_type = 'translate'
            AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
        ");
        $stmt->execute([':days' => $days]);
        $summary['total_translations'] = $stmt->fetch()['total'];
        
        // Popular target languages
        $stmt = $pdo->prepare("
            SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.target_lang')) as language,
                COUNT(*) as use_count
            FROM user_analytics
            WHERE event_type = 'translate'
            AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            AND JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.target_lang')) IS NOT NULL
            GROUP BY language
            ORDER BY use_count DESC
            LIMIT 10
        ");
        $stmt->execute([':days' => $days]);
        $summary['popular_languages'] = $stmt->fetchAll();
        
        // Voice usage over time (for chart)
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.voice')) as voice,
                COUNT(*) as use_count
            FROM user_analytics
            WHERE (event_type = 'play_audio' OR event_type = 'tts_generate' OR event_type = 'tts')
            AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            AND JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.voice')) IS NOT NULL
            GROUP BY DATE(created_at), voice
            ORDER BY date ASC
        ");
        $stmt->execute([':days' => $days]);
        $summary['voice_usage_over_time'] = $stmt->fetchAll();
        
        // File type breakdown (PDF vs DOCX uploads)
        $stmt = $pdo->prepare("
            SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.file_type')) as file_type,
                COUNT(*) as upload_count
            FROM user_analytics
            WHERE event_type = 'upload'
            AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY file_type
            ORDER BY upload_count DESC
        ");
        $stmt->execute([':days' => $days]);
        $summary['file_type_breakdown'] = $stmt->fetchAll();
        
        // Hourly usage pattern
        $stmt = $pdo->prepare("
            SELECT 
                HOUR(created_at) as hour,
                COUNT(*) as event_count
            FROM user_analytics
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY HOUR(created_at)
            ORDER BY hour ASC
        ");
        $stmt->execute([':days' => $days]);
        $summary['hourly_usage'] = $stmt->fetchAll();
        
        // Most active users
        $stmt = $pdo->prepare("
            SELECT 
                ua.user_id,
                u.username,
                u.email,
                COUNT(*) as event_count
            FROM user_analytics ua
            LEFT JOIN users u ON ua.user_id = u.id
            WHERE ua.user_id IS NOT NULL
            AND ua.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY ua.user_id, u.username, u.email
            ORDER BY event_count DESC
            LIMIT 10
        ");
        $stmt->execute([':days' => $days]);
        $summary['most_active_users'] = $stmt->fetchAll();
        
        // Download format stats
        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(JSON_UNQUOTE(JSON_EXTRACT(event_data, '$.format')), 'mp3') as format,
                COUNT(*) as download_count
            FROM user_analytics
            WHERE (event_type = 'download_mp3' OR event_type = 'download')
            AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY format
            ORDER BY download_count DESC
        ");
        $stmt->execute([':days' => $days]);
        $summary['download_formats'] = $stmt->fetchAll();
        
        return $summary;
        
    } catch (PDOException $e) {
        error_log("Analytics summary error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get user's activity history
 */
function getUserActivity($userId, $limit = 50) {
    $pdo = getDbConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT event_type, event_data, page_url, created_at
            FROM user_analytics
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("User activity error: " . $e->getMessage());
        return [];
    }
}
