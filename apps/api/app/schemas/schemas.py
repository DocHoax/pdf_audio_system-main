"""
Pydantic schemas for request/response validation
"""
from pydantic import BaseModel, EmailStr, Field, ConfigDict
from typing import Optional, List
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


class UserResponse(UserBase):
    id: int
    is_active: bool
    is_admin: bool
    created_at: datetime
    
    model_config = ConfigDict(from_attributes=True)


class Token(BaseModel):
    access_token: str
    token_type: str = "bearer"


class TokenData(BaseModel):
    user_id: Optional[int] = None


# Document Schemas
class DocumentBase(BaseModel):
    original_filename: str


class DocumentUploadResponse(BaseModel):
    id: int
    original_filename: str
    file_size: int
    status: DocumentStatusEnum
    created_at: datetime
    
    model_config = ConfigDict(from_attributes=True)


class DocumentResponse(BaseModel):
    id: int
    user_id: int
    original_filename: str
    file_size: int
    page_count: Optional[int]
    status: DocumentStatusEnum
    error_message: Optional[str]
    created_at: datetime
    updated_at: Optional[datetime]
    has_audio: bool = False
    
    model_config = ConfigDict(from_attributes=True)


class DocumentDetailResponse(DocumentResponse):
    extracted_text: Optional[str]
    mime_type: str


class DocumentTextResponse(BaseModel):
    id: int
    extracted_text: Optional[str]
    page_count: Optional[int]
    character_count: int


# Conversion Schemas
class ConversionCreate(BaseModel):
    language: str = Field(default="en", max_length=10)
    voice: str = Field(..., max_length=100)
    speed: float = Field(default=1.0, ge=0.5, le=2.0)
    pitch: Optional[float] = Field(default=None, ge=-10.0, le=10.0)


class ConversionResponse(BaseModel):
    id: int
    document_id: int
    status: ConversionStatusEnum
    progress: float
    language: str
    voice: str
    speed: float
    pitch: Optional[float]
    error_message: Optional[str]
    started_at: Optional[datetime]
    completed_at: Optional[datetime]
    created_at: datetime
    has_audio: bool = False
    
    model_config = ConfigDict(from_attributes=True)


# Audio Schemas
class AudioResponse(BaseModel):
    id: int
    conversion_id: int
    format: str
    duration: Optional[float]
    file_size: Optional[int]
    created_at: datetime
    download_url: str
    
    model_config = ConfigDict(from_attributes=True)


# Voice Schemas
class Voice(BaseModel):
    id: str
    name: str
    language: str
    gender: Optional[str]
    locale: str


class Language(BaseModel):
    code: str
    name: str
    voices: List[Voice]


# API Response Schemas
class SuccessResponse(BaseModel):
    success: bool = True
    message: str
    data: Optional[dict] = None


class ErrorResponse(BaseModel):
    success: bool = False
    error: dict


class HealthResponse(BaseModel):
    status: str
    database: str
    queue: str
    storage: str
