"""
Document management endpoints
"""
from fastapi import APIRouter, Depends, HTTPException, status, UploadFile, File
from sqlalchemy.orm import Session
from typing import List
import os
import uuid

from app.core.database import get_db
from app.core.config import settings
from app.models.models import User, Document, DocumentStatus
from app.schemas.schemas import DocumentUploadResponse, DocumentResponse, DocumentDetailResponse, DocumentTextResponse
from app.api.dependencies import get_current_user
from app.services.storage import get_storage_service
from app.services.document_processor import DocumentProcessorService

router = APIRouter()


@router.post("/upload", response_model=DocumentUploadResponse, status_code=status.HTTP_201_CREATED)
async def upload_document(
    file: UploadFile = File(...),
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """
    Upload a document (PDF, DOCX, or TXT)
    """
    allowed_exts = [ext.strip().lower() for ext in settings.ALLOWED_EXTENSIONS.split(",") if ext.strip()]
    file_ext = os.path.splitext(file.filename)[1].lower().strip(".")

    if file_ext not in allowed_exts:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=f"Invalid file extension. Allowed formats: {', '.join(allowed_exts)}"
        )

    # Read file content
    file_content = await file.read()
    file_size = len(file_content)

    # Validate file size
    if file_size > settings.MAX_FILE_SIZE:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=f"File size exceeds maximum allowed size of {settings.MAX_FILE_SIZE / (1024*1024)}MB"
        )

    if file_size == 0:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="File is empty"
        )

    # Generate unique filename
    stored_filename = f"{uuid.uuid4()}.{file_ext}"

    # Store file
    storage_service = get_storage_service()
    file_path = await storage_service.save_document(stored_filename, file_content)

    # Create document record
    document = Document(
        user_id=current_user.id,
        original_filename=file.filename,
        stored_filename=stored_filename,
        file_size=file_size,
        file_path=file_path,
        mime_type=file.content_type or f"application/{file_ext}",
        status=DocumentStatus.UPLOADED
    )

    db.add(document)
    db.commit()
    db.refresh(document)

    return document


@router.get("", response_model=List[DocumentResponse])
async def list_documents(
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
    skip: int = 0,
    limit: int = 100
):
    """
    List all documents for the current user
    """
    documents = db.query(Document).filter(
        Document.user_id == current_user.id
    ).order_by(Document.created_at.desc()).offset(skip).limit(limit).all()

    # Add has_audio flag
    result = []
    for doc in documents:
        doc_dict = {
            "id": doc.id,
            "user_id": doc.user_id,
            "original_filename": doc.original_filename,
            "file_size": doc.file_size,
            "page_count": doc.page_count,
            "status": doc.status,
            "error_message": doc.error_message,
            "created_at": doc.created_at,
            "updated_at": doc.updated_at,
            "has_audio": any(conv.audio_file is not None for conv in doc.conversions)
        }
        result.append(DocumentResponse(**doc_dict))

    return result


@router.get("/{document_id}", response_model=DocumentDetailResponse)
async def get_document(
    document_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """
    Get document details
    """
    document = db.query(Document).filter(
        Document.id == document_id,
        Document.user_id == current_user.id
    ).first()

    if not document:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Document not found"
        )

    doc_dict = {
        "id": document.id,
        "user_id": document.user_id,
        "original_filename": document.original_filename,
        "file_size": document.file_size,
        "page_count": document.page_count,
        "status": document.status,
        "error_message": document.error_message,
        "created_at": document.created_at,
        "updated_at": document.updated_at,
        "extracted_text": document.extracted_text,
        "mime_type": document.mime_type,
        "has_audio": any(conv.audio_file is not None for conv in document.conversions)
    }

    return DocumentDetailResponse(**doc_dict)


@router.post("/{document_id}/extract", response_model=DocumentTextResponse)
async def extract_text(
    document_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """
    Extract text from a document (PDF, DOCX, or TXT)
    """
    document = db.query(Document).filter(
        Document.id == document_id,
        Document.user_id == current_user.id
    ).first()

    if not document:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Document not found"
        )

    # Update status
    document.status = DocumentStatus.EXTRACTING
    db.commit()

    try:
        # Extract text using unified processor
        storage_service = get_storage_service()
        file_content = await storage_service.get_document(document.file_path)

        processor = DocumentProcessorService()
        file_ext = os.path.splitext(document.original_filename)[1].lower().strip(".")
        extraction_result = processor.extract_text(
            file_bytes=file_content,
            file_type=file_ext or document.mime_type,
            filename=document.original_filename
        )

        # Update document
        document.extracted_text = extraction_result["text"]
        document.page_count = extraction_result.get("page_count", 1)
        document.status = DocumentStatus.EXTRACTED
        db.commit()
        db.refresh(document)

        return DocumentTextResponse(
            id=document.id,
            extracted_text=document.extracted_text,
            page_count=document.page_count,
            character_count=len(document.extracted_text) if document.extracted_text else 0
        )

    except Exception as e:
        document.status = DocumentStatus.FAILED
        document.error_message = str(e)
        db.commit()
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Failed to extract text: {str(e)}"
        )


@router.get("/{document_id}/text", response_model=DocumentTextResponse)
async def get_document_text(
    document_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """
    Get extracted text from a document
    """
    document = db.query(Document).filter(
        Document.id == document_id,
        Document.user_id == current_user.id
    ).first()

    if not document:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Document not found"
        )

    return DocumentTextResponse(
        id=document.id,
        extracted_text=document.extracted_text,
        page_count=document.page_count,
        character_count=len(document.extracted_text) if document.extracted_text else 0
    )


@router.delete("/{document_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_document(
    document_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """
    Delete a document
    """
    document = db.query(Document).filter(
        Document.id == document_id,
        Document.user_id == current_user.id
    ).first()

    if not document:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Document not found"
        )

    # Delete file from storage
    storage_service = get_storage_service()
    await storage_service.delete_document(document.file_path)

    # Delete document record (cascades to conversions and audio files)
    db.delete(document)
    db.commit()

    return None
