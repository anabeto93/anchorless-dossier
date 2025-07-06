// File model interface
export interface FileModel {
  id: number;
  name: string;
  path: string;
  size: number;
  mime_type: string;
  created_at: string;
  updated_at: string;
  previewUrl: string;
}

// Grouped files interface
export interface GroupedFiles {
  PDF: FileModel[];
  PNG: FileModel[];
  JPG: FileModel[];
}

// Standard API response
export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
}

// API error response
export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
  status: number;
}
