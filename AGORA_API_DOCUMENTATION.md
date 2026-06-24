# Agora API Documentation

> Complete reference for integrating with the Agora Campus Marketplace API. Intended for frontend developers and external applications consuming this API.

**Base URL:** `http://localhost:8000/api`  
**Response Format:** JSON (all responses)  
**Authentication:** Laravel Sanctum — Bearer token in `Authorization` header

---

## Table of Contents

- [Authentication](#authentication)
- [Authorization & Roles](#authorization--roles)
- [Request Format](#request-format)
- [Response Format](#response-format)
- [Error Handling](#error-handling)
- [Endpoints](#endpoints)
  - [Auth](#auth-endpoints)
  - [Student Profile](#student-profile-endpoints)
  - [Categories](#category-endpoints)
  - [Listings](#listing-endpoints)
  - [Listing Images](#listing-image-endpoints)
  - [Purchase Requests](#purchase-request-endpoints)
  - [Transactions](#transaction-endpoints)
  - [Pickup Code](#pickup-code-endpoints)
  - [Reviews](#review-endpoints)
  - [Disputes](#dispute-endpoints)
  - [Notifications](#notification-endpoints)
  - [Dashboard](#dashboard-endpoints)
  - [Admin](#admin-endpoints)
- [Enums Reference](#enums-reference)
- [Resource Shapes](#resource-shapes)
- [Middleware & Guards](#middleware--guards)
- [Notification Types](#notification-types)
- [File Uploads](#file-uploads)
- [Pagination](#pagination)
- [Caching Behaviour](#caching-behaviour)
- [Rate Limiting](#rate-limiting)

---

## Authentication

Agora uses **Laravel Sanctum** token authentication.

After login or registration, the API returns a `token`. Send this token on every authenticated request:

```
Authorization: Bearer {token}
```

Tokens are revoked on logout. Only one active token per user is allowed — logging in from a new session invalidates all previous tokens.

---

## Authorization & Roles

| Role | Description |
|---|---|
| `student` | Default role for all registrations. Requires profile verification before full access. |
| `admin` | Pre-seeded only. No registration flow. Full platform access. |

**Student access levels:**

| Level | Condition | Access |
|---|---|---|
| Unauthenticated | No token | Public listings, categories, seller profiles |
| Authenticated + Unverified | Token, no approved profile | Profile creation and update only |
| Authenticated + Verified | Token + approved profile | Full student features |
| Banned | Token + is_banned = true | Blocked on all routes including public ones |

---

## Request Format

- **JSON body:** Set `Content-Type: application/json`
- **File uploads:** Set `Content-Type: multipart/form-data`
- **All requests must include:** `Accept: application/json` (handled automatically by `ForceJsonResponse` middleware — but include it anyway for safety)

---

## Response Format

All responses follow this general shape:

**Single resource:**
```json
{
  "id": 1,
  "field": "value"
}
```

**Collection:**
```json
[
  { "id": 1, "field": "value" },
  { "id": 2, "field": "value" }
]
```

**Paginated collection:**
```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 12,
    "total": 34
  }
}
```

**Success message:**
```json
{
  "message": "Action completed successfully."
}
```

---

## Error Handling

| HTTP Status | Meaning |
|---|---|
| `200` | Success |
| `201` | Resource created |
| `401` | Unauthenticated — missing or invalid token |
| `403` | Unauthorized — valid token but insufficient permissions, or account banned |
| `404` | Resource not found |
| `409` | Conflict — resource already exists |
| `422` | Validation error |
| `429` | Too many requests — rate limit exceeded |
| `500` | Server error |

**Validation error shape (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message here."]
  }
}
```

---

## Endpoints

---

### Auth Endpoints

#### Register
```
POST /auth/register
```
No authentication required.

**Body:**
```json
{
  "name": "Alice Mboua",
  "email": "alice@agora.app",
  "password": "password",
  "password_confirmation": "password"
}
```

> `role` field is prohibited. All registrations default to `student`.

**Response `201`:**
```json
{
  "user": { ...UserResource },
  "token": "1|abc123..."
}
```

---

#### Login
```
POST /auth/login
```
No authentication required.

**Body:**
```json
{
  "email": "alice@agora.app",
  "password": "password"
}
```

**Response `200`:**
```json
{
  "user": { ...UserResource },
  "token": "2|xyz456..."
}
```

> All previous tokens are deleted on login. One active session per user.

---

#### Logout
```
POST /auth/logout
```
Requires authentication.

**Response `200`:**
```json
{
  "message": "Logged out successfully."
}
```

---

### Student Profile Endpoints

#### Create Profile
```
POST /student/profile
```
Requires authentication. Student must not have an existing profile.  
Content-Type: `multipart/form-data`

**Fields:**
| Field | Type | Required | Notes |
|---|---|---|---|
| `matricule` | string | Yes | Must be unique |
| `school` | string | Yes | |
| `department` | string | Yes | |
| `level` | string | Yes | e.g. L1, L2, M1, M2 |
| `phone` | string | Yes | Mobile Money number |
| `whatsapp_number` | string | Yes | |
| `id_card` | file (image) | Yes | Max 2MB |
| `profile_picture` | file (image) | No | Max 1MB |

**Response `201`:** `StudentProfileResource`

> Profile is created with `verification_status: pending`. Student cannot access full features until admin approves.

---

#### View Own Profile
```
GET /student/profile
```
Requires authentication.

**Response `200`:** `StudentProfileResource`

---

#### Update Profile
```
POST /student/profile/update
```
Requires authentication.  
Content-Type: `multipart/form-data`

Same fields as create. All fields optional on update except `matricule` (must still be unique, ignores own current value).

> Updating profile resets `verification_status` to `pending`. Admin must re-approve.

**Response `200`:** `StudentProfileResource`

---

### Category Endpoints

#### List All Categories
```
GET /categories
```
No authentication required. Results cached for 24 hours.

**Response `200`:**
```json
[
  { "id": 1, "name": "Electronics", "slug": "electronics" },
  { "id": 2, "name": "Books", "slug": "books" }
]
```

---

### Listing Endpoints

#### Browse Listings
```
GET /listings
```
No authentication required. Only returns `active` listings. Paginated (12 per page).

**Query Parameters:**
| Parameter | Example | Description |
|---|---|---|
| `filter[category_id]` | `?filter[category_id]=1` | Filter by category |
| `filter[condition]` | `?filter[condition]=like_new` | Filter by condition |
| `filter[title]` | `?filter[title]=laptop` | Partial title search |
| `filter[price_between]` | `?filter[price_between]=5000,50000` | Price range (min,max) |
| `sort` | `?sort=price` or `?sort=-price` | Sort ascending/descending |
| `sort` | `?sort=created_at` | Sort by date |
| `page` | `?page=2` | Pagination |

**Response `200`:** Paginated `ListingCollection`

---

#### View Single Listing
```
GET /listings/{id}
```
No authentication required. Returns listing with all images, category, and seller profile.

**Response `200`:** `ListingResource` (full detail)

---

#### View Seller's Listings
```
GET /sellers/{user_id}
```
No authentication required. Returns all active listings by a specific seller.

**Response `200`:** Array of `ListingResource`

---

#### Create Listing
```
POST /listings
```
Requires authentication + verified student.  
Content-Type: `multipart/form-data`  
Rate limited: 10 per minute.

**Fields:**
| Field | Type | Required | Notes |
|---|---|---|---|
| `category_id` | integer | Yes | Must exist in categories |
| `title` | string | Yes | Max 255 chars |
| `description` | string | Yes | |
| `price` | numeric | Yes | Min 1 (XAF) |
| `quantity` | integer | Yes | Min 1 |
| `condition` | string | Yes | See Enums |
| `images[]` | file (image) | Yes | 1-5 images, max 2MB each |
| `primary_image` | integer | Yes | Index of primary image (0-based) |

**Response `201`:** `ListingResource`

---

#### Update Listing
```
POST /listings/{id}/update
```
Requires authentication + verified student + listing ownership.  
Content-Type: `multipart/form-data`

Same fields as create, all optional. If `images[]` are sent, all previous images are replaced.

**Response `200`:** `ListingResource`

---

#### Delete Listing
```
DELETE /listings/{id}
```
Requires authentication + verified student + listing ownership.

**Response `200`:**
```json
{ "message": "Listing deleted." }
```

---

#### Toggle Listing Status
```
POST /listings/{id}/toggle-status
```
Requires authentication + verified student + listing ownership.

Toggles between `active` and `paused`.

**Response `200`:** `ListingResource`

---

### Listing Image Endpoints

#### Add Images to Listing
```
POST /listings/{id}/images
```
Requires authentication + verified student + listing ownership.  
Content-Type: `multipart/form-data`

**Fields:**
| Field | Type | Notes |
|---|---|---|
| `images[]` | file (image) | Total images per listing cannot exceed 5 |

**Response `201`:** Array of `ListingImageResource`

---

#### Delete Listing Image
```
DELETE /listings/{listing_id}/images/{image_id}
```
Requires authentication + verified student + listing ownership.

> Cannot delete the last remaining image. If the deleted image was primary, the next image becomes primary automatically.

**Response `200`:**
```json
{ "message": "Image deleted." }
```

---

#### Set Image as Primary
```
POST /listings/{listing_id}/images/{image_id}/primary
```
Requires authentication + verified student + listing ownership.

**Response `200`:** `ListingImageResource`

---

### Purchase Request Endpoints

#### Submit Purchase Request
```
POST /requests
```
Requires authentication + verified student.

**Body:**
```json
{
  "listing_id": 1,
  "quantity": 1,
  "meeting_location": "Library Entrance, Block A",
  "whatsapp_number": "655000001",
  "message": "Is this still available?"
}
```

**Validation rules:**
- Cannot request own listing
- Listing must be `active`
- Quantity cannot exceed listing stock
- Cannot have duplicate active request for same listing

**Response `201`:** `PurchaseRequestResource`

---

#### View Sent Requests (as Buyer)
```
GET /requests/sent
```
Requires authentication + verified student.

**Response `200`:** Array of `PurchaseRequestResource`

---

#### View Received Requests (as Seller)
```
GET /requests/received
```
Requires authentication + verified student.

**Response `200`:** Array of `PurchaseRequestResource`

---

#### View Single Request
```
GET /requests/{id}
```
Requires authentication + verified student + buyer or seller of request.

**Response `200`:** `PurchaseRequestResource` (full detail with listing images)

---

#### Approve Request
```
POST /requests/{id}/approve
```
Requires authentication + verified student + seller of request.  
Request must be `pending`.

> Opens a 2-hour payment window. Buyer is notified via in-app notification.

**Response `200`:** `PurchaseRequestResource`

---

#### Reject Request
```
POST /requests/{id}/reject
```
Requires authentication + verified student + seller of request.  
Request must be `pending`.

**Body (optional):**
```json
{
  "reason": "Already sold to someone else."
}
```

**Response `200`:** `PurchaseRequestResource`

---

#### Cancel Request
```
POST /requests/{id}/cancel
```
Requires authentication + verified student + buyer of request.  
Request must be `pending` or `approved`.

**Response `200`:** `PurchaseRequestResource`

---

### Transaction Endpoints

#### Initiate Payment
```
POST /transactions
```
Requires authentication + verified student + buyer of the purchase request.  
Rate limited: 5 per minute.

**Body:**
```json
{
  "purchase_request_id": 1,
  "payment_method": "mtn",
  "buyer_phone": "655000001"
}
```

**Validation rules:**
- Purchase request must be `approved`
- Payment window (`expires_at`) must not be past
- No existing transaction for this request

> In local payment mode, the transaction is immediately moved to escrow, a 6-digit pickup code is generated, and the 48-hour auto-release timer starts. In Campay mode, the backend requests payment from Campay and polls Campay until the transaction becomes successful or failed.

**Response `201`:** `TransactionResource`

---

#### View My Transactions
```
GET /transactions
```
Requires authentication + verified student.

Returns all transactions where the user is buyer or seller.

**Response `200`:** Array of `TransactionResource`

---

#### View Single Transaction
```
GET /transactions/{id}
```
Requires authentication + verified student + buyer or seller of transaction.

> The `pickup_code` field is only included in the response when the authenticated user is the **buyer**.

**Response `200`:** `TransactionResource`

---

### Pickup Code Endpoints

#### Verify Pickup Code
```
POST /transactions/{id}/verify-code
```
Requires authentication + verified student + **seller** of transaction.  
Transaction must be `held`.

**Body:**
```json
{
  "code": "483921"
}
```

> Dispatches `DisbursePaymentJob` on success. Both buyer and seller are notified.

**Response `200`:**
```json
{
  "message": "Code verified. Disbursement in progress.",
  "transaction": { ...TransactionResource }
}
```

---

### Review Endpoints

#### Leave a Review
```
POST /reviews
```
Requires authentication + verified student + **buyer** of the transaction.  
Transaction must be `released`. One review per transaction.

**Body:**
```json
{
  "transaction_id": 1,
  "rating": 5,
  "comment": "Great seller, item exactly as described."
}
```

**Response `201`:** `ReviewResource`

---

#### View Seller Reviews
```
GET /reviews/seller/{user_id}
```
No authentication required.

**Response `200`:**
```json
{
  "average_rating": 4.8,
  "total_reviews": 12,
  "reviews": [ ...ReviewResource ]
}
```

---

### Dispute Endpoints

#### Raise a Dispute
```
POST /disputes
```
Requires authentication + verified student + buyer or seller of the transaction.  
Transaction must be `held`. One dispute per transaction.

**Body:**
```json
{
  "transaction_id": 1,
  "reason": "Seller is not responding after payment."
}
```

> The other party (buyer or seller) is notified immediately.

**Response `201`:** `DisputeResource`

---

#### View My Disputes
```
GET /disputes
```
Requires authentication + verified student.

Returns all disputes where the user is buyer or seller of the related transaction.

**Response `200`:** Array of `DisputeResource`

---

#### View Single Dispute
```
GET /disputes/{id}
```
Requires authentication + verified student + buyer or seller of related transaction.

**Response `200`:** `DisputeResource`

---

### Notification Endpoints

#### View All Notifications
```
GET /notifications
```
Requires authentication.

**Response `200`:**
```json
{
  "unread_count": 3,
  "notifications": [ ...NotificationResource ]
}
```

---

#### Mark Notification as Read
```
POST /notifications/{uuid}/read
```
Requires authentication + owner of notification.

**Response `200`:**
```json
{ "message": "Notification marked as read." }
```

> Note: Notification IDs are **UUIDs**, not integers.

---

#### Mark All Notifications as Read
```
POST /notifications/read-all
```
Requires authentication.

**Response `200`:**
```json
{ "message": "All notifications marked as read." }
```

---

#### Delete Notification
```
DELETE /notifications/{uuid}
```
Requires authentication + owner of notification.

**Response `200`:**
```json
{ "message": "Notification deleted." }
```

---

### Dashboard Endpoints

#### Student Dashboard Stats
```
GET /dashboard/stats
```
Requires authentication + verified student. Results cached 10 minutes per user.

**Response `200`:**
```json
{
  "total_listings": 5,
  "active_listings": 3,
  "total_sales": 2,
  "total_earned": 75000,
  "total_purchases": 1,
  "total_spent": 15000,
  "pending_requests": 1,
  "average_rating": 4.5,
  "total_reviews": 4
}
```

---

### Admin Endpoints

All admin endpoints require authentication + `admin` role.

---

#### Verifications

##### List Pending Verifications
```
GET /admin/verifications
```
**Response `200`:** Array of `StudentProfileResource` (status: pending only)

---

##### View Verification Detail
```
GET /admin/verifications/{profile_id}
```
**Response `200`:** `StudentProfileResource`

---

##### Approve Verification
```
POST /admin/verifications/{profile_id}/approve
```
> Sends approval email to student via Mailtrap. Sends in-app notification.

**Response `200`:**
```json
{ "message": "Student verified successfully." }
```

---

##### Reject Verification
```
POST /admin/verifications/{profile_id}/reject
```
**Body (optional):**
```json
{ "reason": "ID card photo is not readable." }
```

> Sends rejection email to student via Mailtrap. Sends in-app notification.

**Response `200`:**
```json
{ "message": "Student verification rejected." }
```

---

#### Users

##### List All Students
```
GET /admin/users
```
**Response `200`:** Array of `UserResource`

---

##### View Student Detail
```
GET /admin/users/{user_id}
```
**Response `200`:** `UserResource` with listings loaded

---

##### Ban Student
```
POST /admin/users/{user_id}/ban
```
> Cannot ban admins. Sends in-app notification to banned user.

**Response `200`:**
```json
{ "message": "User banned successfully." }
```

---

##### Unban Student
```
POST /admin/users/{user_id}/unban
```
**Response `200`:**
```json
{ "message": "User unbanned successfully." }
```

---

#### Admin Listings

##### List All Listings (Admin View)
```
GET /admin/listings
```
Returns all listings regardless of status. Paginated (20 per page).

**Response `200`:** Paginated `ListingCollection`

---

##### Remove Listing
```
POST /admin/listings/{listing_id}/remove
```
**Body (optional):**
```json
{ "reason": "Prohibited item." }
```

Permanently deletes listing and all associated images from storage.

**Response `200`:**
```json
{ "message": "Listing removed successfully." }
```

---

#### Admin Disputes

##### List All Disputes
```
GET /admin/disputes
```
**Response `200`:** Array of `DisputeResource`

---

##### View Dispute Detail
```
GET /admin/disputes/{dispute_id}
```
**Response `200`:** `DisputeResource` (with buyer, seller, and resolver loaded)

---

##### Resolve Dispute
```
POST /admin/disputes/{dispute_id}/resolve
```
Dispute must be `open`.

**Body:**
```json
{ "resolution": "After review, funds have been refunded to the buyer." }
```

> Both buyer and seller are notified with the resolution.

**Response `200`:** `DisputeResource`

---

##### Close Dispute
```
POST /admin/disputes/{dispute_id}/close
```
**Response `200`:**
```json
{ "message": "Dispute closed." }
```

---

#### Admin Reports

##### Platform Overview
```
GET /admin/reports/overview
```
Cached 30 minutes.

**Response `200`:**
```json
{
  "total_users": 120,
  "pending_verifications": 5,
  "banned_users": 2,
  "total_listings": 80,
  "active_listings": 65,
  "total_transactions": 45,
  "total_revenue": 2350000,
  "held_escrow": 150000,
  "open_disputes": 1,
  "total_reviews": 38,
  "average_rating": 4.6
}
```

---

##### Transaction Report
```
GET /admin/reports/transactions
```
Cached 15 minutes.

**Response `200`:**
```json
{
  "monthly": [
    { "month": "2025-01", "total": 12, "revenue": 450000 }
  ],
  "total_released": 40,
  "total_held": 3,
  "total_failed": 1,
  "total_refunded": 1
}
```

---

##### Listings Report
```
GET /admin/reports/listings
```
Cached 15 minutes.

**Response `200`:**
```json
{
  "by_category": [
    { "category_id": 1, "total": 30, "category": { "id": 1, "name": "Electronics" } }
  ],
  "by_condition": [
    { "condition": "like_new", "total": 25 }
  ],
  "total_active": 65,
  "total_sold": 10,
  "total_removed": 5
}
```

---

##### Users Report
```
GET /admin/reports/users
```
Cached 30 minutes.

**Response `200`:**
```json
{
  "monthly": [
    { "month": "2025-01", "total": 20 }
  ],
  "total_verified": 100,
  "total_pending": 5,
  "total_rejected": 3
}
```

---

#### Admin Categories

##### Create Category
```
POST /admin/categories
```
**Body:**
```json
{ "name": "Electronics" }
```

> Slug is auto-generated from name. Cache is busted on creation.

**Response `201`:** `CategoryResource`

---

##### Update Category
```
PUT /admin/categories/{id}
```
**Body:**
```json
{ "name": "Electronics & Gadgets" }
```

**Response `200`:** `CategoryResource`

---

##### Delete Category
```
DELETE /admin/categories/{id}
```
> Cannot delete a category that has existing listings.

**Response `200`:**
```json
{ "message": "Category deleted." }
```

---

## Enums Reference

### UserRole
| Value | Description |
|---|---|
| `student` | Default role |
| `admin` | Platform administrator |

### VerificationStatus
| Value | Description |
|---|---|
| `pending` | Awaiting admin review |
| `approved` | Verified, full access |
| `rejected` | Rejected, can resubmit |

### ListingCondition
| Value | Description |
|---|---|
| `new` | Brand new, unused |
| `like_new` | Used once or twice |
| `good` | Minor signs of use |
| `fair` | Noticeable wear |

### ListingStatus
| Value | Description |
|---|---|
| `active` | Visible and available |
| `paused` | Hidden by seller |
| `sold` | Sold, no longer available |
| `removed` | Removed by admin |

### RequestStatus
| Value | Description |
|---|---|
| `pending` | Awaiting seller approval |
| `approved` | Approved, awaiting payment |
| `rejected` | Rejected by seller |
| `expired` | Payment window elapsed |
| `paid` | Payment confirmed, in escrow |
| `completed` | Pickup code verified, done |
| `disputed` | Dispute raised |
| `cancelled` | Cancelled by buyer |

### TransactionStatus
| Value | Description |
|---|---|
| `initiated` | Payment triggered |
| `held` | Funds in escrow |
| `released` | Funds disbursed to seller |
| `refunded` | Funds returned to buyer |
| `failed` | Payment failed |

### PaymentMethod
| Value | Description |
|---|---|
| `mtn` | MTN Mobile Money |
| `orange` | Orange Money |

### DisputeStatus
| Value | Description |
|---|---|
| `open` | Active, awaiting admin resolution |
| `resolved` | Admin resolved with a decision |
| `closed` | Closed without formal resolution |

---

## Resource Shapes

### UserResource
```json
{
  "id": 1,
  "name": "Alice Mboua",
  "email": "alice@agora.app",
  "role": "student",
  "profile": { ...StudentProfileResource },
  "created_at": "2025-01-01T00:00:00.000000Z"
}
```

### StudentProfileResource
```json
{
  "id": 1,
  "matricule": "22T1234",
  "school": "ENSP",
  "department": "Computer Engineering",
  "level": "L3",
  "phone": "655000001",
  "whatsapp_number": "655000001",
  "profile_picture": "http://localhost:8000/storage/profile_pictures/alice.jpg",
  "id_card": "http://localhost:8000/storage/id_cards/alice_id.jpg",
  "verification_status": "approved",
  "verified_at": "2025-01-02T10:00:00.000000Z"
}
```

### CategoryResource
```json
{
  "id": 1,
  "name": "Electronics",
  "slug": "electronics"
}
```

### ListingResource
```json
{
  "id": 1,
  "title": "HP Laptop 15s",
  "description": "Barely used, 8GB RAM, 256GB SSD",
  "price": "150000.00",
  "quantity": 1,
  "condition": "like_new",
  "status": "active",
  "category": { ...CategoryResource },
  "seller": { ...UserResource },
  "images": [ ...ListingImageResource ],
  "primary_image": { ...ListingImageResource },
  "created_at": "2025-01-01T00:00:00.000000Z"
}
```

### ListingImageResource
```json
{
  "id": 1,
  "url": "http://localhost:8000/storage/listings/image.jpg",
  "is_primary": true
}
```

### PurchaseRequestResource
```json
{
  "id": 1,
  "listing": { ...ListingResource },
  "buyer": { ...UserResource },
  "seller": { ...UserResource },
  "quantity": 1,
  "total_price": "150000.00",
  "meeting_location": "Library Entrance",
  "whatsapp_number": "655000001",
  "message": "Is this still available?",
  "status": "approved",
  "expires_at": "2025-01-01T12:00:00.000000Z",
  "created_at": "2025-01-01T10:00:00.000000Z"
}
```

### TransactionResource
```json
{
  "id": 1,
  "purchase_request": { ...PurchaseRequestResource },
  "buyer": { ...UserResource },
  "seller": { ...UserResource },
  "amount": "150000.00",
  "status": "held",
  "payment_method": "mtn",
  "pickup_code": "483921",
  "pickup_code_used_at": null,
  "auto_release_at": "2025-01-03T10:00:00.000000Z",
  "created_at": "2025-01-01T10:30:00.000000Z"
}
```

> `pickup_code` is only present when the authenticated user is the buyer.

### ReviewResource
```json
{
  "id": 1,
  "reviewer": { ...UserResource },
  "reviewee": { ...UserResource },
  "rating": 5,
  "comment": "Great seller, very trustworthy.",
  "created_at": "2025-01-04T08:00:00.000000Z"
}
```

### DisputeResource
```json
{
  "id": 1,
  "transaction": { ...TransactionResource },
  "raised_by": { ...UserResource },
  "reason": "Seller did not show up to the meeting.",
  "status": "open",
  "resolution": null,
  "resolved_by": null,
  "resolved_at": null,
  "created_at": "2025-01-02T09:00:00.000000Z"
}
```

### NotificationResource
```json
{
  "id": "uuid-string",
  "type": "payment_held",
  "message": "Payment confirmed. Funds are held in escrow. Check your pickup code.",
  "read_at": null,
  "created_at": "2025-01-01T10:30:00.000000Z"
}
```

---

## Middleware & Guards

| Middleware | Applied To | Effect |
|---|---|---|
| `ForceJsonResponse` | All routes | Forces `Accept: application/json` |
| `auth:sanctum` | Protected routes | Requires valid Bearer token |
| `verified.student` | Student routes | Requires approved profile + not banned |
| `check.banned` | Public listing routes | Blocks banned users even on public routes |
| `can:isAdmin` | Admin routes | Requires `admin` role |
| `throttle:api` | All API routes | 60 requests per minute |
| `throttle:payment` | `POST /transactions` | 5 requests per minute |
| `throttle:listing-create` | `POST /listings` | 10 requests per minute |

---

## Notification Types

All in-app notifications are stored in the `notifications` table. The `type` field in the notification data identifies the event:

| Type | Trigger | Recipient |
|---|---|---|
| `verification_approved` | Admin approves student | Student |
| `verification_rejected` | Admin rejects student | Student |
| `request_approved` | Seller approves request | Buyer |
| `request_rejected` | Seller rejects request | Buyer |
| `payment_initiated` | Buyer initiates payment | Buyer |
| `payment_held` | Payment is confirmed by the active payment driver | Buyer + Seller |
| `payment_failed` | Payment fails in the active payment driver | Buyer |
| `pickup_code_verified` | Seller enters correct code | Buyer + Seller |
| `escrow_released` | Funds disbursed to seller | Buyer + Seller |
| `dispute_raised` | User raises a dispute | Other party |
| `dispute_resolved` | Admin resolves dispute | Buyer + Seller |
| `account_banned` | Admin bans account | Banned user |

---

## File Uploads

All file uploads use `multipart/form-data`. Do **not** set `Content-Type: application/json` for these endpoints.

**Accepted file types:** Images only (`jpeg`, `png`, `jpg`, `gif`, `webp`)  
**Max sizes:**
| File | Max Size |
|---|---|
| Listing images | 2MB each |
| Student ID card | 2MB |
| Profile picture | 1MB |

**File URL format:**  
Files are served from the `public/storage` directory via symlink:
```
http://localhost:8000/storage/{folder}/{filename}
```

---

## Pagination

Paginated endpoints accept a `page` query parameter:
```
GET /listings?page=2
```

Response includes a `meta` object:
```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 2,
    "last_page": 5,
    "per_page": 12,
    "total": 58
  }
}
```

| Endpoint | Per Page |
|---|---|
| `GET /listings` | 12 |
| `GET /admin/listings` | 20 |

---

## Caching Behaviour

Some endpoints are cached in Redis. Cached responses are returned instantly without hitting the database.

| Endpoint | Cache TTL |
|---|---|
| `GET /categories` | 24 hours |
| `GET /listings/{id}` | 10 minutes |
| `GET /sellers/{id}` | 10 minutes |
| `GET /dashboard/stats` | 10 minutes |
| `GET /admin/reports/overview` | 30 minutes |
| `GET /admin/reports/transactions` | 15 minutes |
| `GET /admin/reports/listings` | 15 minutes |
| `GET /admin/reports/users` | 30 minutes |

Cache is automatically busted when relevant data changes.

---

## Rate Limiting

| Limiter | Limit | Applied To |
|---|---|---|
| `api` | 60/min per user or IP | All routes |
| `payment` | 5/min per user | `POST /transactions` |
| `listing-create` | 10/min per user | `POST /listings` |

When exceeded, the API returns:
```json
{
  "message": "Too many requests. Please slow down."
}
```
HTTP Status: `429`
