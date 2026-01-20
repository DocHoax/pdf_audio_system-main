/**
 * EchoDoc - Text-to-Speech Module
 * Uses the YarnGPT API for speech synthesis
 * Processes text in chunks for better performance
 */

// Audio Variables
let audioElement = null;
let currentText = '';
let isPaused = false;
let isPlaying = false;
let isLoading = false;
let audioUrl = null;

// Chunking Variables
let chunks = [];
let currentChunkIndex = 0;
let audioQueue = []; // Pre-fetched audio blobs
const CHUNK_SIZE = 500; // Characters per chunk
const PREFETCH_AHEAD = 2; // How many chunks to prefetch

// Text Highlighting
let highlightEnabled = true;
let originalDisplayHTML = '';

// DOM Elements
const voiceSelect = document.getElementById('voiceSelect');
const volumeRange = document.getElementById('volumeRange');
const volumeValue = document.getElementById('volumeValue');
const playBtn = document.getElementById('playBtn');
const pauseBtn = document.getElementById('pauseBtn');
const resumeBtn = document.getElementById('resumeBtn');
const stopBtn = document.getElementById('stopBtn');
const progressFill = document.getElementById('progressFill');
const progressText = document.getElementById('progressText');
const statusValue = document.getElementById('statusValue');
const hiddenText = document.getElementById('hiddenText');

// YarnGPT API Configuration
const TTS_API_ENDPOINT = 'api/tts.php';

/**
 * Initialize the speech synthesis system
 */
function initSpeech() {
    // Create audio element
    audioElement = new Audio();
    audioElement.preload = 'auto';
    audioElement.addEventListener('ended', handleAudioEnded);
    audioElement.addEventListener('error', handleAudioError);
    audioElement.addEventListener('play', handleAudioPlay);
    audioElement.addEventListener('pause', handleAudioPause);
    audioElement.addEventListener('timeupdate', handleTimeUpdate);

    // Get text from hidden textarea
    if (hiddenText) {
        currentText = hiddenText.value.trim();
    }

    // Initialize volume display
    if (volumeValue && volumeRange) {
        volumeValue.textContent = Math.round(volumeRange.value * 100);
    }

    // Add event listener for volume changes
    if (volumeRange) {
        volumeRange.addEventListener('input', function() {
            volumeValue.textContent = Math.round(this.value * 100);
            if (audioElement) {
                audioElement.volume = parseFloat(this.value);
            }
        });
    }

    updateStatus('Ready');
}

/**
 * Highlight current chunk in text display
 */
function highlightCurrentChunk() {
    if (!highlightEnabled) return;
    
    const textDisplay = document.getElementById('textDisplay');
    if (!textDisplay || chunks.length === 0) return;
    
    // Save original HTML if not saved
    if (!originalDisplayHTML) {
        originalDisplayHTML = textDisplay.innerHTML;
    }
    
    // Build highlighted HTML
    let html = '';
    for (let i = 0; i < chunks.length; i++) {
        const chunkText = chunks[i].replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>');
        if (i === currentChunkIndex) {
            html += `<span class="highlight-chunk active">${chunkText}</span> `;
        } else if (i < currentChunkIndex) {
            html += `<span class="highlight-chunk read">${chunkText}</span> `;
        } else {
            html += `<span class="highlight-chunk">${chunkText}</span> `;
        }
    }
    
    textDisplay.innerHTML = html;
    
    // Scroll to active chunk
    const activeChunk = textDisplay.querySelector('.highlight-chunk.active');
    if (activeChunk) {
        activeChunk.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

/**
 * Clear text highlighting
 */
function clearHighlight() {
    const textDisplay = document.getElementById('textDisplay');
    if (textDisplay && originalDisplayHTML) {
        textDisplay.innerHTML = originalDisplayHTML;
        originalDisplayHTML = '';
    }
}

/**
 * Toggle text highlighting
 */
function toggleHighlight() {
    highlightEnabled = !highlightEnabled;
    const btn = document.getElementById('highlightBtn');
    if (btn) {
        btn.classList.toggle('active', highlightEnabled);
        const icon = btn.querySelector('img');
        if (icon) {
            icon.style.opacity = highlightEnabled ? '1' : '0.5';
        }
    }
    
    if (!highlightEnabled) {
        clearHighlight();
    } else if (isPlaying && chunks.length > 0) {
        highlightCurrentChunk();
    }
}

/**
 * Update the current text (called when translation changes the text)
 */
function updateCurrentText(newText) {
    currentText = newText.trim();
    // Stop any ongoing playback when text changes
    if (isPlaying) {
        stopSpeech();
    }
    // Reset chunks
    chunks = [];
    currentChunkIndex = 0;
    audioQueue = [];
    updateStatus('Ready');
}

/**
 * Split text into chunks at sentence boundaries
 */
function splitTextIntoChunks(text) {
    const result = [];
    
    // Split by sentences first
    const sentences = text.match(/[^.!?]+[.!?]+|[^.!?]+$/g) || [text];
    
    let currentChunk = '';
    
    for (const sentence of sentences) {
        const trimmedSentence = sentence.trim();
        
        if (!trimmedSentence) continue;
        
        // If adding this sentence would exceed chunk size, save current chunk and start new one
        if (currentChunk.length + trimmedSentence.length > CHUNK_SIZE && currentChunk.length > 0) {
            result.push(currentChunk.trim());
            currentChunk = trimmedSentence;
        } else {
            currentChunk += (currentChunk ? ' ' : '') + trimmedSentence;
        }
    }
    
    // Don't forget the last chunk
    if (currentChunk.trim()) {
        result.push(currentChunk.trim());
    }
    
    return result.length > 0 ? result : [text];
}

/**
 * Convert text to speech using YarnGPT API
 */
async function textToSpeech(text, voice) {
    try {
        const response = await fetch(TTS_API_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                text: text,
                voice: voice,
                response_format: 'mp3'
            })
        });

        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || `HTTP error: ${response.status}`);
        }
        
        if (!data.success) {
            throw new Error(data.error || 'TTS conversion failed');
        }

        return data;
    } catch (error) {
        console.error('TTS API Error:', error);
        throw error;
    }
}

