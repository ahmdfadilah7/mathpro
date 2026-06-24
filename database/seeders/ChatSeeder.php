<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()
            ->whereIn('email', [
                'manager@mathpro.test',
                'diana@mathpro.test',
                'rio@mathpro.test',
                'member@mathpro.test',
                'siti@mathpro.test',
                'agus@mathpro.test',
            ])
            ->get()
            ->keyBy('email');

        $sarah = $users['manager@mathpro.test'];
        $diana = $users['diana@mathpro.test'];
        $rio = $users['rio@mathpro.test'];
        $budi = $users['member@mathpro.test'];
        $siti = $users['siti@mathpro.test'];

        $erp = Project::where('code', 'PRJ-ERP-001')->first();
        $mob = Project::where('code', 'PRJ-MOB-002')->first();
        $mkt = Project::where('code', 'PRJ-MKT-003')->first();

        if ($erp) {
            $this->seedProjectChat($erp, [
                [$sarah, 'Tim, sprint review Jumat pukul 14:00. Mohon update status task di board.', 3],
                [$rio, 'Noted. Modul payroll sudah masuk review.', 2],
                [$siti, 'Integrasi inventory hampir selesai, butuh review API dari Rio.', 1],
            ]);
        }

        if ($mob) {
            $this->seedProjectChat($mob, [
                [$sarah, 'Prioritas minggu ini: dark mode + testing Android.', 2],
                [$siti, 'Dark mode sudah di branch feature/dark-mode.', 1],
            ]);
        }

        if ($mkt) {
            $this->seedProjectChat($mkt, [
                [$diana, 'Mohon siapkan draft persona buyer B2B minggu depan.', 1],
            ]);
        }

        $erpTask = Task::query()
            ->where('project_id', $erp?->id)
            ->where('title', 'API payroll & absensi')
            ->first();

        if ($erpTask) {
            $this->seedTaskComments($erpTask, [
                [$rio, 'Endpoint /payroll/summary sudah di staging.', 2],
                [$budi, 'Saya cek dulu untuk skenario UAT.', 1],
                [$sarah, 'Setelah QA OK, kita jadwalkan deploy ke production.', 0],
            ]);
        }

        $mobTask = Task::query()
            ->where('project_id', $mob?->id)
            ->where('title', 'Implementasi dark mode')
            ->first();

        if ($mobTask) {
            $this->seedTaskComments($mobTask, [
                [$siti, 'Warna token sudah diselaraskan dengan design system.', 1],
            ]);
        }
    }

    /** @param  list<array{0: User, 1: string, 2: int}>  $messages */
    private function seedProjectChat(Project $project, array $messages): void
    {
        $conversation = Conversation::firstOrCreate(
            [
                'project_id' => $project->id,
                'participant_key' => Conversation::PROJECT_KEY,
            ],
            ['type' => 'project']
        );

        $participantIds = collect([$project->manager_id])
            ->merge(
                ProjectMember::query()
                    ->where('project_id', $project->id)
                    ->pluck('user_id')
            )
            ->filter()
            ->unique();

        foreach ($participantIds as $userId) {
            $conversation->participants()->syncWithoutDetaching([
                $userId => ['last_read_at' => now()->subHours(2)],
            ]);
        }

        foreach ($messages as [$user, $body, $daysAgo]) {
            Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'body' => $body,
                'created_at' => now()->subDays($daysAgo),
                'updated_at' => now()->subDays($daysAgo),
            ]);
        }
    }

    /** @param  list<array{0: User, 1: string, 2: int}>  $comments */
    private function seedTaskComments(Task $task, array $comments): void
    {
        foreach ($comments as [$user, $body, $daysAgo]) {
            TaskComment::create([
                'task_id' => $task->id,
                'user_id' => $user->id,
                'body' => $body,
                'created_at' => now()->subDays($daysAgo),
                'updated_at' => now()->subDays($daysAgo),
            ]);
        }
    }
}
