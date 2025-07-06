# AnchorLess VISA Dossier

![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![React](https://img.shields.io/badge/React-18.x-61DAFB?style=for-the-badge&logo=react&logoColor=black)
![Remix](https://img.shields.io/badge/Remix-2.x-000000?style=for-the-badge&logo=remix&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)

A full-stack application for VISA Dossier management, featuring file uploads, categorization, and management. This project consists of a Laravel backend API and a React frontend built with Remix.

## 📋 Project Overview

This application allows users to:
- Upload files (PDF, PNG, JPG) up to 4MB
- View uploaded files grouped by file type
- Preview files directly in the browser
- Delete files when no longer needed

## 🚀 Getting Started

### Prerequisites

- PHP 8.1 or higher
- Composer
- Node.js 16+ and npm
- SQLite (default) or MySQL/PostgreSQL

## 🔧 Installation

### Clone the Repository

```bash
git clone https://github.com/anabeto93/anchorless-dossier.git
cd anchorless-dossier
```

### Backend Setup

1. Navigate to the backend directory:
   ```bash
   cd backend
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Create environment file and generate application key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure the database in `.env`:
   ```
   DB_CONNECTION=sqlite
   # Create an empty database file
   touch database/database.sqlite
   ```

5. Run migrations and seed the database:
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. Create a symbolic link for file storage:
   ```bash
   php artisan storage:link
   ```

7. Start the Laravel development server:
   ```bash
   php artisan serve --port=2027
   ```
   The API will be available at http://localhost:2027

8. Open a new terminal and navigate to the backend directory:
   ```bash
   cd ../backend
   ```
   You will be running queue workers in this terminal:
   ```bash
   php artisan queue:work
   ```

9. This last step is to provide you the necessary credentials for the frontend. In another terminal, run the migrations and seeders:
   ```bash
   php artisan migrate:fresh --seed
   ```
Make sure to copy the token that is generated in the terminal and paste it in the .env file of the frontend.

### Frontend Setup

1. Open a new terminal and navigate to the frontend directory:
   ```bash
   cd ../frontend
   ```

2. Install Node.js dependencies:
   ```bash
   npm install
   ```

3. Create environment file:
   ```bash
   cp .env.example .env
   ```

4. Configure the API URL and authentication token in `.env`:
   ```bash
   VITE_API_URL=http://localhost:2027
   VITE_API_AUTH_TOKEN=your_token_from_step_9_backend
   ```

5. Start the development server:
   ```bash
   npm run dev
   ```
   The frontend will be available at http://localhost:5170 or any other available port, check for it in the terminal.

## 🧪 Testing the Application

Explore the application by uploading files, previewing them, and deleting them.

### Backend Testing

Explore the backend API by using tools like Postman or curl.

For the TDD tests, you can go to the backend directory and run the phpunit tests
```bash
./vendor/bin/pest
```

OR

```bash
./vendor/bin/pest --coverage
```

OR

```bash
php artisan test
```

## 📝 License 🤔

Hmm, let's see... This project is proprietary until July 15th, 2025, after which it will magically transform into open-sourced software under the [MIT license](https://opensource.org/licenses/MIT).

Until then, it's mine, all mine! 😉