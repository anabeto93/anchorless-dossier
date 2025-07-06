import { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';
// eslint-disable-next-line import/no-unresolved
import api from '~/utils/api';
import type { FileModel } from '~/types/api';

interface FilePreviewModalProps {
  fileUrl: string; // This could be either a direct URL or a file_id
  onClose: () => void;
}

export default function FilePreviewModal({ fileUrl, onClose }: FilePreviewModalProps) {
  const [isLoading, setIsLoading] = useState(true);
  const [hasError, setHasError] = useState(false);
  const [fileData, setFileData] = useState<FileModel | null>(null);
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);

  const handleError = () => {
    console.error('Failed to load preview for URL:', previewUrl);
    setHasError(true);
    setIsLoading(false);
  };

  // Determine if the fileUrl is a file_id or a direct URL
  useEffect(() => {
    setIsLoading(true);
    setHasError(false);
    
    // Check if the URL is likely a file_id (not a full URL with http/https)
    const isFileId = !fileUrl.startsWith('http');
    
    if (isFileId) {
      // If it's a file_id, fetch the file metadata
      const fetchFileData = async () => {
        try {
          const response = await api.get(`/api/files/${fileUrl}`);
          if (response.data.success) {
            setFileData(response.data.data);
            // Use the preview_url from the response
            setPreviewUrl(response.data.data.preview_url);
          } else {
            setHasError(true);
          }
        } catch (error) {
          console.error('Error fetching file data:', error);
          setHasError(true);
        } finally {
          setIsLoading(false);
        }
      };
      
      fetchFileData();
    } else {
      // If it's a direct URL, use it directly
      setPreviewUrl(fileUrl);
      setIsLoading(false);
    }
  }, [fileUrl]);

  // Render different preview components based on file type
  const renderPreview = () => {
    if (!fileData && !previewUrl) return null;
    
    // If we have file data, use the mime_type to determine how to render
    if (fileData) {
      const mimeType = fileData.mime_type;
      
      // Handle PDFs
      if (mimeType === 'application/pdf') {
        return (
          <iframe 
            src={previewUrl || ''} 
            className="w-full h-[70vh]"
            onLoad={() => setIsLoading(false)}
            onError={handleError}
            title="PDF preview"
          />
        );
      }
      
      // Handle images
      if (mimeType.startsWith('image/')) {
        return (
          <div className="flex justify-center items-center h-[70vh] overflow-auto">
            <img 
              src={previewUrl || ''} 
              alt={fileData.name}
              className="max-w-full max-h-full object-contain" 
              onLoad={() => setIsLoading(false)}
              onError={handleError}
            />
          </div>
        );
      }
      
      // For other file types, show a download link
      return (
        <div className="flex flex-col justify-center items-center h-[70vh]">
          <div className="text-center p-6 bg-gray-50 rounded-lg">
            <svg className="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p className="mt-4 text-sm font-medium text-gray-900">{fileData.name}</p>
            <p className="mt-1 text-sm text-gray-500">{fileData.mime_type}</p>
            <a 
              href={fileData.path} 
              download={fileData.name}
              className="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              onClick={() => setIsLoading(false)}
            >
              Download File
            </a>
          </div>
        </div>
      );
    }
    
    // If we only have a URL but no file data, use an iframe as fallback
    return (
      <iframe 
        src={previewUrl || ''} 
        className="w-full h-[70vh]"
        onLoad={() => setIsLoading(false)}
        onError={handleError}
        title="File preview"
      />
    );
  };

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        onClose();
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => {
      window.removeEventListener('keydown', handleKeyDown);
    };
  }, [onClose]);

  useEffect(() => {
    const modalElement = document.querySelector('[role="dialog"]');
    if (modalElement) {
      (modalElement as HTMLElement).focus();
      const handleFocus = (e: FocusEvent) => {
        if (!modalElement.contains(e.target as Node)) {
          (modalElement as HTMLElement).focus();
        }
      };
      window.addEventListener('focus', handleFocus, true);
      return () => {
        window.removeEventListener('focus', handleFocus, true);
      };
    }
  }, []);

  useEffect(() => {
    document.body.style.overflow = 'hidden';
    return () => {
      document.body.style.overflow = 'unset';
    };
  }, []);

  // This function is now replaced by the handleError function above

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <button
        className="fixed inset-0 bg-black bg-opacity-75 cursor-default outline-none"
        onClick={onClose}
        aria-label="Close modal"
      />
      
      <div className="relative bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div className="flex justify-between items-center p-4 border-b">
          <h3 className="text-lg font-medium">Preview</h3>
          <button 
            onClick={onClose}
            className="text-gray-500 hover:text-gray-700"
            aria-label="Close"
            onKeyDown={(e) => {
              if (e.key === 'Enter' || e.key === ' ') {
                onClose();
              }
            }}
            tabIndex={0}
            type="button"
          >
            <svg xmlns="http://www.w3.org/2000/svg" className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div className="p-4 flex-1 overflow-auto">
          {isLoading && (
            <div className="flex justify-center items-center h-64">
              <div className="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
            </div>
          )}
          {!isLoading && hasError && (
            <div className="flex justify-center items-center h-64">
              <p className="text-red-500">Failed to load preview.</p>
              <a 
                href={fileUrl} 
                target="_blank" 
                rel="noopener noreferrer"
                className="text-blue-500 hover:text-blue-700"
              >
                Open in new tab
              </a>
            </div>
          )}
          {!isLoading && !hasError && renderPreview()}
        </div>
      </div>
    </div>,
    document.body
  );
}
