"""
Application configuration
"""
from pydantic_settings import BaseSettings
from typing import List


class Settings(BaseSettings):
    """Application settings"""
    
    # Database
    DATABASE_URL: str = "postgresql://postgres:postgres@localhost:5432/pdf_audio_db"
    
    # Redis
    REDIS_URL: str = "redis://localhost:6379/0"
    
    # JWT
    JWT_SECRET: str = "your-secret-key-change-this"
    JWT_ALGORITHM: str = "HS256"
    JWT_EXPIRATION_HOURS: int = 24
    
    # TTS Provider
    TTS_PROVIDER: str = "azure"
    TTS_API_KEY: str = ""
    TTS_REGION: str = "eastus"
    
    # Azure Speech
    AZURE_SPEECH_KEY: str = ""
    AZURE_SPEECH_REGION: str = "eastus"
    
    # Google Cloud TTS
    GOOGLE_CLOUD_TTS_KEY: str = ""
    
    # AWS
    AWS_ACCESS_KEY_ID: str = ""
    AWS_SECRET_ACCESS_KEY: str = ""
    AWS_REGION: str = "us-east-1"
    
    # ElevenLabs
    ELEVENLABS_API_KEY: str = ""
    
    # Storage
    STORAGE_PROVIDER: str = "local"
    STORAGE_PATH: str = "./storage"
    STORAGE_BUCKET: str = "pdf-audio-bucket"
    STORAGE_ACCESS_KEY: str = ""
    STORAGE_SECRET_KEY: str = ""
    STORAGE_ENDPOINT: str = ""
    STORAGE_REGION: str = "auto"
    
    # Application
    MAX_FILE_SIZE: int = 10485760  # 10MB
    ALLOWED_EXTENSIONS: str = "pdf"
    MAX_CHUNK_SIZE: int = 5000
    DEFAULT_LANGUAGE: str = "en"
    DEFAULT_VOICE: str = "en-US-AriaNeural"
    DEFAULT_SPEED: float = 1.0
    
    # CORS
    CORS_ORIGINS: List[str] = ["http://localhost:3000", "http://localhost:3001"]
    FRONTEND_URL: str = "http://localhost:3000"
    
    # API
    API_HOST: str = "0.0.0.0"
    API_PORT: int = 8000
    API_WORKERS: int = 4
    
    # Rate Limiting
    RATE_LIMIT_PER_MINUTE: int = 60
    CONVERSION_LIMIT_PER_HOUR: int = 10
    
    # Security
    BCRYPT_ROUNDS: int = 12
    
    # Environment
    ENVIRONMENT: str = "development"
    DEBUG: bool = True
    
    class Config:
        env_file = ".env"
        case_sensitive = True


settings = Settings()
