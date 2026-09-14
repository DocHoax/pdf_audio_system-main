"""
Text-to-Speech provider abstraction
Supports multiple TTS providers: Azure, Google, AWS Polly, ElevenLabs
"""
from abc import ABC, abstractmethod
from typing import List, Dict, Optional
import httpx
from io import BytesIO

from app.core.config import settings
from app.schemas.schemas import Voice, Language


class TTSProvider(ABC):
    """Abstract TTS provider interface"""
    
    @abstractmethod
    async def generate_audio(
        self,
        text: str,
        voice: str,
        language: str,
        speed: float = 1.0,
        pitch: Optional[float] = None
    ) -> bytes:
        """Generate audio from text"""
        pass
    
    @abstractmethod
    async def get_available_voices(self, language: Optional[str] = None) -> List[Voice]:
        """Get available voices"""
        pass
    
    @abstractmethod
    async def get_available_languages(self) -> List[Language]:
        """Get available languages"""
        pass


class AzureTTSProvider(TTSProvider):
    """Azure Cognitive Services Speech TTS"""
    
    def __init__(self):
        self.api_key = settings.AZURE_SPEECH_KEY or settings.TTS_API_KEY
        self.region = settings.AZURE_SPEECH_REGION or settings.TTS_REGION
        self.endpoint = f"https://{self.region}.tts.speech.microsoft.com/cognitiveservices/v1"
    
    async def generate_audio(
        self,
        text: str,
        voice: str,
        language: str,
        speed: float = 1.0,
        pitch: Optional[float] = None
    ) -> bytes:
        """Generate audio using Azure Speech"""
        
        # Build SSML
        rate_percent = int((speed - 1.0) * 100)
        rate_str = f"{rate_percent:+d}%" if rate_percent != 0 else "0%"
        
        pitch_str = "0%"
        if pitch:
            pitch_str = f"{pitch:+.1f}st"
        
        ssml = f"""
        <speak version='1.0' xml:lang='{language}'>
            <voice name='{voice}'>
                <prosody rate='{rate_str}' pitch='{pitch_str}'>
                    {text}
                </prosody>
            </voice>
        </speak>
        """
        
        headers = {
            "Ocp-Apim-Subscription-Key": self.api_key,
            "Content-Type": "application/ssml+xml",
            "X-Microsoft-OutputFormat": "audio-16khz-128kbitrate-mono-mp3"
        }
        
        async with httpx.AsyncClient(timeout=300.0) as client:
            response = await client.post(
                self.endpoint,
                headers=headers,
                content=ssml.strip()
            )
            
            if response.status_code != 200:
                raise Exception(f"Azure TTS failed: {response.text}")
            
            return response.content
    
    async def get_available_voices(self, language: Optional[str] = None) -> List[Voice]:
        """Get available Azure voices"""
        # Common Azure voices
        voices = [
            Voice(id="en-US-AriaNeural", name="Aria (US English, Female)", language="en", gender="female", locale="en-US"),
            Voice(id="en-US-GuyNeural", name="Guy (US English, Male)", language="en", gender="male", locale="en-US"),
            Voice(id="en-GB-SoniaNeural", name="Sonia (British English, Female)", language="en", gender="female", locale="en-GB"),
            Voice(id="en-GB-RyanNeural", name="Ryan (British English, Male)", language="en", gender="male", locale="en-GB"),
            Voice(id="es-ES-ElviraNeural", name="Elvira (Spanish, Female)", language="es", gender="female", locale="es-ES"),
            Voice(id="fr-FR-DeniseNeural", name="Denise (French, Female)", language="fr", gender="female", locale="fr-FR"),
            Voice(id="de-DE-KatjaNeural", name="Katja (German, Female)", language="de", gender="female", locale="de-DE"),
        ]
        
        if language:
            voices = [v for v in voices if v.language == language]
        
        return voices
    
    async def get_available_languages(self) -> List[Language]:
        """Get available languages"""
        return [
            Language(code="en", name="English", voices=await self.get_available_voices("en")),
            Language(code="es", name="Spanish", voices=await self.get_available_voices("es")),
            Language(code="fr", name="French", voices=await self.get_available_voices("fr")),
            Language(code="de", name="German", voices=await self.get_available_voices("de")),
        ]


class MockTTSProvider(TTSProvider):
    """Mock TTS provider for development/testing"""
    
    async def generate_audio(
        self,
        text: str,
        voice: str,
        language: str,
        speed: float = 1.0,
        pitch: Optional[float] = None
    ) -> bytes:
        """Generate mock audio (silent MP3)"""
        # Return a minimal valid MP3 file (silent audio)
        # In production, this would never be used
        mp3_header = bytes([
            0xFF, 0xFB, 0x90, 0x00, 0x00, 0x00, 0x00, 0x00,
            0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00
        ])
        return mp3_header * 1000  # Repeat to create a small file
    
    async def get_available_voices(self, language: Optional[str] = None) -> List[Voice]:
        """Get mock voices"""
        voices = [
            Voice(id="mock-en-us-female", name="Mock Female (US English)", language="en", gender="female", locale="en-US"),
            Voice(id="mock-en-us-male", name="Mock Male (US English)", language="en", gender="male", locale="en-US"),
        ]
        
        if language:
            voices = [v for v in voices if v.language == language]
        
        return voices
    
    async def get_available_languages(self) -> List[Language]:
        """Get mock languages"""
        return [
            Language(code="en", name="English", voices=await self.get_available_voices("en")),
        ]


def get_tts_provider() -> TTSProvider:
    """Factory function to get configured TTS provider"""
    provider = settings.TTS_PROVIDER.lower()
    
    if provider == "azure":
        if settings.AZURE_SPEECH_KEY or settings.TTS_API_KEY:
            return AzureTTSProvider()
        else:
            print("⚠️  Warning: Azure TTS configured but no API key found. Using mock provider.")
            return MockTTSProvider()
    elif provider == "mock":
        return MockTTSProvider()
    else:
        print(f"⚠️  Warning: Unknown TTS provider '{provider}'. Using mock provider.")
        return MockTTSProvider()
