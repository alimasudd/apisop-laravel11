# API Documentation - testMaret (SOP Management System)

This document provides a highly detailed specification of the REST API for the **testMaret** project. All endpoints return JSON and follow a consistent structure.

---

## � General Specification

### Base URL
`http://localhost/api` (Local)
All requests must use `Accept: application/json`.

### Authentication
The API uses **Laravel Sanctum**. For protected endpoints, include the header:
`Authorization: Bearer {your_access_token}`

---

## 📦 Standard Response Formats

### ✅ Success Response
```json
{
    "success": true,
    "message": "Human readable success message",
    "data": { ... } // Could be an object or an array
}
```

### 📄 Paginated Response
Used for all `index` list endpoints.
```json
{
    "success": true,
    "data": {
        "items": [ ... ],
        "pagination": {
            "current_page": 1,
            "last_page": 5,
            "per_page": 10,
            "total": 50,
            "next_page_url": "...",
            "prev_page_url": null
        }
    }
}
```

### ❌ Error Response (Validation - 422)
```json
{
    "success": false,
    "message": "Validation error",
    "data": {
        "field_name": ["Specific error message"],
        "email": ["The email has already been taken."]
    }
}
```

### ❌ Error Response (Standard - 401, 404, 500)
```json
{
    "success": false,
    "message": "Resource not found" // or "Unauthorized", etc.
}
```

---

## � 1. Authentication Module

### 1.1 Register
**Endpoint:** `POST /register`
**Public Access**

| Field | Type | Required | Description |
|---|---|---|---|
| `nama` | string | Yes | Max 255 |
| `email` | string | Yes | Must be unique in `m_user` |
| `password` | string | Yes | Min 8 chars |
| `hp` | string | Yes | Phone number |

**Success (201):** Returns `user` object and `access_token`.

### 1.2 Login
**Endpoint:** `POST /login`
**Public Access**

| Field | Type | Required | Description |
|---|---|---|---|
| `email` | string | Yes | |
| `password` | string | Yes | |

**Success (200):** Returns `access_token` and `token_type: Bearer`.

### 1.3 Profile
**Endpoint:** `GET /profile` (Auth Required)
Returns currently authenticated `user` object.

### 1.4 Logout
**Endpoint:** `POST /logout` (Auth Required)
Revokes the current access token.

---

## � 2. User Management
*Requires Authentication*

### 2.1 List Users
**Endpoint:** `GET /users`
**Query Params:**
- `search`: (Optional) Search by `nama`, `email`, or `hp`.

### 2.2 Create User
**Endpoint:** `POST /users`
**Fields:** `nama`, `email`, `password`, `hp`, `level_id` (default 5), `status_aktif` (default 1).

### 2.3 Update User
**Endpoint:** `PUT /users/{id}`
**Fields:** Same as Create. `password` is optional (only updated if filled).

### 2.4 Delete User
**Endpoint:** `DELETE /users/{id}`

---

## 🗺 3. Area & Ruang (Master Data)

### 3.1 Areas (CRUD)
**Endpoints:** `GET /areas`, `POST /areas`, `GET /areas/{id}`, `PUT /areas/{id}`, `DELETE /areas/{id}`
**Fields:** `nama` (Required), `des` (Optional).

### 3.2 Ruangs (CRUD)
**Endpoints:** Standard CRUD at `/ruangs`.
**Fields:** 
- `area_id`: (Required) Parent Area ID.
- `nama`: (Required).
- `des`: (Optional).
**Relationships:** Index/Show includes the `area` object.

---

## 📂 4. Kategori SOP

### 4.1 Categories (CRUD)
**Endpoints:** Standard CRUD at `/kategori-sops`.
**Fields:** 
- `kode`: (Required|Unique).
- `nama`: (Required).
- `deskripsi`: (Optional).
- `status`: (`aktif`, `nonaktif`).
**Special Feature:** `GET /kategori-sops` includes `sops_count` (total number of SOPs in each category).

