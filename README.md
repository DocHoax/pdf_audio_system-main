# EchoDoc - Modern PDF & Document to Audio System

EchoDoc is a production-grade, full-stack document-to-speech platform designed with accessibility and localization at its core. It transforms PDF and DOCX documents into natural-sounding speech across multiple languages and authentic localized accents.

---

## 🚀 Key Features

- **Document Ingestion & Processing**: High-fidelity text extraction for PDF and DOCX files using **PyMuPDF** (`fitz`) and **python-docx**, with automatic **Tesseract OCR** fallback for scanned documents.
- **Intelligent Text Preprocessing**: Automated ligature resolution, abbreviation expansion, sentence boundary chunking, and paragraph cleaning.
- **Multi-Language Translation**: Document and paragraph translation across global and Nigerian languages (Yoruba, Hausa, Igbo, Nigerian Pidgin, French, Spanish, German, Arabic).
- **Extensible Text-to-Speech Engine**:
  - **YarnGPT**: Specialized for authentic Nigerian and African voice personas (*Idera, Emma, Zainab, Osagie, Wura, Chinedu, Ngozi, Musa*).
  - **Microsoft Azure Speech Service**: Multi-language neural voice synthesis.
  - **Google Cloud TTS & Amazon Polly**: Global enterprise speech synthesis.
  - **ElevenLabs Speech**: High-fidelity generative voice models.
  - **Mock TTS**: Zero-credential local development provider generating synthetic audio waveforms.
- **Asynchronous Processing Pipeline**: Distributed background task queue powered by **Celery** and **Redis** for concurrency-controlled chunked synthesis and `pydub` audio stitching.
- **Dynamic Streaming Audio Player**: Custom HTML5 audio player supporting byte-range streaming, duration seek scrubbers, ±15s jump buttons, speed multipliers (0.75x–2.0x), volume controls, MP3 download, and auto-saving reading bookmarks.
- **Reading History & Resumable Bookmarks**: Track listening progress percentage, last timestamp, and resume playback instantly from any device.
- **Analytics & Insights**: Summary dashboards for document conversion volume, listening time, voice distribution, and storage utilization.
- **Containerized & CI/CD Ready**: Multi-stage Docker builds (`Dockerfile.api`, `Dockerfile.worker`, `Dockerfile.web`), orchestrated `docker-compose.yml`, and automated GitHub Actions CI pipeline.

---

## 🏗️ System Architecture

```
                                  ┌─────────────────────────────────────────┐
                                  │      Next.js 14 Web Frontend           │
                                  │  (App Router, TypeScript, Tailwind CSS) │
                                  └────────────────────┬────────────────────┘
                                                       │ HTTP / REST (JWT Auth)
                                                       ▼
                                  ┌─────────────────────────────────────────┐
                                  │          FastAPI Backend API            │
                                  │    (Auth, Docs, Conversions, Audio,     │
                                  │     Voices, Translation, Analytics)     │
                                  └──────┬─────────────┬─────────────┬──────┘
                                         │             │             │
                              SQLAlchemy │             │ Job Queue   │ Read/Stream
                                         ▼             ▼             ▼
                                  ┌────────────┐ ┌───────────┐ ┌───────────────┐
                                  │ PostgreSQL │ │   Redis   │ │ Storage (S3/  │
                                  │  Database  │ └─────┬─────┘ │ Local FS)     │
                                  └────────────┘       │       └───────────────┘
                                                       ▼
                                         ┌───────────────────────────┐
                                         │   Celery Task Workers     │
                                         │ - PyMuPDF / python-docx   │
                                         │ - Text Chunking & Clean   │
                                         │ - Multi-Provider TTS      │
                                         │ - Audio Concatenation     │
                                         └───────────────────────────┘
```

---

## 🛠️ Technology Stack

### Frontend (`apps/web/`)
- **Framework**: Next.js 14 (App Router)
- **Language**: TypeScript
- **Styling**: Tailwind CSS, Class Variance Authority (CVA), Lucide Icons
- **State & Data Fetching**: TanStack Query (React Query) & Axios
- **Audio**: Custom HTML5 Audio API Player with Byte-Range Streaming

### Backend & Workers (`apps/api/`)
- **Framework**: FastAPI (Python 3.11)
- **Data Validation & Settings**: Pydantic v2 & Pydantic-Settings
- **ORM & Database**: SQLAlchemy 2.0, PostgreSQL, Alembic
- **Task Queue & Broker**: Celery 5.3 & Redis 7
- **Document Extractors**: PyMuPDF (`fitz`), `python-docx`, `pytesseract`
- **Audio Processing**: `pydub`, `ffmpeg`

---

## 📂 Project Structure

