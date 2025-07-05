import { Outlet } from '@remix-run/react';
import { UserCircleIcon, CogIcon, BellIcon, HomeIcon, FolderIcon } from '@heroicons/react/24/outline';

export default function DashboardLayout() {
  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">
      {/* Top Navigation */}
      <header className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            <div className="flex items-center">
              <h1 className="text-xl font-bold text-gray-900">Anchorless Dossier</h1>
            </div>
            <div className="flex items-center space-x-4">
              <button className="p-1 rounded-full text-gray-500 hover:text-gray-700">
                <BellIcon className="h-6 w-6" />
              </button>
              <button className="p-1 rounded-full text-gray-500 hover:text-gray-700">
                <CogIcon className="h-6 w-6" />
              </button>
              <div className="flex items-center">
                <UserCircleIcon className="h-8 w-8 text-gray-500" />
                <span className="ml-2 text-sm font-medium text-gray-700">User Name</span>
              </div>
            </div>
          </div>
        </div>
      </header>

      <div className="flex flex-1">
        {/* Sidebar */}
        <aside className="hidden md:block w-64 bg-white border-r border-gray-200 pt-5">
          <nav className="px-2 space-y-1">
            <a
              href="/"
              className="text-gray-600 hover:bg-gray-50 group flex items-center px-3 py-2 text-sm font-medium rounded-md"
            >
              <HomeIcon className="mr-3 h-5 w-5 text-gray-500" />
              <span className="truncate">Dashboard</span>
            </a>
            <a
              href="/files"
              className="bg-gray-100 text-gray-900 group flex items-center px-3 py-2 text-sm font-medium rounded-md"
            >
              <FolderIcon className="mr-3 h-5 w-5 text-gray-500" />
              <span className="truncate">Files</span>
            </a>
            <a
              href="/analytics"
              className="text-gray-600 hover:bg-gray-50 group flex items-center px-3 py-2 text-sm font-medium rounded-md"
            >
              <span className="truncate">Analytics (Coming Soon)</span>
            </a>
            <a
              href="/settings"
              className="text-gray-600 hover:bg-gray-50 group flex items-center px-3 py-2 text-sm font-medium rounded-md"
            >
              <span className="truncate">Settings (Coming Soon)</span>
            </a>
          </nav>
        </aside>

        {/* Main Content */}
        <main className="flex-1">
          <div className="py-6">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
              <Outlet />
            </div>
          </div>
        </main>
      </div>
    </div>
  );
}
