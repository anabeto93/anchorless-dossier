import type { LoaderFunction } from "@remix-run/node";
import { json } from "@remix-run/node";
import { useLoaderData, useRevalidator } from "@remix-run/react";
// eslint-disable-next-line import/no-unresolved
import api from "~/utils/api";
import type { ApiResponse, GroupedFiles, FileModel } from "~/types/api";
import { useState } from 'react';
import toast from 'react-hot-toast';
// eslint-disable-next-line import/no-unresolved
import { extractValidationErrors, formatValidationErrorsList } from "~/utils/errorHandlers";
// eslint-disable-next-line import/no-unresolved
import FilePreviewModal from "~/components/FilePreviewModal";
// eslint-disable-next-line import/no-unresolved
import FileUploadModal from "~/components/FileUploadModal";
import { createPortal } from 'react-dom';

interface FileItemProps {
  file: FileModel;
  onPreview: (fileId: string) => void;
}

interface FileGroupProps {
  title: string;
  files: FileModel[];
  setPreviewFile: (fileId: string) => void;
}

interface SuccessUploading {
  file_id: string;
  url: string;
}

export const loader: LoaderFunction = async () => {
  try {
    const response = await api.get<ApiResponse<{ grouped_files: GroupedFiles }>>(
      "/api/files"
    );
    return json({
      groupedFiles: response.data.data.grouped_files,
    });
  } catch (error) {
    console.error("Failed to fetch files", error);
    return json({
      error: "Failed to load files. Please try again later.",
    });
  }
};

export default function FilesPage() {
  const data = useLoaderData<typeof loader>();
  const [isUploadModalOpen, setIsUploadModalOpen] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [validationErrors, setValidationErrors] = useState<string[]>([]);
  const [previewFileId, setPreviewFileId] = useState<string | null>(null);
  const revalidator = useRevalidator();

  if (data.error) {
    return (
      <div className="max-w-6xl mx-auto p-6">
        <div className="bg-red-50 border-l-4 border-red-500 p-4 rounded">
          <div className="flex">
            <div className="flex-shrink-0">
              <svg
                className="h-5 w-5 text-red-500"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
              >
                <path
                  fillRule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                  clipRule="evenodd"
                />
              </svg>
            </div>
            <div className="ml-3">
              <p className="text-sm text-red-700">
                <span className="font-medium">Error</span> {data.error}
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  const { groupedFiles } = data as { groupedFiles: GroupedFiles };

  const closePreview = () => setPreviewFileId(null);

  const handleFileUpload = async (file: File) => {
    console.log("Uploading file", { file });
    setIsUploading(true);
    setValidationErrors([]);
    
    // Create a toast promise that will show loading, success, and error states
    const uploadPromise = api.post<ApiResponse<SuccessUploading>>('/api/files', { file }, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'Accept': 'application/json',
      },
    });
    
    toast.promise(
      uploadPromise,
      {
        loading: 'Uploading file...',
        success: 'File uploaded successfully!',
        error: (err) => {
          // Extract validation errors using our utility
          const { toastMessage } = extractValidationErrors(err);
          
          // If it's a validation error, also set the errors for display in the modal
          if (err.response?.status === 422) {
            const errorsList = formatValidationErrorsList(err);
            setValidationErrors(errorsList);
          }
          
          return toastMessage;
        },
      }
    );
    
    try {
      /**
       * {
       *  "success": true,
       *  "error_code": 202,
       *  "message": "File upload queued for processing",
       *  "data": {
       *      "file_id": "file_17517937431184_36d3e69d5fed_screenshot-from-202",
       *      "url": "http:\/\/localhost:2027\/api\/files\/file_17517937431184_36d3e69d5fed_screenshot-from-202"
       *  }
       * }
       */
      const response = await uploadPromise;
      console.log("Response", { response: response.data });
      if (response.data.success) {
        setIsUploading(false);
        setIsUploadModalOpen(false); // Close the modal on success
        revalidator.revalidate();
      }
    } catch (error) {
      console.error('Error uploading file', error);
    } finally {
      setIsUploading(false);
    }
  };

  return (
    <div className="max-w-6xl mx-auto p-6">
        {previewFileId && (
          <FilePreviewModal fileUrl={previewFileId} onClose={closePreview} />
        )}
        {isUploadModalOpen && (
          <FileUploadModal
            isOpen={isUploadModalOpen}
            onClose={() => {
              setIsUploadModalOpen(false);
              setValidationErrors([]);
            }}
            onUpload={handleFileUpload}
            validationErrors={validationErrors}
          />
        )}
        {isUploading && (
          <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex min-h-full items-center justify-center p-4">
              <div className="relative w-full max-w-md">
                <div className="bg-white rounded-lg p-6">
                  <p className="text-center text-lg font-semibold text-gray-900">Uploading...</p>
                </div>
              </div>
            </div>
          </div>
        )}
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Files</h1>
          <button
            onClick={() => setIsUploadModalOpen(true)}
            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
          >
            Upload File
          </button>
      </div>

      {Object.entries(groupedFiles).map(([type, files]) => (
        <FileGroup
          key={type}
          title={type}
          files={files}
          setPreviewFile={setPreviewFileId}
        />
      ))}
    </div>
  );
}

