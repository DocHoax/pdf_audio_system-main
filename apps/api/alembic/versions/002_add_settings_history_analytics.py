"""Add settings, reading history, and analytics tables

Revision ID: 002
Revises: 001
Create Date: 2026-10-02

"""
from alembic import op
import sqlalchemy as sa
from sqlalchemy.dialects import postgresql


# revision identifiers
revision = '002'
down_revision = '001'
branch_labels = None
depends_on = None


def upgrade() -> None:
    # Add columns to users table
    op.add_column('users', sa.Column('avatar', sa.String(length=255), nullable=True))
    op.add_column('users', sa.Column('last_login', sa.DateTime(timezone=True), nullable=True))

    # Add columns to documents table
    op.add_column('documents', sa.Column('file_type', sa.String(length=20), server_default='pdf', nullable=False))
    op.add_column('documents', sa.Column('char_count', sa.Integer(), server_default='0', nullable=True))

    # Add translated_text to conversion_jobs
    op.add_column('conversion_jobs', sa.Column('translated_text', sa.Text(), nullable=True))

    # Create user_settings table
    op.create_table(
        'user_settings',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('user_id', sa.Integer(), nullable=False),
        sa.Column('preferred_voice', sa.String(length=50), nullable=True),
        sa.Column('preferred_language', sa.String(length=20), nullable=True),
        sa.Column('default_speed', sa.Float(), nullable=True),
        sa.Column('default_volume', sa.Float(), nullable=True),
        sa.Column('theme', sa.String(length=20), nullable=True),
        sa.Column('created_at', sa.DateTime(timezone=True), server_default=sa.text('now()'), nullable=True),
        sa.Column('updated_at', sa.DateTime(timezone=True), nullable=True),
        sa.ForeignKeyConstraint(['user_id'], ['users.id'], ),
        sa.PrimaryKeyConstraint('id'),
        sa.UniqueConstraint('user_id')
    )
    op.create_index(op.f('ix_user_settings_id'), 'user_settings', ['id'], unique=False)

    # Create reading_history table
    op.create_table(
        'reading_history',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('user_id', sa.Integer(), nullable=False),
        sa.Column('document_id', sa.Integer(), nullable=False),
        sa.Column('last_position', sa.Integer(), nullable=True),
        sa.Column('completed', sa.Boolean(), nullable=True),
        sa.Column('read_at', sa.DateTime(timezone=True), server_default=sa.text('now()'), nullable=True),
        sa.ForeignKeyConstraint(['document_id'], ['documents.id'], ),
        sa.ForeignKeyConstraint(['user_id'], ['users.id'], ),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index(op.f('ix_reading_history_id'), 'reading_history', ['id'], unique=False)

    # Create user_analytics table
    op.create_table(
        'user_analytics',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('user_id', sa.Integer(), nullable=True),
        sa.Column('session_id', sa.String(length=128), nullable=True),
        sa.Column('event_type', sa.String(length=50), nullable=False),
        sa.Column('event_data', sa.JSON(), nullable=True),
        sa.Column('page_url', sa.String(length=500), nullable=True),
        sa.Column('ip_address', sa.String(length=45), nullable=True),
        sa.Column('user_agent', sa.String(length=500), nullable=True),
        sa.Column('created_at', sa.DateTime(timezone=True), server_default=sa.text('now()'), nullable=True),
        sa.ForeignKeyConstraint(['user_id'], ['users.id'], ),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index(op.f('ix_user_analytics_id'), 'user_analytics', ['id'], unique=False)
    op.create_index(op.f('ix_user_analytics_event_type'), 'user_analytics', ['event_type'], unique=False)


def downgrade() -> None:
    op.drop_index(op.f('ix_user_analytics_event_type'), table_name='user_analytics')
    op.drop_index(op.f('ix_user_analytics_id'), table_name='user_analytics')
    op.drop_table('user_analytics')
    op.drop_index(op.f('ix_reading_history_id'), table_name='reading_history')
    op.drop_table('reading_history')
    op.drop_index(op.f('ix_user_settings_id'), table_name='user_settings')
    op.drop_table('user_settings')
    op.drop_column('conversion_jobs', 'translated_text')
    op.drop_column('documents', 'char_count')
    op.drop_column('documents', 'file_type')
    op.drop_column('users', 'last_login')
    op.drop_column('users', 'avatar')
