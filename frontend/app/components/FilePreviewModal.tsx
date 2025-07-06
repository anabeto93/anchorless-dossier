import { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';

interface FilePreviewModalProps {
  fileUrl: string;
  onClose: () => void;
}

export default function FilePreviewModal({ fileUrl, onClose }: FilePreviewModalProps) {
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(false);
  const [hasError, setHasError] = useState(false);

  useEffect(() => {
    setIsLoading(true);
  }, [fileUrl]);

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

  const handleIframeError = (_error: Event) => {
    console.error('Failed to load iframe for URL:', fileUrl);
    setHasError(true);
    setIsLoading(false);
  };

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
          {!isLoading && !hasError && (
            <iframe 
              src={fileUrl} 
              className="w-full h-[70vh]"
              onLoad={() => {
                setIsLoading(false);
                setError(false);
              }}
              onError={handleIframeError}
              title="File preview"
            />
          )}
        </div>
      </div>
    </div>,
    document.body
  );
}
