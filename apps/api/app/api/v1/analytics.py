"""
Analytics and usage tracking endpoints
"""
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.orm import Session
from sqlalchemy import func
from typing import List, Dict, Any
from datetime import datetime, timedelta

from app.core.database import get_db
from app.models.models import User, UserAnalytics, Document, ConversionJob, AudioFile, ConversionStatus
from app.schemas.schemas import AnalyticsEventCreate, AnalyticsSummaryResponse
from app.api.dependencies import get_current_user

router = APIRouter()


@router.post("/event", status_code=status.HTTP_201_CREATED)
async def log_analytics_event(
    event: AnalyticsEventCreate,
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """
    Log an analytics event
    """
    analytics_record = UserAnalytics(
        user_id=current_user.id,
        session_id=event.session_id,
        event_type=event.event_type,
        event_data=event.event_data,
        page_url=event.page_url
    )
    db.add(analytics_record)
    db.commit()
    return {"status": "success", "event_type": event.event_type}


@router.get("/summary", response_model=AnalyticsSummaryResponse)
async def get_analytics_summary(
    current_user: User = Depends(get_current_user),
    db: Session = Depends(get_db)
):
    """
    Get user analytics summary: document counts, conversion stats, total listening duration, and popular voices
    """
    # Total documents
    total_docs = db.query(func.count(Document.id)).filter(Document.user_id == current_user.id).scalar() or 0

    # Total conversions
    user_conversions = db.query(ConversionJob).join(Document).filter(Document.user_id == current_user.id)
    total_conversions = user_conversions.count()
    completed_conversions = user_conversions.filter(ConversionJob.status == ConversionStatus.COMPLETED).count()

    # Total listening / audio duration in seconds
    total_duration = db.query(func.sum(AudioFile.duration)).join(ConversionJob).join(Document).filter(
        Document.user_id == current_user.id
    ).scalar() or 0.0

    # Voice popularity distribution
    voice_stats = db.query(
        ConversionJob.voice,
        func.count(ConversionJob.id)
    ).join(Document).filter(
        Document.user_id == current_user.id
    ).group_by(ConversionJob.voice).all()

    popular_voices = {voice: count for voice, count in voice_stats if voice}

    # Language distribution
    lang_stats = db.query(
        ConversionJob.language,
        func.count(ConversionJob.id)
    ).join(Document).filter(
        Document.user_id == current_user.id
    ).group_by(ConversionJob.language).all()

    popular_languages = {lang: count for lang, count in lang_stats if lang}

    return AnalyticsSummaryResponse(
        total_documents=total_docs,
        total_conversions=total_conversions,
        completed_conversions=completed_conversions,
        total_audio_duration_seconds=round(total_duration, 2),
        popular_voices=popular_voices,
        popular_languages=popular_languages
    )
