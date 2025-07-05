import { Link } from '@remix-run/react';
import { ChartBarIcon, DocumentTextIcon, PhotoIcon, ArrowUpTrayIcon } from '@heroicons/react/24/outline';
import { useLoaderData } from "@remix-run/react";
import type { LoaderFunction } from "@remix-run/node";
import { json } from "@remix-run/node";
import api from "~/utils/api";
import type { GroupedFiles, FileModel, ApiResponse } from "~/types/api";

type DashboardStats = {
  total_files: number;
  total_size: number;
  recent_files: FileModel[];
};

export const loader: LoaderFunction = async () => {
  try {
    const response = await api.get<ApiResponse<{ grouped_files: GroupedFiles }>>("/api/files");
    const groupedFiles = response.data.data.grouped_files;

    // Flatten all files
    const allFiles = Object.values(groupedFiles).flat();

    // Calculate total files
    const total_files = allFiles.length;

    // Calculate total size
    const total_size = allFiles.reduce((sum, file) => sum + file.size, 0);

    // Get recent files (sort by created_at descending and take 5)
    const recent_files = [...allFiles]
      .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
      .slice(0, 5);

    return json({
      total_files,
      total_size,
      recent_files,
    });
  } catch (error) {
    console.error("Failed to load dashboard stats", error);
    return json({
      error: "Failed to load dashboard data. Please try again later.",
    });
  }
};

export default function DashboardHome() {
  const data = useLoaderData<DashboardStats>();
  
  // If there's an error, display it
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
                <span className="font-medium">Error loading dashboard data</span> - {data.error}
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }

  const { total_files, total_size, recent_files } = data;

  return (
    <div className="py-8">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
        <div className="mb-10">
          <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
          <p className="mt-2 text-gray-600">Welcome back! Here's what's happening with your files.</p>
        </div>
        
        {/* Stats Section */}
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-10">
          <div className="bg-white overflow-hidden shadow rounded-lg">
            <div className="px-4 py-5 sm:p-6">
              <div className="flex items-center">
                <div className="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                  <DocumentTextIcon className="h-6 w-6 text-white" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 truncate">Total Files</dt>
                    <dd className="flex items-baseline">
                      <div className="text-2xl font-semibold text-gray-900">{total_files}</div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-white overflow-hidden shadow rounded-lg">
            <div className="px-4 py-5 sm:p-6">
              <div className="flex items-center">
                <div className="flex-shrink-0 bg-green-500 rounded-md p-3">
                  <PhotoIcon className="h-6 w-6 text-white" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 truncate">Images</dt>
                    <dd className="flex items-baseline">
                      <div className="text-2xl font-semibold text-gray-900">{recent_files.filter(file => file.name.endsWith('.jpg') || file.name.endsWith('.png')).length}</div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-white overflow-hidden shadow rounded-lg">
            <div className="px-4 py-5 sm:p-6">
              <div className="flex items-center">
                <div className="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                  <ChartBarIcon className="h-6 w-6 text-white" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 truncate">Storage Used</dt>
                    <dd className="flex items-baseline">
                      <div className="text-2xl font-semibold text-gray-900">{formatFileSize(total_size)}</div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div className="bg-white overflow-hidden shadow rounded-lg">
            <div className="px-4 py-5 sm:p-6">
              <div className="flex items-center">
                <div className="flex-shrink-0 bg-purple-500 rounded-md p-3">
                  <ArrowUpTrayIcon className="h-6 w-6 text-white" />
                </div>
                <div className="ml-5 w-0 flex-1">
                  <dl>
                    <dt className="text-sm font-medium text-gray-500 truncate">Last Upload</dt>
                    <dd className="text-sm font-medium text-gray-900">{formatDate(recent_files[0].created_at)}</dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Action Cards */}
        <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
          <div className="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl overflow-hidden shadow-lg">
            <div className="p-6">
              <h3 className="text-lg font-medium text-gray-900 mb-2">Upload New Files</h3>
              <p className="text-gray-600 mb-4">Add documents, images, or other files to your storage</p>
              <Link 
                to="/files" 
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                Upload Files
              </Link>
            </div>
          </div>

          <div className="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl overflow-hidden shadow-lg">
            <div className="p-6">
              <h3 className="text-lg font-medium text-gray-900 mb-2">Browse Your Files</h3>
              <p className="text-gray-600 mb-4">View, manage, and organize all your stored files</p>
              <Link 
                to="/files" 
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              >
                View Files
              </Link>
            </div>
          </div>

          <div className="bg-gradient-to-br from-purple-50 to-fuchsia-50 rounded-xl overflow-hidden shadow-lg">
            <div className="p-6">
              <h3 className="text-lg font-medium text-gray-900 mb-2">Activity Insights</h3>
              <p className="text-gray-600 mb-4">View usage statistics and file access patterns</p>
              <button className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 opacity-50 cursor-not-allowed">
                Coming Soon
              </button>
            </div>
          </div>
        </div>
      </div>
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
  return date.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: 'numeric' });
}
