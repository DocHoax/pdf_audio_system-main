"""
Reading history and bookmark tracking endpoints
"""
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from typing import List

from app.core.database import get_db
from app.models.models import User, ReadingHistory, Document
from app.schemas.schemas import ReadingHistoryCreate, ReadingHistoryResponse
from app.api.dependencies import get_current_user

router = APIRouter()


@router.get("", response_model=List[ReadingHistoryResponse])
async def get_reading_history(
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
    skip: int = 0,
    limit: int = 50
):
    """
    Get user reading history with document metadata
    """
    history_records = db.query(ReadingHistory).filter(
        ReadingHistory.user_id == current_user.id
    ).order_by(ReadingHistory.read_at.desc()).offset(skip).limit(limit).all()

    result = []
    for item in history_records:
        result.append(ReadingHistoryResponse(
            id=item.id,
            user_id=item.user_id,
            document_id=item.document_id,
            document_title=item.document.original_filename if item.document else "Unknown",
            last_position=item.last_position,
            completed=item.completed,
            read_at=item.read_at
        ))

    return result


@router.post("/progress", response_model=ReadingHistoryResponse)
async def update_reading_progress(
    progress: ReadingHistoryCreate,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """
    Save or update reading/listening progress for a document
    """
    # Verify document ownership
    document = db.query(Document).filter(
        Document.id == progress.document_id,
        Document.user_id == current_user.id
    ).first()

    if not document:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Document not found"
        )

    # Check if history entry already exists
    history = db.query(ReadingHistory).filter(
        ReadingHistory.user_id == current_user.id,
        ReadingHistory.document_id == progress.document_id
    ).first()

    if history:
        history.last_position = progress.last_position
        history.completed = progress.completed
    else:
        history = ReadingHistory(
            user_id=current_user.id,
            document_id=progress.document_id,
            last_position=progress.last_position,
            completed=progress.completed
        )
        db.add(history)

    db.commit()
    db.refresh(history)

    return ReadingHistoryResponse(
        id=history.id,
        user_id=history.user_id,
        document_id=history.document_id,
        document_title=document.original_filename,
        last_position=history.last_position,
        completed=history.completed,
        read_at=history.read_at
    )


@router.delete("/{history_id}", status_code=status.HTTP_204_NO_CONTENT)
async def delete_reading_history_entry(
    history_id: int,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """
    Remove an item from reading history
    """
    item = db.query(ReadingHistory).filter(
        ReadingHistory.id == history_id,
        ReadingHistory.user_id == current_user.id
    ).first()

    if not item:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Reading history entry not found"
        )

    db.delete(item)
    db.commit()
    return None
