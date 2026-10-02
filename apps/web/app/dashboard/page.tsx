"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  FileAudio,
  UploadCloud,
  FileText,
  Clock,
  Sparkles,
  Play,
  Trash2,
  ExternalLink,
  BookOpen,
  Headphones,
  CheckCircle2,
  AlertCircle,
  Loader2,
} from "lucide-react";
import { Navbar } from "@/components/navbar";
import { FileUpload } from "@/components/file-upload";
import { AudioPlayer } from "@/components/audio-player";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";
import {
  documentsApi,
  historyApi,
  usersApi,
  Document,
  ReadingHistory,
  UserStats,
} from "@/lib/api";
import { toast } from "sonner";

export default function DashboardPage() {
  const router = useRouter();
  const [documents, setDocuments] = useState<Document[]>([]);
  const [readingHistory, setReadingHistory] = useState<ReadingHistory[]>([]);
  const [stats, setStats] = useState<UserStats | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [selectedAudioDoc, setSelectedAudioDoc] = useState<{
    audioId: number;
    docId: number;
    title: string;
    pos?: number;
  } | null>(null);

  const fetchDashboardData = async () => {
    setIsLoading(true);
    try {
      const [docsRes, histRes, statsRes] = await Promise.allSettled([
        documentsApi.list(),
        historyApi.getHistory(5),
        usersApi.getStats(),
      ]);

      if (docsRes.status === "fulfilled") {
        setDocuments(docsRes.value.data);
      }
      if (histRes.status === "fulfilled") {
        setReadingHistory(histRes.value.data);
      }
      if (statsRes.status === "fulfilled") {
        setStats(statsRes.value.data);
      }
    } catch {
      toast.error("Failed to load dashboard data");
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const handleDeleteDocument = async (id: number, e: React.MouseEvent) => {
    e.stopPropagation();
    if (!confirm("Are you sure you want to delete this document?")) return;
    try {
      await documentsApi.delete(id);
      toast.success("Document deleted");
      setDocuments((prev) => prev.filter((d) => d.id !== id));
    } catch {
      toast.error("Failed to delete document");
    }
  };

  const formatListeningTime = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const hrs = (mins / 60).toFixed(1);
    return mins > 60 ? `${hrs} hrs` : `${mins} mins`;
  };

  return (
    <div className="min-h-screen bg-zinc-50/50 dark:bg-zinc-950 text-foreground">
      <Navbar />

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        {/* Welcome Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <h1 className="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
              Document Audio Studio
            </h1>
            <p className="text-sm text-muted-foreground mt-1">
              Upload documents, extract text, and convert into authentic African & global speech.
            </p>
          </div>
          <Link href="/convert">
            <Button className="gap-2 shadow-sm">
              <Sparkles className="h-4 w-4" />
              Open Conversion Studio
            </Button>
          </Link>
        </div>

        {/* Selected Audio Floating Player */}
        {selectedAudioDoc && (
          <div className="sticky top-20 z-40">
            <AudioPlayer
              audioId={selectedAudioDoc.audioId}
              documentId={selectedAudioDoc.docId}
              documentTitle={selectedAudioDoc.title}
              initialPositionSeconds={selectedAudioDoc.pos || 0}
            />
          </div>
        )}

        {/* Metrics Grid */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <Card className="bg-card">
            <CardContent className="p-5 flex items-center justify-between">
              <div>
                <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Total Documents
                </p>
                <h3 className="text-2xl font-bold text-foreground mt-1">
                  {stats?.total_documents ?? documents.length}
                </h3>
              </div>
              <div className="h-11 w-11 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                <FileText className="h-5 w-5" />
              </div>
            </CardContent>
          </Card>

          <Card className="bg-card">
            <CardContent className="p-5 flex items-center justify-between">
              <div>
                <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Audio Generated
                </p>
                <h3 className="text-2xl font-bold text-foreground mt-1">
                  {stats?.completed_conversions ?? 0}
                </h3>
              </div>
              <div className="h-11 w-11 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <Headphones className="h-5 w-5" />
              </div>
            </CardContent>
          </Card>

          <Card className="bg-card">
            <CardContent className="p-5 flex items-center justify-between">
              <div>
                <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Listening Time
                </p>
                <h3 className="text-2xl font-bold text-foreground mt-1">
                  {formatListeningTime(stats?.total_audio_duration_seconds ?? 0)}
                </h3>
              </div>
              <div className="h-11 w-11 rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center">
                <Clock className="h-5 w-5" />
              </div>
            </CardContent>
          </Card>

          <Card className="bg-card">
            <CardContent className="p-5 flex items-center justify-between">
              <div>
                <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                  Active Bookmarks
                </p>
                <h3 className="text-2xl font-bold text-foreground mt-1">
                  {readingHistory.length}
                </h3>
              </div>
              <div className="h-11 w-11 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                <BookOpen className="h-5 w-5" />
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Quick Upload Dropzone */}
        <Card className="border-primary/20 bg-primary/[0.02]">
          <CardHeader className="pb-3">
            <CardTitle className="text-lg flex items-center gap-2">
              <UploadCloud className="h-5 w-5 text-primary" />
              Quick Upload & Speech Synthesis
            </CardTitle>
            <CardDescription>
              Upload PDF or Word documents to immediately inspect text and generate speech.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <FileUpload
              onUploadSuccess={(newDoc) => {
                setDocuments((prev) => [newDoc, ...prev]);
                router.push(`/convert?docId=${newDoc.id}`);
              }}
            />
          </CardContent>
        </Card>

        {/* Main Content Sections: Document Library & Reading Bookmarks */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Document Library (2 columns) */}
          <div className="lg:col-span-2 space-y-4">
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-bold text-foreground flex items-center gap-2">
                <FileAudio className="h-5 w-5 text-primary" />
                Your Documents ({documents.length})
              </h2>
            </div>

            {isLoading ? (
              <div className="p-12 text-center text-muted-foreground flex flex-col items-center">
                <Loader2 className="h-8 w-8 animate-spin text-primary mb-2" />
                <p className="text-sm">Loading document library...</p>
              </div>
            ) : documents.length === 0 ? (
              <div className="border rounded-xl p-12 text-center bg-card">
                <FileText className="h-12 w-12 text-muted-foreground/40 mx-auto mb-3" />
                <h3 className="font-semibold text-base">No documents yet</h3>
                <p className="text-xs text-muted-foreground mt-1 mb-4">
                  Upload a PDF or Word document above to get started
                </p>
              </div>
            ) : (
              <div className="space-y-3">
                {documents.map((doc) => (
                  <div
                    key={doc.id}
                    className="border rounded-xl p-4 bg-card hover:bg-zinc-50 dark:hover:bg-zinc-900/40 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm"
                  >
                    <div className="flex items-start gap-3">
                      <div className="h-10 w-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <FileText className="h-5 w-5" />
                      </div>
                      <div>
                        <h4 className="font-semibold text-sm text-foreground line-clamp-1">
                          {doc.original_filename}
                        </h4>
                        <div className="flex items-center gap-2 mt-1">
                          <span className="text-xs text-muted-foreground">
                            {(doc.file_size / (1024 * 1024)).toFixed(2)} MB
                          </span>
                          <span className="text-zinc-300 dark:text-zinc-700">•</span>
                          <span className="text-xs text-muted-foreground">
                            {doc.page_count ? `${doc.page_count} pages` : "Document"}
                          </span>
                          <span className="text-zinc-300 dark:text-zinc-700">•</span>
                          <Badge
                            variant={
                              doc.status === "extracted"
                                ? "success"
                                : doc.status === "failed"
                                ? "destructive"
                                : "secondary"
                            }
                            className="text-[10px] uppercase"
                          >
                            {doc.status}
                          </Badge>
                        </div>
                      </div>
                    </div>

                    <div className="flex items-center gap-2 self-end sm:self-center">
                      <Link href={`/convert?docId=${doc.id}`}>
                        <Button size="sm" variant="default" className="text-xs gap-1.5">
                          <Sparkles className="h-3.5 w-3.5" />
                          Convert / Read
                        </Button>
                      </Link>

                      <Button
                        size="icon"
                        variant="ghost"
                        onClick={(e) => handleDeleteDocument(doc.id, e)}
                        className="h-8 w-8 text-muted-foreground hover:text-destructive"
                        title="Delete document"
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Reading Bookmarks Sidebar (1 column) */}
          <div className="space-y-4">
            <h2 className="text-lg font-bold text-foreground flex items-center gap-2">
              <BookOpen className="h-5 w-5 text-primary" />
              Recent Reading History
            </h2>

            {readingHistory.length === 0 ? (
              <div className="border rounded-xl p-6 text-center bg-card text-muted-foreground">
                <BookOpen className="h-8 w-8 text-muted-foreground/40 mx-auto mb-2" />
                <p className="text-xs">No bookmarks recorded yet</p>
                <p className="text-[11px] text-muted-foreground mt-1">
                  Start listening to documents and your progress will appear here
                </p>
              </div>
            ) : (
              <div className="space-y-3">
                {readingHistory.map((item) => (
                  <div
                    key={item.id}
                    className="border rounded-xl p-4 bg-card hover:bg-zinc-50 dark:hover:bg-zinc-900/40 transition-all shadow-sm space-y-3"
                  >
                    <div className="flex items-start justify-between gap-2">
                      <div>
                        <h4 className="font-semibold text-xs text-foreground line-clamp-1">
                          {item.document_title}
                        </h4>
                        <p className="text-[11px] text-muted-foreground mt-0.5">
                          Position: {Math.floor(item.last_position_seconds / 60)}m{" "}
                          {item.last_position_seconds % 60}s
                        </p>
                      </div>
                      <Badge variant="info" className="text-[10px]">
                        {item.progress_percentage}%
                      </Badge>
                    </div>

                    <div className="w-full bg-zinc-200 dark:bg-zinc-800 h-1.5 rounded-full overflow-hidden">
                      <div
                        className="bg-primary h-full transition-all"
                        style={{ width: `${item.progress_percentage}%` }}
                      />
                    </div>

                    <Link href={`/convert?docId=${item.document_id}`}>
                      <Button size="sm" variant="outline" className="w-full text-xs gap-1.5 mt-1">
                        <Play className="h-3 w-3" />
                        Resume Reading
                      </Button>
                    </Link>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </main>
    </div>
  );
}
