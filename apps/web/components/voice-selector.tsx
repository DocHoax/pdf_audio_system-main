"use client";

import React, { useState } from "react";
import { Mic, Volume2, Globe, Sparkles, Sliders, Check } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Label } from "@/components/ui/label";

export interface VoiceOption {
  id: string;
  name: string;
  language: string;
  gender: "female" | "male";
  accent: string;
  category: "african" | "global";
  description: string;
  isPopular?: boolean;
}

export const VOICES_CATALOG: VoiceOption[] = [
  // African / Nigerian Accents
  {
    id: "Idera",
    name: "Idera",
    language: "en-NG",
    gender: "female",
    accent: "Nigerian (Lagos/Yoruba)",
    category: "african",
    description: "Warm, professional, articulate Nigerian female voice. Ideal for long-form books & articles.",
    isPopular: true,
  },
  {
    id: "Emma",
    name: "Emma",
    language: "en-NG",
    gender: "male",
    accent: "Nigerian (Standard English)",
    category: "african",
    description: "Clear, confident Nigerian male voice. Excellent for news, technical docs & academic reading.",
    isPopular: true,
  },
  {
    id: "Zainab",
    name: "Zainab",
    language: "en-NG",
    gender: "female",
    accent: "Nigerian (Northern/Hausa)",
    category: "african",
    description: "Gentle, expressive cadence with Northern Nigerian inflection.",
  },
  {
    id: "Osagie",
    name: "Osagie",
    language: "en-NG",
    gender: "male",
    accent: "Nigerian (Edo/Midwest)",
    category: "african",
    description: "Deep, resonant tone with natural storytelling tempo.",
  },
  {
    id: "Wura",
    name: "Wura",
    language: "en-NG",
    gender: "female",
    accent: "Nigerian (Southwest)",
    category: "african",
    description: "Bright, engaging tone for educational and narrative content.",
  },
  {
    id: "Chinedu",
    name: "Chinedu",
    language: "en-NG",
    gender: "male",
    accent: "Nigerian (Southeastern/Igbo)",
    category: "african",
    description: "Rich, energetic voice with authentic Igbo-English rhythm.",
  },
  {
    id: "Ngozi",
    name: "Ngozi",
    language: "en-NG",
    gender: "female",
    accent: "Nigerian (Eastern)",
    category: "african",
    description: "Smooth, soothing tone perfect for literature and essays.",
  },
  {
    id: "Musa",
    name: "Musa",
    language: "en-NG",
    gender: "male",
    accent: "Nigerian (Northern/Hausa)",
    category: "african",
    description: "Authoritative, dignified baritone for reports and lectures.",
  },
  // Global Accents
  {
    id: "en-US-JennyNeural",
    name: "Jenny",
    language: "en-US",
    gender: "female",
    accent: "US English",
    category: "global",
    description: "Natural, conversational American English voice.",
  },
  {
    id: "en-US-GuyNeural",
    name: "Guy",
    language: "en-US",
    gender: "male",
    accent: "US English",
    category: "global",
    description: "Friendly, casual American English male voice.",
  },
  {
    id: "en-GB-SoniaNeural",
    name: "Sonia",
    language: "en-GB",
    gender: "female",
    accent: "British (RP)",
    category: "global",
    description: "Polished, classical British English voice.",
  },
  {
    id: "en-GB-RyanNeural",
    name: "Ryan",
    language: "en-GB",
    gender: "male",
    accent: "British (Standard)",
    category: "global",
    description: "Clear, approachable contemporary British male voice.",
  },
];

interface VoiceSelectorProps {
  selectedVoice: string;
  onVoiceChange: (voiceId: string) => void;
  speed: number;
  onSpeedChange: (speed: number) => void;
  pitch?: number;
  onPitchChange?: (pitch: number) => void;
  language?: string;
  onLanguageChange?: (lang: string) => void;
}

