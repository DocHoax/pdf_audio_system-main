<?php
/**
 * PDF Text Extractor Class
 * Extracts text content from PDF files
 */

class PDFTextExtractor {
    
    /**
     * Extract text from a PDF file
     * 
     * @param string $filePath Path to the PDF file
     * @return string Extracted text content
     */
    public function extractText($filePath) {
        if (!file_exists($filePath)) {
            return '';
        }
        
        // Try different extraction methods
        $text = $this->extractWithPdfParser($filePath);
        
        if (empty(trim($text))) {
            $text = $this->extractBasic($filePath);
        }
        
        return $this->cleanText($text);
    }
    
    /**
     * Extract text using Smalot PDF Parser library
     * This is the preferred method if the library is available
     * 
     * @param string $filePath
     * @return string
     */
    private function extractWithPdfParser($filePath) {
        // Check if PDF Parser is available via Composer
        $autoloadPath = __DIR__ . '/../vendor/autoload.php';
        
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
            
            if (class_exists('\Smalot\PdfParser\Parser')) {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($filePath);
                    return $pdf->getText();
                } catch (Exception $e) {
                    error_log("PDF Parser error: " . $e->getMessage());
                }
            }
        }
        
        return '';
    }
    
    /**
     * Basic PDF text extraction without external libraries
     * Works with simple text-based PDFs
     * 
     * @param string $filePath
     * @return string
     */
    private function extractBasic($filePath) {
        $content = file_get_contents($filePath);
        
        if ($content === false) {
            return '';
        }
        
        $text = '';
        
        // Extract text from PDF streams
        // Find all stream objects
        preg_match_all('/stream\s*\n(.*?)\nendstream/s', $content, $streams);
        
        foreach ($streams[1] as $stream) {
            // Try to decode if compressed
            $decoded = $this->decodeStream($stream);
            
            // Extract text from BT...ET blocks (text objects)
            preg_match_all('/BT\s*(.*?)\s*ET/s', $decoded, $textBlocks);
            
            foreach ($textBlocks[1] as $block) {
                // Extract text from Tj and TJ operators
                preg_match_all('/\((.*?)\)\s*Tj/s', $block, $tjMatches);
                foreach ($tjMatches[1] as $match) {
                    $text .= $this->decodeString($match) . ' ';
                }
                
                // Extract text from TJ arrays
                preg_match_all('/\[(.*?)\]\s*TJ/s', $block, $tjArrayMatches);
                foreach ($tjArrayMatches[1] as $array) {
                    preg_match_all('/\((.*?)\)/', $array, $strings);
                    foreach ($strings[1] as $str) {
                        $text .= $this->decodeString($str);
                    }
                    $text .= ' ';
                }
            }
        }
        
        // Also try direct text extraction for uncompressed PDFs
        if (empty(trim($text))) {
            // Look for text in parentheses within the content
            preg_match_all('/\(([^\)]+)\)/', $content, $directText);
            $filtered = array_filter($directText[1], function($t) {
                // Filter out non-text entries
                $decoded = $this->decodeString($t);
                return strlen($decoded) > 2 && preg_match('/[a-zA-Z]/', $decoded);
            });
            $text = implode(' ', $filtered);
        }
        
        return $text;
    }
    
    /**
     * Attempt to decode a PDF stream
     * 
     * @param string $stream
     * @return string
     */
    private function decodeStream($stream) {
        // Try zlib decompression (FlateDecode)
        $decoded = @gzuncompress($stream);
        if ($decoded !== false) {
            return $decoded;
        }
        
        // Try deflate
        $decoded = @gzinflate($stream);
        if ($decoded !== false) {
            return $decoded;
        }
        
        // Return original if no decompression worked
        return $stream;
    }
    
    /**
     * Decode a PDF string (handle escape sequences)
     * 
     * @param string $str
     * @return string
     */
    private function decodeString($str) {
        // Handle PDF escape sequences
        $replacements = [
            '\\n' => "\n",
            '\\r' => "\r",
            '\\t' => "\t",
            '\\b' => "\b",
            '\\f' => "\f",
            '\\(' => '(',
            '\\)' => ')',
            '\\\\' => '\\'
        ];
        
        $str = str_replace(array_keys($replacements), array_values($replacements), $str);
        
        // Handle octal character codes
        $str = preg_replace_callback('/\\\\([0-7]{1,3})/', function($matches) {
            return chr(octdec($matches[1]));
        }, $str);
        
        return $str;
    }
    
    /**
     * Clean extracted text
     * 
     * @param string $text
     * @return string
     */
    private function cleanText($text) {
        // Remove null bytes
        $text = str_replace("\0", '', $text);
        
        // Normalize whitespace
        $text = preg_replace('/[\r\n]+/', "\n", $text);
        $text = preg_replace('/[^\S\n]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        // Remove non-printable characters (except newlines)
        $text = preg_replace('/[^\x20-\x7E\n]/', '', $text);
        
        // Trim
        $text = trim($text);
        
        // If still empty or too short, return a message
        if (strlen($text) < 10) {
            return "Unable to extract text from this PDF. The document may be:\n" .
                   "- Image-based (scanned document)\n" .
                   "- Password protected\n" .
                   "- Using an unsupported encoding\n\n" .
                   "Please try a different PDF file with selectable text.";
        }
        
        return $text;
    }
}
