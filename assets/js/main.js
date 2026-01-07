/**
 * EchoDoc - Main JavaScript
 * Handles UI interactions and file upload
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initNavigation();
    initFileUpload();
    initFAQ();
    initUserMenu();
});

/**
 * Initialize user menu dropdown
 */
function initUserMenu() {
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const userMenu = document.querySelector('.user-menu');
        const userDropdown = document.getElementById('userDropdown');
        
        if (userMenu && userDropdown && !userMenu.contains(e.target)) {
            userDropdown.classList.remove('show');
        }
    });
}

/**
 * Toggle user dropdown menu
 */
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

/**
 * Initialize mobile navigation toggle
 */
function initNavigation() {
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.querySelector('.nav-menu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            
            // Toggle icon
            const icon = navToggle.querySelector('img');
            if (icon) {
                if (navMenu.classList.contains('active')) {
                    icon.src = 'https://img.icons8.com/fluency/48/close-window.png';
                } else {
                    icon.src = 'https://img.icons8.com/fluency/48/menu.png';
                }
            }
        });

        // Close menu when clicking on a link
        const navLinks = navMenu.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navMenu.classList.remove('active');
                const icon = navToggle.querySelector('img');
                if (icon) {
                    icon.src = 'https://img.icons8.com/fluency/48/menu.png';
                }
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
                navMenu.classList.remove('active');
                const icon = navToggle.querySelector('img');
                if (icon) {
                    icon.src = 'https://img.icons8.com/fluency/48/menu.png';
                }
            }
        });
    }
}

/**
 * Initialize file upload functionality
 */
function initFileUpload() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('pdfFile');
    const fileInfo = document.getElementById('fileInfo');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadForm = document.getElementById('uploadForm');

    if (!dropZone || !fileInput) return;

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Highlight drop zone when item is dragged over
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        dropZone.classList.add('dragover');
    }

    function unhighlight() {
        dropZone.classList.remove('dragover');
    }

    // Handle dropped files
    dropZone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;

        if (files.length > 0) {
            fileInput.files = files;
            handleFileSelect(files[0]);
        }
    }

    // Handle file selection via input
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFileSelect(this.files[0]);
        }
    });

    function handleFileSelect(file) {
        if (!file) return;

        const fileName = file.name;
        const fileSize = formatFileSize(file.size);
        const fileType = file.type;
        const fileExtension = fileName.toLowerCase().split('.').pop();

        // Validate file type (PDF or DOCX)
        const validTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        const validExtensions = ['pdf', 'docx'];
        
        if (!validTypes.includes(fileType) && !validExtensions.includes(fileExtension)) {
            showFileInfo(`Error: "${fileName}" is not a supported file. Please upload a PDF or DOCX file.`, 'error');
            fileInput.value = '';
            return;
        }

        // Validate file size (10MB max)
        if (file.size > 10 * 1024 * 1024) {
            showFileInfo(`Error: File size (${fileSize}) exceeds 10MB limit.`, 'error');
            fileInput.value = '';
            return;
        }

        showFileInfo(`Selected: ${fileName} (${fileSize})`, 'success');
    }

    function showFileInfo(message, type) {
        if (!fileInfo) return;

        fileInfo.textContent = message;
        fileInfo.className = 'file-info show';
        
        if (type === 'error') {
            fileInfo.style.background = '#ef4444';
        } else {
            fileInfo.style.background = '#2563eb';
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Form submission handling
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                showFileInfo('Please select a PDF file first.', 'error');
                return;
            }

            // Show loading state
            if (uploadBtn) {
                uploadBtn.disabled = true;
                uploadBtn.innerHTML = '<img src="https://img.icons8.com/fluency/48/spinner-frame-5.png" alt="Loading" style="width: 20px; height: 20px; animation: spin 1s linear infinite;"> Processing...';
            }
        });
    }
}

/**
 * Initialize FAQ accordion functionality
 */
function initFAQ() {
    const faqQuestions = document.querySelectorAll('.faq-question');

    faqQuestions.forEach(question => {
        question.addEventListener('click', function() {
            const answer = this.nextElementSibling;
            const icon = this.querySelector('i');

            // Toggle active state
            this.classList.toggle('active');
            
            // Toggle answer visibility
            if (answer) {
                answer.classList.toggle('show');
            }

            // Close other open FAQs
            faqQuestions.forEach(otherQuestion => {
                if (otherQuestion !== this) {
                    otherQuestion.classList.remove('active');
                    const otherAnswer = otherQuestion.nextElementSibling;
                    if (otherAnswer) {
                        otherAnswer.classList.remove('show');
                    }
                }
            });
        });
    });
}

