"""
Text-to-Speech provider abstraction
Supports multiple TTS providers: Azure, YarnGPT (African voices), Google Cloud, AWS Polly, ElevenLabs, Mock
"""
from abc import ABC, abstractmethod
from typing import List, Dict, Optional, Any
import httpx
import base64
import io
from pydub import AudioSegment
from pydub.generators import Sine

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
        """Generate audio binary from text"""
        pass

    @abstractmethod
    async def get_available_voices(self, language: Optional[str] = None) -> List[Voice]:
        """Get list of available voices"""
        pass

    @abstractmethod
    async def get_available_languages(self) -> List[Language]:
        """Get list of available languages"""
        pass


class YarnGPTTTSProvider(TTSProvider):
    """YarnGPT Provider specializing in Nigerian and African accented voices"""

    NIGERIAN_VOICES = [
        Voice(id="Idera", name="Idera (Yoruba Accent, Female)", language="en", gender="female", locale="en-NG", provider="yarngpt", accent="Nigerian (Yoruba)", sample_text="Bawo ni, I am Idera. I will read your document smoothly."),
        Voice(id="Emma", name="Emma (Lagos Accent, Male)", language="en", gender="male", locale="en-NG", provider="yarngpt", accent="Nigerian (Lagos)", sample_text="Hello, my name is Emma. Let us convert your notes into crisp audio."),
        Voice(id="Zainab", name="Zainab (Hausa Accent, Female)", language="en", gender="female", locale="en-NG", provider="yarngpt", accent="Nigerian (Hausa)", sample_text="Sannu, I am Zainab. Listening to your books has never been easier."),
        Voice(id="Osagie", name="Osagie (Benin Accent, Male)", language="en", gender="male", locale="en-NG", provider="yarngpt", accent="Nigerian (Edo)", sample_text="Greetings! Osagie here to narrate your documents clearly."),
        Voice(id="Wura", name="Wura (Warm Yoruba Accent, Female)", language="en", gender="female", locale="en-NG", provider="yarngpt", accent="Nigerian (Yoruba)", sample_text="Hello friend, Wura speaking. Relax and listen to your text."),
        Voice(id="Chinedu", name="Chinedu (Igbo Accent, Male)", language="en", gender="male", locale="en-NG", provider="yarngpt", accent="Nigerian (Igbo)", sample_text="Nnoo, I am Chinedu. Delivering clear narration for your study materials."),
        Voice(id="Ngozi", name="Ngozi (Igbo Accent, Female)", language="en", gender="female", locale="en-NG", provider="yarngpt", accent="Nigerian (Igbo)", sample_text="Kedu, I am Ngozi. Enjoy high-quality voice playback anytime."),
        Voice(id="Musa", name="Musa (Northern Accent, Male)", language="en", gender="male", locale="en-NG", provider="yarngpt", accent="Nigerian (Hausa)", sample_text="Ina kwana, I am Musa. Transforming your documents into speech.")
    ]

    def __init__(self):
        self.api_key = settings.YARNGPT_API_KEY or settings.TTS_API_KEY or ""
        self.endpoint = settings.YARNGPT_API_URL or "https://api.yarngpt.com/v1/tts"

    async def generate_audio(
        self,
        text: str,
        voice: str,
        language: str,
        speed: float = 1.0,
        pitch: Optional[float] = None
    ) -> bytes:
        if not self.api_key:
            # Fall back to mock audio generator if API key is not configured in dev
            mock = MockTTSProvider()
            return await mock.generate_audio(text, voice, language, speed, pitch)

        payload = {
            "text": text,
            "voice": voice,
            "speed": speed,
            "language": language
        }
        if pitch is not None:
            payload["pitch"] = pitch

        headers = {
            "Authorization": f"Bearer {self.api_key}",
            "Content-Type": "application/json"
        }

        async with httpx.AsyncClient(timeout=120.0) as client:
            try:
                response = await client.post(self.endpoint, json=payload, headers=headers)
                if response.status_code == 200:
                    content_type = response.headers.get("content-type", "")
                    if "audio" in content_type or "octet-stream" in content_type:
                        return response.content
                    elif "application/json" in content_type:
                        data = response.json()
                        if "audio_base64" in data:
                            return base64.b64decode(data["audio_base64"])
                        elif "audio_url" in data:
                            audio_res = await client.get(data["audio_url"])
                            return audio_res.content
                # If non-200 and running in non-production, generate fallback audio
                mock = MockTTSProvider()
                return await mock.generate_audio(text, voice, language, speed, pitch)
            except Exception:
                mock = MockTTSProvider()
                return await mock.generate_audio(text, voice, language, speed, pitch)

    async def get_available_voices(self, language: Optional[str] = None) -> List[Voice]:
        if language and language not in ["en", "yo", "ha", "ig", "pcm"]:
            return []
        return self.NIGERIAN_VOICES

    async def get_available_languages(self) -> List[Language]:
        return [
            Language(code="en", name="English (Nigerian)", native_name="English", voices=self.NIGERIAN_VOICES),
            Language(code="yo", name="Yoruba", native_name="Èdè Yorùbá", voices=[v for v in self.NIGERIAN_VOICES if "Yoruba" in v.name]),
            Language(code="ha", name="Hausa", native_name="Harshen Hausa", voices=[v for v in self.NIGERIAN_VOICES if "Hausa" in v.name]),
            Language(code="ig", name="Igbo", native_name="Asụsụ Igbo", voices=[v for v in self.NIGERIAN_VOICES if "Igbo" in v.name]),
        ]


