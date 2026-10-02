"""
Unified document processing dispatcher for PDF, DOCX, and text files
"""
from typing import Dict
from app.services.pdf_extractor import PDFExtractorService
from app.services.docx_extractor import DOCXExtractorService


class DocumentProcessorService:
    """Service to extract text from supported file types"""

    def __init__(self):
        self.pdf_extractor = PDFExtractorService()
        self.docx_extractor = DOCXExtractorService()

    def extract_text(self, file_bytes: bytes, file_type: str, filename: str = "") -> Dict:
        """
        Extract text based on file_type or filename extension

        Args:
            file_bytes: Raw binary content of document
            file_type: 'pdf', 'docx', 'txt', etc.
            filename: Original file name for fallback extension detection

        Returns:
            Dict containing 'text', 'page_count', 'title', 'author', 'has_text', 'char_count'
        """
        normalized_type = file_type.lower().strip(".")
        if not normalized_type and "." in filename:
            normalized_type = filename.rsplit(".", 1)[-1].lower()

        if normalized_type in ["pdf", "application/pdf"]:
            return self.pdf_extractor.extract_text_from_bytes(file_bytes)

        elif normalized_type in ["docx", "application/vnd.openxmlformats-officedocument.wordprocessingml.document"]:
            return self.docx_extractor.extract_text_from_bytes(file_bytes)

        elif normalized_type in ["txt", "text/plain"]:
            try:
                decoded_text = file_bytes.decode("utf-8")
            except UnicodeDecodeError:
                decoded_text = file_bytes.decode("latin-1", errors="ignore")

            cleaned = decoded_text.strip()
            char_count = len(cleaned)
            est_page_count = max(1, (char_count + 2499) // 2500)
            return {
                "text": cleaned,
                "page_count": est_page_count,
                "title": filename,
                "author": "",
                "has_text": char_count > 0,
                "char_count": char_count
            }

        else:
            raise ValueError(f"Unsupported document type: {file_type}")
