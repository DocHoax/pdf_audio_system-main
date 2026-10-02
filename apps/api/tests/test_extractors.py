"""
Unit tests for Document, PDF, and DOCX text extractors
"""
import io
import fitz
from docx import Document as DocxDoc

from app.services.pdf_extractor import PDFExtractorService
from app.services.docx_extractor import DOCXExtractorService
from app.services.document_processor import DocumentProcessorService


def test_pdf_extractor_service_with_text():
    """Test extracting text from generated PDF document"""
    # Create in-memory PDF using PyMuPDF
    doc = fitz.open()
    page = doc.new_page()
    page.insert_text((50, 72), "Hello from EchoDoc automated test suite.\nAudio accessibility for everyone.")
    pdf_bytes = doc.write()
    doc.close()

    extractor = PDFExtractorService()
    result = extractor.extract_text_from_bytes(pdf_bytes)

    assert result["has_text"] is True
    assert result["page_count"] == 1
    assert "Hello from EchoDoc" in result["text"]
    assert "Audio accessibility" in result["text"]


def test_docx_extractor_service():
    """Test extracting text from generated DOCX document"""
    doc = DocxDoc()
    doc.add_heading("EchoDoc DOCX Document", level=1)
    doc.add_paragraph("First paragraph testing Nigerian English speech synthesis.")
    doc.add_paragraph("Second paragraph testing reading bookmark progress.")

    stream = io.BytesIO()
    doc.save(stream)
    docx_bytes = stream.getvalue()

    extractor = DOCXExtractorService()
    result = extractor.extract_text_from_bytes(docx_bytes)

    assert result["has_text"] is True
    assert "EchoDoc DOCX Document" in result["text"]
    assert "First paragraph testing Nigerian English" in result["text"]
    assert result["char_count"] > 0


def test_document_processor_dispatcher():
    """Test unified document processor dispatcher for PDF, DOCX, and TXT"""
    processor = DocumentProcessorService()

    # Test plain text
    txt_bytes = "Plain text document content for EchoDoc conversion.".encode("utf-8")
    txt_result = processor.extract_text(txt_bytes, file_type="txt", filename="sample.txt")
    assert txt_result["has_text"] is True
    assert "Plain text document" in txt_result["text"]

    # Test docx via dispatcher
    doc = DocxDoc()
    doc.add_paragraph("Testing Word dispatching.")
    stream = io.BytesIO()
    doc.save(stream)
    docx_result = processor.extract_text(stream.getvalue(), file_type="docx", filename="sample.docx")
    assert "Testing Word dispatching" in docx_result["text"]
