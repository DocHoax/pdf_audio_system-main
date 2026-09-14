"""
Voice and language endpoints
"""
from fastapi import APIRouter, Depends
from typing import List

from app.schemas.schemas import Voice, Language
from app.services.tts_provider import get_tts_provider

router = APIRouter()


@router.get("/languages", response_model=List[Language])
async def get_languages():
    """
    Get available languages and voices
    """
    tts_provider = get_tts_provider()
    return await tts_provider.get_available_languages()


@router.get("", response_model=List[Voice])
async def get_voices(language: str = None):
    """
    Get available voices, optionally filtered by language
    """
    tts_provider = get_tts_provider()
    return await tts_provider.get_available_voices(language)
