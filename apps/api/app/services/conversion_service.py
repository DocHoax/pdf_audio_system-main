"""
Conversion service for processing text-to-speech jobs
"""
from sqlalchemy.orm import Session
from datetime import datetime
import uuid

from app.models.models import ConversionJob, ConversionStatus, Document, AudioFile
from app.services.tts_provider import get_tts_provider
from app.services.text_preprocessor import TextPreprocessor
from app.services.storage import get_storage_service
from app.core.config import settings


class ConversionService:
    """Service for processing TTS conversion jobs"""
    
    def __init__(self, db: Session):
        self.db = db
        self.tts_provider = get_tts_provider()
        self.preprocessor = TextPreprocessor()
        self.storage_service = get_storage_service()
    
    async def process_conversion(self, conversion_id: int):
        """
        Process a conversion job
        This runs in the background
        """
        conversion = self.db.query(ConversionJob).filter(
            ConversionJob.id == conversion_id
        ).first()
        
        if not conversion:
            return
        
        try:
            # Update status
            conversion.status = ConversionStatus.PROCESSING
            conversion.started_at = datetime.utcnow()
            conversion.progress = 0.0
            self.db.commit()
            
            # Get document
            document = conversion.document
            text = document.extracted_text
            
            if not text:
                raise Exception("No text available for conversion")
            
            # Preprocess text
            cleaned_text = self.preprocessor.preprocess(text)
            conversion.progress = 10.0
            self.db.commit()
            
            # Split into chunks if text is large
            chunks = self.preprocessor.chunk_text(
                cleaned_text,
                max_chunk_size=settings.MAX_CHUNK_SIZE
            )
            
            if not chunks:
                raise Exception("No valid text chunks for conversion")
            
            conversion.progress = 20.0
            self.db.commit()
            
            # Generate audio for each chunk
            audio_segments = []
            chunk_count = len(chunks)
            
            for i, chunk in enumerate(chunks):
                try:
                    audio_data = await self.tts_provider.generate_audio(
                        text=chunk,
                        voice=conversion.voice,
                        language=conversion.language,
                        speed=conversion.speed,
                        pitch=conversion.pitch
                    )
                    audio_segments.append(audio_data)
                    
                    # Update progress
                    progress = 20.0 + (70.0 * (i + 1) / chunk_count)
                    conversion.progress = progress
                    self.db.commit()
                    
                except Exception as e:
                    raise Exception(f"Failed to generate audio for chunk {i+1}: {str(e)}")
            
            # Combine audio segments
            combined_audio = b"".join(audio_segments)
            conversion.progress = 90.0
            self.db.commit()
            
            # Save audio file
            audio_filename = f"{uuid.uuid4()}.mp3"
            audio_path = await self.storage_service.save_audio(audio_filename, combined_audio)
            
            # Create audio file record
            audio_file = AudioFile(
                conversion_id=conversion.id,
                file_path=audio_path,
                format="mp3",
                file_size=len(combined_audio),
                duration=None  # Could be calculated with audio analysis library
            )
            
            self.db.add(audio_file)
            
            # Update conversion status
            conversion.status = ConversionStatus.COMPLETED
            conversion.progress = 100.0
            conversion.completed_at = datetime.utcnow()
            self.db.commit()
            
        except Exception as e:
            # Update conversion with error
            conversion.status = ConversionStatus.FAILED
            conversion.error_message = str(e)
            conversion.completed_at = datetime.utcnow()
            self.db.commit()
