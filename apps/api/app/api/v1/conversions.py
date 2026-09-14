"""
Conversion job endpoints
"""
from fastapi import APIRouter, Depends, HTTPException, status, BackgroundTasks
from sqlalchemy.orm import Session
from typing import List

from app.core.database import get_db
from app.models.models import User, Document, ConversionJob, ConversionStatus, DocumentStatus
from app.schemas.schemas import ConversionCreate, ConversionResponse
from app.api.dependencies import get_current_user
from app.services.conversion_service import ConversionService

router = APIRouter()


@router.post("/documents/{document_id}/convert", response_model=ConversionResponse, status_code=status.HTTP_201_CREATED)
async def create_conversion(
    document_id: int,
    conversion_data: ConversionCreate,
    background_tasks: BackgroundTasks,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """
    Start audio conversion for a document
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
    
    if document.status != DocumentStatus.EXTRACTED:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Document text must be extracted before conversion"
        )
    
    if not document.extracted_text:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="No text available for conversion"
        )
    
    # Create conversion job
    conversion = ConversionJob(
        document_id=document_id,
        language=conversion_data.language,
        voice=conversion_data.voice,
        speed=conversion_data.speed,
        pitch=conversion_data.pitch,
        status=ConversionStatus.QUEUED
    )
    
    db.add(conversion)
    db.commit()
    db.refresh(conversion)
    
    # Start conversion in background
    conversion_service = ConversionService(db)
    background_tasks.add_task(
        conversion_service.process_conversion,
        conversion.id
    )
    
    result = {
        "id": conversion.id,
        "document_id": conversion.document_id,
        "status": conversion.status,
        "progress": conversion.progress,
        "language": conversion.language,
        "voice": conversion.voice,
        "speed": conversion.speed,
        "pitch": conversion.pitch,
        "error_message": conversion.error_message,
        "started_at": conversion.started_at,
        "completed_at": conversion.completed_at,
        "created_at": conversion.created_at,
        "has_audio": False
    }
    
    return ConversionResponse(**result)


@router.get("/jobs/{job_id}", response_model=ConversionResponse)
async def get_conversion_job(
    job_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """
    Get conversion job status
    """
    conversion = db.query(ConversionJob).join(Document).filter(
        ConversionJob.id == job_id,
        Document.user_id == current_user.id
    ).first()
    
    if not conversion:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Conversion job not found"
        )
    
    result = {
        "id": conversion.id,
        "document_id": conversion.document_id,
        "status": conversion.status,
        "progress": conversion.progress,
        "language": conversion.language,
        "voice": conversion.voice,
        "speed": conversion.speed,
        "pitch": conversion.pitch,
        "error_message": conversion.error_message,
        "started_at": conversion.started_at,
        "completed_at": conversion.completed_at,
        "created_at": conversion.created_at,
        "has_audio": conversion.audio_file is not None
    }
    
    return ConversionResponse(**result)


@router.get("/documents/{document_id}/conversions", response_model=List[ConversionResponse])
async def list_document_conversions(
    document_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """
    List all conversions for a document
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
    
    conversions = db.query(ConversionJob).filter(
        ConversionJob.document_id == document_id
    ).order_by(ConversionJob.created_at.desc()).all()
    
    result = []
    for conv in conversions:
        conv_dict = {
            "id": conv.id,
            "document_id": conv.document_id,
            "status": conv.status,
            "progress": conv.progress,
            "language": conv.language,
            "voice": conv.voice,
            "speed": conv.speed,
            "pitch": conv.pitch,
            "error_message": conv.error_message,
            "started_at": conv.started_at,
            "completed_at": conv.completed_at,
            "created_at": conv.created_at,
            "has_audio": conv.audio_file is not None
        }
        result.append(ConversionResponse(**conv_dict))
    
    return result
