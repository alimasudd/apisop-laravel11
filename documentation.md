# API Documentation - testMaret

This documentation provides details on the available API endpoints for the testMaret application.

## Authentication

All endpoints except `/register` and `/login` require a Bearer Token in the `Authorization` header.

### 1. Register
**Endpoint:** `POST /api/register`

**Request Body:**
| Field | Type | Required | Description |
|---|---|---|---|
| `nama` | string | Yes | Full name of the user |
| `email` | string | Yes | Unique email address |
| `password` | string | Yes | Minimum 8 characters |
| `hp` | string | Yes | Phone number |

**Response:** `201 Created`
```json
{
    "status": "success",
    "message": "User registered successfully",
    "data": { ... },
    "access_token": "...",
    "token_type": "Bearer"
}
```

### 2. Login
**Endpoint:** `POST /api/login`

**Request Body:**
| Field | Type | Required | Description |
|---|---|---|---|
| `email` | string | Yes | Registered email address |
| `password` | string | Yes | User's password |

**Response:** `200 OK`
```json
{
    "status": "success",
    "message": "Login success",
    "access_token": "...",
    "token_type": "Bearer"
}
```

### 3. Profile
**Endpoint:** `GET /api/profile`
**Header:** `Authorization: Bearer {token}`

**Response:** `200 OK`
```json
{
    "status": "success",
    "message": "Profile retrieved successfully",
    "data": { ... }
}
```

### 4. Logout
**Endpoint:** `POST /api/logout`
**Header:** `Authorization: Bearer {token}`

**Response:** `200 OK`
```json
{
    "status": "success",
    "message": "Logged out successfully"
}
```

---

## User Management (CRUD)

All endpoints require authentication.

### 1. List Users
**Endpoint:** `GET /api/users`

**Query Parameters:**
| Parameter | Type | Description |
|---|---|---|
| `search` | string | Search by name, email, or phone |
| `page` | integer | Page number for pagination (default: 1) |

**Response:** `200 OK`

### 2. Create User
**Endpoint:** `POST /api/users`

**Request Body:** `nama`, `email`, `password`, `hp` (optional), `level_id` (optional), `status_aktif` (optional).

### 3. Get User Details
**Endpoint:** `GET /api/users/{id}`

### 4. Update User
**Endpoint:** `PUT /api/users/{id}`

### 5. Delete User
**Endpoint:** `DELETE /api/users/{id}`

---

## Area Management (CRUD)

All endpoints require authentication.

### 1. List Areas
**Endpoint:** `GET /api/areas`

**Query Parameters:**
| Parameter | Type | Description |
|---|---|---|
| `search` | string | Search by area name or description |
| `page` | integer | Page number for pagination |

**Response:** `200 OK`
```json
{
    "success": true,
    "data": {
        "areas": [
            {
                "id": 1,
                "nama": "Area Alpha",
                "des": "Alpha description",
                "created_at": "...",
                "updated_at": "..."
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 5,
            "per_page": 10,
            "total": 45
        }
    }
}
```

### 2. Create Area
**Endpoint:** `POST /api/areas`

**Request Body:**
| Field | Type | Required | Description |
|---|---|---|---|
| `nama` | string | Yes | Area name |
| `des` | string | No | Area description |

**Response:** `201 Created`

### 3. Get Area Details
**Endpoint:** `GET /api/areas/{id}`

**Response:** `200 OK`

### 4. Update Area
**Endpoint:** `PUT /api/areas/{id}`

**Request Body:** `nama`, `des` (optional).

**Response:** `200 OK`

### 5. Delete Area
**Endpoint:** `DELETE /api/areas/{id}`

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "Area deleted successfully"
}
```

---

## Ruang Management (CRUD)

All endpoints require authentication.

### 1. List Ruang
**Endpoint:** `GET /api/ruangs`

**Query Parameters:**
| Parameter | Type | Description |
|---|---|---|
| `search` | string | Search by room name, description, or area name |
| `page` | integer | Page number for pagination |

**Response:** `200 OK`
```json
{
    "success": true,
    "data": {
        "ruangs": [
            {
                "id": 1,
                "area_id": 6,
                "nama": "SERVER GA",
                "des": "SERVER GA",
                "created_at": "...",
                "area": {
                    "id": 6,
                    "nama": "HO",
                    "des": "HO"
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 1,
            "per_page": 10,
            "total": 3
        }
    }
}
```

### 2. Create Ruang
**Endpoint:** `POST /api/ruangs`

**Request Body:**
| Field | Type | Required | Description |
|---|---|---|---|
| `area_id` | integer | Yes | ID of the linked area |
| `nama` | string | Yes | Room name |
| `des` | string | No | Room description |

**Response:** `201 Created`

### 3. Get Ruang Details
**Endpoint:** `GET /api/ruangs/{id}`

**Response:** `200 OK`

### 4. Update Ruang
**Endpoint:** `PUT /api/ruangs/{id}`

**Request Body:** `area_id`, `nama`, `des` (optional).

**Response:** `200 OK`

### 5. Delete Ruang
**Endpoint:** `DELETE /api/ruangs/{id}`

**Response:** `200 OK`
```json
{
    "success": true,
    "message": "Ruang deleted successfully"
}
```