function FileGroup({ title, files, setPreviewFile }: FileGroupProps) {
  const openModal = (fileUrl: string) => {
    setPreviewFile(fileUrl);
  };
  return (
    <div className="mb-8">
      <h2 className="text-lg font-semibold text-gray-900 mb-4">{title}</h2>
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {files.map((file) => (
          <FileItem key={file.id} file={file} onPreview={openModal} />
        ))}
      </div>
    </div>
  );
}

function FileItem({ file, onPreview }: FileItemProps) {
  let icon = null;
  if (file.mime_type === 'application/pdf') {
    icon = (
      <div className="bg-red-100 p-2 rounded-md">
        <svg className="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
    );
  } else if (file.mime_type?.startsWith('image/')) {
    icon = (
      <div className="bg-blue-100 p-2 rounded-md">
        <svg className="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
    );
  } else {
    icon = (
      <div className="bg-gray-100 p-2 rounded-md">
        <svg className="h-6 w-6 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
    );
  }

  const revalidator = useRevalidator();
  const [isDeleting, setIsDeleting] = useState(false);
  const [showDeleteConfirm, setShowDeleteConfirm] = useState(false);

  const handleDelete = async () => {
    setShowDeleteConfirm(false);
    setIsDeleting(true);
    
    const deletePromise = api.delete(`/api/files/${file.id}`);
    
    toast.promise(
      deletePromise,
      {
        loading: 'Deleting file...',
        success: 'File deleted successfully!',
        error: 'Failed to delete file. Please try again.',
      }
    );
    
    try {
      await deletePromise;
      revalidator.revalidate();
    } catch (error) {
      console.error('Error deleting file', error);
    } finally {
      setIsDeleting(false);
    }
  };

  return (
    <div className="bg-white rounded-lg shadow overflow-hidden border border-gray-200">
      <div className="p-4">
        <div className="flex items-start">
          <div className="flex-shrink-0">
            {icon}
          </div>
          <div className="ml-4 flex-1">
            <h3 className="font-medium text-gray-900 truncate">{file.name}</h3>
            <p className="text-sm text-gray-500 truncate">
              {formatFileSize(file.size)} • {formatDate(file.created_at)}
            </p>
          </div>
          <div className="ml-2 flex-shrink-0 flex">
            <button
              type="button"
              className="inline-flex items-center p-1 border border-transparent rounded-full text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <svg
                className="h-5 w-5"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>
      <div className="bg-gray-50 px-4 py-3 flex justify-end">
        <button
          type="button"
          onClick={() => onPreview(file.id.toString())}
          className="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          Preview
        </button>
        <button
          type="button"
          onClick={() => setShowDeleteConfirm(true)}
          className="ml-2 inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
        >
          Delete
        </button>
      </div>

      {showDeleteConfirm && createPortal(
        <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center">
          <div className="bg-white rounded-lg p-6 max-w-sm w-full">
            <h3 className="text-lg font-medium mb-4">Delete File</h3>
            <p className="mb-4">Are you sure you want to delete {file.name}? This action cannot be undone.</p>
            <div className="flex justify-end space-x-3">
              <button
                type="button"
                className="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                onClick={() => setShowDeleteConfirm(false)}
              >
                Cancel
              </button>
              <button
                type="button"
                className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                onClick={handleDelete}
                disabled={isDeleting}
              >
                {isDeleting ? 'Deleting...' : 'Delete'}
              </button>
            </div>
          </div>
        </div>,
        document.body
      )}
    </div>
  );
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} bytes`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(2)} KB`;
  if (bytes < 1024 * 1024 * 1024) return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
  return `${(bytes / (1024 * 1024 * 1024)).toFixed(2)} GB`;
}

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}
