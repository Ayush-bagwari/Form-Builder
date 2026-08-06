# Form Builder

A dynamic web application built with Laravel 11, Livewire 3, Alpine.js, and Tailwind CSS. It allows users to visually create forms, generate forms using AI (Google Gemini API), and import existing forms from Word (.docx) or Excel (.xlsx) files.

---

## Features

- **Drag & Drop Builder**: Create and edit forms visually or modify raw JSON schemas directly.
- **AI Form Generator**: Generate complete forms from simple text prompts using Google Gemini API.
- **AI Form Assistant**: Modify existing forms with prompt instructions (e.g., "Add emergency contact section", "Make phone required").
- **Import from Word & Excel**: Upload `.docx` or `.xlsx` files to extract fields with interactive type preview & field mapping.
- **Public Form Links**: Share forms via unique public URLs (`/f/{slug}`) with rate limiting and file uploads.
- **Submissions & Analytics**: View response dashboards, filter submissions, and export data as CSV.

---

## Tech Stack

- **Framework**: Laravel 11 (PHP 8.2+)
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS, jQuery `formBuilder`
- **AI Provider**: Google Gemini API (`gemini-1.5-flash`)
- **Document Parsers**: PhpOffice (`phpword`, `phpspreadsheet`)
- **Database**: MySQL (SQLite for testing)
- **Testing**: PHPUnit (38 automated tests)

---

## Setup & Installation

1. Clone the repository and install dependencies:
   ```bash
   git clone https://github.com/Ayush-bagwari/Form-Builder.git
   cd form-builder
   composer install --ignore-platform-req=ext-gd
   npm install && npm run build
   ```

2. Set up environment configuration:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Update `.env` with your database credentials and Gemini API key:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=root
   DB_PASSWORD=

   GEMINI_API_KEY=your_gemini_api_key_here
   GEMINI_MODEL=gemini-1.5-flash
   QUEUE_CONNECTION=sync
   ```

4. Run database migrations:
   ```bash
   php artisan migrate
   ```

5. Run test suite:
   ```bash
   php vendor/bin/phpunit
   ```

6. Start the local server:
   ```bash
   php artisan serve
   ```