### 4.2 Get SOPs in Category
**Endpoint:** `GET /kategori-sops/{id}/sops`
Returns a targeted list of SOPs for a specific category.
**Response Data:** `{ "nama_kategori": "...", "sops": [...] }`.

---

## 📜 5. SOP Master Data

### 5.1 List SOP
**Endpoint:** `GET /sops`
**Query Params:** `search` (Search by code, name, desc, or category name).
**Included Data:** Includes `kategori`, `pengawas` (User), and `langkah_count`.

### 5.2 Create/Update SOP
**Endpoints:** `POST /sops`, `PUT /sops/{id}`
**Body Fields:**
- `katsop_id`: (Required) FK to Category.
- `kode`: (Required|Unique).
- `nama`: (Required).
- `status_sop`: (Required|`mutlak`, `custom`).
- `pengawas_id`: (Optional) FK to User.
- `tanggal_berlaku`, `tanggal_kadaluarsa`: (Optional|Date).
- `status`: (`aktif`, `nonaktif`, `draft`, `expired`).

### 5.3 Detail SOP (Show)
**Endpoint:** `GET /sops/{id}`
**Extremely Detailed:** Returns SOP info + `kategori` + `pengawas` + FULL ARRAY of `langkah` (including nested `ruang` and `user` for each step).

---

## 👟 6. SOP Langkah (Steps)

### 6.1 List Langkah
**Endpoint:** `GET /langkah-sops`
**Filters (Query Params):**
- `sop_id`: (Recommended) Filter by specific SOP.
- `wajib`: (Optional|`1` or `0`).
- `search`: Search step descriptions or room names.

### 6.2 Standard CRUD
**Endpoints:** Standard CRUD at `/langkah-sops`.
**Key Fields:** `sop_id`, `urutan`, `deskripsi_langkah`, `wajib`, `poin`, `deadline_waktu` (minutes/int), `wa_reminder` (boolean), `wa_jam_kirim` (HH:MM).

---

## 👥 7. SOP Tugas (Assignments)
*Assigns users to specific SOPs or specific Steps.*

### 7.1 List Assignments
**Endpoint:** `GET /tugas-sops`
**Data:** Includes `sop`, `langkah`, and `user`.

### 7.2 Create Assignment (Bulk Ready)
**Endpoint:** `POST /tugas-sops`
**Body Fields:**
- `sop_id`: (Required).
- `user_ids`: (Required|Array) List of User IDs.
- `ditugaskan_pada`: (`semua` or `tertentu`).
- `sop_langkah_ids`: (Required if `tertentu`|Array) List of Step IDs.
**Logic:** Automatically skips existing duplicates.

---

## 📝 8. Pelaksanaan SOP (Execution Logs)
*Logs when a user completes an SOP/Step.*

### 8.1 API Endpoints
Standard CRUD at `/pelaksanaan-sops`.

### 8.2 Execution Data
- `sop_id`, `user_id`: (Required).
- `status_sop`: (Integer|0=Daily, 1=Weekly, 2=Monthly, 3=Yearly).
- `url`: (Optional) URL to photo/video evidence.
- `poin`: (Optional) Points earned.
- `deadline_waktu`, `waktu_mulai`, `waktu_selesai`: (Unix Timestamps).

---

## 📊 Enum Reference

| Variable | Values | Meaning |
|---|---|---|
| `status` (SOP) | `aktif`, `nonaktif`, `draft`, `expired` | Current lifecycle of the SOP document |
| `status_sop` (SOP) | `mutlak`, `custom` | Whether the procedure is strictly fixed or flexible |
| `status_sop` (Log) | `0`, `1`, `2`, `3` | Periodicity: Daily, Weekly, Monthly, Yearly |
| `wajib` (Step) | `1`, `0` | Whether the step is mandatory for completion |
