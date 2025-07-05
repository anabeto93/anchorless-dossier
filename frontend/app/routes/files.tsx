import { useLoaderData } from "@remix-run/react";
import type { LoaderFunction } from "@remix-run/node";
import { json } from "@remix-run/node";
import api from "~/utils/api";
import type { GroupedFiles, FileModel, ApiResponse } from "~/types/api";

type LoaderData = {
  groupedFiles: GroupedFiles;
  apiUrl: string;
} | { 
  error: string 
};

export const loader: LoaderFunction = async () => {
  try {
    const response = await api.get<ApiResponse<{ grouped_files: GroupedFiles }>>("/api/files");
    return json({ 
      groupedFiles: response.data.data.grouped_files,
      apiUrl: process.env.API_URL || ""
    });
  } catch (error) {
    console.error("Failed to load files", error);
    return json({ 
      error: "Failed to load files. Please try again later." 
    }, { status: 500 });
  }
};

export default function FilesPage() {
  const data = useLoaderData<LoaderData>();
  
  if ('error' in data) {
    return (
      <div className="max-w-6xl mx-auto p-6">
        <div className="bg-red-50 border-l-4 border-red-500 p-4 rounded">
          <div className="flex">
            <div className="flex-shrink-0">
              <svg className="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
              </svg>
            </div>
            <div className="ml-3">
              <p className="text-sm text-red-700">
                <span className="font-medium">Error loading files</span> - {data.error}
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  const { groupedFiles, apiUrl } = data;

  return (
    <div className="max-w-6xl mx-auto p-6">
      <div className="flex justify-between items-center mb-8">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Your Files</h1>
          <p className="text-gray-500 mt-2">All your uploaded files organized by type</p>
        </div>
        <button className="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
          Upload New File
        </button>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        {Object.entries(groupedFiles).map(([type, files]) => (
          <FileGroup 
            key={type} 
            title={`${type} Files`} 
            files={files} 
            apiUrl={apiUrl}
          />
        ))}
        
        {Object.keys(groupedFiles).length === 0 && (
          <div className="text-center py-16">
            <svg className="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
            </svg>
            <h3 className="mt-2 text-lg font-medium text-gray-900">No files yet</h3>
            <p className="mt-1 text-sm text-gray-500">Get started by uploading your first file</p>
            <div className="mt-6">
              <button className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                Upload File
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

function FileGroup({ title, files, apiUrl }: { title: string; files?: FileModel[]; apiUrl: string }) {
  const fileItems = files || [];
  
  return (
    <div className="mb-8">
      <h2 className="text-xl font-bold mb-4">{title}</h2>
      {fileItems.length > 0 ? (
        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          {fileItems.map((file) => (
            <FileCard key={file.id} file={file} apiUrl={apiUrl} />
          ))}
        </div>
      ) : (
        <p className="text-gray-500 italic">No files in this category</p>
      )}
    </div>
  );
}

function FileCard({ file, apiUrl }: { file: FileModel; apiUrl: string }) {
  return (
    <div className="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition-shadow">
      {/* Preview section */}
      {file.preview_url && (
        <div className="mb-3">
          <img 
            src={file.preview_url} 
            alt={`Preview of ${file.name}`} 
            className="w-full h-32 object-contain rounded"
          />
        </div>
      )}
      
      <div className="flex justify-between items-start">
        <div>
          <h3 className="font-medium text-gray-900">{file.name}</h3>
          <p className="text-sm text-gray-500">{formatFileSize(file.size)}</p>
          <p className="text-xs text-gray-400">{formatDate(file.created_at)}</p>
        </div>
        <div className="flex space-x-2">
          <a 
            href={`${apiUrl}${file.path}`} 
            download={file.name}
            className="text-blue-600 hover:text-blue-800"
          >
            Download
          </a>
          <button className="text-red-600 hover:text-red-800">Delete</button>
        </div>
      </div>
    </div>
  );
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} bytes`;
  if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / 1048576).toFixed(1)} MB`;
}

function formatDate(dateString: string): string {
  return new Date(dateString).toLocaleDateString();
}
