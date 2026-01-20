/**
 * EchoDoc Analytics Helper
 * Tracks user events for analytics
 */

const EchoAnalytics = {
    /**
     * Track an event
     * @param {string} event - Event type
     * @param {object} data - Event data
     */
    track: function(event, data = {}) {
        try {
            fetch('api/analytics.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ event, data })
            }).catch(err => console.log('Analytics tracking failed:', err));
        } catch (e) {
            // Silent fail - don't interrupt user experience
        }
    },

    /**
     * Track page view
     * @param {string} page - Page name
     */
    pageView: function(page = null) {
        const pageName = page || window.location.pathname.split('/').pop() || 'index';
        this.track('page_view', { page: pageName });
    },

    /**
     * Track file upload
     * @param {string} fileType - File type (pdf, docx, txt)
     * @param {number} fileSize - File size in bytes
     */
    upload: function(fileType, fileSize) {
        this.track('upload', { file_type: fileType, file_size: fileSize });
    },

    /**
     * Track audio play
     * @param {string} document - Document name
     * @param {string} voice - Voice used
     * @param {number} duration - Duration in seconds
     */
    audioPlay: function(document = null, voice = 'default', duration = 0) {
        this.track('audio_play', { document, voice, duration });
    },

    /**
     * Track download
     * @param {string} document - Document name
     * @param {string} format - Download format (mp3)
     */
    download: function(document = null, format = 'mp3') {
        this.track('download', { document, format });
    },

    /**
     * Track TTS generation
     * @param {number} textLength - Length of text
     * @param {string} voice - Voice used
     */
    ttsGenerate: function(textLength, voice) {
        this.track('tts', { text_length: textLength, voice });
    },

    /**
     * Track translation
     * @param {string} sourceLang - Source language
     * @param {string} targetLang - Target language
     */
    translate: function(sourceLang, targetLang) {
        this.track('translate', { source_lang: sourceLang, target_lang: targetLang });
    }
};

// Auto-track page view on load
document.addEventListener('DOMContentLoaded', function() {
    EchoAnalytics.pageView();
});
