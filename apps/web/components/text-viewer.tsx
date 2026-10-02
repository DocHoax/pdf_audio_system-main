"use client";

import React, { useState } from "react";
import {
  FileText,
  Languages,
  Copy,
  Check,
  Sparkles,
  BookOpen,
  Clock,
  RotateCcw,
  Loader2,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { translationApi, DocumentDetail } from "@/lib/api";
import { toast } from "sonner";

interface TextViewerProps {
  document: DocumentDetail | null;
  text: string;
  onTextUpdated?: (newText: string) => void;
  documentId?: number;
}

const SUPPORTED_TRANSLATION_LANGUAGES = [
  { code: "yo", name: "Yorùbá (Nigeria)", flag: "🇳🇬" },
  { code: "ha", name: "Hausa (Nigeria)", flag: "🇳🇬" },
  { code: "ig", name: "Igbo (Nigeria)", flag: "🇳🇬" },
  { code: "pcm", name: "Nigerian Pidgin", flag: "🇳🇬" },
  { code: "en", name: "English", flag: "🇬🇧" },
  { code: "fr", name: "French (Français)", flag: "🇫🇷" },
  { code: "es", name: "Spanish (Español)", flag: "🇪🇸" },
  { code: "ar", name: "Arabic (العربية)", flag: "🇸🇦" },
];

export function TextViewer({
  document,
  text,
  onTextUpdated,
  documentId,
}: TextViewerProps) {
  const [activeTab, setActiveTab] = useState<"original" | "translated">("original");
  const [targetLang, setTargetLang] = useState("yo");
  const [translatedText, setTranslatedText] = useState<string>("");
  const [isTranslating, setIsTranslating] = useState(false);
  const [hasCopied, setHasCopied] = useState(false);

  // Compute text statistics
  const currentDisplayedText = activeTab === "original" ? text : translatedText || text;
  const wordCount = currentDisplayedText
    ? currentDisplayedText.trim().split(/\s+/).filter(Boolean).length
    : 0;
  const charCount = currentDisplayedText ? currentDisplayedText.length : 0;
  const estReadingMins = Math.max(1, Math.ceil(wordCount / 150));
  const paragraphs = currentDisplayedText
    ? currentDisplayedText.split(/\n\s*\n/).filter((p) => p.trim().length > 0)
    : [];

  const handleTranslate = async () => {
    if (!text || text.trim().length === 0) {
      toast.error("No text available to translate");
      return;
    }

    setIsTranslating(true);
    try {
      if (documentId) {
        const res = await translationApi.translateDocument(documentId, {
          target_language: targetLang,
        });
        setTranslatedText(res.data.translated_text);
      } else {
        const res = await translationApi.translate({
          text: text,
          target_language: targetLang,
        });
        setTranslatedText(res.data.translated_text);
      }
      setActiveTab("translated");
      toast.success("Text translated successfully!");
    } catch (error: any) {
      toast.error(error.response?.data?.detail || "Translation failed");
    } finally {
      setIsTranslating(false);
    }
  };

  const handleCopy = () => {
    if (!currentDisplayedText) return;
    navigator.clipboard.writeText(currentDisplayedText);
    setHasCopied(true);
    toast.success("Copied to clipboard");
    setTimeout(() => setHasCopied(false), 2000);
  };

  return (
    <div className="border rounded-xl bg-card text-card-foreground shadow-sm flex flex-col h-full">
      {/* Header Bar */}
      <div className="p-4 border-b flex flex-wrap items-center justify-between gap-3 bg-zinc-50/50 dark:bg-zinc-900/50 rounded-t-xl">
        <div className="flex items-center gap-3">
          <div className="h-9 w-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
            <FileText className="h-5 w-5" />
          </div>
          <div>
            <h3 className="text-sm font-semibold text-foreground">
              {document?.original_filename || "Document Content"}
            </h3>
            <div className="flex items-center gap-2 mt-0.5">
              <span className="text-xs text-muted-foreground">
                {document?.page_count ? `${document.page_count} Pages` : "Extracted Text"}
              </span>
              <span className="text-zinc-300 dark:text-zinc-700">•</span>
              <span className="text-xs text-muted-foreground flex items-center gap-1">
                <Clock className="h-3 w-3" />
                ~{estReadingMins} min listen
              </span>
            </div>
          </div>
        </div>

        {/* Translation Controls & Action Buttons */}
        <div className="flex items-center gap-2 flex-wrap">
          <div className="flex items-center gap-1.5 bg-white dark:bg-zinc-800 p-1 rounded-lg border">
            <select
              value={targetLang}
              onChange={(e) => setTargetLang(e.target.value)}
              className="text-xs bg-transparent border-0 font-medium text-foreground focus:outline-none cursor-pointer pr-2"
            >
              {SUPPORTED_TRANSLATION_LANGUAGES.map((lang) => (
                <option key={lang.code} value={lang.code} className="dark:bg-zinc-800">
                  {lang.flag} {lang.name}
                </option>
              ))}
            </select>
            <Button
              size="sm"
              variant="default"
              onClick={handleTranslate}
              disabled={isTranslating || !text}
              className="h-7 text-xs px-2.5 gap-1.5"
            >
              {isTranslating ? (
                <>
                  <Loader2 className="h-3.5 w-3.5 animate-spin" />
                  Translating...
                </>
              ) : (
                <>
                  <Languages className="h-3.5 w-3.5" />
                  Translate
                </>
              )}
            </Button>
          </div>

          <Button
            size="sm"
            variant="outline"
            onClick={handleCopy}
            className="h-8 text-xs gap-1.5"
          >
            {hasCopied ? (
              <>
                <Check className="h-3.5 w-3.5 text-emerald-500" />
                Copied
              </>
            ) : (
              <>
                <Copy className="h-3.5 w-3.5" />
                Copy
              </>
            )}
          </Button>
        </div>
      </div>

      {/* Tabs Switcher (if translated text is present) */}
      {translatedText && (
        <div className="flex border-b bg-zinc-100/60 dark:bg-zinc-900/60 px-4">
          <button
            onClick={() => setActiveTab("original")}
            className={`px-4 py-2 text-xs font-semibold border-b-2 transition-colors ${
              activeTab === "original"
                ? "border-primary text-primary bg-white dark:bg-zinc-800"
                : "border-transparent text-muted-foreground hover:text-foreground"
            }`}
          >
            Original Text
          </button>
          <button
            onClick={() => setActiveTab("translated")}
            className={`px-4 py-2 text-xs font-semibold border-b-2 transition-colors flex items-center gap-1.5 ${
              activeTab === "translated"
                ? "border-primary text-primary bg-white dark:bg-zinc-800"
                : "border-transparent text-muted-foreground hover:text-foreground"
            }`}
          >
            <Sparkles className="h-3 w-3 text-amber-500" />
            Translated ({targetLang.toUpperCase()})
          </button>
        </div>
      )}

      {/* Main Text Content Area */}
      <div className="p-5 overflow-y-auto max-h-[480px] min-h-[260px] space-y-4 text-sm leading-relaxed text-zinc-800 dark:text-zinc-200">
        {paragraphs.length > 0 ? (
          paragraphs.map((para, idx) => (
            <p
              key={idx}
              className="p-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors border border-transparent hover:border-zinc-200 dark:hover:border-zinc-800"
            >
              {para}
            </p>
          ))
        ) : (
          <div className="text-center py-12 text-muted-foreground flex flex-col items-center justify-center">
            <BookOpen className="h-10 w-10 text-muted-foreground/40 mb-3" />
            <p className="font-medium text-sm">No text content available</p>
            <p className="text-xs text-muted-foreground mt-1">
              Upload a document or trigger text extraction to read content here
            </p>
          </div>
        )}
      </div>

      {/* Footer Metrics */}
      <div className="p-3 border-t bg-zinc-50/50 dark:bg-zinc-900/50 rounded-b-xl flex items-center justify-between text-xs text-muted-foreground">
        <div className="flex items-center gap-4">
          <span>{wordCount.toLocaleString()} words</span>
          <span>•</span>
          <span>{charCount.toLocaleString()} characters</span>
          <span>•</span>
          <span>{paragraphs.length} paragraphs</span>
        </div>
        {activeTab === "translated" && (
          <Badge variant="info" className="text-[10px]">
            Synthesizing will use translated text
          </Badge>
        )}
      </div>
    </div>
  );
}
