<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAccessService;

$s = app(ProjectAccessService::class);
$emails = [
    'admin@mathpro.test',
    'manager@mathpro.test',
    'diana@mathpro.test',
    'rio@mathpro.test',
    'member@mathpro.test',
    'siti@mathpro.test',
    'agus@mathpro.test',
];
$codes = ['PRJ-ERP-001', 'PRJ-MKT-003', 'PRJ-INF-004'];

foreach ($emails as $email) {
    $u = User::where('email', $email)->with('role')->first();
    echo "\n=== {$email} ({$u->role->slug}) ===\n";
    foreach ($codes as $code) {
        $p = Project::where('code', $code)->first();
        if (! $p) {
            continue;
        }
        $perm = $s->permissionsFor($u, $p);
        echo sprintf(
            "%s: view=%d manage=%d create=%d assign=%d delete=%d [%s]\n",
            $code,
            (int) $perm['can_view'],
            (int) $perm['can_manage_project'],
            (int) $perm['can_create_task'],
            (int) $s->canAssignTask($u, $p),
            (int) $perm['can_delete_task'],
            $perm['access_label'] ?? '-'
        );
    }
    echo 'accessible: '.Project::accessibleBy($u)->pluck('code')->join(', ')."\n";
}
