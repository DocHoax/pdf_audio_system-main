import axios, { AxiosError } from "axios";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api/v1";

export const api = axios.create({
  baseURL: API_URL,
  headers: {
    "Content-Type": "application/json",
  },
});

// Add token to requests
api.interceptors.request.use((config) => {
  if (typeof window !== "undefined") {
    const token = localStorage.getItem("token");
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  }
  return config;
});

// Handle auth errors
api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (typeof window !== "undefined" && error.response?.status === 401) {
      // Avoid infinite redirects if already on login/register page
      if (
        !window.location.pathname.includes("/login") &&
        !window.location.pathname.includes("/register") &&
        window.location.pathname !== "/"
      ) {
        localStorage.removeItem("token");
        window.location.href = "/login";
      }
    }
    return Promise.reject(error);
  }
);

// Auth & Users
export interface RegisterData {
  email: string;
  username: string;
  password: string;
  full_name?: string;
}

export interface LoginData {
  email: string;
  password: string;
}

export interface User {
  id: number;
  email: string;
  username: string;
  full_name?: string;
  avatar?: string;
  is_active: boolean;
  is_admin: boolean;
  created_at?: string;
}

export interface UserSettings {
  id?: number;
  user_id?: number;
  preferred_voice: string;
  preferred_language: string;
  default_speed: number;
  default_pitch: number;
  theme: string;
  email_notifications: boolean;
}

export interface UserSettingsUpdate {
  preferred_voice?: string;
  preferred_language?: string;
  default_speed?: number;
  default_pitch?: number;
  theme?: string;
  email_notifications?: boolean;
}

export interface UserProfileUpdate {
  full_name?: string;
  avatar?: string;
  password?: string;
}

export interface UserStats {
  total_documents: number;
  total_conversions: number;
  completed_conversions: number;
  total_audio_duration_seconds: number;
  reading_history_count: number;
}

export const authApi = {
  register: (data: RegisterData) => api.post<User>("/auth/register", data),
  login: (data: LoginData) => api.post<{ access_token: string; token_type: string }>("/auth/login", data),
  me: () => api.get<User>("/auth/me"),
  logout: () => api.post<{ message: string }>("/auth/logout"),
};

export const usersApi = {
  getSettings: () => api.get<UserSettings>("/users/me/settings"),
  updateSettings: (data: UserSettingsUpdate) => api.patch<UserSettings>("/users/me/settings", data),
  getProfile: () => api.get<User>("/users/me/profile"),
  updateProfile: (data: UserProfileUpdate) => api.put<User>("/users/me/profile", data),
  getStats: () => api.get<UserStats>("/users/me/stats"),
};

// Documents
export interface Document {
  id: number;
  user_id: number;
  original_filename: string;
  stored_filename?: string;
  file_path?: string;
  file_size: number;
  file_type?: string;
  mime_type?: string;
  page_count?: number;
  extracted_text?: string;
  status: "uploaded" | "extracting" | "extracted" | "failed";
  error_message?: string;
  created_at: string;
  updated_at?: string;
  has_audio?: boolean;
}

export interface DocumentDetail extends Document {
  extracted_text?: string;
  character_count?: number;
}

export interface DocumentText {
  id: number;
  extracted_text?: string;
  page_count?: number;
  character_count?: number;
  word_count?: number;
  has_text?: boolean;
}