/**
 * Prefetch upcoming chunks
 */
async function prefetchChunks(startIndex) {
    const selectedVoice = voiceSelect ? voiceSelect.value : 'Idera';
    
    for (let i = startIndex; i < Math.min(startIndex + PREFETCH_AHEAD, chunks.length); i++) {
        // Skip if already fetched
        if (audioQueue[i]) continue;
        
        // Mark as fetching (null placeholder)
        audioQueue[i] = null;
        
        try {
            const result = await textToSpeech(chunks[i], selectedVoice);
            const audioData = result.audio;
            const mimeType = result.mime_type || 'audio/mpeg';
            const audioBlob = base64ToBlob(audioData, mimeType);
            audioQueue[i] = URL.createObjectURL(audioBlob);
        } catch (error) {
            console.error(`Error prefetching chunk ${i}:`, error);
            audioQueue[i] = 'error';
        }
    }
}

/**
 * Start speaking the text
 */
async function speakText() {
    if (!currentText || currentText.length === 0) {
        updateStatus('No text to read');
        return;
    }

    if (isLoading) {
        updateStatus('Already processing...');
        return;
    }

    // Stop any ongoing playback
    stopSpeech();

    // Split text into chunks
    chunks = splitTextIntoChunks(currentText);
    currentChunkIndex = 0;
    audioQueue = [];

    // Get selected voice
    const selectedVoice = voiceSelect ? voiceSelect.value : 'Idera';

    // Update UI
    isLoading = true;
    isPlaying = true;
    isPaused = false;
    updateButtons();
    updateStatus(`Loading chunk 1 of ${chunks.length}...`);
    updateProgress(0);
    
    // Initialize highlighting
    originalDisplayHTML = '';
    if (highlightEnabled) {
        highlightCurrentChunk();
    }

    try {
        // Fetch first chunk
        const result = await textToSpeech(chunks[0], selectedVoice);
        
        if (!isPlaying) return; // Check if stopped while loading

        // Create audio URL from base64
        const audioData = result.audio;
        const mimeType = result.mime_type || 'audio/mpeg';
        const audioBlob = base64ToBlob(audioData, mimeType);
        
        // Clean up previous audio URL
        if (audioUrl) {
            URL.revokeObjectURL(audioUrl);
        }
        audioUrl = URL.createObjectURL(audioBlob);
        audioQueue[0] = audioUrl;

        // Start prefetching next chunks
        prefetchChunks(1);

        // Play the first chunk
        isLoading = false;
        audioElement.src = audioUrl;
        audioElement.volume = volumeRange ? parseFloat(volumeRange.value) : 1;
        
        await audioElement.play();
        updateStatus(`Playing chunk ${currentChunkIndex + 1} of ${chunks.length}...`);
        
        // Track audio play analytics
        if (typeof EchoAnalytics !== 'undefined') {
            const fileNameElement = document.querySelector('.file-badge');
            const docName = fileNameElement ? fileNameElement.textContent.trim() : null;
            EchoAnalytics.audioPlay(docName, selectedVoice, 0);
            EchoAnalytics.ttsGenerate(currentText.length, selectedVoice);
        }
    } catch (error) {
        console.error('TTS Error:', error);
        isLoading = false;
        isPlaying = false;
        updateStatus('Error: ' + error.message);
        updateButtons();
    }
}