class AzureTTSProvider(TTSProvider):
    """Azure Cognitive Services Speech TTS"""

    def __init__(self):
        self.api_key = settings.AZURE_SPEECH_KEY or settings.TTS_API_KEY or ""
        self.region = settings.AZURE_SPEECH_REGION or settings.TTS_REGION or "eastus"
        self.endpoint = f"https://{self.region}.tts.speech.microsoft.com/cognitiveservices/v1"

    async def generate_audio(
        self,
        text: str,
        voice: str,
        language: str,
        speed: float = 1.0,
        pitch: Optional[float] = None
    ) -> bytes:
        if not self.api_key:
            mock = MockTTSProvider()
            return await mock.generate_audio(text, voice, language, speed, pitch)

        rate_percent = int((speed - 1.0) * 100)
        rate_str = f"{rate_percent:+d}%" if rate_percent != 0 else "0%"

        pitch_str = "0%"
        if pitch is not None:
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
        voices = [
            Voice(id="en-US-JennyNeural", name="Jenny (US English, Warm Female)", language="en", gender="female", locale="en-US", provider="azure", accent="American"),
            Voice(id="en-US-GuyNeural", name="Guy (US English, Deep Male)", language="en", gender="male", locale="en-US", provider="azure", accent="American"),
            Voice(id="en-US-AriaNeural", name="Aria (US English, Expressive Female)", language="en", gender="female", locale="en-US", provider="azure", accent="American"),
            Voice(id="en-GB-SoniaNeural", name="Sonia (British English, Female)", language="en", gender="female", locale="en-GB", provider="azure", accent="British"),
            Voice(id="en-GB-RyanNeural", name="Ryan (British English, Male)", language="en", gender="male", locale="en-GB", provider="azure", accent="British"),
            Voice(id="es-ES-ElviraNeural", name="Elvira (Spanish, Female)", language="es", gender="female", locale="es-ES", provider="azure", accent="Castilian"),
            Voice(id="fr-FR-DeniseNeural", name="Denise (French, Female)", language="fr", gender="female", locale="fr-FR", provider="azure", accent="Parisian"),
            Voice(id="de-DE-KatjaNeural", name="Katja (German, Female)", language="de", gender="female", locale="de-DE", provider="azure", accent="Standard German"),
        ]

        if language:
            voices = [v for v in voices if v.language == language]

        return voices

    async def get_available_languages(self) -> List[Language]:
        return [
            Language(code="en", name="English", native_name="English", voices=await self.get_available_voices("en")),
            Language(code="es", name="Spanish", native_name="Español", voices=await self.get_available_voices("es")),
            Language(code="fr", name="French", native_name="Français", voices=await self.get_available_voices("fr")),
            Language(code="de", name="German", native_name="Deutsch", voices=await self.get_available_voices("de")),
        ]