/**
 * Smooth scroll to element
 */
function scrollToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

/**
 * Show notification toast
 */
function showToast(message, type = 'info', duration = 3000) {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    const iconUrl = type === 'success' ? 'https://img.icons8.com/fluency/48/checkmark--v1.png' : 
                    type === 'error' ? 'https://img.icons8.com/fluency/48/cancel--v1.png' : 
                    'https://img.icons8.com/fluency/48/info--v1.png';
    toast.innerHTML = `
        <img src="${iconUrl}" alt="${type}" style="width: 20px; height: 20px;">
        <span>${message}</span>
    `;

    // Add styles
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#2563eb'};
        color: white;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        z-index: 9999;
        animation: slideInUp 0.3s ease;
    `;

    // Add keyframe animation
    if (!document.getElementById('toast-styles')) {
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            @keyframes slideInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            @keyframes slideOutDown {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(20px);
                }
            }
        `;
        document.head.appendChild(style);
    }

    // Add to document
    document.body.appendChild(toast);

    // Remove after duration
    setTimeout(() => {
        toast.style.animation = 'slideOutDown 0.3s ease';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, duration);
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text)
            .then(() => {
                showToast('Copied to clipboard!', 'success');
            })
            .catch(err => {
                console.error('Failed to copy:', err);
                showToast('Failed to copy text', 'error');
            });
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            showToast('Copied to clipboard!', 'success');
        } catch (err) {
            console.error('Failed to copy:', err);
            showToast('Failed to copy text', 'error');
        }
        
        document.body.removeChild(textArea);
    }
}

/**
 * Initialize translation functionality
 */
function initTranslation() {
    const translateBtn = document.getElementById('translateBtn');
    const languageSelect = document.getElementById('languageSelect');
    const textDisplay = document.getElementById('textDisplay');
    const translationStatus = document.getElementById('translationStatus');
    const hiddenText = document.getElementById('hiddenText');

    if (!translateBtn || !languageSelect || !textDisplay) return;

    // Store original text
    let originalText = hiddenText ? hiddenText.value : textDisplay.innerText;
    let currentLanguage = 'en';

    translateBtn.addEventListener('click', async function() {
        const targetLang = languageSelect.value;
        
        // If same language, do nothing
        if (targetLang === currentLanguage) {
            showTranslationStatus('Text is already in the selected language.', 'success');
            return;
        }

        // If selecting English, restore original
        if (targetLang === 'en') {
            textDisplay.innerHTML = originalText.replace(/\n/g, '<br>');
            if (hiddenText) hiddenText.value = originalText;
            currentLanguage = 'en';
            showTranslationStatus('Restored to original English text.', 'success');
            
            // Update speech module
            if (typeof updateCurrentText === 'function') {
                updateCurrentText(originalText);
            }
            return;
        }

        // Show loading status
        showTranslationStatus('Translating... This may take a moment for longer texts.', 'loading');
        translateBtn.disabled = true;

        try {
            const response = await fetch('api/translate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    text: originalText,
                    target_language: targetLang
                })
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Translation failed');
            }

            // Update display
            textDisplay.innerHTML = data.translated_text.replace(/\n/g, '<br>');
            
            // Update hidden text for speech
            if (hiddenText) hiddenText.value = data.translated_text;
            
            // Update speech module
            if (typeof updateCurrentText === 'function') {
                updateCurrentText(data.translated_text);
            }

            currentLanguage = targetLang;
            showTranslationStatus(`Successfully translated to ${data.language_name}!`, 'success');

        } catch (error) {
            console.error('Translation error:', error);
            showTranslationStatus('Error: ' + error.message, 'error');
        } finally {
            translateBtn.disabled = false;
        }
    });

    function showTranslationStatus(message, type) {
        if (!translationStatus) return;
        
        translationStatus.textContent = message;
        translationStatus.className = 'translation-status show ' + type;

        // Auto-hide success messages after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                translationStatus.classList.remove('show');
            }, 5000);
        }
    }
}

