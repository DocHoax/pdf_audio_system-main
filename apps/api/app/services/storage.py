"""
Storage service abstraction
Supports local filesystem and cloud storage (S3, R2, etc.)
"""
from abc import ABC, abstractmethod
from typing import Optional
import os
import aiofiles
from pathlib import Path

from app.core.config import settings


class StorageService(ABC):
    """Abstract storage service"""
    
    @abstractmethod
    async def save_document(self, filename: str, content: bytes) -> str:
        """Save document and return file path"""
        pass
    
    @abstractmethod
    async def get_document(self, file_path: str) -> bytes:
        """Get document content"""
        pass
    
    @abstractmethod
    async def delete_document(self, file_path: str) -> None:
        """Delete document"""
        pass
    
    @abstractmethod
    async def save_audio(self, filename: str, content: bytes) -> str:
        """Save audio file and return file path"""
        pass
    
    @abstractmethod
    async def get_audio(self, file_path: str) -> bytes:
        """Get audio content"""
        pass
    
    @abstractmethod
    async def delete_audio(self, file_path: str) -> None:
        """Delete audio file"""
        pass


class LocalStorageService(StorageService):
    """Local filesystem storage"""
    
    def __init__(self):
        self.storage_path = Path(settings.STORAGE_PATH)
        self.documents_path = self.storage_path / "documents"
        self.audio_path = self.storage_path / "audio"
        
        # Create directories if they don't exist
        self.documents_path.mkdir(parents=True, exist_ok=True)
        self.audio_path.mkdir(parents=True, exist_ok=True)
    
    async def save_document(self, filename: str, content: bytes) -> str:
        """Save document to local filesystem"""
        file_path = self.documents_path / filename
        
        async with aiofiles.open(file_path, 'wb') as f:
            await f.write(content)
        
        return str(file_path)
    
    async def get_document(self, file_path: str) -> bytes:
        """Read document from local filesystem"""
        async with aiofiles.open(file_path, 'rb') as f:
            content = await f.read()
        return content
    
    async def delete_document(self, file_path: str) -> None:
        """Delete document from local filesystem"""
        if os.path.exists(file_path):
            os.remove(file_path)
    
    async def save_audio(self, filename: str, content: bytes) -> str:
        """Save audio file to local filesystem"""
        file_path = self.audio_path / filename
        
        async with aiofiles.open(file_path, 'wb') as f:
            await f.write(content)
        
        return str(file_path)
    
    async def get_audio(self, file_path: str) -> bytes:
        """Read audio from local filesystem"""
        async with aiofiles.open(file_path, 'rb') as f:
            content = await f.read()
        return content
    
    async def delete_audio(self, file_path: str) -> None:
        """Delete audio from local filesystem"""
        if os.path.exists(file_path):
            os.remove(file_path)


# Factory function to get storage service
def get_storage_service() -> StorageService:
    """Get configured storage service"""
    if settings.STORAGE_PROVIDER == "local":
        return LocalStorageService()
    else:
        # For now, default to local storage
        # In production, implement S3StorageService, R2StorageService, etc.
        return LocalStorageService()
