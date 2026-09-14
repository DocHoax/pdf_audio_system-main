"""
Text preprocessing service
"""
import re
from typing import List


class TextPreprocessor:
    """Service for preprocessing text before TTS conversion"""
    
    def preprocess(self, text: str) -> str:
        """
        Preprocess text for TTS
        """
        if not text:
            return ""
        
        # Remove excessive whitespace
        text = self._normalize_whitespace(text)
        
        # Normalize punctuation
        text = self._normalize_punctuation(text)
        
        # Remove obvious artifacts
        text = self._remove_artifacts(text)
        
        return text.strip()
    
    def chunk_text(self, text: str, max_chunk_size: int = 5000) -> List[str]:
        """
        Split text into chunks for TTS processing
        Respects sentence boundaries
        """
        if not text:
            return []
        
        # Split into sentences
        sentences = self._split_into_sentences(text)
        
        chunks = []
        current_chunk = []
        current_size = 0
        
        for sentence in sentences:
            sentence_size = len(sentence)
            
            # If single sentence exceeds max size, split it
            if sentence_size > max_chunk_size:
                if current_chunk:
                    chunks.append(" ".join(current_chunk))
                    current_chunk = []
                    current_size = 0
                
                # Split long sentence by commas or other punctuation
                sub_parts = re.split(r'([,;:])', sentence)
                temp_chunk = ""
                
                for part in sub_parts:
                    if len(temp_chunk) + len(part) < max_chunk_size:
                        temp_chunk += part
                    else:
                        if temp_chunk:
                            chunks.append(temp_chunk)
                        temp_chunk = part
                
                if temp_chunk:
                    chunks.append(temp_chunk)
                continue
            
            # Check if adding this sentence would exceed chunk size
            if current_size + sentence_size > max_chunk_size:
                if current_chunk:
                    chunks.append(" ".join(current_chunk))
                current_chunk = [sentence]
                current_size = sentence_size
            else:
                current_chunk.append(sentence)
                current_size += sentence_size
        
        # Add remaining chunk
        if current_chunk:
            chunks.append(" ".join(current_chunk))
        
        return chunks
    
    def _normalize_whitespace(self, text: str) -> str:
        """Normalize whitespace"""
        # Replace multiple spaces with single space
        text = re.sub(r' +', ' ', text)
        
        # Replace multiple newlines with double newline
        text = re.sub(r'\n\s*\n+', '\n\n', text)
        
        return text.strip()
    
    def _normalize_punctuation(self, text: str) -> str:
        """Normalize punctuation"""
        # Ensure space after punctuation
        text = re.sub(r'([.!?;:,])([A-Za-z])', r'\1 \2', text)
        
        # Remove space before punctuation
        text = re.sub(r'\s+([.!?;:,])', r'\1', text)
        
        return text
    
    def _remove_artifacts(self, text: str) -> str:
        """Remove common PDF artifacts"""
        # Remove standalone page numbers
        text = re.sub(r'^\s*\d+\s*$', '', text, flags=re.MULTILINE)
        
        # Remove common headers/footers patterns
        text = re.sub(r'^\s*Page \d+ of \d+\s*$', '', text, flags=re.MULTILINE)
        
        return text
    
    def _split_into_sentences(self, text: str) -> List[str]:
        """Split text into sentences"""
        # Simple sentence splitting
        # This is a basic implementation; for better results, use nltk or spacy
        
        # Split on period, exclamation, question mark followed by space
        sentences = re.split(r'(?<=[.!?])\s+', text)
        
        # Filter out empty sentences
        sentences = [s.strip() for s in sentences if s.strip()]
        
        return sentences
