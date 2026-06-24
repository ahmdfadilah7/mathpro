<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('task_number')->nullable()->after('project_id');
        });

        $rows = DB::table('tasks')
            ->select('id', 'project_id')
            ->orderBy('project_id')
            ->orderBy('id')
            ->get();

        $sequence = [];

        foreach ($rows as $row) {
            $sequence[$row->project_id] = ($sequence[$row->project_id] ?? 0) + 1;
            DB::table('tasks')
                ->where('id', $row->id)
                ->update(['task_number' => $sequence[$row->project_id]]);
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('task_number')->nullable(false)->change();
            $table->unique(['project_id', 'task_number']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'task_number']);
            $table->dropColumn('task_number');
        });
    }
};
