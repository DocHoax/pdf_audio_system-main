<?php
/**
 * DOCX Text Extractor Class
 * Extracts text content from Microsoft Word (.docx) files
 */

class DOCXTextExtractor {
    
    /**
     * Extract text from a DOCX file
     * 
     * @param string $filePath Path to the DOCX file
     * @return string Extracted text content
     */
    public function extractText($filePath) {
        if (!file_exists($filePath)) {
            return '';
        }
        
        // DOCX files are ZIP archives containing XML files
        $text = $this->extractFromZip($filePath);
        
        return $this->cleanText($text);
    }
    
    /**
     * Extract text from DOCX ZIP archive
     * 
     * @param string $filePath
     * @return string
     */
    private function extractFromZip($filePath) {
        $text = '';
        
        // Check if ZipArchive class exists
        if (!class_exists('ZipArchive')) {
            return 'Error: PHP ZipArchive extension is not installed.';
        }
        
        $zip = new ZipArchive();
        
        if ($zip->open($filePath) === true) {
            // The main document content is in word/document.xml
            $content = $zip->getFromName('word/document.xml');
            
            if ($content !== false) {
                $text = $this->extractTextFromXml($content);
            }
            
            $zip->close();
        } else {
            return 'Error: Unable to open the DOCX file.';
        }
        
        return $text;
    }
    
    /**
     * Extract text from Word XML content
     * 
     * @param string $xmlContent
     * @return string
     */
    private function extractTextFromXml($xmlContent) {
        $text = '';
        
        // Suppress XML errors
        libxml_use_internal_errors(true);
        
        $dom = new DOMDocument();
        $dom->loadXML($xmlContent);
        
        // Get all text nodes (w:t elements contain the actual text)
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        
        // Extract paragraphs to maintain structure
        $paragraphs = $xpath->query('//w:p');
        
        foreach ($paragraphs as $paragraph) {
            $paragraphText = '';
            
            // Get all text runs within the paragraph
            $textNodes = $xpath->query('.//w:t', $paragraph);
            
            foreach ($textNodes as $textNode) {
                $paragraphText .= $textNode->nodeValue;
            }
            
            if (!empty(trim($paragraphText))) {
                $text .= $paragraphText . "\n";
            }
        }
        
        libxml_clear_errors();
        
        return $text;
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
        
        // Trim each line
        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);
        $text = implode("\n", $lines);
        
        // Trim overall
        $text = trim($text);
        
        // If empty or too short, return a message
        if (strlen($text) < 10) {
            return "Unable to extract text from this DOCX file. The document may be:\n" .
                   "- Empty or contain only images\n" .
                   "- Password protected\n" .
                   "- Corrupted\n\n" .
                   "Please try a different Word document with text content.";
        }
        
        return $text;
    }
}
