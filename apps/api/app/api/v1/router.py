"""
API Router configuration
"""
from fastapi import APIRouter

from app.api.v1 import (
    auth,
    users,
    documents,
    conversions,
    audio,
    voices,
    translation,
    analytics,
    history
)

api_router = APIRouter()

# Include all route modules
api_router.include_router(auth.router, prefix="/auth", tags=["Authentication"])
api_router.include_router(users.router, prefix="/users", tags=["Users & Settings"])
api_router.include_router(documents.router, prefix="/documents", tags=["Documents"])
api_router.include_router(conversions.router, prefix="/conversions", tags=["Conversions"])
api_router.include_router(audio.router, prefix="/audio", tags=["Audio"])
api_router.include_router(voices.router, prefix="/voices", tags=["Voices"])
api_router.include_router(translation.router, prefix="/translation", tags=["Translation"])
api_router.include_router(analytics.router, prefix="/analytics", tags=["Analytics"])
api_router.include_router(history.router, prefix="/history", tags=["Reading History"])
