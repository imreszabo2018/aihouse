# Invoice Processor

A Docker-based invoice processing application built with Laravel, React, TypeScript, and MySQL.

The application allows users to upload PDF invoices, extract invoice data, manage business partners, and review invoice statistics through a dashboard.

## Features

- Upload PDF invoices through the web interface.
- Upload multiple PDF files in one operation.
- Process uploaded invoices independently so that an individual upload failure does not interrupt the remaining files.
- Extract invoice information, including invoice number, seller, buyer, tax numbers, dates, currency, and gross amount.
- Store invoice and partner data in a MySQL database.
- Retrieve and cache official Hungarian National Bank (MNB) exchange rates.
- Convert invoice amounts to HUF using the exchange rate applicable to the fulfillment date.
- Display invoice details, including the original amount, currency, applied exchange rate, exchange-rate date, and HUF amount.
- Display monthly incoming and outgoing cash flow.
- Display partner-level incoming and outgoing totals.

## Technology Stack

### Backend
- PHP 8.3
- Laravel 13
- MySQL 8.4
- Laravel Sanctum
- Smalot PDF Parser

### Frontend
- React 19
- TypeScript
- Vite
- React Router
- Recharts

### Infrastructure
- Docker
- Docker Compose

## Requirements

- Docker Desktop
- Docker Compose
- Git

No local PHP, Composer, Node.js, or MySQL installation is required.

## Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/imreszabo2018/aihouse.git
cd aihouse
```

### 2. Configure environment variables

Copy the root environment example:

```bash
cp .env.example .env
```

The default values are suitable for local development. Change the passwords before using the application in a shared or production environment.

### 3. Start the application

```bash
docker compose up --build -d
```

Docker Compose starts the database, Laravel backend, and React frontend.

On the first startup, the backend container installs Composer dependencies, generates the Laravel application key, and runs database migrations.

## Testing

All commands should be executed from the project root directory while Docker containers are running.

### Backend tests

```bash
docker compose exec backend php artisan test
```

### Frontend tests

```bash
docker compose exec frontend npm test -- --run
```

### Frontend production build

```bash
docker compose exec frontend npm run build
```

## Application URLs

- Frontend: http://localhost:5173
- Backend API: http://localhost:8000
- Health check: http://localhost:8000/api/health

## Stopping the Application

To stop the containers without deleting stored data:

```bash
docker compose down
```

To remove the database and other Docker volumes as well:

```bash
docker compose down -v
```

**Warning:** The second command permanently deletes the database volume and its stored data.

## AI Integration

The application supports optional invoice data extraction using OpenRouter.

Configure the following variables in `backend/.env`:

```dotenv

OPENROUTER_API_KEY=
OPENROUTER_MODEL=openrouter/free
OPENROUTER_BASE_URL=https://openrouter.ai/api/v1

```

An OpenRouter API key is required to use the AI-powered extraction feature. The rest of the application can be started without configuring an API key.

Never commit API keys or other secrets to the repository.

## Project Structure

aihouse/
├── backend/              # Laravel API
├── frontend/             # React application
├── docker/
│   └── php/              # PHP Docker configuration
├── .env.example          # Root environment template
├── .gitattributes        # Git line-ending configuration
├── docker-compose.yml    # Application services
├── AI_NAPLO.md           # AI development log
└── README.md

```