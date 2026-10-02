"""
Celery asynchronous tasks for document and audio processing
"""
import asyncio
from app.tasks.celery_app import celery_app
from app.core.database import SessionLocal
from app.services.conversion_service import ConversionService


@celery_app.task(bind=True, name="app.tasks.conversion_tasks.process_conversion_task")
def process_conversion_task(self, conversion_id: int):
    """
    Celery task wrapper to execute audio conversion job asynchronously
    """
    db = SessionLocal()
    try:
        service = ConversionService(db)
        # Run the async process_conversion coroutine inside sync celery worker
        loop = asyncio.new_event_loop()
        asyncio.set_event_loop(loop)
        try:
            loop.run_until_complete(service.process_conversion(conversion_id))
        finally:
            loop.close()
        return {"status": "success", "conversion_id": conversion_id}
    except Exception as exc:
        return {"status": "failed", "conversion_id": conversion_id, "error": str(exc)}
    finally:
        db.close()