export function VoiceSelector({
  selectedVoice,
  onVoiceChange,
  speed,
  onSpeedChange,
  pitch = 0,
  onPitchChange,
  language = "en",
  onLanguageChange,
}: VoiceSelectorProps) {
  const [activeCategory, setActiveCategory] = useState<"african" | "global">("african");

  const filteredVoices = VOICES_CATALOG.filter(
    (v) => v.category === activeCategory
  );

  const speedPresets = [0.75, 1.0, 1.25, 1.5, 2.0];

  return (
    <div className="space-y-6">
      {/* Category Tabs */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b">
        <div>
          <h3 className="text-base font-semibold text-foreground flex items-center gap-2">
            <Mic className="h-4 w-4 text-primary" />
            Select Voice & Audio Settings
          </h3>
          <p className="text-xs text-muted-foreground mt-0.5">
            Choose authentic localized African accents or international voices
          </p>
        </div>

        <div className="flex bg-zinc-100 dark:bg-zinc-900 p-1 rounded-lg border">
          <button
            type="button"
            onClick={() => setActiveCategory("african")}
            className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all ${
              activeCategory === "african"
                ? "bg-white dark:bg-zinc-800 text-primary shadow-sm"
                : "text-muted-foreground hover:text-foreground"
            }`}
          >
            <Sparkles className="h-3.5 w-3.5" />
            African Accents
          </button>
          <button
            type="button"
            onClick={() => setActiveCategory("global")}
            className={`flex items-center gap-1.5 px-3 py-1.5 rounded-md text-xs font-semibold transition-all ${
              activeCategory === "global"
                ? "bg-white dark:bg-zinc-800 text-primary shadow-sm"
                : "text-muted-foreground hover:text-foreground"
            }`}
          >
            <Globe className="h-3.5 w-3.5" />
            Global Voices
          </button>
        </div>
      </div>

      {/* Voice Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-3 max-h-[340px] overflow-y-auto pr-1">
        {filteredVoices.map((voice) => {
          const isSelected = selectedVoice === voice.id;
          return (
            <div
              key={voice.id}
              onClick={() => onVoiceChange(voice.id)}
              className={`border rounded-xl p-4 cursor-pointer transition-all text-left relative ${
                isSelected
                  ? "border-primary bg-primary/5 ring-2 ring-primary/20 shadow-sm"
                  : "hover:border-primary/40 hover:bg-zinc-50 dark:hover:bg-zinc-900/40"
              }`}
            >
              <div className="flex items-start justify-between gap-2 mb-2">
                <div className="flex items-center gap-2">
                  <div
                    className={`h-8 w-8 rounded-full flex items-center justify-center font-bold text-xs ${
                      isSelected
                        ? "bg-primary text-primary-foreground"
                        : "bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300"
                    }`}
                  >
                    {voice.name.charAt(0)}
                  </div>
                  <div>
                    <h4 className="font-semibold text-sm text-foreground flex items-center gap-1.5">
                      {voice.name}
                      {voice.isPopular && (
                        <Badge variant="success" className="text-[10px] px-1.5 py-0">
                          Popular
                        </Badge>
                      )}
                    </h4>
                    <span className="text-[11px] text-muted-foreground">
                      {voice.gender === "female" ? "Female" : "Male"} • {voice.accent}
                    </span>
                  </div>
                </div>

                {isSelected && (
                  <div className="h-5 w-5 rounded-full bg-primary text-primary-foreground flex items-center justify-center shrink-0">
                    <Check className="h-3 w-3" />
                  </div>
                )}
              </div>

              <p className="text-xs text-muted-foreground leading-relaxed">
                {voice.description}
              </p>
            </div>
          );
        })}
      </div>

      {/* Speed & Audio Tuning Controls */}
      <div className="bg-zinc-50 dark:bg-zinc-900/50 rounded-xl p-4 border space-y-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Sliders className="h-4 w-4 text-primary" />
            <Label className="text-sm font-semibold">Speech Speed Multiplier</Label>
          </div>
          <span className="text-xs font-mono font-semibold px-2 py-0.5 bg-primary/10 text-primary rounded-md">
            {speed.toFixed(2)}x
          </span>
        </div>

        <div className="flex items-center gap-3">
          <input
            type="range"
            min="0.5"
            max="2.0"
            step="0.05"
            value={speed}
            onChange={(e) => onSpeedChange(parseFloat(e.target.value))}
            className="w-full accent-primary h-2 bg-zinc-200 dark:bg-zinc-800 rounded-lg cursor-pointer"
          />
        </div>

        <div className="flex items-center gap-2 pt-1">
          <span className="text-[11px] text-muted-foreground mr-1">Presets:</span>
          {speedPresets.map((preset) => (
            <button
              key={preset}
              type="button"
              onClick={() => onSpeedChange(preset)}
              className={`px-2 py-1 rounded text-xs font-medium transition-colors ${
                speed === preset
                  ? "bg-primary text-primary-foreground"
                  : "bg-white dark:bg-zinc-800 text-muted-foreground hover:text-foreground border"
              }`}
            >
              {preset}x
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}
