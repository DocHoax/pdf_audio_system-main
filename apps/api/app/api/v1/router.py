"""
API Router configuration
"""
from fastapi import APIRouter

from app.api.v1 import auth, documents, conversions, audio, voices

api_router = APIRouter()

# Include all route modules
api_router.include_router(auth.router, prefix="/auth", tags=["Authentication"])
api_router.include_router(documents.router, prefix="/documents", tags=["Documents"])
api_router.include_router(conversions.router, prefix="/conversions", tags=["Conversions"])
api_router.include_router(audio.router, prefix="/audio", tags=["Audio"])
api_router.include_router(voices.router, prefix="/voices", tags=["Voices"])
