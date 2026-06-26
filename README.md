# MathPro — Project Management System

Aplikasi manajemen project profesional untuk perusahaan multi-divisi dan multi-departemen. Dibangun dengan **Laravel 12**, **Vue 3**, **Inertia.js**, dan **Tailwind CSS**.

## Tech Stack

- **Backend:** Laravel 12, SQLite (default) / MySQL
- **Frontend:** Vue 3, Inertia.js, Tailwind CSS, Heroicons
- **Auth:** Laravel Breeze

## Fitur

| Modul | Status |
|-------|--------|
| Dashboard (scoped per user) | ✅ |
| Projects (CRUD + membership) | ✅ |
| My Tasks | ✅ |
| Calendar & Timeline | ✅ |
| Unassigned | ✅ |
| Chat (tim + komentar task) | ✅ |
| Reports | ✅ |
| Activity Log | ✅ |
| Roles & Users (Super Admin) | ✅ |
| Profile (foto + kekuatan password) | ✅ |
| Global Search & Notifikasi navbar | ✅ |

## Model Akses

Sistem memakai **dua lapisan**:

1. **Role global** (`roles.permissions`) — mengatur menu & aksi sistem (lihat project, kelola project, laporan, dll.)
2. **Keanggotaan project** (`project_members.access`) — Viewer / Contributor / Admin + `manager_id`

Super Admin selalu bypass permission global. Akses task di dalam project ditentukan oleh `ProjectAccessService`.

### Permission global

| Permission | Kegunaan |
|------------|----------|
| `projects.view` | Menu Projects, Calendar, Chat, Activity Log |
| `projects.manage` | Buat project baru |
| `tasks.manage` | Menu My Tasks |
| `reports.view` | Menu Reports |
| `users.manage` / `roles.manage` | Super Admin only (via slug) |

Menu **Unassigned** hanya tampil jika user bisa assign task di minimal satu project.

## Instalasi

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan storage:link

php artisan migrate:fresh --seed

php artisan serve
npm run dev
```

Buka `http://localhost:8000`

### Mode development (disarankan)

Jalankan server, queue, log, dan Vite sekaligus:

```bash
composer dev
```

Perintah ini menjalankan `php artisan serve`, `queue:listen` (email assign task), `pail` (log), dan `npm run dev`.

---

## Panduan Penggunaan

### 1. Login

