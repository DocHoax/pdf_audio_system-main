"use client";

import React, { useState, useRef, DragEvent, ChangeEvent } from "react";
import { UploadCloud, FileText, CheckCircle2, AlertCircle, Loader2, X } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Progress } from "@/components/ui/progress";
import { Badge } from "@/components/ui/badge";
import { documentsApi, Document } from "@/lib/api";
import { toast } from "sonner";

interface FileUploadProps {
  onUploadSuccess?: (doc: Document) => void;
  onUploadStart?: () => void;
  allowedFormats?: string[];
  maxSizeMB?: number;
}

export function FileUpload({
  onUploadSuccess,
  onUploadStart,
  allowedFormats = [".pdf", ".docx", ".txt"],
  maxSizeMB = 50,
}: FileUploadProps) {
  const [isDragging, setIsDragging] = useState(false);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [isUploading, setIsUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const formatFileSize = (bytes: number) => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
  };

  const validateFile = (file: File): boolean => {
    const ext = "." + file.name.split(".").pop()?.toLowerCase();
    if (!allowedFormats.includes(ext)) {
      toast.error(`Invalid format. Please upload ${allowedFormats.join(", ")}`);
      return false;
    }
    const maxSizeBytes = maxSizeMB * 1024 * 1024;
    if (file.size > maxSizeBytes) {
      toast.error(`File size exceeds ${maxSizeMB}MB limit`);
      return false;
    }
    return true;
  };

  const handleFile = (file: File) => {
    if (validateFile(file)) {
      setSelectedFile(file);
    }
  };

  const handleDragOver = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    setIsDragging(true);
  };

  const handleDragLeave = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    setIsDragging(false);
  };

  const handleDrop = (e: DragEvent<HTMLDivElement>) => {
    e.preventDefault();
    setIsDragging(false);
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFile(e.dataTransfer.files[0]);
    }
  };

  const handleInputChange = (e: ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      handleFile(e.target.files[0]);
    }
  };

  const handleUpload = async () => {
    if (!selectedFile) return;

    setIsUploading(true);
    setUploadProgress(20);
    onUploadStart?.();

    try {
      setUploadProgress(50);
      const response = await documentsApi.upload(selectedFile);
      setUploadProgress(100);
      toast.success("Document uploaded successfully!");
      setSelectedFile(null);
      if (fileInputRef.current) fileInputRef.current.value = "";
      onUploadSuccess?.(response.data);
    } catch (error: any) {
      toast.error(error.response?.data?.detail || "Failed to upload document");
    } finally {
      setIsUploading(false);
      setUploadProgress(0);
    }
  };

  const removeSelectedFile = () => {
    setSelectedFile(null);
    if (fileInputRef.current) fileInputRef.current.value = "";
  };

  return (
    <div className="w-full">
      <input
        type="file"
        ref={fileInputRef}
        onChange={handleInputChange}
        accept={allowedFormats.join(",")}
        className="hidden"
      />

      {!selectedFile ? (
        <div
          onDragOver={handleDragOver}
          onDragLeave={handleDragLeave}
          onDrop={handleDrop}
          onClick={() => fileInputRef.current?.click()}
          className={`border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all duration-200 flex flex-col items-center justify-center min-h-[220px] ${
            isDragging
              ? "border-primary bg-primary/5 scale-[0.99]"
              : "border-zinc-300 dark:border-zinc-800 hover:border-primary/50 hover:bg-zinc-50 dark:hover:bg-zinc-900/50"
          }`}
        >
          <div className="h-14 w-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mb-4 shadow-sm">
            <UploadCloud className="h-7 w-7" />
          </div>
          <h3 className="text-base font-semibold text-foreground mb-1">
            Click to upload or drag & drop
          </h3>
          <p className="text-xs text-muted-foreground mb-3 max-w-sm">
            Upload PDF, Word document (DOCX), or plain text files to extract and synthesize into natural audio
          </p>
          <div className="flex items-center gap-2">
            <Badge variant="outline" className="text-[11px] font-medium">
              PDF
            </Badge>
            <Badge variant="outline" className="text-[11px] font-medium">
              DOCX
            </Badge>
            <Badge variant="outline" className="text-[11px] font-medium">
              TXT
            </Badge>
            <span className="text-[11px] text-muted-foreground">Up to {maxSizeMB}MB</span>
          </div>
        </div>
      ) : (
        <div className="border rounded-xl p-5 bg-card text-card-foreground shadow-sm">
          <div className="flex items-start justify-between gap-4 mb-4">
            <div className="flex items-center gap-3">
              <div className="h-12 w-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <FileText className="h-6 w-6" />
              </div>
              <div>
                <h4 className="font-semibold text-sm text-foreground line-clamp-1">
                  {selectedFile.name}
                </h4>
                <div className="flex items-center gap-2 mt-1">
                  <span className="text-xs text-muted-foreground">
                    {formatFileSize(selectedFile.size)}
                  </span>
                  <span className="text-zinc-300 dark:text-zinc-700">•</span>
                  <Badge variant="secondary" className="text-[10px] uppercase">
                    {selectedFile.name.split(".").pop()}
                  </Badge>
                </div>
              </div>
            </div>
            {!isUploading && (
              <button
                onClick={removeSelectedFile}
                className="text-muted-foreground hover:text-destructive p-1 rounded-md transition-colors"
              >
                <X className="h-5 w-5" />
              </button>
            )}
          </div>

          {isUploading && (
            <div className="space-y-2 mb-4">
              <div className="flex justify-between text-xs text-muted-foreground">
                <span>Uploading and analyzing document...</span>
                <span>{uploadProgress}%</span>
              </div>
              <Progress value={uploadProgress} className="h-2" />
            </div>
          )}

          <div className="flex justify-end gap-2 pt-2 border-t">
            <Button
              variant="outline"
              size="sm"
              onClick={removeSelectedFile}
              disabled={isUploading}
            >
              Cancel
            </Button>
            <Button
              size="sm"
              onClick={handleUpload}
              disabled={isUploading}
              className="gap-2"
            >
              {isUploading ? (
                <>
                  <Loader2 className="h-4 w-4 animate-spin" />
                  Processing...
                </>
              ) : (
                <>
                  <UploadCloud className="h-4 w-4" />
                  Upload & Extract
                </>
              )}
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}