export const documentsApi = {
  upload: (file: File) => {
    const formData = new FormData();
    formData.append("file", file);
    return api.post<Document>("/documents/upload", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
  },
  list: (skip = 0, limit = 50) =>
    api.get<Document[]>("/documents", { params: { skip, limit } }),
  get: (id: number) => api.get<DocumentDetail>(`/documents/${id}`),
  extract: (id: number) => api.post<DocumentText>(`/documents/${id}/extract`),
  getText: (id: number) => api.get<DocumentText>(`/documents/${id}/text`),
  delete: (id: number) => api.delete<{ message: string }>(`/documents/${id}`),
};

// Conversions
export interface ConversionCreate {
  language: string;
  voice: string;
  speed: number;
  pitch?: number;
  target_language?: string;
}

export interface Conversion {
  id: number;
  document_id: number;
  status: "queued" | "processing" | "completed" | "failed";
  progress: number;
  language: string;
  voice: string;
  speed: number;
  pitch?: number;
  error_message?: string;
  started_at?: string;
  completed_at?: string;
  created_at: string;
  has_audio?: boolean;
  audio_file_id?: number;
}

export const conversionsApi = {
  create: (documentId: number, data: ConversionCreate) =>
    api.post<Conversion>(`/conversions/documents/${documentId}/convert`, data),
  get: (jobId: number) => api.get<Conversion>(`/conversions/jobs/${jobId}`),
  list: (documentId: number) =>
    api.get<Conversion[]>(`/conversions/documents/${documentId}/conversions`),
  cancel: (jobId: number) =>
    api.post<{ message: string }>(`/conversions/jobs/${jobId}/cancel`),
};

// Audio
export interface AudioFile {
  id: number;
  conversion_id: number;
  document_id?: number;
  format: string;
  duration?: number;
  file_size?: number;
  created_at: string;
  download_url?: string;
}

export const audioApi = {
  get: (id: number) => api.get<AudioFile>(`/audio/${id}`),
  getDownloadUrl: (id: number) => `${API_URL}/audio/${id}/download`,
  getStreamUrl: (id: number) => `${API_URL}/audio/${id}/stream`,
};

// Voices
export interface Voice {
  id: string;
  name: string;
  language: string;
  gender?: string;
  locale?: string;
  accent?: string;
  description?: string;
  provider?: string;
  preview_url?: string;
}

export interface Language {
  code: string;
  name: string;
  native_name?: string;
  voices: Voice[];
}

export const voicesApi = {
  getLanguages: () => api.get<Language[]>("/voices/languages"),
  getVoices: (language?: string, provider?: string) =>
    api.get<Voice[]>("/voices", { params: { language, provider } }),
  getPopular: () => api.get<Voice[]>("/voices/popular"),
};

// Translation
export interface TranslationLanguage {
  code: string;
  name: string;
  native_name?: string;
  is_african?: boolean;
}

export interface TranslationRequest {
  text: string;
  target_language: string;
  source_language?: string;
}

export interface TranslationResponse {
  original_text: string;
  translated_text: string;
  source_language: string;
  target_language: string;
  character_count: number;
}

export interface DocumentTranslationRequest {
  target_language: string;
  source_language?: string;
}

export const translationApi = {
  getLanguages: () => api.get<TranslationLanguage[]>("/translation/languages"),
  translate: (data: TranslationRequest) =>
    api.post<TranslationResponse>("/translation/translate", data),
  translateDocument: (documentId: number, data: DocumentTranslationRequest) =>
    api.post<TranslationResponse>(`/translation/documents/${documentId}/translate`, data),
};

// Reading History & Bookmarks
export interface ReadingHistory {
  id: number;
  user_id: number;
  document_id: number;
  document_title: string;
  last_position_seconds: number;
  total_duration_seconds: number;
  progress_percentage: number;
  last_paragraph_index?: number;
  is_completed: boolean;
  updated_at: string;
  created_at: string;
}

export interface ReadingProgressUpdate {
  document_id: number;
  last_position_seconds: number;
  total_duration_seconds: number;
  last_paragraph_index?: number;
  is_completed?: boolean;
}

export const historyApi = {
  getHistory: (limit = 20) =>
    api.get<ReadingHistory[]>("/history", { params: { limit } }),
  saveProgress: (data: ReadingProgressUpdate) =>
    api.post<ReadingHistory>("/history/progress", data),
  getDocumentProgress: (documentId: number) =>
    api.get<ReadingHistory>(`/history/document/${documentId}`),
  deleteHistory: (historyId: number) =>
    api.delete<{ message: string }>(`/history/${historyId}`),
};

// Analytics
export interface AnalyticsSummary {
  total_documents: number;
  total_conversions: number;
  total_listening_minutes: number;
  active_conversions: number;
  most_used_voice: string;
  voices_breakdown?: Record<string, number>;
  languages_breakdown?: Record<string, number>;
}

export interface TrackEventRequest {
  event_type: string;
  event_data?: Record<string, any>;
  duration_seconds?: number;
}

export interface AnalyticsEvent {
  id: number;
  user_id: number;
  event_type: string;
  event_data?: Record<string, any>;
  duration_seconds?: number;
  created_at: string;
}

export const analyticsApi = {
  getSummary: () => api.get<AnalyticsSummary>("/analytics/summary"),
  trackEvent: (data: TrackEventRequest) =>
    api.post<{ message: string }>("/analytics/event", data),
  getHistory: (limit = 50) =>
    api.get<AnalyticsEvent[]>("/analytics/history", { params: { limit } }),
};
