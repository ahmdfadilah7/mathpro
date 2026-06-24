<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Data demo mengikuti alur MathPro:
 * 1. Role & organisasi (divisi/departemen)
 * 2. User (Super Admin, PM, Team Lead, Member)
 * 3. Project + anggota + task (My Tasks, Unassigned, Calendar, Reports)
 * 4. Chat project & komentar task
 *
 * Login: semua password = "password"
 * - admin@mathpro.test (Super Admin)
 * - manager@mathpro.test (PM — ERP & Mobile)
 * - diana@mathpro.test (PM — Marketing)
 * - member@mathpro.test (QA)
 * - siti@mathpro.test (Developer)
 * - rio@mathpro.test (Tech Lead)
 * - agus@mathpro.test (DevOps)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            OrganizationSeeder::class,
            UserSeeder::class,
            ProjectSeeder::class,
            ChatSeeder::class,
            ActivityLogSeeder::class,
        ]);
    }
}
