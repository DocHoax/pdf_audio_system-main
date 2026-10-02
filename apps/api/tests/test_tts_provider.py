"""
Unit tests for TTS providers and African accent voice configurations
"""
import pytest
from app.services.tts_provider import (
    MockTTSProvider,
    YarnGPTTTSProvider,
    AzureTTSProvider,
    get_tts_provider
)


@pytest.mark.asyncio
async def test_mock_tts_provider_audio_generation():
    provider = MockTTSProvider()
    audio_bytes = await provider.generate_audio(
        text="Hello world test",
        voice="Idera",
        language="en",
        speed=1.0
    )
    assert len(audio_bytes) > 0
    assert isinstance(audio_bytes, bytes)


@pytest.mark.asyncio
async def test_yarngpt_provider_voices():
    provider = YarnGPTTTSProvider()
    voices = await provider.get_available_voices()
    voice_ids = [v.id for v in voices]

    # Verify Nigerian accents are included
    assert "Idera" in voice_ids
    assert "Emma" in voice_ids
    assert "Zainab" in voice_ids
    assert "Osagie" in voice_ids
    assert "Wura" in voice_ids
    assert "Chinedu" in voice_ids


@pytest.mark.asyncio
async def test_yarngpt_fallback_generation():
    # Without API key configured, should gracefully fall back to mock audio
    provider = YarnGPTTTSProvider()
    audio_bytes = await provider.generate_audio(
        text="Testing African accent TTS fallback",
        voice="Idera",
        language="en",
        speed=1.0
    )
    assert len(audio_bytes) > 0


def test_get_tts_provider_factory():
    assert isinstance(get_tts_provider("mock"), MockTTSProvider)
    assert isinstance(get_tts_provider("yarngpt"), YarnGPTTTSProvider)
    assert isinstance(get_tts_provider("azure"), AzureTTSProvider)