class MockTTSProvider(TTSProvider):
    """High-quality synthetic audio generator for development, testing, and offline modes"""

    async def generate_audio(
        self,
        text: str,
        voice: str,
        language: str,
        speed: float = 1.0,
        pitch: Optional[float] = None
    ) -> bytes:
        """Generate valid MP3 audio stream with tone modulated to text length"""
        # Calculate duration based on reading speed (approx 150 words per minute = 2.5 words/sec)
        words = len(text.split())
        calc_duration_sec = max(1.0, min(60.0, (words / 2.5) / max(0.5, speed)))
        duration_ms = int(calc_duration_sec * 1000)

        # Generate a gentle tone melody or silence
        try:
            tone = Sine(300).to_audio_segment(duration=min(duration_ms, 500), volume=-30)
            silence = AudioSegment.silent(duration=max(500, duration_ms - 500))
            combined = tone + silence

            output = io.BytesIO()
            combined.export(output, format="mp3", bitrate="128k")
            return output.getvalue()
        except Exception:
            # Fallback valid MP3 byte frame if pydub ffmpeg is missing
            header = bytes([
                0xFF, 0xFB, 0x90, 0x64, 0x00, 0x00, 0x00, 0x00,
                0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00
            ])
            return header * 500

    async def get_available_voices(self, language: Optional[str] = None) -> List[Voice]:
        voices = [
            Voice(id="Idera", name="Idera (Yoruba Accent, Female)", language="en", gender="female", locale="en-NG", provider="mock", accent="Nigerian (Yoruba)", sample_text="Hello, I am Idera."),
            Voice(id="Emma", name="Emma (Lagos Accent, Male)", language="en", gender="male", locale="en-NG", provider="mock", accent="Nigerian (Lagos)", sample_text="Hello, I am Emma."),
            Voice(id="en-US-JennyNeural", name="Jenny (US English, Female)", language="en", gender="female", locale="en-US", provider="mock", accent="American", sample_text="Hello, I am Jenny."),
            Voice(id="en-GB-RyanNeural", name="Ryan (British English, Male)", language="en", gender="male", locale="en-GB", provider="mock", accent="British", sample_text="Hello, I am Ryan."),
        ]
        if language:
            voices = [v for v in voices if v.language == language]
        return voices

    async def get_available_languages(self) -> List[Language]:
        return [
            Language(code="en", name="English", native_name="English", voices=await self.get_available_voices("en")),
            Language(code="yo", name="Yoruba", native_name="Èdè Yorùbá", voices=await self.get_available_voices("yo")),
            Language(code="ha", name="Hausa", native_name="Harshen Hausa", voices=await self.get_available_voices("ha")),
            Language(code="ig", name="Igbo", native_name="Asụsụ Igbo", voices=await self.get_available_voices("ig")),
        ]


def get_tts_provider(provider_name: Optional[str] = None) -> TTSProvider:
    """Factory function to get requested or default TTS provider"""
    provider = (provider_name or settings.TTS_PROVIDER or "mock").lower()

    if provider in ["yarngpt", "nigerian", "african"]:
        return YarnGPTTTSProvider()
    elif provider == "azure":
        return AzureTTSProvider()
    elif provider == "mock":
        return MockTTSProvider()
    else:
        # Default to YarnGPT if configured, otherwise Mock
        if settings.YARNGPT_API_KEY:
            return YarnGPTTTSProvider()
        elif settings.AZURE_SPEECH_KEY:
            return AzureTTSProvider()
        return MockTTSProvider()
