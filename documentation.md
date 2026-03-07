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

---

## Kategori SOP Management (CRUD)

All endpoints require authentication.

### 1. List Kategori SOP
**Endpoint:** `GET /api/kategori-sops`

**Query Parameters:**
| Parameter | Type | Description |
|---|---|---|
| `search` | string | Search by code, name, or description |
| `page` | integer | Page number |

**Response Fields (Extra):**
- `sops_count`: Total number of SOPs in this category.

**Response:** `200 OK`

### 2. Create Kategori SOP
**Endpoint:** `POST /api/kategori-sops`

**Request Body:**
| Field | Type | Required | Description |
|---|---|---|---|
| `kode` | string | Yes | Unique code (e.g., KAT-001) |
| `nama` | string | Yes | Category name |
| `deskripsi` | string | No | |
| `status` | enum | No | `aktif` or `nonaktif` (default: `aktif`) |

**Response:** `201 Created`

### 3. Get Details & SOP List
**Endpoint:** `GET /api/kategori-sops/{id}`

**Response:** includes category data and an array of `sops`.

### 4. Update Kategori SOP
**Endpoint:** `PUT /api/kategori-sops/{id}`

**Request Body:** `kode`, `nama`, `deskripsi`, `status`.

### 5. Delete Kategori SOP
**Endpoint:** `DELETE /api/kategori-sops/{id}`

### 6. Get SOPs only (Modal View)
**Endpoint:** `GET /api/kategori-sops/{id}/sops`

**Response:** returns the list of SOPs for the specific category.

---

## SOP Management (CRUD)

All endpoints require authentication.

### 1. List SOP
**Endpoint:** `GET /api/sops`

**Query Parameters:**
| Parameter | Type | Description |
|---|---|---|
| `search` | string | Search by code, name, description, or category name |
| `page` | integer | Page number |

**Response Fields (Extra):**
- `langkah_count`: Total number of steps in this SOP.
- `kategori`: Category object.
- `pengawas`: User object for the supervisor.

**Response:** `200 OK`

### 2. Create SOP
**Endpoint:** `POST /api/sops`

**Request Body:**
| Field | Type | Required | Description |
|---|---|---|---|
| `katsop_id` | integer | Yes | Category ID |
| `kode` | string | Yes | Unique code (e.g., SOP-OPR-001) |
| `nama` | string | Yes | SOP name |
| `deskripsi` | string | No | |
| `versi` | string | No | e.g., 1.0 |
| `tanggal_berlaku` | date | No | YYYY-MM-DD |
| `tanggal_kadaluarsa` | date | No | YYYY-MM-DD |
| `status` | enum | No | `aktif`, `nonaktif`, `draft`, `expired` |
| `status_sop` | enum | Yes | `mutlak` or `custom` (Mapped to Periode in UI) |
| `pengawas_id` | integer | No | User ID of the supervisor |
| `total_poin` | integer | No | |

**Response:** `201 Created`

### 3. Get Details & Steps
**Endpoint:** `GET /api/sops/{id}`

**Response:** includes SOP data, `kategori`, `pengawas`, and an array of `langkah` (steps).

### 4. Update SOP
**Endpoint:** `PUT /api/sops/{id}`

**Request Body:** Same as Create, but `kode` must be unique except for current ID.

### 5. Delete SOP
**Endpoint:** `DELETE /api/sops/{id}`

---

## Langkah SOP Management (CRUD)

All endpoints require authentication.

### 1. List Langkah SOP
**Endpoint:** `GET /api/langkah-sops`

**Query Parameters:**
| Parameter | Type | Description |
|---|---|---|
| `search` | string | Search by step description, SOP name, or Room name |
| `sop_id` | integer | Filter by SOP |
| `wajib` | boolean | Filter by mandatory status |
| `page` | integer | Page number |

**Response Fields (Extra):**
- `sop`: Associated SOP object.
- `ruang`: Associated Room object.
- `user`: Associated User object (responsible person).

**Response:** `200 OK`

### 2. Create Langkah SOP
**Endpoint:** `POST /api/langkah-sops`

