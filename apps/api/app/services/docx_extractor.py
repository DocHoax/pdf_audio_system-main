"""
DOCX text extraction service using python-docx
"""
import io
import re
from typing import Dict
from docx import Document as DocxDocument


class DOCXExtractorService:
    """Service for extracting structured text from DOCX documents"""

    def extract_text_from_bytes(self, docx_bytes: bytes) -> Dict:
        """
        Extract text from DOCX bytes

        Returns:
            Dict with extracted text, page/paragraph stats, and metadata
        """
        try:
            file_stream = io.BytesIO(docx_bytes)
            doc = DocxDocument(file_stream)

            paragraphs = []
            for p in doc.paragraphs:
                text = p.text.strip()
                if text:
                    paragraphs.append(text)

            # Also extract text from tables
            table_text = []
            for table in doc.tables:
                for row in table.rows:
                    row_data = [cell.text.strip() for cell in row.cells if cell.text.strip()]
                    if row_data:
                        table_text.append(" | ".join(row_data))

            full_paragraphs = paragraphs + table_text
            full_text = "\n\n".join(full_paragraphs)
            cleaned_text = self._clean_text(full_text)

            # Extract document core properties if available
            title = ""
            author = ""
            try:
                core_props = doc.core_properties
                title = core_props.title or ""
                author = core_props.author or ""
            except Exception:
                pass

            # Estimate page count (approx 400 words / 2500 chars per page)
            char_count = len(cleaned_text)
            est_page_count = max(1, (char_count + 2499) // 2500)

            return {
                "text": cleaned_text,
                "page_count": est_page_count,
                "title": title,
                "author": author,
                "has_text": len(cleaned_text.strip()) > 0,
                "char_count": char_count
            }

        except Exception as e:
            raise Exception(f"Failed to extract text from DOCX: {str(e)}")

    def _clean_text(self, text: str) -> str:
        """Clean extracted DOCX text"""
        if not text:
            return ""

        # Normalize line endings
        text = text.replace("\r\n", "\n").replace("\r", "\n")

        # Replace non-breaking spaces and tabs
        text = text.replace(" ", " ").replace("\t", " ")

        # Remove repeated whitespace within lines
        text = re.sub(r'[^\S\n]+', ' ', text)

        # Remove excessive empty lines
        text = re.sub(r'\n{3,}', '\n\n', text)

        return text.strip()
