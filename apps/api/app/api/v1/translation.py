"""
Translation API endpoints
"""
from fastapi import APIRouter, Depends, HTTPException, status
from typing import Dict, List

from app.schemas.schemas import TranslationRequest, TranslationResponse, Language
from app.services.translation_service import TranslationService
from app.api.dependencies import get_current_user
from app.models.models import User

router = APIRouter()


@router.post("", response_model=TranslationResponse)
async def translate_text(
    request: TranslationRequest,
    current_user: User = Depends(get_current_user)
):
    """
    Translate text into a target language (supporting Yoruba, Hausa, Igbo, English, French, Spanish, etc.)
    """
    if not request.text.strip():
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Text cannot be empty"
        )

    service = TranslationService()
    try:
        result = await service.translate(
            text=request.text,
            target_lang=request.target_language,
            source_lang=request.source_language
        )
        return TranslationResponse(**result)
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Translation failed: {str(e)}"
        )


@router.get("/languages", response_model=Dict[str, str])
async def get_supported_translation_languages():
    """
    Get list of supported translation languages
    """
    return TranslationService.SUPPORTED_LANGUAGES
