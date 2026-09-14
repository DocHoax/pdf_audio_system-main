# PDF-to-Audio System

Modern accessibility-focused document reader that converts PDF documents into natural-sounding speech.

## Overview

This is a complete modern redevelopment of a PDF-to-Audio system, replacing the legacy PHP implementation with a production-quality architecture built on Next.js and FastAPI.

## Features

- **PDF Upload & Validation**: Secure file upload with validation
- **Text Extraction**: Advanced PDF text extraction using PyMuPDF
- **Text-to-Speech**: Multiple TTS provider support (Azure, Google, AWS Polly)
- **Audio Playback**: Custom audio player with playback controls
- **Audio Download**: Download generated audio as MP3 files
- **User Authentication**: Secure JWT-based authentication
- **Document Management**: Complete document library with search and filtering
- **Accessibility First**: WCAG-compliant interface with keyboard navigation
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile

## Technology Stack

### Frontend
- **Next.js 14** (App Router)
- **TypeScript**
- **React**
- **Tailwind CSS**
- **shadcn/ui**
- **TanStack Query**
- **React Hook Form**
- **Zod**

### Backend
- **Python 3.11+**
- **FastAPI**
- **SQLAlchemy**
- **PostgreSQL**
- **Redis**
- **PyMuPDF** (PDF processing)
- **Azure Speech/Google TTS/AWS Polly** (Text-to-Speech)

## Project Structure

```
pdf-to-audio/
├── apps/
│   ├── web/                 # Next.js frontend
│   │   ├── app/            # App router pages
│   │   ├── components/     # React components
│   │   └── lib/            # Utilities and API client
│   │
│   └── api/                # FastAPI backend
│       ├── app/
│       │   ├── api/        # API endpoints
│       │   ├── core/       # Core configuration
│       │   ├── models/     # Database models
│       │   ├── schemas/    # Pydantic schemas
│       │   └── services/   # Business logic
│       └── alembic/        # Database migrations
│
├── infrastructure/
│   └── docker/             # Docker configurations
│
├── docs/                   # Documentation
├── docker-compose.yml      # Local development setup
└── .env.example           # Environment variables template
```

## Prerequisites

- **Docker & Docker Compose** (recommended for local development)
- **Python 3.11+** (if running without Docker)
- **Node.js 20+** (if running without Docker)
- **PostgreSQL 14+**
- **Redis 7+**

## Quick Start with Docker

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/pdf-to-audio.git
   cd pdf-to-audio
   ```

2. **Configure environment variables**
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` and add your TTS provider credentials:
   - For Azure: `AZURE_SPEECH_KEY` and `AZURE_SPEECH_REGION`
   - For Google: `GOOGLE_CLOUD_TTS_KEY`
   - For AWS: `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY`

3. **Start all services**
   ```bash
   docker-compose up -d
   ```

4. **Run database migrations**
   ```bash
   docker-compose exec api alembic upgrade head
   ```

5. **Access the application**
   - Frontend: http://localhost:3000
   - API: http://localhost:8000
   - API Docs: http://localhost:8000/docs

## Manual Setup

### Backend Setup

1. **Navigate to API directory**
   ```bash
   cd apps/api
   ```

2. **Create virtual environment**
   ```bash
   python -m venv venv
   source venv/bin/activate  # On Windows: venv\Scripts\activate
   ```

3. **Install dependencies**
   ```bash
   pip install -r requirements.txt
   ```

4. **Configure database**
   - Ensure PostgreSQL is running
   - Update `DATABASE_URL` in `.env`

5. **Run migrations**
   ```bash
   alembic upgrade head
   ```

6. **Start API server**
   ```bash
   uvicorn app.main:app --reload --host 0.0.0.0 --port 8000
   ```

### Frontend Setup

1. **Navigate to web directory**
   ```bash
   cd apps/web
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.local .env.local
   ```
   Update `NEXT_PUBLIC_API_URL` if needed

4. **Start development server**
   ```bash
   npm run dev
   ```

5. **Access application**
   Open http://localhost:3000

