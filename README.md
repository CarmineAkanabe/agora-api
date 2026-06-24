# Agora API

> **Agora** is a verified campus peer-to-peer marketplace REST API built with Laravel 13. It enables enrolled university students to buy and sell items securely within a closed, identity-verified community. The platform features escrow-based payments via Campay/local payment mode (MTN Mobile Money / Orange Money), pickup code delivery verification, dispute resolution, and a comprehensive admin panel.

---

## Table of Contents

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Environment Configuration](#environment-configuration)
- [Database Setup](#database-setup)
- [Storage Setup](#storage-setup)
- [Queue & Scheduler](#queue--scheduler)
- [Running the Server](#running-the-server)
- [Architecture Overview](#architecture-overview)
- [Key Concepts](#key-concepts)
- [Dependencies](#dependencies)
- [Project Structure](#project-structure)

---

## Overview

Agora solves the problem of unstructured, unverified campus trading that typically happens through WhatsApp groups and word of mouth. By requiring students to submit their matricule and a student ID card photo for admin verification, the platform ensures that every user is a real enrolled student.

Core features:
- Student identity verification with admin approval flow
- Product listings with multi-image support
- Purchase request flow with seller approval
- Escrow payments via Campay, with local demo mode available
- 6-digit pickup code delivery verification
- 48-hour auto-release safety net for escrow
- In-app database notifications throughout all flows
- Verification email notifications via Mailtrap
- Review and rating system for sellers
- Dispute management for contested transactions
- Full admin panel for platform moderation
- Redis caching for performance
- Role-based access control with Laravel Sanctum

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.4 |
| Framework | Laravel 13 |
| Database | PostgreSQL 18.0 |
| Cache / Queue | Redis via Memurai |
| Authentication | Laravel Sanctum |
| Payment Gateway | Campay for MTN/Orange Mobile Money; local demo mode available |
| Mail | Mailtrap (SMTP sandbox) |
| Image Processing | Intervention Image for Laravel |
| Search & Filter | Spatie Laravel Query Builder |
| Debugging | Laravel Telescope (dev only) |

---

## Requirements

- PHP >= 8.4
- PostgreSQL >= 18.0
- Memurai (Redis for Windows) — latest version
- Composer
- A Mailtrap account (free sandbox)
- A Campay app token or Campay app username/password for live provider testing

---

## Installation

**1. Clone the repository**
```bash
git clone https://github.com/your-username/agora-api.git
cd agora-api
```

**2. Install PHP dependencies**
```bash
composer install
```

**3. Copy environment file**
```bash
cp .env.example .env
```

**4. Generate application key**
```bash
php artisan key:generate
```

---

## Environment Configuration

Open `.env` and configure the following:

```env
APP_NAME=Agora
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=agora
DB_USERNAME=postgres
DB_PASSWORD=your_postgres_password

# Cache, Queue, Session — all via Redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# Redis (Memurai)
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0
REDIS_CACHE_DB=2

# Mail (Mailtrap sandbox)
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=no-reply@agora.app
MAIL_FROM_NAME="Agora"

# Payment Mode
# local confirms payments internally for demos and development.
# campay uses the external Campay API.
PAYMENT_DRIVER=local

# Campay Payment Gateway
CAMPAY_BASE_URL=https://demo.campay.net
CAMPAY_TOKEN=
CAMPAY_USERNAME=
CAMPAY_PASSWORD=
```

---

## Database Setup

**1. Create the PostgreSQL database**

Open your PostgreSQL client (pgAdmin or psql) and run:
```sql
CREATE DATABASE agora;
```

**2. Run migrations**
```bash
php artisan migrate
```

**3. Seed the database**
```bash
php artisan db:seed
```

This seeds:
- 2 admin accounts
- 6 product categories
- 3 verified student accounts

**Seeded admin credentials:**
| Email | Password |
|---|---|
| admin1@agora.app | admin1234 |
| admin2@agora.app | admin1234 |

**Seeded student credentials:**
| Email | Password |
|---|---|
| alice@agora.app | student1234 |
| bob@agora.app | student1234 |
| clara@agora.app | student1234 |

> All seeded students are pre-approved. New registrations go through the manual admin verification flow.

---

## Storage Setup

Agora stores uploaded files (student ID cards, profile pictures, listing images) on the local filesystem.

**Create the storage symlink:**
```bash
php artisan storage:link
```

Uploaded files are stored under `storage/app/public/` and served via `public/storage/`.

**Storage directories created automatically:**
```
storage/app/public/id_cards/
storage/app/public/profile_pictures/
storage/app/public/listings/
```

---

## Queue & Scheduler

Agora uses Redis-backed queues for background jobs and scheduled commands.

**Start Memurai** (must be running before anything else):
```bash
net start memurai
```

Verify Memurai is running:
```bash
memurai-cli ping
# Expected: PONG
```

**Start the queue worker** (keep this terminal open during development):
```bash
php artisan queue:work
```

The queue handles:
- `PollPaymentStatusJob` - polls Campay when `PAYMENT_DRIVER=campay`
- `DisbursePaymentJob` - releases local escrow or triggers Campay withdrawal after pickup code verification
- `VerificationApprovedMail` / `VerificationRejectedMail` — queued mail delivery

**Start the scheduler** (keep this terminal open during development):
```bash
php artisan schedule:work
```

The scheduler runs:
- `requests:expire` — every 5 minutes, expires approved purchase requests past the 2-hour payment window
- `escrow:release` — every hour, auto-releases escrow funds held longer than 48 hours

**Run scheduled commands manually (for testing):**
```bash
php artisan requests:expire
php artisan escrow:release
```

---

## Running the Server

```bash
php artisan serve
```

API is available at: `http://localhost:8000/api`

You will need **three terminals** running simultaneously during development:

| Terminal | Command |
|---|---|
| 1 | `php artisan serve` |
| 2 | `php artisan queue:work` |
| 3 | `php artisan schedule:work` |

---

## Architecture Overview

Agora follows a strict layered architecture:

```
Request
  ↓
Middleware (ForceJsonResponse, Auth, EnsureStudentIsVerified, CheckIfBanned)
  ↓
FormRequest (validation firewall)
  ↓
Controller (traffic cop — no business logic)
  ↓
Policy (authorization check)
  ↓
Service (all business logic lives here)
  ↓
Model (Eloquent, relationships, casts)
  ↓
Resource (serialization filter — controls what the API exposes)
  ↓
JSON Response
```

**Key principle:** Controllers never contain business logic. They receive a validated request, check authorization, delegate to a Service, and return a Resource. Services are the only layer that touches models directly.

---

## Key Concepts

### User Roles
| Role | Description |
|---|---|
| `student` | Default role. Must complete profile and pass admin verification before full access. |
| `admin` | Pre-seeded only. Full platform access including moderation and reports. |

### Student Verification Flow
```
Register → Create Profile (upload matricule + ID card photo)
→ Admin reviews → Approved or Rejected
→ If approved: full student access + notification + email
→ If rejected: resubmit with corrected info
```

### Purchase & Escrow Flow
```
Buyer submits purchase request
→ Seller approves (2-hour payment window opens)
-> Buyer pays through Campay or local demo mode (MTN or Orange)
→ Funds held in platform escrow
→ Buyer receives 6-digit pickup code
→ They meet on campus, buyer shows code
→ Seller enters code in app → funds disbursed to seller
→ If no code entered within 48h → funds auto-released
```

### Escrow States
| Status | Meaning |
|---|---|
| `initiated` | Payment triggered; transient in local mode, waiting for Campay confirmation in campay mode |
| `held` | Payment confirmed, funds in escrow, pickup code generated |
| `released` | Pickup code verified, funds disbursed to seller |
| `refunded` | Funds returned to buyer |
| `failed` | Payment could not be processed |

### Payment Mode
Agora defaults to `PAYMENT_DRIVER=local` for demos and development. In local mode, initiating a payment immediately places the transaction in escrow, generates a 6-digit pickup code, and sets the 48-hour auto-release timer. Set `PAYMENT_DRIVER=campay` to collect payment through Campay and poll Campay until the transaction becomes successful or failed.

### Notifications
All in-app notifications are stored in the `notifications` table (Laravel database notifications). Emails are only sent for student verification approval and rejection events. Every other event (request approved, payment received, pickup code verified, dispute raised, etc.) uses database notifications only.

### Redis Caching
| Cache Key | TTL | Busted When |
|---|---|---|
| `categories` | 24 hours | Category created / updated / deleted |
| `listing:{id}` | 10 minutes | Listing updated / deleted |
| `seller:{id}:listings` | 10 minutes | Listing updated / deleted / toggled |
| `reports:overview` | 30 minutes | Key platform events |
| `reports:transactions` | 15 minutes | Escrow released |
| `reports:listings` | 15 minutes | Listing removed by admin |
| `reports:users` | 30 minutes | User banned / unbanned |
| `reports:student:{id}` | 10 minutes | Transaction completed |

---

## Dependencies

### Production
| Package | Purpose |
|---|---|
| `laravel/sanctum` | SPA token authentication |
| `predis/predis` | Redis client for PHP |
| `intervention/image-laravel` | Image resizing and processing |
| `spatie/laravel-query-builder` | Filterable, sortable API queries |

### Development
| Package | Purpose |
|---|---|
| `laravel/telescope` | Request, query, and job debugging |

---

## Project Structure

```
app/
├── Console/
│   └── Commands/
│       ├── ExpireStaleRequestsCommand.php
│       └── AutoReleaseEscrowCommand.php
├── Enums/
│   ├── UserRole.php
│   ├── VerificationStatus.php
│   ├── ListingCondition.php
│   ├── ListingStatus.php
│   ├── RequestStatus.php
│   ├── TransactionStatus.php
│   ├── PaymentMethod.php
│   └── DisputeStatus.php
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── VerificationController.php
│   │   │   ├── UserController.php
│   │   │   ├── ListingController.php
│   │   │   ├── DisputeController.php
│   │   │   └── ReportController.php
│   │   ├── AuthController.php
│   │   ├── StudentProfileController.php
│   │   ├── CategoryController.php
│   │   ├── ListingController.php
│   │   ├── ListingImageController.php
│   │   ├── PurchaseRequestController.php
│   │   ├── TransactionController.php
│   │   ├── PickupCodeController.php
│   │   ├── ReviewController.php
│   │   ├── DisputeController.php
│   │   └── NotificationController.php
│   ├── Middleware/
│   │   ├── ForceJsonResponse.php
│   │   ├── EnsureStudentIsVerified.php
│   │   └── CheckIfBanned.php
│   ├── Requests/
│   │   ├── Auth/
│   │   ├── Listings/
│   │   ├── PurchaseRequests/
│   │   ├── Transactions/
│   │   ├── Reviews/
│   │   ├── Disputes/
│   │   ├── Admin/
│   │   └── Profile/
│   └── Resources/
│       ├── UserResource.php
│       ├── StudentProfileResource.php
│       ├── CategoryResource.php
│       ├── ListingResource.php
│       ├── ListingCollection.php
│       ├── ListingImageResource.php
│       ├── PurchaseRequestResource.php
│       ├── TransactionResource.php
│       ├── ReviewResource.php
│       ├── DisputeResource.php
│       └── NotificationResource.php
├── Jobs/
│   ├── PollPaymentStatusJob.php
│   └── DisbursePaymentJob.php
├── Mail/
│   ├── VerificationApprovedMail.php
│   └── VerificationRejectedMail.php
├── Models/
│   ├── User.php
│   ├── StudentProfile.php
│   ├── Category.php
│   ├── Listing.php
│   ├── ListingImage.php
│   ├── PurchaseRequest.php
│   ├── Transaction.php
│   ├── Review.php
│   └── Dispute.php
├── Notifications/
│   ├── VerificationApprovedNotification.php
│   ├── VerificationRejectedNotification.php
│   ├── PurchaseRequestApprovedNotification.php
│   ├── PurchaseRequestRejectedNotification.php
│   ├── PaymentInitiatedNotification.php
│   ├── PaymentHeldNotification.php
│   ├── PaymentFailedNotification.php
│   ├── PickupCodeVerifiedNotification.php
│   ├── EscrowReleasedNotification.php
│   ├── DisputeRaisedNotification.php
│   ├── DisputeResolvedNotification.php
│   └── AccountBannedNotification.php
├── Policies/
│   ├── ListingPolicy.php
│   ├── PurchaseRequestPolicy.php
│   ├── TransactionPolicy.php
│   ├── ReviewPolicy.php
│   └── DisputePolicy.php
└── Services/
    ├── AuthService.php
    ├── StudentVerificationService.php
    ├── ListingService.php
    ├── PurchaseRequestService.php
    ├── PaymentService.php
    ├── EscrowService.php
    ├── PickupCodeService.php
    ├── NotificationService.php
    ├── ReviewService.php
    ├── DisputeService.php
    └── ReportService.php

database/
├── migrations/
├── seeders/
└── factories/

routes/
├── api.php
└── console.php

storage/
└── app/
    └── public/
        ├── id_cards/
        ├── profile_pictures/
        └── listings/
```
