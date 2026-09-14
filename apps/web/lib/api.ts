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
  const token = localStorage.getItem("token");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle auth errors
api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      localStorage.removeItem("token");
      window.location.href = "/login";
    }
    return Promise.reject(error);
  }
);

// Auth
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
  is_active: boolean;
  is_admin: boolean;
  created_at: string;
}

export const authApi = {
  register: (data: RegisterData) => api.post("/auth/register", data),
  login: (data: LoginData) => api.post<{ access_token: string }>("/auth/login", data),
  me: () => api.get<User>("/auth/me"),
  logout: () => api.post("/auth/logout"),
};

// Documents
export interface Document {
  id: number;
  user_id: number;
  original_filename: string;
  file_size: number;
  page_count?: number;
  status: "uploaded" | "extracting" | "extracted" | "failed";
  error_message?: string;
  created_at: string;
  updated_at?: string;
  has_audio: boolean;
}

export interface DocumentDetail extends Document {
  extracted_text?: string;
  mime_type: string;
}

export interface DocumentText {
  id: number;
  extracted_text?: string;
  page_count?: number;
  character_count: number;
}

export const documentsApi = {
  upload: (file: File) => {
    const formData = new FormData();
    formData.append("file", file);
    return api.post<Document>("/documents/upload", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
  },
  list: () => api.get<Document[]>("/documents"),
  get: (id: number) => api.get<DocumentDetail>(`/documents/${id}`),
  extract: (id: number) => api.post<DocumentText>(`/documents/${id}/extract`),
  getText: (id: number) => api.get<DocumentText>(`/documents/${id}/text`),
  delete: (id: number) => api.delete(`/documents/${id}`),
};

// Conversions
export interface ConversionCreate {
  language: string;
  voice: string;
  speed: number;
  pitch?: number;
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
  has_audio: boolean;
}

export const conversionsApi = {
  create: (documentId: number, data: ConversionCreate) =>
    api.post<Conversion>(`/conversions/documents/${documentId}/convert`, data),
  get: (jobId: number) => api.get<Conversion>(`/conversions/jobs/${jobId}`),
  list: (documentId: number) => api.get<Conversion[]>(`/conversions/documents/${documentId}/conversions`),
};

// Audio
export interface AudioFile {
  id: number;
  conversion_id: number;
  format: string;
  duration?: number;
  file_size?: number;
  created_at: string;
  download_url: string;
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
  locale: string;
}

export interface Language {
  code: string;
  name: string;
  voices: Voice[];
}

export const voicesApi = {
  getLanguages: () => api.get<Language[]>("/voices/languages"),
  getVoices: (language?: string) => api.get<Voice[]>("/voices", { params: { language } }),
};
