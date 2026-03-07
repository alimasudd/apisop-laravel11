# API Documentation - testMaret

This documentation provides comprehensive details on the available API endpoints for the testMaret application.

---

## 🚀 General Information

### Base URL
All requests should be sent to:
`http://localhost/api` (or your production domain)

### Response Formats

All responses are returned in JSON format.

#### ✅ Success Response (Standard)
```json
{
    "success": true,
    "message": "Operasi berhasil",
    "data": { ... }
}
```

#### 📄 Success Response (With Pagination)
```json
{
    "success": true,
    "data": {
        "items": [ ... ],
        "pagination": {
            "current_page": 1,
            "last_page": 5,
            "per_page": 10,
            "total": 45,
            "next_page_url": "...",
            "prev_page_url": null
        }
    }
}
```

#### ❌ Error Response (Validation Error - 422)
```json
{
    "success": false,
    "message": "Validation error",
    "data": {
        "field_name": ["The field_name is required."],
        "another_field": ["The another_field must be at least 8 characters."]
    }
}
```

#### ❌ Error Response (Not Found - 404)
```json
{
    "success": false,
    "message": "Resource not found"
}
```

---

## 🔒 Authentication

This API uses **Laravel Sanctum** for token-based authentication.
- For most endpoints, you must include the following header:
  `Authorization: Bearer {your_token}`

### 1. Register User
**Method:** `POST`
**Endpoint:** `/register`

**Request Body:**
| Field | Type | Required | Description |
|---|---|---|---|
| `nama` | string | Yes | Full name |
| `email` | string | Yes | Unique email address |
| `password` | string | Yes | Minimum 8 characters |
| `hp` | string | Yes | Phone number |

**Response (201):**
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": { ... },
        "access_token": "1|abc...",
        "token_type": "Bearer"
    }
}
```

### 2. Login User
**Method:** `POST`
**Endpoint:** `/login`

**Request Body:**
| Field | Type | Required | Description |
|---|---|---|---|
| `email` | string | Yes | |
| `password` | string | Yes | |

**Response (200):**
```json
{
    "success": true,
    "message": "Login success",
    "data": {
        "access_token": "2|xyz...",
        "token_type": "Bearer"
    }
}
```

---

## 👤 User Management (CRUD)
*Requires Authentication*

### 1. List Users
**Method:** `GET`
**Endpoint:** `/users`
**Query Params:** `search` (Optional: Filter by name, email, or phone)

### 2. Create User
**Method:** `POST`
**Endpoint:** `/users`
**Payload:** `nama`, `email`, `password`, `hp`, `level_id` (default: 5), `status_aktif` (default: 1).

---

## 🗺️ Area Management
*Requires Authentication*

### 1. List Areas
**Method:** `GET`
**Endpoint:** `/areas`
**Query Params:** `search` (Optional: Search by name or description)

### 2. Create Area
**Method:** `POST`
**Endpoint:** `/areas`
**Payload:**
| Field | Type | Required |
|---|---|---|
| `nama` | string | Yes |
| `des` | string | No |

---

## 🏢 Room (Ruang) Management
*Requires Authentication*

### 1. List Rooms
**Method:** `GET`
**Endpoint:** `/ruangs`
**Query Params:** `search`, `area_id` (Filter by specific Area).

### 2. Create Room
**Method:** `POST`
**Endpoint:** `/ruangs`
**Payload:**
| Field | Type | Required | Description |
|---|---|---|---|
| `area_id` | integer | Yes | Parent Area ID |
| `nama` | string | Yes | |
| `des` | string | No | |

---

## 📂 Kategori SOP
*Requires Authentication*

### 1. List Categories
**Method:** `GET`
**Endpoint:** `/kategori-sops`

### 2. Create Category
**Method:** `POST`
**Endpoint:** `/kategori-sops`
**Payload:** `kode`, `nama`, `deskripsi`, `status` (`aktif`/`nonaktif`).

### 3. Get SOPs in Category
**Method:** `GET`
**Endpoint:** `/kategori-sops/{id}/sops`
Returns a list of SOPs belonging to this category (ideal for modal views).

---

## 📜 SOP (Standard Operating Procedure)
*Requires Authentication*

### 1. List SOP
**Method:** `GET`
**Endpoint:** `/sops`
**Query Params:** `search` (Searches in code, name, description, or category name).

### 2. Create SOP
**Method:** `POST`
**Endpoint:** `/sops`
**Payload:**
- `katsop_id` (Required)
- `kode`, `nama` (Required)
- `deskripsi`, `versi`, `tanggal_berlaku`, `tanggal_kadaluarsa`
- `status` (`aktif`, `nonaktif`, `draft`, `expired`)
- `status_sop` (Required: `mutlak` or `custom`)
- `pengawas_id` (FK to User)
- `total_poin`

### 3. Detail SOP (Includes Steps)
**Method:** `GET`
**Endpoint:** `/sops/{id}`
Returns SOP data along with a `langkah` (steps) array.

---

## 👟 SOP Langkah (Steps)
*Requires Authentication*

### 1. List Langkah
**Method:** `GET`
**Endpoint:** `/langkah-sops`
**Query Params:** `sop_id` (Required), `wajib`, `search`.

### 2. Create Langkah
**Method:** `POST`
**Endpoint:** `/langkah-sops`
**Payload:** `sop_id`, `ruang_id`, `user_id` (Responsible person/User), `urutan`, `deskripsi_langkah`, `wajib` (bool), `poin`, `deadline_waktu` (timestamp), `wa_reminder` (bool), `wa_jam_kirim` (HH:MM).

---

## 👥 SOP Tugas (Assignments)
*Requires Authentication*

### 1. List Assignments
**Method:** `GET`
**Endpoint:** `/tugas-sops`

### 2. Bulk/Single Assign
**Method:** `POST`
**Endpoint:** `/tugas-sops`
**Payload:**
| Field | Type | Required | Description |
|---|---|---|---|
| `sop_id` | integer | Yes | |
| `user_ids` | array | Yes | Array of integers `[1,2,3]` |
| `ditugaskan_pada`| enum | Yes | `semua` or `tertentu` |
| `sop_langkah_ids`| array | No | Array of Step IDs (Required if "tertentu") |

*Note: Duplicates are skipped automatically.*

---

## 📝 Pelaksanaan SOP (Execution)
*Requires Authentication*

### 1. Log Execution
**Method:** `POST`
**Endpoint:** `/pelaksanaan-sops`
**Payload:**
- `sop_id`, `user_id` (Required)
- `area_id`, `ruang_id`, `sop_langkah_id`
- `status_sop` (0=Day, 1=Week, 2=Month, 3=Year)
- `poin`, `des` (Notes)
- `url` (Evidence URL: Photo/Video)
- `deadline_waktu`, `waktu_mulai`, `waktu_selesai` (Unix Timestamps)

---

## ⚠️ Common Status Codes

| Code | Meaning | Reason |
|---|---|---|
| `200` | OK | Success |
| `201` | Created | Resource successfully created |
| `401` | Unauthorized | Token missing or invalid |
| `403` | Forbidden | Account inactive or lack permissions |
| `404` | Not Found | Resource ID does not exist |
| `422` | Unprocessable Content | Validation error (Missing/Invalid fields) |
