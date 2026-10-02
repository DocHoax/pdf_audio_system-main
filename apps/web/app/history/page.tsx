"use client";

import React, { useState, useEffect } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  BookOpen,
  Play,
  Trash2,
  Clock,
  CheckCircle2,
  Search,
  ArrowLeft,
  FileText,
  Loader2,
  Sparkles,
} from "lucide-react";
import { Navbar } from "@/components/navbar";
import { AudioPlayer } from "@/components/audio-player";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { historyApi, conversionsApi, ReadingHistory } from "@/lib/api";
import { toast } from "sonner";

export default function HistoryPage() {
  const router = useRouter();
  const [history, setHistory] = useState<ReadingHistory[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedItem, setSelectedItem] = useState<{
    audioId: number;
    docId: number;
    title: string;
    pos: number;
  } | null>(null);

  const fetchHistory = async () => {
    setIsLoading(true);
    try {
      const res = await historyApi.getHistory(100);
      setHistory(res.data);
    } catch {
      toast.error("Failed to load reading history");
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchHistory();
  }, []);

  const handleDeleteHistory = async (id: number, e: React.MouseEvent) => {
    e.stopPropagation();
    try {
      await historyApi.deleteHistory(id);
      setHistory((prev) => prev.filter((h) => h.id !== id));
      toast.success("Bookmark removed");
    } catch {
      toast.error("Failed to remove bookmark");
    }
  };

  const handlePlayBookmark = async (item: ReadingHistory) => {
    try {
      const convs = await conversionsApi.list(item.document_id);
      const completed = convs.data.find((c) => c.status === "completed" && c.audio_file_id);
      if (completed && completed.audio_file_id) {
        setSelectedItem({
          audioId: completed.audio_file_id,
          docId: item.document_id,
          title: item.document_title,
          pos: item.last_position_seconds,
        });
      } else {
        router.push(`/convert?docId=${item.document_id}`);
      }
    } catch {
      router.push(`/convert?docId=${item.document_id}`);
    }
  };

  const formatSeconds = (secs: number) => {
    const mins = Math.floor(secs / 60);
    const remainder = secs % 60;
    return `${mins}m ${remainder < 10 ? "0" : ""}${remainder}s`;
  };

  const filteredHistory = history.filter((item) =>
    item.document_title.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <div className="min-h-screen bg-zinc-50/50 dark:bg-zinc-950 text-foreground">
      <Navbar />

      <main className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <Button
              variant="outline"
              size="icon"
              onClick={() => router.push("/dashboard")}
              className="h-9 w-9"
            >
              <ArrowLeft className="h-4 w-4" />
            </Button>
            <div>
              <h1 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <BookOpen className="h-6 w-6 text-primary" />
                Reading History & Bookmarks
              </h1>
              <p className="text-xs text-muted-foreground mt-0.5">
                Resume playback from where you left off across all your documents
              </p>
            </div>
          </div>

          {/* Search Box */}
          <div className="relative w-full sm:w-64">
            <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
            <input
              type="text"
              placeholder="Search history..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-9 pr-4 py-1.5 text-xs bg-card border rounded-lg focus:outline-none focus:ring-1 focus:ring-primary"
            />
          </div>
        </div>

        {/* Selected Audio Floating Player */}
        {selectedItem && (
          <div className="sticky top-20 z-40">
            <AudioPlayer
              audioId={selectedItem.audioId}
              documentId={selectedItem.docId}
              documentTitle={selectedItem.title}
              initialPositionSeconds={selectedItem.pos}
            />
          </div>
        )}

        {/* History List */}
        {isLoading ? (
          <div className="p-16 text-center text-muted-foreground flex flex-col items-center">
            <Loader2 className="h-8 w-8 animate-spin text-primary mb-2" />
            <p className="text-sm">Loading reading bookmarks...</p>
          </div>
        ) : filteredHistory.length === 0 ? (
          <Card className="bg-card text-center p-12">
            <BookOpen className="h-12 w-12 text-muted-foreground/40 mx-auto mb-3" />
            <h3 className="font-semibold text-base">No reading history found</h3>
            <p className="text-xs text-muted-foreground mt-1 mb-4">
              {searchQuery
                ? "No bookmarks match your search query."
                : "Listen to documents to automatically track your reading position."}
            </p>
            <Link href="/dashboard">
              <Button size="sm" variant="default">
                Go to Dashboard
              </Button>
            </Link>
          </Card>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {filteredHistory.map((item) => (
              <Card
                key={item.id}
                className="bg-card hover:shadow-md transition-shadow cursor-pointer"
                onClick={() => handlePlayBookmark(item)}
              >
                <CardContent className="p-5 space-y-4">
                  <div className="flex items-start justify-between gap-3">
                    <div className="flex items-center gap-3">
                      <div className="h-10 w-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <FileText className="h-5 w-5" />
                      </div>
                      <div>
                        <h4 className="font-semibold text-sm text-foreground line-clamp-1">
                          {item.document_title}
                        </h4>
                        <span className="text-[11px] text-muted-foreground">
                          Last read: {new Date(item.last_read_at || item.updated_at || item.created_at).toLocaleDateString()}
                        </span>
                      </div>
                    </div>

                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={(e) => handleDeleteHistory(item.id, e)}
                      className="h-7 w-7 text-muted-foreground hover:text-destructive shrink-0"
                    >
                      <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                  </div>

                  {/* Progress Bar */}
                  <div className="space-y-1.5">
                    <div className="flex justify-between text-xs text-muted-foreground">
                      <span className="flex items-center gap-1">
                        <Clock className="h-3 w-3" />
                        {formatSeconds(item.last_position_seconds)} /{" "}
                        {formatSeconds(item.total_duration_seconds)}
                      </span>
                      <span className="font-semibold text-foreground">
                        {item.progress_percentage}%
                      </span>
                    </div>
                    <div className="w-full bg-zinc-100 dark:bg-zinc-800 h-2 rounded-full overflow-hidden">
                      <div
                        className="bg-primary h-full transition-all"
                        style={{ width: `${item.progress_percentage}%` }}
                      />
                    </div>
                  </div>

                  <div className="flex items-center justify-between pt-2 border-t text-xs">
                    {item.is_completed ? (
                      <Badge variant="success" className="gap-1 text-[10px]">
                        <CheckCircle2 className="h-3 w-3" />
                        Completed
                      </Badge>
                    ) : (
                      <Badge variant="secondary" className="text-[10px]">
                        In Progress
                      </Badge>
                    )}

                    <div className="flex items-center gap-2">
                      <Link href={`/convert?docId=${item.document_id}`} onClick={(e) => e.stopPropagation()}>
                        <Button size="sm" variant="ghost" className="h-7 text-xs gap-1">
                          <Sparkles className="h-3 w-3 text-primary" />
                          Studio
                        </Button>
                      </Link>
                      <Button size="sm" variant="default" className="h-7 text-xs gap-1 shadow-sm">
                        <Play className="h-3 w-3" />
                        Resume
                      </Button>
                    </div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </main>
    </div>
  );
}
