"""
Integration tests for FastAPI v1 endpoints
"""
import io
import fitz
from app.models.models import Document, DocumentStatus


def test_auth_registration_and_login(client):
    # Test Register
    reg_res = client.post(
        "/api/v1/auth/register",
        json={
            "email": "newuser@example.com",
            "username": "newuser",
            "password": "strongPassword123",
            "full_name": "New User"
        }
    )
    assert reg_res.status_code == 201
    reg_data = reg_res.json()
    assert reg_data["username"] == "newuser"
    assert "id" in reg_data

    # Test Login
    login_res = client.post(
        "/api/v1/auth/login",
        data={
            "username": "newuser",
            "password": "strongPassword123"
        }
    )
    assert login_res.status_code == 200
    login_data = login_res.json()
    assert "access_token" in login_data
    assert login_data["token_type"] == "bearer"


def test_voices_and_languages_endpoints(client):
    # Test Voices
    voices_res = client.get("/api/v1/voices")
    assert voices_res.status_code == 200
    voices = voices_res.json()
    assert len(voices) > 0
    voice_ids = [v["id"] for v in voices]
    assert "Idera" in voice_ids or "Emma" in voice_ids

    # Test Languages
    lang_res = client.get("/api/v1/voices/languages")
    assert lang_res.status_code == 200
    languages = lang_res.json()
    assert len(languages) > 0


def test_document_upload_and_extraction(client, auth_headers):
    # Create test PDF
    doc = fitz.open()
    page = doc.new_page()
    page.insert_text((50, 72), "EchoDoc test document content for API integration testing.")
    pdf_bytes = doc.write()
    doc.close()

    # Upload document
    files = {"file": ("test_doc.pdf", io.BytesIO(pdf_bytes), "application/pdf")}
    upload_res = client.post("/api/v1/documents/upload", files=files, headers=auth_headers)
    assert upload_res.status_code == 201
    doc_data = upload_res.json()
    doc_id = doc_data["id"]
    assert doc_data["original_filename"] == "test_doc.pdf"

    # Extract text
    extract_res = client.post(f"/api/v1/documents/{doc_id}/extract", headers=auth_headers)
    assert extract_res.status_code == 200
    extract_data = extract_res.json()
    assert "EchoDoc test document" in extract_data["extracted_text"]
    assert extract_data["page_count"] == 1

    # List documents
    list_res = client.get("/api/v1/documents", headers=auth_headers)
    assert list_res.status_code == 200
    docs = list_res.json()
    assert len(docs) >= 1


def test_user_settings_and_analytics(client, auth_headers):
    # Get user settings
    settings_res = client.get("/api/v1/users/me/settings", headers=auth_headers)
    assert settings_res.status_code == 200
    settings = settings_res.json()
    assert settings["preferred_voice"] == "Idera"

    # Update settings
    patch_res = client.patch(
        "/api/v1/users/me/settings",
        json={"preferred_voice": "Emma", "theme": "dark", "default_speed": 1.25},
        headers=auth_headers
    )
    assert patch_res.status_code == 200
    updated_settings = patch_res.json()
    assert updated_settings["preferred_voice"] == "Emma"
    assert updated_settings["theme"] == "dark"

    # Get analytics summary
    analytics_res = client.get("/api/v1/analytics/summary", headers=auth_headers)
    assert analytics_res.status_code == 200
    analytics_data = analytics_res.json()
    assert "total_documents" in analytics_data
    assert "total_conversions" in analytics_data