```
pdf_audio_system/
├── apps/
│   ├── api/                    # FastAPI backend service
│   │   ├── alembic/            # Database schema migrations
│   │   ├── app/
│   │   │   ├── api/v1/         # Modular REST endpoints (auth, docs, audio, voices, etc.)
│   │   │   ├── core/           # Configuration, security, database session
│   │   │   ├── models/         # SQLAlchemy 2.0 ORM models
│   │   │   ├── schemas/        # Pydantic v2 request/response schemas
│   │   │   ├── services/       # Extractors, TTS engine, translation, preprocessor
│   │   │   └── tasks/          # Celery app and async conversion tasks
│   │   ├── tests/              # Pytest test suite
│   │   └── requirements.txt    # Python dependencies
│   │
│   └── web/                    # Next.js 14 frontend application
│       ├── app/                # App Router pages (login, register, dashboard, convert, history, analytics, settings)
│       ├── components/         # Reusable UI primitives and audio player
│       ├── lib/                # Axios API client, query provider, TypeScript interfaces
│       └── package.json        # Frontend dependencies
│
├── .github/workflows/          # GitHub Actions CI workflow (Pytest, Next.js build, Docker verification)
├── Dockerfile.api              # Multi-stage Python FastAPI container
├── Dockerfile.worker           # Celery async worker container with FFmpeg and Tesseract
├── Dockerfile.web              # Next.js standalone runner container
├── docker-compose.yml          # Multi-container orchestration (postgres, redis, api, worker, web)
├── .env.example                # Environment configuration template
└── README.md
```

---

## ⚡ Quickstart with Docker Compose

1. **Clone the repository**:
   ```bash
   git clone https://github.com/yourusername/pdf-to-audio.git
   cd pdf-to-audio
   ```

2. **Set up environment variables**:
   ```bash
   cp .env.example .env
   ```

3. **Build and launch all services**:
   ```bash
   docker compose up --build -d
   ```

4. **Verify running containers**:
   ```bash
   docker compose ps
   ```

5. **Access the application**:
   - **Web Application**: [http://localhost:3000](http://localhost:3000)
   - **FastAPI Documentation (Swagger UI)**: [http://localhost:8000/docs](http://localhost:8000/docs)
   - **Interactive API ReDoc**: [http://localhost:8000/redoc](http://localhost:8000/redoc)

---

## 💻 Local Development Setup

### 1. Backend Service (`apps/api`)

```bash
cd apps/api

# Create and activate Python 3.11 virtual environment
python -m venv venv
# On Windows:
venv\Scripts\activate
# On macOS/Linux:
source venv/bin/activate

# Install dependencies
pip install --upgrade pip
pip install -r requirements.txt

# Download required NLTK tokenizer data
python -c "import nltk; nltk.download('punkt'); nltk.download('punkt_tab')"

# Run database migrations
alembic upgrade head

# Start FastAPI development server
uvicorn app.main:app --reload --host 0.0.0.0 --port 8000
```

### 2. Celery Worker

```bash
cd apps/api

# Start Celery worker process
celery -A app.tasks.celery_app worker --loglevel=info --concurrency=4
```

### 3. Frontend Application (`apps/web`)

```bash
cd apps/web

# Install dependencies
npm install

# Start development server
npm run dev
```

Open [http://localhost:3000](http://localhost:3000) in your browser.

---

## 📡 REST API Reference

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/v1/auth/register` | Register a new user account |
| `POST` | `/api/v1/auth/login` | Authenticate and obtain JWT access token |
| `GET` | `/api/v1/auth/me` | Retrieve authenticated user profile |
| `POST` | `/api/v1/documents/upload` | Upload a PDF or DOCX document |
| `GET` | `/api/v1/documents` | List uploaded documents with metadata |
| `GET` | `/api/v1/documents/{id}` | Get document metadata and extracted text |
| `POST` | `/api/v1/documents/{id}/extract` | Re-trigger OCR / text extraction on document |
| `DELETE` | `/api/v1/documents/{id}` | Delete document and associated files |
| `POST` | `/api/v1/conversions/documents/{id}/convert` | Dispatch async audio conversion job to Celery |
| `GET` | `/api/v1/conversions/jobs/{id}` | Poll conversion job status and progress percentage |
| `GET` | `/api/v1/audio/{id}` | Retrieve audio file metadata |
| `GET` | `/api/v1/audio/{id}/stream` | Stream audio with HTTP 206 Partial Content support |
| `GET` | `/api/v1/audio/{id}/download` | Download synthesized MP3 audio file |
| `GET` | `/api/v1/voices` | List available TTS voices filtered by provider/gender/accent |
| `GET` | `/api/v1/voices/languages` | List supported voice languages |
| `POST` | `/api/v1/translation/translate` | Translate text into target language |
| `GET` | `/api/v1/translation/languages` | List supported translation languages |
| `GET` | `/api/v1/history` | List reading progress bookmarks |
| `POST` | `/api/v1/history/progress` | Update audio playback timestamp bookmark |
| `GET` | `/api/v1/analytics/dashboard` | Retrieve system-wide or user analytics metrics |

---

## 🧪 Testing & Validation

### Backend Pytest Suite
```bash
cd apps/api
pytest -v --tb=short tests/
```

### Frontend Type-checking & Build
```bash
cd apps/web
npm run type-check
npm run build
```

### Continuous Integration (CI)
GitHub Actions automatically runs backend unit tests, frontend TypeScript checks, Next.js production builds, and Docker container builds on every push to `main` and pull requests.

---

## 🔒 Security & Privacy

- **Password Hashing**: Secure password storage utilizing `bcrypt` hashing with salt rounds.
- **Stateless Authentication**: Signed JWT (JSON Web Tokens) with configurable token expiration.
- **File Validation**: Strict MIME type validation, file header inspection, and configurable upload limits.
- **Database Safety**: Parameterized queries through SQLAlchemy ORM protecting against SQL injection.
- **CORS Protection**: Explicit cross-origin resource sharing controls for trusted frontend origins.

---

## 📄 License

This project is licensed under the **MIT License**.
