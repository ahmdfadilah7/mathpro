export const DEMO_PASSWORD = 'password';

export const demoAccounts = [
    {
        name: 'Ahmad Rizki',
        email: 'admin@mathpro.test',
        role: 'Super Admin',
        roleColor: 'rose',
        department: 'DEV',
        menu: 'Semua menu + Roles/Users',
        projects: 'Semua project (bypass)',
    },
    {
        name: 'Sarah Wijaya',
        email: 'manager@mathpro.test',
        role: 'Project Manager',
        roleColor: 'brand',
        department: 'DEV',
        menu: 'Projects, Tasks, Calendar, Unassigned, Chat, Reports',
        projects: 'PM: ERP, Mobile, Portal FIN — bisa buat project',
    },
    {
        name: 'Diana Putri',
        email: 'diana@mathpro.test',
        role: 'Project Manager',
        roleColor: 'brand',
        department: 'DIGITAL',
        menu: 'Sama seperti PM (tanpa Roles/Users)',
        projects: 'PM: Kampanye Digital Q2',
    },
    {
        name: 'Rio Pratama',
        email: 'rio@mathpro.test',
        role: 'Team Lead',
        roleColor: 'indigo',
        department: 'DEV',
        menu: 'Sama seperti PM, tanpa buat project',
        projects: 'Admin ERP & Mobile; Contributor INF',
    },
    {
        name: 'Budi Santoso',
        email: 'member@mathpro.test',
        role: 'Member',
        roleColor: 'slate',
        department: 'QA',
        menu: 'Tanpa Reports & Unassigned',
        projects: 'Contributor ERP',
    },
    {
        name: 'Siti Aminah',
        email: 'siti@mathpro.test',
        role: 'Member',
        roleColor: 'slate',
        department: 'DEV',
        menu: 'Tanpa Reports & Unassigned',
        projects: 'Contributor ERP & Mobile; Viewer Marketing',
    },
    {
        name: 'Agus Hermawan',
        email: 'agus@mathpro.test',
        role: 'Member',
        roleColor: 'slate',
        department: 'INFRA',
        menu: 'Tanpa Reports & Unassigned',
        projects: 'PM: Migrasi Infrastruktur Cloud',
    },
];

export const demoProjects = [
    { code: 'PRJ-ERP-001', name: 'Modernisasi ERP', pm: 'Sarah', status: 'Active' },
    { code: 'PRJ-MOB-002', name: 'Redesign Aplikasi Mobile', pm: 'Sarah', status: 'Active' },
    { code: 'PRJ-MKT-003', name: 'Kampanye Digital Q2', pm: 'Diana', status: 'Planning' },
    { code: 'PRJ-INF-004', name: 'Migrasi Infrastruktur Cloud', pm: 'Agus', status: 'On Hold' },
    { code: 'PRJ-FIN-005', name: 'Portal Laporan Tahunan', pm: 'Sarah', status: 'Completed' },
];

export const usageSections = [
    {
        id: 'login',
        title: 'Login',
        steps: [
            'Buka halaman login dan masukkan email akun demo.',
            `Password semua akun: **${DEMO_PASSWORD}**`,
            'Setelah masuk, Anda diarahkan ke Dashboard.',
        ],
    },
    {
        id: 'projects',
        title: 'Projects & Task',
        steps: [
            'Buka Projects untuk melihat daftar project yang dapat diakses.',
            'Project Manager dapat membuat project baru (tombol New Project).',
            'Kelola task di board: ubah status, assignee, due date, dan prioritas.',
            'Menu Unassigned menampilkan task tanpa assignee — hanya untuk PM / Admin anggota.',
            'Contributor dapat menandai task Done pada task yang ditugaskan ke dirinya.',
        ],
    },
    {
        id: 'chat',
        title: 'Chat',
        steps: [
            'Pilih project di panel kiri untuk obrolan tim.',
            'Mode Task untuk komentar pada thread task tertentu.',
            'Pesan ter-refresh otomatis setiap 30 detik saat tab aktif.',
        ],
    },
    {
        id: 'search',
        title: 'Pencarian & Notifikasi',
        steps: [
            'Global Search (Ctrl+K): cari project, task, dan user.',
            'Lonceng notifikasi: task overdue, jatuh tempo hari ini, unassigned, chat belum dibaca.',
            'Notifikasi bisa ditandai dibaca per item atau sekaligus.',
            'Badge navbar ter-refresh otomatis setiap 45 detik.',
        ],
    },
    {
        id: 'reports',
        title: 'Reports & Export',
        steps: [
            'Buka Reports (role dengan akses laporan).',
            'Filter opsional per project.',
            'Unduh Export Project (CSV) atau Export Task (CSV).',
        ],
    },
    {
        id: 'profile',
        title: 'Profile & Admin',
        steps: [
            'Ubah nama, email, foto profil, dan password di halaman Profile.',
            'Perubahan profil tercatat di Activity Log.',
            'Super Admin (admin@) mengelola Roles & Users.',
        ],
    },
];

export const demoScenarios = [
    { label: 'Akses penuh sistem', email: 'admin@mathpro.test' },
    { label: 'Buat project & kelola ERP/Mobile/FIN', email: 'manager@mathpro.test' },
    { label: 'PM hanya Marketing', email: 'diana@mathpro.test' },
    { label: 'Assign task & admin tim ERP', email: 'rio@mathpro.test' },
    { label: 'Member — task sendiri saja', email: 'member@mathpro.test' },
    { label: 'Contributor + Viewer di project berbeda', email: 'siti@mathpro.test' },
    { label: 'PM project infrastruktur', email: 'agus@mathpro.test' },
];
