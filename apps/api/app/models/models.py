"""
Database models for PDF-to-Audio system (EchoDoc)
"""
from sqlalchemy import Column, Integer, String, Text, Float, DateTime, ForeignKey, Enum, Boolean, JSON
from sqlalchemy.orm import relationship
from sqlalchemy.sql import func
import enum

from app.core.database import Base


class DocumentStatus(str, enum.Enum):
    """Document processing status"""
    UPLOADED = "uploaded"
    EXTRACTING = "extracting"
    EXTRACTED = "extracted"
    FAILED = "failed"


class ConversionStatus(str, enum.Enum):
    """Audio conversion status"""
    QUEUED = "queued"
    PROCESSING = "processing"
    COMPLETED = "completed"
    FAILED = "failed"


class User(Base):
    """User model"""
    __tablename__ = "users"

    id = Column(Integer, primary_key=True, index=True)
    email = Column(String(255), unique=True, index=True, nullable=False)
    username = Column(String(100), unique=True, index=True, nullable=False)
    hashed_password = Column(String(255), nullable=False)
    full_name = Column(String(255), nullable=True)
    avatar = Column(String(255), nullable=True)
    is_active = Column(Boolean, default=True)
    is_admin = Column(Boolean, default=False)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())
    last_login = Column(DateTime(timezone=True), nullable=True)

    # Relationships
    documents = relationship("Document", back_populates="user", cascade="all, delete-orphan")
    settings = relationship("UserSettings", back_populates="user", uselist=False, cascade="all, delete-orphan")
    reading_history = relationship("ReadingHistory", back_populates="user", cascade="all, delete-orphan")
    analytics = relationship("UserAnalytics", back_populates="user")


class Document(Base):
    """Document model"""
    __tablename__ = "documents"

    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False)
    original_filename = Column(String(255), nullable=False)
    stored_filename = Column(String(255), nullable=False)
    file_size = Column(Integer, nullable=False)
    file_path = Column(String(500), nullable=False)
    file_type = Column(String(20), default="pdf", nullable=False)
    mime_type = Column(String(100), nullable=False)
    page_count = Column(Integer, nullable=True)
    char_count = Column(Integer, default=0)
    extracted_text = Column(Text, nullable=True)
    status = Column(Enum(DocumentStatus), default=DocumentStatus.UPLOADED)
    error_message = Column(Text, nullable=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())

    # Relationships
    user = relationship("User", back_populates="documents")
    conversions = relationship("ConversionJob", back_populates="document", cascade="all, delete-orphan")
    reading_history = relationship("ReadingHistory", back_populates="document", cascade="all, delete-orphan")


class ConversionJob(Base):
    """Audio conversion job model"""
    __tablename__ = "conversion_jobs"

    id = Column(Integer, primary_key=True, index=True)
    document_id = Column(Integer, ForeignKey("documents.id"), nullable=False)
    status = Column(Enum(ConversionStatus), default=ConversionStatus.QUEUED)
    progress = Column(Float, default=0.0)
    language = Column(String(20), nullable=False, default="en")
    voice = Column(String(100), nullable=False, default="Idera")
    speed = Column(Float, default=1.0)
    pitch = Column(Float, nullable=True)
    translated_text = Column(Text, nullable=True)
    error_message = Column(Text, nullable=True)
    started_at = Column(DateTime(timezone=True), nullable=True)
    completed_at = Column(DateTime(timezone=True), nullable=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now())

    # Relationships
    document = relationship("Document", back_populates="conversions")
    audio_file = relationship("AudioFile", back_populates="conversion", uselist=False, cascade="all, delete-orphan")


class AudioFile(Base):
    """Audio file model"""
    __tablename__ = "audio_files"

    id = Column(Integer, primary_key=True, index=True)
    conversion_id = Column(Integer, ForeignKey("conversion_jobs.id"), unique=True, nullable=False)
    file_path = Column(String(500), nullable=False)
    format = Column(String(10), default="mp3")
    duration = Column(Float, nullable=True)
    file_size = Column(Integer, nullable=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now())

    # Relationships
    conversion = relationship("ConversionJob", back_populates="audio_file")


class UserSettings(Base):
    """User preferences & settings model"""
    __tablename__ = "user_settings"

    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), unique=True, nullable=False)
    preferred_voice = Column(String(50), default="Idera")
    preferred_language = Column(String(20), default="en")
    default_speed = Column(Float, default=1.0)
    default_volume = Column(Float, default=1.0)
    theme = Column(String(20), default="light")
    created_at = Column(DateTime(timezone=True), server_default=func.now())
    updated_at = Column(DateTime(timezone=True), onupdate=func.now())

    # Relationships
    user = relationship("User", back_populates="settings")


class ReadingHistory(Base):
    """Reading & playback progress bookmark model"""
    __tablename__ = "reading_history"

    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False)
    document_id = Column(Integer, ForeignKey("documents.id"), nullable=False)
    last_position = Column(Integer, default=0)
    completed = Column(Boolean, default=False)
    read_at = Column(DateTime(timezone=True), server_default=func.now(), onupdate=func.now())

    # Relationships
    user = relationship("User", back_populates="reading_history")
    document = relationship("Document", back_populates="reading_history")


class UserAnalytics(Base):
    """Analytics & usage tracking model"""
    __tablename__ = "user_analytics"

    id = Column(Integer, primary_key=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=True)
    session_id = Column(String(128), nullable=True)
    event_type = Column(String(50), nullable=False, index=True)
    event_data = Column(JSON, nullable=True)
    page_url = Column(String(500), nullable=True)
    ip_address = Column(String(45), nullable=True)
    user_agent = Column(String(500), nullable=True)
    created_at = Column(DateTime(timezone=True), server_default=func.now(), index=True)

    # Relationships
    user = relationship("User", back_populates="analytics")