/**
 * Play the next chunk
 */
async function playNextChunk() {
    currentChunkIndex++;
    
    if (currentChunkIndex >= chunks.length) {
        // All chunks done
        finishSpeaking();
        return;
    }

    const selectedVoice = voiceSelect ? voiceSelect.value : 'Idera';
    
    updateStatus(`Loading chunk ${currentChunkIndex + 1} of ${chunks.length}...`);
    isLoading = true;

    try {
        let chunkAudioUrl = audioQueue[currentChunkIndex];
        
        // If not prefetched or errored, fetch now
        if (!chunkAudioUrl || chunkAudioUrl === 'error') {
            const result = await textToSpeech(chunks[currentChunkIndex], selectedVoice);
            
            if (!isPlaying) return;
            
            const audioData = result.audio;
            const mimeType = result.mime_type || 'audio/mpeg';
            const audioBlob = base64ToBlob(audioData, mimeType);
            chunkAudioUrl = URL.createObjectURL(audioBlob);
            audioQueue[currentChunkIndex] = chunkAudioUrl;
        }
        
        // Wait for prefetched URL if it's still loading (null)
        if (chunkAudioUrl === null) {
            // Wait a bit and check again
            let attempts = 0;
            while (audioQueue[currentChunkIndex] === null && attempts < 50) {
                await new Promise(resolve => setTimeout(resolve, 100));
                attempts++;
            }
            chunkAudioUrl = audioQueue[currentChunkIndex];
            
            if (!chunkAudioUrl || chunkAudioUrl === 'error') {
                // Fetch directly
                const result = await textToSpeech(chunks[currentChunkIndex], selectedVoice);
                if (!isPlaying) return;
                const audioData = result.audio;
                const mimeType = result.mime_type || 'audio/mpeg';
                const audioBlob = base64ToBlob(audioData, mimeType);
                chunkAudioUrl = URL.createObjectURL(audioBlob);
                audioQueue[currentChunkIndex] = chunkAudioUrl;
            }
        }

        // Start prefetching next chunks
        prefetchChunks(currentChunkIndex + 1);

        // Clean up previous audio URL
        if (audioUrl && audioUrl !== chunkAudioUrl) {
            URL.revokeObjectURL(audioUrl);
        }
        audioUrl = chunkAudioUrl;

        // Play the chunk
        isLoading = false;
        audioElement.src = audioUrl;
        audioElement.volume = volumeRange ? parseFloat(volumeRange.value) : 1;
        
        await audioElement.play();
        updateStatus(`Playing chunk ${currentChunkIndex + 1} of ${chunks.length}...`);
        
        // Update highlighting
        if (highlightEnabled) {
            highlightCurrentChunk();
        }
    } catch (error) {
        console.error('Error playing next chunk:', error);
        isLoading = false;
        isPlaying = false;
        updateStatus('Error: ' + error.message);
        updateButtons();
    }
}

/**
 * Convert base64 string to Blob
 */
function base64ToBlob(base64, mimeType) {
    const byteCharacters = atob(base64);
    const byteNumbers = new Array(byteCharacters.length);
    for (let i = 0; i < byteCharacters.length; i++) {
        byteNumbers[i] = byteCharacters.charCodeAt(i);
    }
    const byteArray = new Uint8Array(byteNumbers);
    return new Blob([byteArray], { type: mimeType });
}

/**
 * Handle audio ended event
 */
function handleAudioEnded() {
    if (isPlaying && currentChunkIndex < chunks.length - 1) {
        // Play next chunk
        playNextChunk();
    } else {
        finishSpeaking();
    }
}

/**
 * Handle audio error
 */
function handleAudioError(event) {
    // Ignore errors from empty src or page URL (happens when stopping)
    const src = audioElement.src;
    if (!src || src === '' || src === window.location.href || src.endsWith('index.php')) {
        return;
    }
    
    console.error('Audio playback error:', audioElement.error ? audioElement.error.message : 'unknown');
    updateStatus('Error playing audio');
    isPlaying = false;
    isLoading = false;
    updateButtons();
}

