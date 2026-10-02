"""
PDF text extraction service using PyMuPDF and OCR fallback
"""
import fitz  # PyMuPDF
import re
import io
from typing import Dict


class PDFExtractorService:
    """Service for extracting text from PDF documents with OCR fallback"""

    def extract_text_from_bytes(self, pdf_bytes: bytes) -> Dict:
        """
        Extract text from PDF bytes

        Returns:
            Dict with extracted text, page count, and metadata
        """
        try:
            pdf_document = fitz.open(stream=pdf_bytes, filetype="pdf")
            metadata = pdf_document.metadata or {}
            page_count = pdf_document.page_count

            text_content = []

            for page_num in range(page_count):
                page = pdf_document[page_num]
                text = page.get_text("text")

                if text.strip():
                    text_content.append(text.strip())

            full_text = "\n\n".join(text_content)

            # If standard extraction yielded little to no text, check for OCR
            is_scanned = len(full_text.strip()) < 50 and page_count > 0
            if is_scanned:
                ocr_text = self._try_ocr_extraction(pdf_document)
                if ocr_text.strip():
                    full_text = ocr_text

            pdf_document.close()
            cleaned_text = self._clean_text(full_text)

            return {
                "text": cleaned_text,
                "page_count": page_count,
                "title": metadata.get("title", ""),
                "author": metadata.get("author", ""),
                "has_text": len(cleaned_text.strip()) > 0,
                "char_count": len(cleaned_text)
            }

        except Exception as e:
            raise Exception(f"Failed to extract text from PDF: {str(e)}")

    def _try_ocr_extraction(self, pdf_document: fitz.Document) -> str:
        """Attempt OCR extraction using pytesseract if available"""
        try:
            import pytesseract
            from PIL import Image

            ocr_pages = []
            # OCR up to first 20 pages
            limit_pages = min(20, pdf_document.page_count)
            for i in range(limit_pages):
                page = pdf_document[i]
                pix = page.get_pixmap(dpi=150)
                img = Image.open(io.BytesIO(pix.tobytes("png")))
                page_text = pytesseract.image_to_string(img)
                if page_text.strip():
                    ocr_pages.append(page_text.strip())
            return "\n\n".join(ocr_pages)
        except Exception:
            return ""

    def _clean_text(self, text: str) -> str:
        """Clean and normalize extracted PDF text"""
        if not text:
            return ""

        # Normalize carriage returns
        text = text.replace("\r\n", "\n").replace("\r", "\n")

        # Remove null bytes
        text = text.replace("\0", "")

        # Join hyphenated line breaks (e.g. "com-\nputer" -> "computer")
        text = re.sub(r'(\w+)-\n(\w+)', r'\1\2', text)

        # Remove standalone page numbers (e.g., "\n 12 \n")
        text = re.sub(r'\n\s*\d+\s*\n', '\n', text)

        # Normalize non-breaking spaces
        text = text.replace(" ", " ")

        # Collapse multiple spaces on the same line
        text = re.sub(r'[^\S\n]+', ' ', text)

        # Collapse excessive newlines
        text = re.sub(r'\n{3,}', '\n\n', text)

        return text.strip()

    def detect_scanned_pdf(self, pdf_bytes: bytes) -> bool:
        """Detect if PDF is scanned (no selectable text)"""
        try:
            pdf_document = fitz.open(stream=pdf_bytes, filetype="pdf")
            pages_to_check = min(3, pdf_document.page_count)
            total_text_length = 0

            for page_num in range(pages_to_check):
                page = pdf_document[page_num]
                text = page.get_text().strip()
                total_text_length += len(text)

            pdf_document.close()
            return total_text_length < 100
        except Exception:
            return False
