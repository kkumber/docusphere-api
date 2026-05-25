# Docusphere API

Docusphere is a web-based document tracking and monitoring system specifically designed for DepEd Makati. This repository serves as the backend API for the platform, facilitating efficient document lifecycle management, automated tracking, and secure inter-departmental workflows.

Developed as a capstone project, Docusphere addresses the need for a centralized, digital repository to streamline the handling of official documents, reduce processing time, and ensure accountability through a comprehensive audit trail.

## Core Features

- **Document Lifecycle Management**: Comprehensive support for document creation, assignment, and status monitoring.
- **Role-Based Access Control (RBAC)**: Secure access management for various administrative and operational roles (SDS, Chief, Staff, Admin, Records).
- **Automated Document Tracking**: Real-time tracking of document actions including acknowledgments, approvals, signatures, and returns.
- **Electronic Signature Integration**: Capability to digitally sign and validate official documents.
- **Advanced Notification System**: System-generated alerts for document assignments, status changes, and pending actions.
- **Reporting and Analytics**: Automated generation of monthly reports and data-driven insights via a centralized dashboard.
- **Audit Logging**: Detailed historical logging of all document interactions for transparency and accountability.

## Tech Stack

- **Framework**: [Laravel 12](https://laravel.com/)
- **Language**: [PHP 8.2+](https://www.php.net/)
- **Authentication**: [Laravel Sanctum](https://laravel.com/docs/sanctum)
- **Database**: MySQL / PostgreSQL
- **Media Storage**: [Cloudinary](https://cloudinary.com/)
- **PDF Handling**: TCPDF / FPDI
- **Authorization**: [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/)
- **Testing**: [Pest PHP](https://pestphp.com/)

## System Requirements

- PHP >= 8.2
- Composer
- MySQL 8.0+ or PostgreSQL
- Cloudinary Account (for file uploads)

## Installation

### 1. Clone the Repository
```bash
git clone https://github.com/kkumber/docusphere-api.git
cd docusphere-api
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Configuration
Copy the example environment file and configure your local settings:
```bash
cp .env.example .env
```
Update the `.env` file with your database credentials and Cloudinary API keys.

### 4. Application Key Generation
```bash
php artisan key:generate
```

### 5. Database Migration and Seeding
```bash
php artisan migrate --seed
```

### 6. Start the Development Server
```bash
php artisan serve
```

## Default Accounts

The database seeder provides several pre-configured accounts for testing and demonstration purposes:

| Role    | Email                  | Password |
|---------|------------------------|----------|
| Admin   | docusphere@admin.com   | password |
| Records | docusphere@records.com | password |
| SDS     | docusphere@sds.com     | password |
| Chief   | docusphere@chief.com   | password |
| Staff   | docusphere@staff.com   | password |

## Docker Setup

The project includes Docker configuration for streamlined deployment and development.

### Using Docker Compose
```bash
docker-compose up -d
```

## Testing

The project uses Pest PHP for testing. To execute the test suite, run:
```bash
php artisan test
```

## Frontend Repository

The frontend application for Docusphere is maintained in a separate repository.
(https://github.com/kkumber/docusphere-fe)

## License

This project is developed for academic purposes as a capstone project. All rights reserved.