/**
 * Handle audio play event
 */
function handleAudioPlay() {
    isPlaying = true;
    isPaused = false;
    updateButtons();
}

/**
 * Handle audio pause event
 */
function handleAudioPause() {
    if (!audioElement.ended) {
        isPaused = true;
        updateButtons();
    }
}

/**
 * Handle time update for progress
 */
function handleTimeUpdate() {
    if (audioElement && audioElement.duration > 0 && chunks.length > 0) {
        // Calculate overall progress based on chunks completed + current chunk progress
        const chunkProgress = audioElement.currentTime / audioElement.duration;
        const overallProgress = ((currentChunkIndex + chunkProgress) / chunks.length) * 100;
        updateProgress(overallProgress);
    }
}

/**
 * Update progress bar and text
 */
function updateProgress(progress) {
    const roundedProgress = Math.round(progress);
    
    if (progressFill) {
        progressFill.style.width = roundedProgress + '%';
    }
    
    if (progressText) {
        progressText.textContent = roundedProgress + '%';
    }
}

/**
 * Finish speaking
 */
function finishSpeaking() {
    isPlaying = false;
    isPaused = false;
    isLoading = false;
    
    // Show 100% progress
    updateProgress(100);
    
    updateStatus('Finished');
    updateButtons();
    
    // Clear highlighting after delay
    setTimeout(() => {
        if (!isPlaying) {
            clearHighlight();
        }
    }, 2000);
    
    // Cleanup audio queue
    cleanupAudioQueue();
    
    // Reset progress after delay
    setTimeout(() => {
        if (!isPlaying) {
            updateProgress(0);
            updateStatus('Ready');
        }
    }, 2000);
}

/**
 * Cleanup audio queue URLs
 */
function cleanupAudioQueue() {
    for (const url of audioQueue) {
        if (url && url !== 'error' && url !== audioUrl) {
            URL.revokeObjectURL(url);
        }
    }
    audioQueue = [];
}

/**
 * Pause speech
 */
function pauseSpeech() {
    if (audioElement && isPlaying && !isPaused) {
        audioElement.pause();
        isPaused = true;
        updateStatus('Paused');
        updateButtons();
    }
}

/**
 * Resume speech
 */
function resumeSpeech() {
    if (audioElement && isPaused) {
        audioElement.play();
        isPaused = false;
        updateStatus(`Playing chunk ${currentChunkIndex + 1} of ${chunks.length}...`);
        updateButtons();
    }
}

/**
 * Stop speech
 */
function stopSpeech() {
    if (audioElement) {
        // Remove error listener temporarily to avoid error when clearing src
        audioElement.removeEventListener('error', handleAudioError);
        audioElement.pause();
        audioElement.currentTime = 0;
        audioElement.removeAttribute('src');
        audioElement.load(); // Reset the audio element
        // Re-add error listener
        audioElement.addEventListener('error', handleAudioError);
    }
    
    // Clean up audio URLs
    if (audioUrl) {
        URL.revokeObjectURL(audioUrl);
        audioUrl = null;
    }
    cleanupAudioQueue();
    
    // Reset chunk state
    chunks = [];
    currentChunkIndex = 0;
    
    isPlaying = false;
    isPaused = false;
    isLoading = false;
    
    updateProgress(0);
    
    // Clear highlighting
    clearHighlight();
    
    updateStatus('Stopped');
    updateButtons();
    
    setTimeout(() => {
        if (!isPlaying) {
            updateStatus('Ready');
        }
    }, 1000);
}

/**
 * Update button states
 */
function updateButtons() {
    if (playBtn) playBtn.disabled = isPlaying && !isPaused;
    if (pauseBtn) pauseBtn.disabled = !isPlaying || isPaused || isLoading;
    if (resumeBtn) resumeBtn.disabled = !isPaused;
    if (stopBtn) stopBtn.disabled = !isPlaying && !isPaused && !isLoading;
}

/**
 * Update status display
 */
