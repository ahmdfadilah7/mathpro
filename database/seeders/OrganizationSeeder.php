<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Division;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            [
                'name' => 'Technology',
                'code' => 'TECH',
                'description' => 'Divisi teknologi & produk digital',
                'departments' => [
                    ['name' => 'Software Development', 'code' => 'DEV'],
                    ['name' => 'Infrastructure', 'code' => 'INFRA'],
                    ['name' => 'Quality Assurance', 'code' => 'QA'],
                ],
            ],
            [
                'name' => 'Operations',
                'code' => 'OPS',
                'description' => 'Divisi operasional perusahaan',
                'departments' => [
                    ['name' => 'Human Resources', 'code' => 'HR'],
                    ['name' => 'Finance', 'code' => 'FIN'],
                ],
            ],
            [
                'name' => 'Marketing',
                'code' => 'MKT',
                'description' => 'Divisi pemasaran & brand',
                'departments' => [
                    ['name' => 'Digital Marketing', 'code' => 'DIGITAL'],
                    ['name' => 'Brand & Creative', 'code' => 'CREATIVE'],
                ],
            ],
        ];

        foreach ($structure as $divisionData) {
            $division = Division::create([
                'name' => $divisionData['name'],
                'code' => $divisionData['code'],
                'description' => $divisionData['description'],
                'is_active' => true,
            ]);

            foreach ($divisionData['departments'] as $dept) {
                Department::create([
                    'division_id' => $division->id,
                    'name' => $dept['name'],
                    'code' => $dept['code'],
                    'is_active' => true,
                ]);
            }
        }
    }
}