1. Buka halaman login (`/login`)
2. Masukkan **email** dan password **`password`** (lihat [Akun Demo](#akun-demo))
3. Setelah login, Anda diarahkan ke **Dashboard**

### 2. Navigasi utama

| Menu | Fungsi |
|------|--------|
| **Dashboard** | Ringkasan statistik, aktivitas terbaru, dan anggota tim (scoped ke project Anda) |
| **Projects** | Daftar & detail project — task board, anggota, timeline |
| **My Tasks** | Task yang ditugaskan ke Anda |
| **Calendar** | Jadwal task berdasarkan due date |
| **Unassigned** | Task tanpa assignee — tampil jika Anda bisa assign di minimal satu project |
| **Chat** | Obrolan tim per project + komentar pada task |
| **Reports** | Statistik & grafik — Manager/Lead bisa export CSV |
| **Activity Log** | Riwayat aksi sistem (create task, assign, update profil, dll.) |
| **Roles / Users** | Hanya **Super Admin** — kelola role & pengguna |

Menu sidebar otomatis disembunyikan jika role Anda tidak punya permission yang diperlukan.

### 3. Projects

- **Lihat project:** Klik nama project di daftar atau dari Dashboard
- **Buat project baru:** Tombol **New Project** — hanya **Project Manager** & Super Admin (`projects.manage`)
- **Kelola anggota:** Tab anggota di detail project — PM menambah/mengubah akses (Viewer / Contributor / Admin)
- **Kelola task:** Drag-and-drop di board, edit status, assignee, due date, prioritas
- **Akses project** ditentukan oleh: Super Admin, `manager_id`, atau keanggotaan `project_members`

### 4. Task & Unassigned

- **Contributor** dapat menandai task **Done** pada task yang ditugaskan ke dirinya
- **Admin anggota** atau PM dapat assign task ke anggota tim
- Menu **Unassigned** menampilkan task tanpa assignee dari project yang Anda kelola/assign
- Saat task ditetapkan ke seseorang, sistem mengirim **email notifikasi** (via queue)

### 5. Chat

- Pilih project di panel kiri untuk chat tim
- Mode **Task** — komentar pada thread task tertentu
- Pesan baru ter-refresh otomatis setiap **30 detik** saat tab browser aktif
- Lampiran file didukung; pesan bisa dihapus oleh pengirim

### 6. Pencarian & notifikasi (navbar)

- **Global Search** (`Ctrl+K` / klik ikon search) — cari project, task, dan user
- **Lonceng notifikasi** — task overdue, jatuh tempo hari ini, unassigned, chat belum dibaca
- Notifikasi bisa ditandai **dibaca** per item atau **semua sekaligus**
- Badge notifikasi ter-refresh otomatis setiap **45 detik** saat tab aktif

### 7. Reports & export

1. Buka **Reports** (role dengan `reports.view`)
2. Opsional: filter per project
3. Klik **Export Project (CSV)** atau **Export Task (CSV)** untuk unduh data

### 8. Profile

- Ubah nama, email, foto profil
- Ganti password — ada indikator **kekuatan password**
- Perubahan profil, foto, dan password tercatat di **Activity Log**

### 9. Super Admin — Roles & Users

Login sebagai `admin@mathpro.test` untuk:

- Mengelola **role** dan permission global
- CRUD **pengguna** (aktif/nonaktif, role, departemen)
- Mengakses semua project tanpa keanggotaan

### Skenario uji per role

| Skenario | Akun yang dipakai |
|----------|-------------------|
| Akses penuh sistem | `admin@mathpro.test` |
| Buat project & kelola ERP/Mobile/FIN | `manager@mathpro.test` |
| PM hanya project Marketing | `diana@mathpro.test` |
| Assign task & admin tim ERP | `rio@mathpro.test` |
| Member biasa — task sendiri saja | `member@mathpro.test` |
| Contributor + Viewer di project berbeda | `siti@mathpro.test` |
| PM project infrastruktur (via `manager_id`) | `agus@mathpro.test` |
| Member tidak bisa lihat ERP | Login `member@` → project ERP tidak muncul |
| Member tidak bisa Reports | Login `member@` → menu Reports disembunyikan |

---

## Akun Demo

> **Password semua akun:** `password`  
> Data dihasilkan oleh `php artisan migrate:fresh --seed`.

### Ringkasan cepat

| Nama | Email | Role | Departemen |
|------|-------|------|------------|
| Ahmad Rizki | admin@mathpro.test | Super Admin | DEV |
| Sarah Wijaya | manager@mathpro.test | Project Manager | DEV |
| Diana Putri | diana@mathpro.test | Project Manager | DIGITAL |
| Rio Pratama | rio@mathpro.test | Team Lead | DEV |
| Budi Santoso | member@mathpro.test | Member | QA |
| Siti Aminah | siti@mathpro.test | Member | DEV |
| Agus Hermawan | agus@mathpro.test | Member | INFRA |

### Detail akses per akun

| Email | Menu yang tampil | Project & peran |
|-------|------------------|-----------------|
| **admin@mathpro.test** | Semua menu + Roles/Users | Semua project (bypass) |
| **manager@mathpro.test** | Dashboard, Projects, My Tasks, Calendar, Unassigned, Chat, Reports, Activity | **PM:** ERP, Mobile, Portal FIN — bisa buat project baru |
| **diana@mathpro.test** | Sama seperti PM (tanpa Roles/Users) | **PM:** Kampanye Digital Q2 — tidak akses ERP |
| **rio@mathpro.test** | Sama seperti PM kecuali **tidak bisa buat project** | **Admin** anggota ERP & Mobile; **Contributor** INF |
| **member@mathpro.test** | Dashboard, Projects, My Tasks, Calendar, Chat, Activity — **tanpa** Reports & Unassigned* | **Contributor** ERP — task UAT & testing Mobile |
| **siti@mathpro.test** | Sama seperti member | **Contributor** ERP & Mobile; **Viewer** Marketing |
| **agus@mathpro.test** | Sama seperti member | **PM** Migrasi Infrastruktur Cloud; **Contributor** tidak ada di project lain |

\* Menu **Unassigned** hanya muncul jika user punya hak assign di minimal satu project.

### Project seed data

| Kode | Nama | PM | Status |
|------|------|-----|--------|
| PRJ-ERP-001 | Modernisasi ERP | Sarah | Active |
| PRJ-MOB-002 | Redesign Aplikasi Mobile | Sarah | Active |
| PRJ-MKT-003 | Kampanye Digital Q2 | Diana | Planning |
| PRJ-INF-004 | Migrasi Infrastruktur Cloud | Agus | On Hold |
| PRJ-FIN-005 | Portal Laporan Tahunan | Sarah | Completed |

Beberapa task sengaja **tanpa assignee** dan ada yang **overdue** agar notifikasi navbar terisi.

### Uji akses cepat

```bash
php scripts/access-matrix.php
```

## Struktur Penting

```
app/Services/
├── PermissionService.php      # Permission role global
├── ProjectAccessService.php   # Akses per project & task
├── ActivityLogService.php
└── NotificationService.php

resources/js/config/navigation.js  # Menu + ability filter
```

## Catatan Pengembangan

- Contributor dapat menandai task **Done** pada task yang ditugaskan ke dirinya.
- Statistik Dashboard & Projects di-scope ke project yang `accessibleBy` user.
- Akses lihat project hanya via PM, keanggotaan, atau Super Admin (bukan lagi otomatis per departemen).
- **Notifikasi navbar** disimpan di `notification_dismissals` — bisa ditandai dibaca per item atau sekaligus.
- **Team Lead** otomatis mendapat akses **Admin** saat ditambahkan ke tim project.
- Badge **PM N project** tampil di sidebar & profil jika user menjadi `manager_id`.
- Statistik **Team members** di dashboard di-scope ke anggota project yang dapat diakses.

## Pengujian

```bash
php artisan test
php scripts/access-matrix.php
```

## Tahap 3 — Email, Live Refresh, Export

| Fitur | Keterangan |
|-------|------------|
| Email assign task | Otomatis ke assignee saat task ditetapkan (queue) |
| Email pengingat | `php artisan mathpro:send-task-reminders` — jadwal harian 08:00 |
| Live refresh | Polling 45s (navbar) & 30s (chat) saat tab aktif |
| Export CSV | Reports → Export Project / Export Task |
| Activity log | Profil, foto, password tercatat |

### Email (development)

Default `MAIL_MAILER=log` — cek `storage/logs/laravel.log`.  
Production: set SMTP di `.env` dan jalankan `php artisan queue:work`.

### Scheduler (production)

```bash
* * * * * cd /path/to/mathpro && php artisan schedule:run >> /dev/null 2>&1
```