// Initialize translation on DOM load
document.addEventListener('DOMContentLoaded', initTranslation);

/**
 * Handle keyboard shortcuts
 */
document.addEventListener('keydown', function(e) {
    // Check if we have audio controls on the page
    const playBtn = document.getElementById('playBtn');
    const pauseBtn = document.getElementById('pauseBtn');
    const stopBtn = document.getElementById('stopBtn');

    if (!playBtn) return;

    // Space bar to play/pause (when not in form)
    if (e.code === 'Space' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        
        if (typeof synth !== 'undefined' && synth.speaking) {
            if (synth.paused) {
                resumeSpeech();
            } else {
                pauseSpeech();
            }
        } else {
            speakText();
        }
    }

    // Escape to stop
    if (e.code === 'Escape') {
        if (typeof stopSpeech === 'function') {
            stopSpeech();
        }
    }
});

/**
 * Text Editing Functions
 */
let originalText = '';
let isEditMode = false;

/**
 * Toggle between view and edit mode
 */
function toggleEditMode() {
    const textDisplay = document.getElementById('textDisplay');
    const textEditor = document.getElementById('textEditor');
    const editBtn = document.getElementById('editTextBtn');
    const saveBtn = document.getElementById('saveTextBtn');
    const cancelBtn = document.getElementById('cancelEditBtn');
    const editStatus = document.getElementById('editStatus');
    
    if (!textDisplay || !textEditor) return;
    
    // Save original text before editing
    originalText = textEditor.value;
    
    // Switch to edit mode
    textDisplay.style.display = 'none';
    textEditor.style.display = 'block';
    editBtn.style.display = 'none';
    saveBtn.style.display = 'inline-flex';
    cancelBtn.style.display = 'inline-flex';
    editStatus.textContent = '';
    
    // Focus on editor
    textEditor.focus();
    isEditMode = true;
}

/**
 * Save edited text
 */
function saveEditedText() {
    const textDisplay = document.getElementById('textDisplay');
    const textEditor = document.getElementById('textEditor');
    const editBtn = document.getElementById('editTextBtn');
    const saveBtn = document.getElementById('saveTextBtn');
    const cancelBtn = document.getElementById('cancelEditBtn');
    const editStatus = document.getElementById('editStatus');
    const hiddenText = document.getElementById('hiddenText');
    
    if (!textEditor) return;
    
    const newText = textEditor.value.trim();
    
    if (newText.length === 0) {
        editStatus.textContent = 'Text cannot be empty!';
        editStatus.className = 'edit-status error';
        return;
    }
    
    // Update the display
    textDisplay.innerHTML = newText.replace(/\n/g, '<br>');
    
    // Update hidden text for speech
    if (hiddenText) {
        hiddenText.value = newText;
    }
    
    // Update speech module's current text
    if (typeof updateCurrentText === 'function') {
        updateCurrentText(newText);
    }
    
    // Switch back to view mode
    textDisplay.style.display = 'block';
    textEditor.style.display = 'none';
    editBtn.style.display = 'inline-flex';
    saveBtn.style.display = 'none';
    cancelBtn.style.display = 'none';
    
    // Show success message
    editStatus.textContent = 'Text saved successfully!';
    editStatus.className = 'edit-status success';
    
    setTimeout(() => {
        editStatus.textContent = '';
        editStatus.className = 'edit-status';
    }, 3000);
    
    isEditMode = false;
}

/**
 * Cancel editing and restore original text
 */
function cancelEdit() {
    const textDisplay = document.getElementById('textDisplay');
    const textEditor = document.getElementById('textEditor');
    const editBtn = document.getElementById('editTextBtn');
    const saveBtn = document.getElementById('saveTextBtn');
    const cancelBtn = document.getElementById('cancelEditBtn');
    const editStatus = document.getElementById('editStatus');
    
    if (!textEditor) return;
    
    // Restore original text
    textEditor.value = originalText;
    
    // Switch back to view mode
    textDisplay.style.display = 'block';
    textEditor.style.display = 'none';
    editBtn.style.display = 'inline-flex';
    saveBtn.style.display = 'none';
    cancelBtn.style.display = 'none';
    editStatus.textContent = '';
    
    isEditMode = false;
}
