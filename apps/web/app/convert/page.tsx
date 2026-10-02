"use client";

import React, { useState, useEffect } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import {
  Sparkles,
  FileText,
  Volume2,
  Loader2,
  ArrowLeft,
  CheckCircle2,
  AlertCircle,
  Play,
  RotateCw,
} from "lucide-react";
import { Navbar } from "@/components/navbar";
import { FileUpload } from "@/components/file-upload";
import { VoiceSelector } from "@/components/voice-selector";
import { TextViewer } from "@/components/text-viewer";
import { AudioPlayer } from "@/components/audio-player";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Progress } from "@/components/ui/progress";
import { Card, CardContent } from "@/components/ui/card";
import {
  documentsApi,
  conversionsApi,
  DocumentDetail,
  Document,
  Conversion,
} from "@/lib/api";
import { toast } from "sonner";

export default function ConvertStudioPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const docIdParam = searchParams.get("docId");

  const [documents, setDocuments] = useState<Document[]>([]);
  const [selectedDocId, setSelectedDocId] = useState<number | null>(
    docIdParam ? parseInt(docIdParam) : null
  );
  const [selectedDoc, setSelectedDoc] = useState<DocumentDetail | null>(null);
  const [extractedText, setExtractedText] = useState<string>("");
  const [isLoadingDoc, setIsLoadingDoc] = useState(false);
  const [isExtracting, setIsExtracting] = useState(false);

  // Conversion Config
  const [selectedVoice, setSelectedVoice] = useState("Idera");
  const [speed, setSpeed] = useState(1.0);
  const [pitch, setPitch] = useState(0);

  // Conversion Job Tracking
  const [activeJob, setActiveJob] = useState<Conversion | null>(null);
  const [isConverting, setIsConverting] = useState(false);
  const [conversionProgress, setConversionProgress] = useState(0);
  const [completedAudioId, setCompletedAudioId] = useState<number | null>(null);

  // Fetch document list
  useEffect(() => {
    const fetchDocs = async () => {
      try {
        const res = await documentsApi.list();
        setDocuments(res.data);
        if (!selectedDocId && res.data.length > 0) {
          setSelectedDocId(res.data[0].id);
        }
      } catch {
        // ignore
      }
    };
    fetchDocs();
  }, []);

  // Fetch selected document details & text
  useEffect(() => {
    if (!selectedDocId) return;

    const loadDocDetails = async () => {
      setIsLoadingDoc(true);
      try {
        const res = await documentsApi.get(selectedDocId);
        setSelectedDoc(res.data);

        if (res.data.extracted_text) {
          setExtractedText(res.data.extracted_text);
        } else {
          // Trigger extraction if not already extracted
          handleExtractText(selectedDocId);
        }

        // Check if document already has conversions
        const convsRes = await conversionsApi.list(selectedDocId);
        const latestCompleted = convsRes.data.find(
          (c) => c.status === "completed" && c.has_audio
        );
        if (latestCompleted && latestCompleted.audio_file_id) {
          setCompletedAudioId(latestCompleted.audio_file_id);
        } else {
          setCompletedAudioId(null);
        }
      } catch {
        toast.error("Failed to load document details");
      } finally {
        setIsLoadingDoc(false);
      }
    };

    loadDocDetails();
  }, [selectedDocId]);

  const handleExtractText = async (id: number) => {
    setIsExtracting(true);
    try {
      const res = await documentsApi.extract(id);
      if (res.data.extracted_text) {
        setExtractedText(res.data.extracted_text);
        toast.success("Text extracted from document");
      }
    } catch {
      toast.error("Failed to extract text from document");
    } finally {
      setIsExtracting(false);
    }
  };

  const handleStartConversion = async () => {
    if (!selectedDocId) {
      toast.error("Please select a document");
      return;
    }

    setIsConverting(true);
    setConversionProgress(10);
    setCompletedAudioId(null);

    try {
      const response = await conversionsApi.create(selectedDocId, {
        voice: selectedVoice,
        language: "en-NG",
        speed: speed,
        pitch: pitch,
      });

      const job = response.data;
      setActiveJob(job);
      toast.success("Speech synthesis started...");

      // Poll conversion job status
      const pollInterval = setInterval(async () => {
        try {
          const pollRes = await conversionsApi.get(job.id);
          const currentJob = pollRes.data;
          setActiveJob(currentJob);
          setConversionProgress(currentJob.progress || 50);

          if (currentJob.status === "completed") {
            clearInterval(pollInterval);
            setIsConverting(false);
            setConversionProgress(100);
            if (currentJob.audio_file_id) {
              setCompletedAudioId(currentJob.audio_file_id);
            }
            toast.success("Audio conversion completed!");
          } else if (currentJob.status === "failed") {
            clearInterval(pollInterval);
            setIsConverting(false);
            toast.error(currentJob.error_message || "Conversion failed");
          }
        } catch {
          clearInterval(pollInterval);
          setIsConverting(false);
        }
      }, 2000);
    } catch (error: any) {
      setIsConverting(false);
      toast.error(error.response?.data?.detail || "Failed to start conversion");
    }
  };

  return (
    <div className="min-h-screen bg-zinc-50/50 dark:bg-zinc-950 text-foreground">
      <Navbar />

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
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
                <Sparkles className="h-6 w-6 text-primary" />
                Speech Synthesis Studio
              </h1>
              <p className="text-xs text-muted-foreground mt-0.5">
                Preview extracted text, translate into localized languages, and synthesize natural audio
              </p>
            </div>
          </div>

          {/* Document Picker Dropdown */}
          <div className="flex items-center gap-2">
            <span className="text-xs font-medium text-muted-foreground">Document:</span>
            <select
              value={selectedDocId || ""}
              onChange={(e) => setSelectedDocId(Number(e.target.value))}
              className="bg-white dark:bg-zinc-900 border rounded-lg px-3 py-1.5 text-xs font-medium text-foreground focus:outline-none cursor-pointer max-w-[220px] truncate shadow-sm"
            >
              {documents.map((doc) => (
                <option key={doc.id} value={doc.id}>
                  {doc.original_filename}
                </option>
              ))}
            </select>
          </div>
        </div>

        {/* Audio Player if Audio Exists */}
        {completedAudioId && (
          <div className="sticky top-20 z-40">
            <AudioPlayer
              audioId={completedAudioId}
              documentId={selectedDocId || undefined}
              documentTitle={selectedDoc?.original_filename}
              voiceName={selectedVoice}
              speed={speed}
            />
          </div>
        )}

        {/* Progress Bar during active conversion */}
        {isConverting && (
          <Card className="border-primary bg-primary/5">
            <CardContent className="p-4 space-y-2">
              <div className="flex items-center justify-between text-xs font-semibold text-foreground">
                <span className="flex items-center gap-2">
                  <Loader2 className="h-4 w-4 animate-spin text-primary" />
                  Generating audio with {selectedVoice} voice...
                </span>
                <span>{conversionProgress}%</span>
              </div>
              <Progress value={conversionProgress} className="h-2" />
            </CardContent>
          </Card>
        )}

        {/* Two Column Layout: Text Preview (Left) vs Voice & Tuning (Right) */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
          {/* Left Column: Document Text & Translation (7 cols) */}
          <div className="lg:col-span-7 space-y-4">
            {isLoadingDoc ? (
              <div className="border rounded-xl p-16 text-center text-muted-foreground flex flex-col items-center bg-card">
                <Loader2 className="h-8 w-8 animate-spin text-primary mb-2" />
                <p className="text-sm">Loading document contents...</p>
              </div>
            ) : selectedDoc ? (
              <TextViewer
                document={selectedDoc}
                text={extractedText}
                documentId={selectedDoc.id}
                onTextUpdated={(txt) => setExtractedText(txt)}
              />
            ) : (
              <div className="border rounded-xl p-8 bg-card text-center">
                <FileUpload
                  onUploadSuccess={(newDoc) => {
                    setDocuments((prev) => [newDoc, ...prev]);
                    setSelectedDocId(newDoc.id);
                  }}
                />
              </div>
            )}
          </div>

          {/* Right Column: Voice Selection & Generation Controls (5 cols) */}
          <div className="lg:col-span-5 space-y-6">
            <Card className="bg-card">
              <CardContent className="p-5 space-y-6">
                <VoiceSelector
                  selectedVoice={selectedVoice}
                  onVoiceChange={(v) => setSelectedVoice(v)}
                  speed={speed}
                  onSpeedChange={(s) => setSpeed(s)}
                  pitch={pitch}
                  onPitchChange={(p) => setPitch(p)}
                />

                <Button
                  size="lg"
                  onClick={handleStartConversion}
                  disabled={isConverting || !extractedText || isLoadingDoc}
                  className="w-full gap-2 text-sm font-semibold shadow-md"
                >
                  {isConverting ? (
                    <>
                      <Loader2 className="h-4 w-4 animate-spin" />
                      Synthesizing Audio ({conversionProgress}%)...
                    </>
                  ) : (
                    <>
                      <Volume2 className="h-5 w-5" />
                      Generate Audio with {selectedVoice}
                    </>
                  )}
                </Button>
              </CardContent>
            </Card>

            {/* Upload Another Document Box */}
            <Card className="bg-card">
              <CardContent className="p-5">
                <h4 className="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">
                  Upload Another Document
                </h4>
                <FileUpload
                  onUploadSuccess={(newDoc) => {
                    setDocuments((prev) => [newDoc, ...prev]);
                    setSelectedDocId(newDoc.id);
                  }}
                />
              </CardContent>
            </Card>
          </div>
        </div>
      </main>
    </div>
  );
}
