"""
Unit tests for text preprocessing and chunking
"""
from app.services.text_preprocessor import TextPreprocessor


def test_text_preprocessor_cleaning():
    preprocessor = TextPreprocessor()
    raw_text = "   This is  a test   document.\n\n\nPage 1 of 5\n\nNext section with space .   "
    cleaned = preprocessor.preprocess(raw_text)

    assert "Page 1 of 5" not in cleaned
    assert "This is a test document." in cleaned
    assert "  " not in cleaned


def test_text_preprocessor_chunking():
    preprocessor = TextPreprocessor()
    text = "Sentence one. Sentence two is longer and describes features. Sentence three completes the paragraph."
    chunks = preprocessor.chunk_text(text, max_chunk_size=40)

    assert len(chunks) >= 2
    for chunk in chunks:
        assert len(chunk) <= 50  # sentence boundaries might be close to max
    assert "Sentence one." in chunks[0]
