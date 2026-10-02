"use client";

import React, { useState, useRef, useEffect } from "react";
import {
  Play,
  Pause,
  RotateCcw,
  RotateCw,
  Volume2,
  VolumeX,
  Download,
  Share2,
  Sparkles,
  Music,
  Bookmark,
  Check,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { audioApi, historyApi, AudioFile } from "@/lib/api";
import { toast } from "sonner";

interface AudioPlayerProps {
  audioId: number;
  documentId?: number;
  documentTitle?: string;
  voiceName?: string;
  speed?: number;
  initialPositionSeconds?: number;
  onProgressUpdate?: (seconds: number, totalSeconds: number) => void;
}

export function AudioPlayer({
  audioId,
  documentId,
  documentTitle = "Synthesized Document Audio",
  voiceName = "Idera",
  speed = 1.0,
  initialPositionSeconds = 0,
  onProgressUpdate,
}: AudioPlayerProps) {
  const audioRef = useRef<HTMLAudioElement | null>(null);
  const [isPlaying, setIsPlaying] = useState(false);
  const [currentTime, setCurrentTime] = useState(0);
  const [duration, setDuration] = useState(0);
  const [playbackRate, setPlaybackRate] = useState(speed);
  const [volume, setVolume] = useState(1);
  const [isMuted, setIsMuted] = useState(false);
  const [isBookmarking, setIsBookmarking] = useState(false);

  const streamUrl = audioApi.getStreamUrl(audioId);
  const downloadUrl = audioApi.getDownloadUrl(audioId);

  // Set initial position once metadata is loaded
  const handleLoadedMetadata = () => {
    if (audioRef.current) {
      setDuration(audioRef.current.duration || 0);
      if (initialPositionSeconds > 0 && initialPositionSeconds < audioRef.current.duration) {
        audioRef.current.currentTime = initialPositionSeconds;
        setCurrentTime(initialPositionSeconds);
      }
    }
  };

  const togglePlayPause = () => {
    if (!audioRef.current) return;
    if (isPlaying) {
      audioRef.current.pause();
      setIsPlaying(false);
      saveBookmark(currentTime);
    } else {
      audioRef.current.play().then(() => setIsPlaying(true)).catch(() => {
        toast.error("Audio playback error");
      });
    }
  };

  const handleTimeUpdate = () => {
    if (!audioRef.current) return;
    const current = audioRef.current.currentTime;
    setCurrentTime(current);
    onProgressUpdate?.(current, duration);

    // Autosave progress every 15 seconds
    if (documentId && Math.floor(current) % 15 === 0 && Math.floor(current) > 0) {
      saveBookmark(current, false);
    }
  };

  const handleSeek = (e: React.ChangeEvent<HTMLInputElement>) => {
    const time = parseFloat(e.target.value);
    setCurrentTime(time);
    if (audioRef.current) {
      audioRef.current.currentTime = time;
    }
  };

  const handleSkip = (seconds: number) => {
    if (!audioRef.current) return;
    const newTime = Math.min(Math.max(0, audioRef.current.currentTime + seconds), duration);
    audioRef.current.currentTime = newTime;
    setCurrentTime(newTime);
  };

  const handleSpeedChange = (rate: number) => {
    setPlaybackRate(rate);
    if (audioRef.current) {
      audioRef.current.playbackRate = rate;
    }
  };

  const toggleMute = () => {
    if (!audioRef.current) return;
    const newMute = !isMuted;
    setIsMuted(newMute);
    audioRef.current.muted = newMute;
  };

  const handleVolumeChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const val = parseFloat(e.target.value);
    setVolume(val);
    if (audioRef.current) {
      audioRef.current.volume = val;
      setIsMuted(val === 0);
    }
  };

  const saveBookmark = async (pos: number, showToast = true) => {
    if (!documentId) return;
    try {
      setIsBookmarking(true);
      await historyApi.saveProgress({
        document_id: documentId,
        last_position_seconds: Math.floor(pos),
        total_duration_seconds: Math.floor(duration),
        is_completed: duration > 0 && pos >= duration - 5,
      });
      if (showToast) {
        toast.success("Reading progress saved!");
      }
    } catch {
      // ignore background save errors
    } finally {
      setIsBookmarking(false);
    }
  };

  const formatTime = (secs: number) => {
    if (isNaN(secs) || secs < 0) return "0:00";
    const minutes = Math.floor(secs / 60);
    const seconds = Math.floor(secs % 60);
    return `${minutes}:${seconds < 10 ? "0" : ""}${seconds}`;
  };

  const speedOptions = [0.75, 1.0, 1.25, 1.5, 2.0];

  return (
    <div className="border rounded-xl p-5 bg-gradient-to-br from-card to-zinc-50 dark:to-zinc-900 text-card-foreground shadow-md">
      <audio
        ref={audioRef}
        src={streamUrl}
        onLoadedMetadata={handleLoadedMetadata}
        onTimeUpdate={handleTimeUpdate}
        onEnded={() => {
          setIsPlaying(false);
          if (documentId) saveBookmark(duration, false);
        }}
        preload="metadata"
      />

      {/* Header Info */}
      <div className="flex items-start justify-between gap-4 mb-4">
        <div className="flex items-center gap-3">
          <div className="h-12 w-12 rounded-xl bg-primary text-primary-foreground flex items-center justify-center shadow-sm">
            <Music className="h-6 w-6" />
          </div>
          <div>
            <h4 className="font-bold text-sm text-foreground line-clamp-1">
              {documentTitle}
            </h4>
            <div className="flex items-center gap-2 mt-1">
              <Badge variant="secondary" className="text-[10px] font-semibold gap-1">
                <Sparkles className="h-3 w-3 text-primary" />
                {voiceName}
              </Badge>
              <span className="text-zinc-300 dark:text-zinc-700">•</span>
              <span className="text-xs text-muted-foreground">MP3 Audio</span>
            </div>
          </div>
        </div>

        <div className="flex items-center gap-2">
          {documentId && (
            <Button
              variant="outline"
              size="sm"
              onClick={() => saveBookmark(currentTime, true)}
              disabled={isBookmarking}
              className="h-8 text-xs gap-1.5"
              title="Save current reading position"
            >
              <Bookmark className="h-3.5 w-3.5" />
              Bookmark
            </Button>
          )}

          <a href={downloadUrl} download>
            <Button variant="outline" size="sm" className="h-8 text-xs gap-1.5">
              <Download className="h-3.5 w-3.5" />
              Download MP3
            </Button>
          </a>
        </div>
      </div>

      {/* Scrubber / Progress Bar */}
      <div className="space-y-1.5 mb-4">
        <input
          type="range"
          min="0"
          max={duration || 100}
          step="0.5"
          value={currentTime}
          onChange={handleSeek}
          className="w-full accent-primary h-2 bg-zinc-200 dark:bg-zinc-800 rounded-lg cursor-pointer"
        />
        <div className="flex justify-between text-xs font-mono text-muted-foreground">
          <span>{formatTime(currentTime)}</span>
          <span>{formatTime(duration)}</span>
        </div>
      </div>

      {/* Playback Controls & Tuning */}
      <div className="flex flex-wrap items-center justify-between gap-4 pt-2 border-t">
        {/* Play / Skip Buttons */}
        <div className="flex items-center gap-2">
          <Button
            variant="ghost"
            size="icon"
            onClick={() => handleSkip(-15)}
            className="h-9 w-9 text-muted-foreground hover:text-foreground"
            title="Rewind 15 seconds"
          >
            <RotateCcw className="h-4 w-4" />
          </Button>

          <Button
            size="icon"
            onClick={togglePlayPause}
            className="h-11 w-11 rounded-full shadow-md"
            title={isPlaying ? "Pause" : "Play"}
          >
            {isPlaying ? (
              <Pause className="h-5 w-5" />
            ) : (
              <Play className="h-5 w-5 ml-0.5" />
            )}
          </Button>

          <Button
            variant="ghost"
            size="icon"
            onClick={() => handleSkip(15)}
            className="h-9 w-9 text-muted-foreground hover:text-foreground"
            title="Forward 15 seconds"
          >
            <RotateCw className="h-4 w-4" />
          </Button>
        </div>

        {/* Speed Controls */}
        <div className="flex items-center gap-1 bg-zinc-100 dark:bg-zinc-800 p-1 rounded-lg">
          {speedOptions.map((rate) => (
            <button
              key={rate}
              onClick={() => handleSpeedChange(rate)}
              className={`px-2 py-1 rounded text-xs font-medium transition-colors ${
                playbackRate === rate
                  ? "bg-primary text-primary-foreground font-semibold"
                  : "text-muted-foreground hover:text-foreground"
              }`}
            >
              {rate}x
            </button>
          ))}
        </div>

        {/* Volume Scrubber */}
        <div className="flex items-center gap-2">
          <button
            onClick={toggleMute}
            className="text-muted-foreground hover:text-foreground p-1"
          >
            {isMuted || volume === 0 ? (
              <VolumeX className="h-4 w-4 text-destructive" />
            ) : (
              <Volume2 className="h-4 w-4" />
            )}
          </button>
          <input
            type="range"
            min="0"
            max="1"
            step="0.05"
            value={isMuted ? 0 : volume}
            onChange={handleVolumeChange}
            className="w-20 accent-primary h-1.5 bg-zinc-200 dark:bg-zinc-800 rounded-lg cursor-pointer"
          />
        </div>
      </div>
    </div>
  );
}
