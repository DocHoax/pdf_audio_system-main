# EchoDoc

A web-based screen reader application designed for effective audio communication and accessibility. This system converts PDF documents to speech to assist students' reading comprehension skills and help people with reading disabilities.

## Features

- **PDF Upload**: Drag and drop or click to upload PDF documents (up to 10MB)
- **Text Extraction**: Automatic text extraction from PDF files
- **Text-to-Speech**: Convert extracted text to natural speech using YarnGPT API
- **Multiple Voice Options**: 16 different voice characters to choose from
- **Volume Control**: Adjust playback volume
- **Progress Tracking**: Visual progress bar showing reading position
- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Accessibility**: Designed to assist users with reading disabilities

## Available Voices

| Voice Name | Description         |
| ---------- | ------------------- |
| Idera      | Melodic, gentle     |
| Emma       | Authoritative, deep |
| Zainab     | Soothing, gentle    |
| Osagie     | Smooth, calm        |
| Wura       | Young, sweet        |
| Jude       | Warm, confident     |
| Chinenye   | Engaging, warm      |
| Tayo       | Upbeat, energetic   |
| Regina     | Mature, warm        |
| Femi       | Rich, reassuring    |
| Adaora     | Warm, Engaging      |
| Umar       | Calm, smooth        |
| Mary       | Energetic, youthful |
| Nonso      | Bold, resonant      |
| Remi       | Melodious, warm     |
| Adam       | Deep, Clear         |

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP 7+
- **Text-to-Speech**: YarnGPT API
- **PDF Processing**: PHP-based text extraction
- **Icons**: Font Awesome 6

## Installation

### Requirements

- Web server with PHP 7.0 or higher (Apache, Nginx, or XAMPP/WAMP/MAMP)
- Modern web browser (Chrome, Edge, Firefox, or Safari)

### Quick Setup

1. **Download/Clone the project** to your web server's document root:

   ```bash
   # For XAMPP (Windows)
   cd C:\xampp\htdocs

   # For MAMP (macOS)
   cd /Applications/MAMP/htdocs

   # For Linux Apache
   cd /var/www/html
   ```

2. **Copy the project files** to a folder named `pdf_audio_system`

3. **Set proper permissions** (Linux/macOS):

   ```bash
   chmod 755 -R pdf_audio_system
   chmod 777 pdf_audio_system/uploads
   ```

4. **Start your web server** and navigate to:
   ```
   http://localhost/pdf_audio_system/
   ```

### Using Built-in PHP Server (Development)

For quick testing without a full web server:

```bash
cd pdf_audio_system
php -S localhost:8000
```

Then open `http://localhost:8000` in your browser.

## Usage

1. **Upload a PDF**: Click or drag-and-drop a PDF file to the upload area
2. **Wait for Extraction**: The system will extract text from your PDF
3. **Configure Audio**: Select your preferred voice and adjust volume
4. **Play Audio**: Click "Play" to start listening to your document
5. **Control Playback**: Use Pause, Resume, and Stop buttons as needed

## File Structure

```
pdf_audio_system/
├── index.php              # Main application page
├── about.php              # About page
├── help.php               # Help and documentation
├── contact.php            # Contact form
├── config.php             # Configuration (API keys, settings)
├── clear_session.php      # Session clearing utility
├── README.md              # This file
├── api/
│   └── tts.php            # YarnGPT API endpoint
├── assets/
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   └── js/
│       ├── main.js        # UI interactions
│       └── speech.js      # Text-to-speech module
├── includes/
│   └── pdf_extractor.php  # PDF text extraction class
└── uploads/               # Uploaded PDF files (created automatically)
```

## Browser Support

| Browser           | Support                       |
| ----------------- | ----------------------------- |
| Google Chrome     | ✅ Full support (recommended) |
| Microsoft Edge    | ✅ Full support               |
| Mozilla Firefox   | ✅ Supported                  |
| Apple Safari      | ✅ Supported                  |
| Internet Explorer | ❌ Not supported              |

## Limitations

- **Image-based PDFs**: Scanned documents without selectable text cannot be processed
- **Complex layouts**: Tables and multi-column layouts may not extract perfectly
- **File size**: Maximum upload size is 10MB
- **Text length**: YarnGPT API has a 2000 character limit per request (handled automatically by chunking)
- **Internet required**: Requires internet connection for TTS API calls

## Troubleshooting

### Audio not playing

- Check your internet connection (YarnGPT API requires internet)
- Verify that the API key in `config.php` is valid
- Check browser console for error messages

### Text extraction failed

- Verify the PDF contains selectable text (not just images)
- Try a different PDF file

### Loading takes too long

- Long documents are split into chunks and processed sequentially
- Each chunk requires an API call, so larger documents take longer
- Check your internet connection speed

### Upload fails

- Check file size (max 10MB)
- Ensure the file is a valid PDF document

## API Configuration

The YarnGPT API key is stored in `config.php`. To update it:

```php
define('YARNGPT_API_KEY', 'your_api_key_here');
```

## For Enhanced PDF Parsing

For better PDF text extraction, install the Smalot PDF Parser library:

```bash
cd pdf_audio_system
composer require smalot/pdfparser
```

The system will automatically use this library if available.

## License

This project is created for educational purposes as part of a research study on improving accessibility and reading comprehension through text-to-speech technology.

## Acknowledgments

- Adobe for creating the PDF format
- YarnGPT for the Text-to-Speech API
- Font Awesome for icons

---

**Note**: This system is designed to improve the usage of PDF documents and achieve a more flexible audio speech system, particularly for educational settings and assisting users with reading disabilities.
