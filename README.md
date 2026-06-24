# MathPro — Project Management System

Aplikasi manajemen project profesional untuk perusahaan multi-divisi dan multi-departemen. Dibangun dengan **Laravel 12**, **Vue 3**, **Inertia.js**, dan **Tailwind CSS**.

## Tech Stack

- **Backend:** Laravel 12, SQLite (default) / MySQL
- **Frontend:** Vue 3, Inertia.js, Tailwind CSS, Heroicons
- **Auth:** Laravel Breeze

## Fitur (Roadmap)

| Modul | Status |
|-------|--------|
| Dashboard | ✅ |
| Projects | ✅ |
| My Tasks | ✅ |
| Calendar & Timeline | ✅ |
| Unassigned | ✅ |
| Chat | ✅ |
| Reports | ✅ |
| Roles | ✅ |
| Users | ✅ |

## Struktur Project

```
app/
├── Enums/          # ProjectStatus, TaskStatus, TaskPriority
├── Http/Controllers/
└── Models/         # Division, Department, Role, Project, Task, User

resources/js/
├── Components/
│   ├── Layout/     # Sidebar, AppHeader
│   └── UI/         # StatCard, Badge, Avatar, ProgressBar
├── config/         # navigation.js
├── Layouts/        # AppLayout.vue
└── Pages/
    ├── Dashboard/
    └── Placeholder/
```

## Instalasi

```bash
# Install dependencies
composer install
npm install

# Environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate:fresh --seed

# Development
php artisan serve
npm run dev
```

Buka `http://localhost:8000`

## Akun Demo

Semua akun: password **`password`**

| Email | Role | Kegunaan uji |
|-------|------|----------------|
| admin@mathpro.test | Super Admin | Semua menu + Roles/Users |
| manager@mathpro.test | Project Manager | PM project ERP & Mobile |
| diana@mathpro.test | Project Manager | PM project Marketing |
| rio@mathpro.test | Team Lead | Admin anggota di ERP |
| member@mathpro.test | Member | My Tasks, task QA |
| siti@mathpro.test | Member | Developer, task frontend |
| agus@mathpro.test | Member | DevOps, project infrastruktur |

## Tahap Pengembangan

Proyek dikembangkan **satu modul per tahap**:

1. ✅ **Tahap 1** — Fondasi + Dashboard
2. ✅ **Tahap 2** — Projects (CRUD, filter divisi/departemen)
3. **Tahap 3** — My Tasks
4. **Tahap 4** — Calendar & Timeline
5. **Tahap 5** — Unassigned
6. **Tahap 6** — Chat
7. **Tahap 7** — Reports
8. **Tahap 8** — Roles & Users
