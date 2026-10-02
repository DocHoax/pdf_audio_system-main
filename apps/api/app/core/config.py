"""
Application configuration for EchoDoc PDF-to-Audio System
"""
from pydantic_settings import BaseSettings
from pydantic import field_validator, model_validator
from typing import List, Union, Optional, Any
import json


class Settings(BaseSettings):
    """Application settings"""

    # Project Info
    PROJECT_NAME: str = "EchoDoc PDF-to-Audio API"
    VERSION: str = "1.0.0"

    # Database
    DATABASE_URL: str = "postgresql://postgres:postgres@localhost:5432/pdf_audio_db"

    # Redis & Celery Broker
    REDIS_URL: str = "redis://localhost:6379/0"
    CELERY_BROKER_URL: str = "redis://localhost:6379/0"
    CELERY_RESULT_BACKEND: str = "redis://localhost:6379/0"

    # JWT & Auth
    JWT_SECRET: str = "echodoc-development-jwt-secret-key-change-in-production-2026"
    SECRET_KEY: Optional[str] = None
    JWT_ALGORITHM: str = "HS256"
    JWT_EXPIRATION_HOURS: int = 24 * 30  # 30 days

    # TTS Providers
    TTS_PROVIDER: str = "yarngpt"  # yarngpt | azure | google | polly | elevenlabs | mock
    TTS_API_KEY: str = ""
    TTS_REGION: str = "eastus"

    # YarnGPT / African Accents
    YARNGPT_API_KEY: str = ""
    YARNGPT_API_URL: str = "https://api.yarngpt.com/v1/tts"

    # Azure Speech
    AZURE_SPEECH_KEY: str = ""
    AZURE_SPEECH_REGION: str = "eastus"

    # Google Cloud TTS
    GOOGLE_CLOUD_TTS_KEY: str = ""
    GOOGLE_APPLICATION_CREDENTIALS: str = ""

    # AWS Polly & S3
    AWS_ACCESS_KEY_ID: str = ""
    AWS_SECRET_ACCESS_KEY: str = ""
    AWS_REGION: str = "us-east-1"

    # ElevenLabs
    ELEVENLABS_API_KEY: str = ""

    # Storage
    STORAGE_PROVIDER: str = "local"  # local | s3
    STORAGE_TYPE: Optional[str] = None
    STORAGE_PATH: str = "./storage"
    LOCAL_STORAGE_PATH: Optional[str] = None
    STORAGE_BUCKET: str = "pdf-audio-bucket"
    S3_BUCKET_NAME: Optional[str] = None
    STORAGE_ACCESS_KEY: str = ""
    STORAGE_SECRET_KEY: str = ""
    STORAGE_ENDPOINT: str = ""
    STORAGE_REGION: str = "auto"

    # Document Constraints
    MAX_FILE_SIZE: int = 50 * 1024 * 1024  # 50MB
    ALLOWED_EXTENSIONS: str = "pdf,docx,txt"
    MAX_CHUNK_SIZE: int = 4000
    DEFAULT_LANGUAGE: str = "en"
    DEFAULT_VOICE: str = "Idera"
    DEFAULT_SPEED: float = 1.0

    # CORS
    CORS_ORIGINS: Union[List[str], str] = [
        "http://localhost:3000",
        "http://localhost:3001",
        "http://127.0.0.1:3000",
        "http://127.0.0.1:3001"
    ]
    FRONTEND_URL: str = "http://localhost:3000"

    # Server API
    API_HOST: str = "0.0.0.0"
    API_PORT: int = 8000
    API_WORKERS: int = 4

    # Rate Limiting
    RATE_LIMIT_PER_MINUTE: int = 120
    CONVERSION_LIMIT_PER_HOUR: int = 60

    # Security
    BCRYPT_ROUNDS: int = 12

    # Environment
    ENVIRONMENT: str = "development"
    DEBUG: bool = True

    @field_validator("CORS_ORIGINS", mode="before")
    @classmethod
    def assemble_cors_origins(cls, v: Any) -> List[str]:
        if isinstance(v, str) and not v.startswith("["):
            return [i.strip() for i in v.split(",") if i.strip()]
        elif isinstance(v, (list, str)):
            if isinstance(v, str):
                try:
                    return json.loads(v)
                except Exception:
                    return [v]
            return v
        return v

    @model_validator(mode="after")
    def sync_compatibility_aliases(self) -> "Settings":
        # Handle SECRET_KEY -> JWT_SECRET
        if self.SECRET_KEY:
            self.JWT_SECRET = self.SECRET_KEY
        # Handle STORAGE_TYPE -> STORAGE_PROVIDER
        if self.STORAGE_TYPE:
            self.STORAGE_PROVIDER = self.STORAGE_TYPE
        # Handle LOCAL_STORAGE_PATH -> STORAGE_PATH
        if self.LOCAL_STORAGE_PATH:
            self.STORAGE_PATH = self.LOCAL_STORAGE_PATH
        # Handle S3_BUCKET_NAME -> STORAGE_BUCKET
        if self.S3_BUCKET_NAME:
            self.STORAGE_BUCKET = self.S3_BUCKET_NAME
        return self

    class Config:
        env_file = ".env"
        case_sensitive = True
        extra = "allow"


settings = Settings()
