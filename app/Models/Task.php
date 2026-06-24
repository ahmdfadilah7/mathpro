<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Task extends Model
{
    protected $fillable = [
        'project_id',
        'assignee_id',
        'created_by',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'start_date',
        'order',
        'estimated_hours',
        'actual_hours',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'date',
            'start_date' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->orderBy('created_at');
    }

    public function latestComment(): HasOne
    {
        return $this->hasOne(TaskComment::class)->latestOfMany();
    }

    protected static function booted(): void
    {
        static::creating(function (Task $task) {
            $task->task_number = static::generateTaskNumber($task->project_id);
        });
    }

    public static function generateTaskNumber(int $projectId): string
    {
        $project = Project::query()->find($projectId);
        $prefix = $project?->code ?? 'TASK';

        $maxSeq = static::query()
            ->where('project_id', $projectId)
            ->where('task_number', 'like', $prefix.'-T%')
            ->lockForUpdate()
            ->pluck('task_number')
            ->map(fn (string $code) => (int) preg_replace('/^.*-T0*/i', '', $code))
            ->max() ?? 0;

        return sprintf('%s-T%03d', $prefix, $maxSeq + 1);
    }

    /** Task yang ditugaskan kepada user (My Tasks) */
    public function scopeAssignedTo(Builder $query, User $user): Builder
    {
        return $query
            ->where('assignee_id', $user->id)
            ->whereHas('project', fn (Builder $p) => $p->accessibleBy($user));
    }

    /** Task belum assignee di project tempat user PM atau anggota */
    public function scopeUnassignedInMemberProjects(Builder $query, User $user): Builder
    {
        return $query
            ->whereNull('assignee_id')
            ->whereHas(
                'project',
                fn (Builder $p) => $p->whereUserIsMemberOrManager($user)
            );
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhere('task_number', 'like', '%'.strtoupper($search).'%')
                        ->orWhereHas('project', function (Builder $p) use ($search) {
                            $p->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['priority'] ?? null, fn (Builder $q, string $priority) => $q->where('priority', $priority))
            ->when($filters['project_id'] ?? null, fn (Builder $q, $id) => $q->where('project_id', $id));
    }
}
