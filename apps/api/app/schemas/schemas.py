"""
Pydantic schemas for request/response validation (EchoDoc)
"""
from pydantic import BaseModel, EmailStr, Field, ConfigDict
from typing import Optional, List, Any, Dict
from datetime import datetime
from enum import Enum


# Enums
class DocumentStatusEnum(str, Enum):
    UPLOADED = "uploaded"
    EXTRACTING = "extracting"
    EXTRACTED = "extracted"
    FAILED = "failed"


class ConversionStatusEnum(str, Enum):
    QUEUED = "queued"
    PROCESSING = "processing"
    COMPLETED = "completed"
    FAILED = "failed"


# User Schemas
class UserBase(BaseModel):
    email: EmailStr
    username: str = Field(..., min_length=3, max_length=100)
    full_name: Optional[str] = None


class UserCreate(UserBase):
    password: str = Field(..., min_length=8, max_length=100)


class UserLogin(BaseModel):
    email: EmailStr
    password: str


class UserUpdate(BaseModel):
    full_name: Optional[str] = None
    avatar: Optional[str] = None
    password: Optional[str] = Field(None, min_length=8, max_length=100)


class UserResponse(UserBase):
    id: int
    avatar: Optional[str] = None
    is_active: bool
    is_admin: bool
    created_at: datetime
    last_login: Optional[datetime] = None

    model_config = ConfigDict(from_attributes=True)


class Token(BaseModel):
    access_token: str
    token_type: str = "bearer"


class TokenData(BaseModel):
    user_id: Optional[int] = None


# Settings Schemas
class UserSettingsBase(BaseModel):
    preferred_voice: str = "Idera"
    preferred_language: str = "en"
    default_speed: float = Field(default=1.0, ge=0.5, le=2.0)
    default_volume: float = Field(default=1.0, ge=0.0, le=1.0)
    theme: str = "light"


class UserSettingsUpdate(BaseModel):
    preferred_voice: Optional[str] = None
    preferred_language: Optional[str] = None
    default_speed: Optional[float] = Field(None, ge=0.5, le=2.0)
    default_volume: Optional[float] = Field(None, ge=0.0, le=1.0)
    theme: Optional[str] = None


class UserSettingsResponse(UserSettingsBase):
    id: int
    user_id: int
    created_at: datetime
    updated_at: Optional[datetime] = None

    model_config = ConfigDict(from_attributes=True)


# Document Schemas
class DocumentBase(BaseModel):
    original_filename: str


class DocumentUploadResponse(BaseModel):
    id: int
    original_filename: str
    file_type: str
    file_size: int
    status: DocumentStatusEnum
    created_at: datetime

    model_config = ConfigDict(from_attributes=True)


class DocumentResponse(BaseModel):
    id: int
    user_id: int
    original_filename: str
    file_type: str
    file_size: int
    page_count: Optional[int] = None
    char_count: int = 0
    status: DocumentStatusEnum
    error_message: Optional[str] = None
    created_at: datetime
    updated_at: Optional[datetime] = None
    has_audio: bool = False

    model_config = ConfigDict(from_attributes=True)


class DocumentDetailResponse(DocumentResponse):
    extracted_text: Optional[str] = None
    mime_type: str


class DocumentTextResponse(BaseModel):
    id: int
    extracted_text: Optional[str] = None
    page_count: Optional[int] = None
    char_count: int = 0


# Conversion Schemas
class ConversionCreate(BaseModel):
    language: str = Field(default="en", max_length=20)
    voice: str = Field(..., max_length=100)
    speed: float = Field(default=1.0, ge=0.5, le=2.0)
    pitch: Optional[float] = Field(default=None, ge=-10.0, le=10.0)
    target_language: Optional[str] = Field(default=None, description="Optional translation before TTS")


class ConversionResponse(BaseModel):
    id: int
    document_id: int
    status: ConversionStatusEnum
    progress: float
    language: str
    voice: str
    speed: float
    pitch: Optional[float] = None
    error_message: Optional[str] = None
    started_at: Optional[datetime] = None
    completed_at: Optional[datetime] = None
    created_at: datetime
    has_audio: bool = False
    audio_id: Optional[int] = None

    model_config = ConfigDict(from_attributes=True)


# Audio Schemas
class AudioResponse(BaseModel):
    id: int
    conversion_id: int
    format: str
    duration: Optional[float] = None
    file_size: Optional[int] = None
    created_at: datetime
    download_url: str
    stream_url: str

    model_config = ConfigDict(from_attributes=True)


# Voice Schemas
class Voice(BaseModel):
    id: str
    name: str
    language: str
    gender: Optional[str] = None
    locale: str
    provider: str
    accent: Optional[str] = None
    sample_text: Optional[str] = None


class Language(BaseModel):
    code: str
    name: str
    native_name: Optional[str] = None
    voices: List[Voice]


# Translation Schemas
class TranslationRequest(BaseModel):
    text: str = Field(..., min_length=1)
    source_language: str = Field(default="auto")
    target_language: str = Field(..., min_length=2, max_length=20)


class TranslationResponse(BaseModel):
    source_text: str
    translated_text: str
    source_language: str
    target_language: str


# Reading History Schemas
class ReadingHistoryCreate(BaseModel):
    document_id: int
    last_position: int = Field(default=0, ge=0)
    completed: bool = False


class ReadingHistoryResponse(BaseModel):
    id: int
    user_id: int
    document_id: int
    last_position: int
    completed: bool
    read_at: datetime
    document: Optional[DocumentResponse] = None

    model_config = ConfigDict(from_attributes=True)


# Analytics Schemas
class AnalyticsEventCreate(BaseModel):
    event_type: str = Field(..., max_length=50)
    event_data: Optional[Dict[str, Any]] = None
    page_url: Optional[str] = Field(None, max_length=500)


class AnalyticsSummaryResponse(BaseModel):
    total_documents: int
    total_conversions: int
    total_duration_seconds: float
    top_voices: List[Dict[str, Any]]
    language_distribution: List[Dict[str, Any]]
    recent_activity: List[Dict[str, Any]]


# API Generic Responses
class SuccessResponse(BaseModel):
    success: bool = True
    message: str
    data: Optional[Any] = None


class ErrorResponse(BaseModel):
    success: bool = False
    error: dict


class HealthResponse(BaseModel):
    status: str
    service: str
    version: str
    environment: str
    database: str
    queue: str
    storage: str
