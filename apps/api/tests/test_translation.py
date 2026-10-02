"""
Unit tests for Translation service and language support
"""
import pytest
from app.services.translation_service import TranslationService


def test_supported_languages_contain_african_languages():
    langs = TranslationService.SUPPORTED_LANGUAGES
    assert "yo" in langs  # Yoruba
    assert "ha" in langs  # Hausa
    assert "ig" in langs  # Igbo
    assert "pcm" in langs  # Nigerian Pidgin


def test_chunk_text():
    service = TranslationService()
    short_text = "Short text."
    assert service._chunk_text(short_text, max_len=100) == [short_text]

    long_text = "First sentence. " * 30
    chunks = service._chunk_text(long_text, max_len=50)
    assert len(chunks) > 1


@pytest.mark.asyncio
async def test_translate_same_language():
    service = TranslationService()
    result = await service.translate("Hello world", target_lang="en", source_lang="en")
    assert result["translated_text"] == "Hello world"
    assert result["target_language"] == "en"
