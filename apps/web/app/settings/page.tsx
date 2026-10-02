"use client";

import React, { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import {
  Settings,
  User,
  Sliders,
  Sparkles,
  ArrowLeft,
  Save,
  CheckCircle2,
  Loader2,
  Volume2,
} from "lucide-react";
import { Navbar } from "@/components/navbar";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";
import { usersApi, authApi, UserSettings, UserProfile } from "@/lib/api";
import { VOICES_CATALOG } from "@/components/voice-selector";
import { toast } from "sonner";

export default function SettingsPage() {
  const router = useRouter();
  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [settings, setSettings] = useState<UserSettings>({
    default_voice: "Idera",
    default_speed: 1.0,
    theme: "system",
    notifications_enabled: true,
  });
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    const fetchProfileAndSettings = async () => {
      setIsLoading(true);
      try {
        const [meRes, setRes] = await Promise.allSettled([
          authApi.getMe(),
          usersApi.getSettings(),
        ]);
        if (meRes.status === "fulfilled") {
          setProfile(meRes.value.data);
        }
        if (setRes.status === "fulfilled" && setRes.value.data) {
          setSettings(setRes.value.data);
        }
      } catch {
        // use defaults
      } finally {
        setIsLoading(false);
      }
    };
    fetchProfileAndSettings();
  }, []);

  const handleSaveSettings = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSaving(true);
    try {
      await usersApi.updateSettings(settings);
      toast.success("Preferences saved successfully!");
    } catch {
      toast.error("Failed to update preferences");
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <div className="min-h-screen bg-zinc-50/50 dark:bg-zinc-950 text-foreground">
      <Navbar />

      <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        {/* Header */}
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
              <Settings className="h-6 w-6 text-primary" />
              Settings & Preferences
            </h1>
            <p className="text-xs text-muted-foreground mt-0.5">
              Configure your default speech voice, playback preferences, and profile details
            </p>
          </div>
        </div>

        {isLoading ? (
          <div className="p-16 text-center text-muted-foreground flex flex-col items-center">
            <Loader2 className="h-8 w-8 animate-spin text-primary mb-2" />
            <p className="text-sm">Loading settings...</p>
          </div>
        ) : (
          <form onSubmit={handleSaveSettings} className="space-y-6">
            {/* Account Info */}
            <Card className="bg-card">
              <CardHeader>
                <CardTitle className="text-base flex items-center gap-2">
                  <User className="h-4 w-4 text-primary" />
                  Account Profile
                </CardTitle>
                <CardDescription>
                  Your registered account credentials and information
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div className="space-y-1.5">
                    <Label className="text-xs text-muted-foreground">Email Address</Label>
                    <input
                      type="email"
                      disabled
                      value={profile?.email || "user@example.com"}
                      className="w-full px-3 py-2 text-xs bg-zinc-100 dark:bg-zinc-800/60 border rounded-lg text-muted-foreground cursor-not-allowed"
                    />
                  </div>
                  <div className="space-y-1.5">
                    <Label className="text-xs text-muted-foreground">Full Name</Label>
                    <input
                      type="text"
                      disabled
                      value={profile?.full_name || "EchoDoc User"}
                      className="w-full px-3 py-2 text-xs bg-zinc-100 dark:bg-zinc-800/60 border rounded-lg text-muted-foreground cursor-not-allowed"
                    />
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Speech & Voice Defaults */}
            <Card className="bg-card">
              <CardHeader>
                <CardTitle className="text-base flex items-center gap-2">
                  <Volume2 className="h-4 w-4 text-primary" />
                  Default Speech Synthesis Settings
                </CardTitle>
                <CardDescription>
                  Choose the default voice persona and playback speed for new conversions
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-5">
                <div className="space-y-2">
                  <Label className="text-xs font-semibold">Preferred Voice Persona</Label>
                  <select
                    value={settings.default_voice}
                    onChange={(e) =>
                      setSettings((prev) => ({ ...prev, default_voice: e.target.value }))
                    }
                    className="w-full px-3 py-2 text-xs bg-card border rounded-lg focus:outline-none focus:ring-1 focus:ring-primary cursor-pointer"
                  >
                    <optgroup label="African Accents (Nigerian)">
                      {VOICES_CATALOG.filter((v) => v.category === "african").map((v) => (
                        <option key={v.id} value={v.id}>
                          {v.name} ({v.accent} - {v.gender})
                        </option>
                      ))}
                    </optgroup>
                    <optgroup label="Global Accents">
                      {VOICES_CATALOG.filter((v) => v.category === "global").map((v) => (
                        <option key={v.id} value={v.id}>
                          {v.name} ({v.accent} - {v.gender})
                        </option>
                      ))}
                    </optgroup>
                  </select>
                </div>

                <div className="space-y-2">
                  <div className="flex justify-between items-center">
                    <Label className="text-xs font-semibold">Default Speech Speed Multiplier</Label>
                    <span className="text-xs font-mono font-semibold text-primary">
                      {settings.default_speed.toFixed(2)}x
                    </span>
                  </div>
                  <input
                    type="range"
                    min="0.5"
                    max="2.0"
                    step="0.05"
                    value={settings.default_speed}
                    onChange={(e) =>
                      setSettings((prev) => ({
                        ...prev,
                        default_speed: parseFloat(e.target.value),
                      }))
                    }
                    className="w-full accent-primary h-2 bg-zinc-200 dark:bg-zinc-800 rounded-lg cursor-pointer"
                  />
                </div>
              </CardContent>
            </Card>

            {/* Save Button */}
            <div className="flex justify-end">
              <Button
                type="submit"
                disabled={isSaving}
                className="gap-2 shadow-sm min-w-[140px]"
              >
                {isSaving ? (
                  <>
                    <Loader2 className="h-4 w-4 animate-spin" />
                    Saving...
                  </>
                ) : (
                  <>
                    <Save className="h-4 w-4" />
                    Save Settings
                  </>
                )}
              </Button>
            </div>
          </form>
        )}
      </main>
    </div>
  );
}
