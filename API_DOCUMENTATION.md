# Mobile API Documentation

## Base URL
```
https://your-domain.com/api
```

## Authentication
All API endpoints require authentication using Laravel Sanctum tokens.

### Get API Token
```http
POST /login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

**Response:**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com"
  }
}
```

Use the token in subsequent requests:
```http
Authorization: Bearer 1|abc123...
```

---

## Endpoints

### 1. Get User Info
```http
GET /api/user
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "phone": "+1234567890",
    "role": "customer",
    "status": "active"
  }
}
```

---

### 2. List Courts
```http
GET /api/courts
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Court A",
      "type": "indoor",
      "capacity": 4,
      "hourly_rate": "$50.00",
      "time_block_minutes": 60,
      "daily_start_time": "08:00:00",
      "daily_end_time": "22:00:00"
    }
  ]
}
```

---

### 3. Get Available Slots
```http
GET /api/courts/{courtId}/available-slots?date=2026-02-10&duration=60
```

**Response:**
```json
{
  "date": "2026-02-10",
  "court": "Court A",
  "slots": [
    {
      "start_time": "08:00:00",
      "end_time": "09:00:00",
      "date": "2026-02-10"
    },
    {
      "start_time": "09:00:00",
      "end_time": "10:00:00",
      "date": "2026-02-10"
    }
  ]
}
```

---

### 4. Create Booking
```http
POST /api/bookings
Content-Type: application/json

{
  "resource_id": 1,
  "date": "2026-02-10",
  "start_time": "14:00",
  "end_time": "15:00",
  "visibility": "private",
  "notes": "Birthday party"
}
```

**Response:**
```json
{
  "data": {
    "id": 123,
    "status": "pending",
    "payment_status": "pending",
    "access_code": "ORG-C1-20260210-A3B9",
    "resource": {
      "id": 1,
      "name": "Court A"
    },
    "reservations": [
      {
        "date": "2026-02-10",
        "start_time": "14:00:00",
        "end_time": "15:00:00"
      }
    ],
    "total_amount": "$50.00"
  }
}
```

---

### 5. List User Bookings
```http
GET /api/bookings
```

**Response:**
```json
{
  "data": [
    {
      "id": 123,
      "status": "confirmed",
      "payment_status": "paid",
      "access_code": "ORG-C1-20260210-A3B9",
      "resource": {
        "name": "Court A"
      },
      "total_amount": "$50.00"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

### 6. Get Booking Details
```http
GET /api/bookings/{bookingId}
```

**Response:**
```json
{
  "data": {
    "id": 123,
    "status": "confirmed",
    "payment_status": "paid",
    "access_code": "ORG-C1-20260210-A3B9",
    "resource": {
      "id": 1,
      "name": "Court A",
      "type": "indoor"
    },
    "reservations": [...],
    "line_items": [...],
    "total_amount": "$50.00"
  }
}
```

---

### 7. Cancel Booking
```http
POST /api/bookings/{bookingId}/cancel
```

**Response:**
```json
{
  "message": "Booking cancelled successfully."
}
```

---

### 8. Get Wallet
```http
GET /api/wallet
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "balance": "$100.00",
    "balance_cents": 10000,
    "transactions": [...]
  }
}
```

---

### 9. Get Wallet Transactions
```http
GET /api/wallet/transactions
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "amount": "$50.00",
      "type": "credit",
      "description": "Wallet top-up",
      "created_at": "2026-02-07T10:00:00Z"
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```

### 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
```

### 404 Not Found
```json
{
  "message": "Resource not found."
}
```

### 422 Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "date": ["The date field is required."]
  }
}
```

---

## Rate Limiting
- 60 requests per minute per user
- Returns `429 Too Many Requests` when exceeded

---

## Notes
- All dates are in `Y-m-d` format (e.g., `2026-02-10`)
- All times are in `H:i` format (e.g., `14:00`)
- All timestamps are in ISO 8601 format with UTC timezone
- Amounts are provided in both cents (integer) and formatted strings
