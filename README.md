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

## Akun Demo

Password semua akun: **`password`**

| Email | Role global | Skenario uji |
|-------|-------------|--------------|
| admin@mathpro.test | Super Admin | Semua menu + Roles/Users |
| manager@mathpro.test | Project Manager | PM ERP, Mobile, FIN — bisa buat project |
| diana@mathpro.test | Project Manager | PM Marketing saja |
| rio@mathpro.test | Team Lead | Admin anggota ERP, assign task |
| member@mathpro.test | Member | Contributor ERP, My Tasks saja |
| siti@mathpro.test | Member | Contributor ERP, Viewer MKT |
| agus@mathpro.test | Member | PM project INF (via `manager_id`) |

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
