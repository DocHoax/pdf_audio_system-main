"""
PDF text extraction service using PyMuPDF
"""
import fitz  # PyMuPDF
import re
from typing import Dict, BinaryIO
from io import BytesIO


class PDFExtractorService:
    """Service for extracting text from PDF documents"""
    
    def extract_text_from_bytes(self, pdf_bytes: bytes) -> Dict:
        """
        Extract text from PDF bytes
        
        Returns:
            Dict with extracted text, page count, and metadata
        """
        try:
            # Open PDF from bytes
            pdf_document = fitz.open(stream=pdf_bytes, filetype="pdf")
            
            # Extract metadata
            metadata = pdf_document.metadata
            page_count = pdf_document.page_count
            
            # Extract text from all pages
            text_content = []
            
            for page_num in range(page_count):
                page = pdf_document[page_num]
                text = page.get_text()
                
                if text.strip():
                    text_content.append(text)
            
            pdf_document.close()
            
            # Combine all text
            full_text = "\n\n".join(text_content)
            
            # Clean the text
            cleaned_text = self._clean_text(full_text)
            
            return {
                "text": cleaned_text,
                "page_count": page_count,
                "title": metadata.get("title", ""),
                "author": metadata.get("author", ""),
                "has_text": len(cleaned_text.strip()) > 0
            }
            
        except Exception as e:
            raise Exception(f"Failed to extract text from PDF: {str(e)}")
    
    def _clean_text(self, text: str) -> str:
        """
        Clean extracted text
        """
        if not text:
            return ""
        
        # Remove excessive whitespace
        text = re.sub(r'\s+', ' ', text)
        
        # Remove repeated spaces
        text = re.sub(r' +', ' ', text)
        
        # Fix common PDF extraction issues
        # Join hyphenated words at line breaks
        text = re.sub(r'(\w+)-\s+(\w+)', r'\1\2', text)
        
        # Normalize line breaks
        text = re.sub(r'\n\s*\n\s*\n+', '\n\n', text)
        
        # Remove page numbers (simple pattern)
        text = re.sub(r'\n\s*\d+\s*\n', '\n', text)
        
        # Preserve paragraph structure
        paragraphs = [p.strip() for p in text.split('\n\n') if p.strip()]
        text = '\n\n'.join(paragraphs)
        
        return text.strip()
    
    def detect_scanned_pdf(self, pdf_bytes: bytes) -> bool:
        """
        Detect if PDF is scanned (no selectable text)
        """
        try:
            pdf_document = fitz.open(stream=pdf_bytes, filetype="pdf")
            
            # Check first few pages for text
            pages_to_check = min(3, pdf_document.page_count)
            total_text_length = 0
            
            for page_num in range(pages_to_check):
                page = pdf_document[page_num]
                text = page.get_text().strip()
                total_text_length += len(text)
            
            pdf_document.close()
            
            # If very little text found, likely scanned
            return total_text_length < 100
            
        except Exception:
            return False