function updateStatus(message) {
    if (statusValue) {
        statusValue.textContent = message;
        
        // Update status color based on state
        statusValue.className = 'status-value';
        if (message.includes('Playing')) {
            statusValue.style.color = '#10b981';
        } else if (message.includes('Paused')) {
            statusValue.style.color = '#f59e0b';
        } else if (message.includes('Loading')) {
            statusValue.style.color = '#8b5cf6';
        } else if (message.includes('Error') || message.includes('Stopped')) {
            statusValue.style.color = '#ef4444';
        } else if (message.includes('Finished')) {
            statusValue.style.color = '#2563eb';
        } else {
            statusValue.style.color = '#2563eb';
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initSpeech);

// Handle page visibility to pause/resume speech
document.addEventListener('visibilitychange', function() {
    if (document.hidden && isPlaying && !isPaused) {
        pauseSpeech();
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    stopSpeech();
});

// Download Variables
let isDownloading = false;
const downloadBtn = document.getElementById('downloadBtn');
const downloadProgress = document.getElementById('downloadProgress');
const downloadProgressFill = document.getElementById('downloadProgressFill');
const downloadProgressText = document.getElementById('downloadProgressText');

/**
 * Update download progress display
 */
function updateDownloadProgress(percent) {
    if (downloadProgressFill) {
        downloadProgressFill.style.width = percent + '%';
    }
    if (downloadProgressText) {
        downloadProgressText.textContent = Math.round(percent) + '%';
    }
}

/**
 * Download generated audio as MP3
 */
async function downloadAudio() {
    if (!currentText || currentText.length === 0) {
        updateStatus('No text to convert');
        return;
    }

    if (isDownloading) {
        updateStatus('Download already in progress...');
        return;
    }

    // Get selected voice
    const selectedVoice = voiceSelect ? voiceSelect.value : 'Idera';

    // Split text into chunks
    const downloadChunks = splitTextIntoChunks(currentText);
    const totalChunks = downloadChunks.length;

    // Update UI
    isDownloading = true;
    if (downloadBtn) downloadBtn.disabled = true;
    if (downloadProgress) downloadProgress.style.display = 'block';
    updateDownloadProgress(0);
    updateStatus(`Generating audio... (0/${totalChunks} chunks)`);

    const audioBlobs = [];

    try {
        // Fetch all chunks
        for (let i = 0; i < totalChunks; i++) {
            updateStatus(`Generating audio... (${i + 1}/${totalChunks} chunks)`);
            updateDownloadProgress(((i + 1) / totalChunks) * 90); // Reserve 10% for combining

            const result = await textToSpeech(downloadChunks[i], selectedVoice);
            
            if (!result.success || !result.audio) {
                throw new Error(`Failed to generate audio for chunk ${i + 1}`);
            }

            const audioData = result.audio;
            const mimeType = result.mime_type || 'audio/mpeg';
            const blob = base64ToBlob(audioData, mimeType);
            audioBlobs.push(blob);
        }

        updateStatus('Combining audio chunks...');
        updateDownloadProgress(95);

        // Combine all blobs into one
        const combinedBlob = new Blob(audioBlobs, { type: 'audio/mpeg' });

        updateDownloadProgress(100);
        updateStatus('Download ready!');

        // Create download link
        const downloadUrl = URL.createObjectURL(combinedBlob);
        const link = document.createElement('a');
        link.href = downloadUrl;
        
        // Generate filename from document name or use default
        const fileNameElement = document.querySelector('.file-badge');
        let baseName = 'echodoc_audio';
        if (fileNameElement) {
            const fullName = fileNameElement.textContent.trim();
            baseName = fullName.replace(/\.(pdf|docx)$/i, '').trim() || 'echodoc_audio';
        }
        link.download = baseName + '.mp3';
        
        // Trigger download
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Track download analytics
        if (typeof EchoAnalytics !== 'undefined') {
            EchoAnalytics.download(baseName, 'mp3');
        }
        
        // Send email notification that MP3 is ready
        sendAudioReadyNotification(baseName + '.mp3');
        
        // Cleanup
        setTimeout(() => {
            URL.revokeObjectURL(downloadUrl);
        }, 1000);

        setTimeout(() => {
            updateStatus('Ready');
            if (downloadProgress) downloadProgress.style.display = 'none';
        }, 2000);

    } catch (error) {
        console.error('Download error:', error);
        updateStatus('Download failed: ' + error.message);
    } finally {
        isDownloading = false;
        if (downloadBtn) downloadBtn.disabled = false;
    }
}

/**
 * Send email notification when audio is ready
 */
async function sendAudioReadyNotification(documentName) {
    try {
        const response = await fetch('api/notify-audio-ready.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                document_name: documentName
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('Audio ready notification sent');
        } else {
            console.log('Notification not sent:', data.error || data.message);
        }
    } catch (error) {
        // Silently fail - notification is not critical
        console.log('Could not send notification:', error.message);
    }
}
