"""
Audio file endpoints
"""
from fastapi import APIRouter, Depends, HTTPException, status
from fastapi.responses import FileResponse, StreamingResponse
from sqlalchemy.orm import Session
import os

from app.core.database import get_db
from app.models.models import User, AudioFile, ConversionJob, Document
from app.schemas.schemas import AudioResponse
from app.api.dependencies import get_current_user
from app.services.storage import get_storage_service

router = APIRouter()


@router.get("/{audio_id}", response_model=AudioResponse)
async def get_audio_info(
    audio_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """
    Get audio file information
    """
    audio = db.query(AudioFile).join(ConversionJob).join(Document).filter(
        AudioFile.id == audio_id,
        Document.user_id == current_user.id
    ).first()
    
    if not audio:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Audio file not found"
        )
    
    result = {
        "id": audio.id,
        "conversion_id": audio.conversion_id,
        "format": audio.format,
        "duration": audio.duration,
        "file_size": audio.file_size,
        "created_at": audio.created_at,
        "download_url": f"/api/v1/audio/{audio.id}/download"
    }
    
    return AudioResponse(**result)


@router.get("/{audio_id}/download")
async def download_audio(
    audio_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """
    Download audio file
    """
    audio = db.query(AudioFile).join(ConversionJob).join(Document).filter(
        AudioFile.id == audio_id,
        Document.user_id == current_user.id
    ).first()
    
    if not audio:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Audio file not found"
        )
    
    # Get file from storage
    storage_service = get_storage_service()
    
    try:
        file_content = await storage_service.get_audio(audio.file_path)
        
        # Get original document filename for download
        document = audio.conversion.document
        base_name = os.path.splitext(document.original_filename)[0]
        download_filename = f"{base_name}-audio.{audio.format}"
        
        return StreamingResponse(
            iter([file_content]),
            media_type=f"audio/{audio.format}",
            headers={
                "Content-Disposition": f"attachment; filename=\"{download_filename}\"",
                "Content-Length": str(len(file_content))
            }
        )
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Failed to retrieve audio file: {str(e)}"
        )


@router.get("/{audio_id}/stream")
async def stream_audio(
    audio_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """
    Stream audio file for playback
    """
    audio = db.query(AudioFile).join(ConversionJob).join(Document).filter(
        AudioFile.id == audio_id,
        Document.user_id == current_user.id
    ).first()
    
    if not audio:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Audio file not found"
        )
    
    # Get file from storage
    storage_service = get_storage_service()
    
    try:
        file_content = await storage_service.get_audio(audio.file_path)
        
        return StreamingResponse(
            iter([file_content]),
            media_type=f"audio/{audio.format}",
            headers={
                "Accept-Ranges": "bytes",
                "Content-Length": str(len(file_content))
            }
        )
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Failed to stream audio file: {str(e)}"
        )