**Request Body:**
| Field | Type | Required | Description |
|---|---|---|---|
| `sop_id` | integer | Yes | Parent SOP ID |
| `ruang_id` | integer | No | Linked room ID |
| `user_id` | integer | No | User ID of person responsible |
| `urutan` | integer | Yes | Step order (1, 2, 3...) |
| `deskripsi_langkah` | string | Yes | Detail step description |
| `wajib` | boolean | Yes | Whether the step is mandatory |
| `poin` | integer | No | Points awarded for this step |
| `deadline_waktu` | bigint | No | Timestamp or duration |
| `toleransi_waktu_sebelum` | bigint | No | |
| `toleransi_waktu_sesudah` | bigint | No | |
| `wa_reminder` | boolean | No | WhatsApp notification |
| `wa_jam_kirim` | string | No | Format `HH:MM` |

**Response:** `201 Created`

### 3. Get Step Details
**Endpoint:** `GET /api/langkah-sops/{id}`

**Response:** `200 OK`

### 4. Update Langkah SOP
**Endpoint:** `PUT /api/langkah-sops/{id}`

**Request Body:** Same as Create.

### 5. Delete Langkah SOP
**Endpoint:** `DELETE /api/langkah-sops/{id}`

---

## SOP Tugas Management (Assignments)

All endpoints require authentication.

### 1. List Assignments
**Endpoint:** `GET /api/tugas-sops`

**Query Parameters:**
| Parameter | Type | Description |
|---|---|---|
| `search` | string | Search by Employee Name, SOP Name, or Step Description |

**Response:** List of assignments with related `sop`, `langkah`, and `user` data.

### 2. Create Assignment (Single or Mass)
**Endpoint:** `POST /api/tugas-sops`

**This endpoint handles both Single and Mass assignments.** Simply provide an array of user IDs and/or step IDs.

**Request Body:**
| Field | Type | Required | Description |
|---|---|---|---|
| `sop_id` | integer | Yes | SOP ID |
| `user_ids` | array | Yes | Array of integers representing User IDs (e.g., `[1, 2, 3]`) |
| `ditugaskan_pada` | enum | Yes | `semua` (for all steps) or `tertentu` (for specific steps) |
| `sop_langkah_ids` | array | No | Array of integers representing Step IDs. Required if `ditugaskan_pada` is `tertentu`. |

**How it works:** 
- If `ditugaskan_pada` is `semua`, the system creates one row for each employee where `sop_langkah_id` is `null`.
- If `ditugaskan_pada` is `tertentu`, the system creates one row for each employee-step combination.
- **Duplicates are automatically skipped.**

**Response:** `201 Created`

### 3. Delete Assignment
**Endpoint:** `DELETE /api/tugas-sops/{id}`

---

## Pelaksanaan SOP Management (Execution)

All endpoints require authentication.

### 1. List Pelaksanaan
**Endpoint:** `GET /api/pelaksanaan-sops`

**Query Parameters:**
| Parameter | Type | Description |
|---|---|---|
| `search` | string | Search by SOP Name, Employee, Area, or Room |

**Response:** List of execution logs with full details including `sop`, `user` (pelaksana), `area`, and `ruang`.

### 2. Record Pelaksanaan
**Endpoint:** `POST /api/pelaksanaan-sops`

**Request Body:**
| Field | Type | Required | Description |
|---|---|---|---|
| `sop_id` | integer | Yes | SOP ID |
| `user_id` | integer | Yes | User ID who executed the SOP |
| `area_id` | integer | No | |
| `ruang_id` | integer | No | |
| `sop_langkah_id` | integer | No | Specific step ID (if executing single step) |
| `status_sop` | integer | No | Periode: `0`=Day, `1`=Week, `2`=Month, `3`=Year |
| `poin` | integer | No | Points earned |
| `des` | string | No | Notes/Description |
| `url` | string | No | URL to evidence (Photo/Video) |
| `deadline_waktu` | bigint | No | Unix timestamp |
| `waktu_mulai` | bigint | No | Unix timestamp |
| `waktu_selesai` | bigint | No | Unix timestamp |

**Response:** `201 Created`

### 3. Get Details
**Endpoint:** `GET /api/pelaksanaan-sops/{id}`

### 4. Update Execution Record
**Endpoint:** `PUT /api/pelaksanaan-sops/{id}`

### 5. Delete Execution Record
**Endpoint:** `DELETE /api/pelaksanaan-sops/{id}`