## API Documentation

Once the backend is running, visit:
- **Swagger UI**: http://localhost:8000/docs
- **ReDoc**: http://localhost:8000/redoc

### Main Endpoints

#### Authentication
- `POST /api/v1/auth/register` - Register new user
- `POST /api/v1/auth/login` - Login user
- `GET /api/v1/auth/me` - Get current user

#### Documents
- `POST /api/v1/documents/upload` - Upload PDF
- `GET /api/v1/documents` - List documents
- `GET /api/v1/documents/{id}` - Get document details
- `POST /api/v1/documents/{id}/extract` - Extract text
- `DELETE /api/v1/documents/{id}` - Delete document

#### Conversions
- `POST /api/v1/conversions/documents/{id}/convert` - Start conversion
- `GET /api/v1/conversions/jobs/{id}` - Get job status

#### Audio
- `GET /api/v1/audio/{id}/stream` - Stream audio
- `GET /api/v1/audio/{id}/download` - Download audio

#### Voices
- `GET /api/v1/voices/languages` - Get available languages
- `GET /api/v1/voices` - Get available voices

## Configuration

### TTS Providers

The system supports multiple TTS providers. Configure via environment variables:

**Azure Speech** (Recommended)
```env
TTS_PROVIDER=azure
AZURE_SPEECH_KEY=your_key_here
AZURE_SPEECH_REGION=eastus
```

**Google Cloud TTS**
```env
TTS_PROVIDER=google
GOOGLE_CLOUD_TTS_KEY=your_key_here
```

**AWS Polly**
```env
TTS_PROVIDER=aws_polly
AWS_ACCESS_KEY_ID=your_key_here
AWS_SECRET_ACCESS_KEY=your_secret_here
AWS_REGION=us-east-1
```

### Storage Providers

**Local Storage** (Development)
```env
STORAGE_PROVIDER=local
STORAGE_PATH=./storage
```

**S3/R2** (Production)
```env
STORAGE_PROVIDER=s3
STORAGE_BUCKET=your-bucket
STORAGE_ACCESS_KEY=your_key
STORAGE_SECRET_KEY=your_secret
STORAGE_ENDPOINT=https://your-endpoint.com
```

## Development

### Running Tests

**Backend**
```bash
cd apps/api
pytest
```

**Frontend**
```bash
cd apps/web
npm test
```

### Database Migrations

**Create new migration**
```bash
cd apps/api
alembic revision --autogenerate -m "Description"
```

**Apply migrations**
```bash
alembic upgrade head
```

**Rollback migration**
```bash
alembic downgrade -1
```

## Deployment

### Production Checklist

- [ ] Set strong `JWT_SECRET`
- [ ] Configure production database
- [ ] Set up cloud storage (S3/R2)
- [ ] Configure TTS provider
- [ ] Enable HTTPS
- [ ] Set up monitoring
- [ ] Configure rate limiting
- [ ] Set proper CORS origins
- [ ] Use production-grade WSGI server
- [ ] Set up database backups
- [ ] Configure logging

### Recommended Hosting

- **Frontend**: Vercel, Netlify
- **Backend**: Railway, Render, Fly.io, AWS
- **Database**: Railway, Supabase, AWS RDS
- **Storage**: Cloudflare R2, AWS S3

## Security

- Passwords are hashed using bcrypt
- JWT tokens for authentication
- File validation and size limits
- SQL injection protection via SQLAlchemy
- CORS configuration
- Rate limiting support
- Secure file storage

## Accessibility

This application prioritizes accessibility:

- WCAG 2.1 Level AA compliance
- Keyboard navigation support
- Screen reader compatible
- High contrast support
- Focus indicators
- Semantic HTML
- ARIA labels where appropriate

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

MIT License - see LICENSE file for details

## Support

For issues and questions:
- Create an issue on GitHub
- Check existing documentation
- Review API documentation at `/docs`

## Acknowledgments

Built with accessibility and inclusion in mind. Special thanks to all contributors and the open-source community.

---

**Built with ❤️ for Accessibility**
