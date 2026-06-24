<?php

namespace App\Models;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'division_id',
        'department_id',
        'manager_id',
        'status',
        'priority',
        'progress',
        'start_date',
        'due_date',
        'budget',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'priority' => ProjectPriority::class,
            'start_date' => 'date',
            'due_date' => 'date',
            'budget' => 'decimal:2',
            'progress' => 'integer',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->role?->slug === 'super-admin') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('manager_id', $user->id)
                ->orWhereHas('members', fn (Builder $m) => $m->where('user_id', $user->id));

            if ($user->department_id) {
                $q->orWhere('department_id', $user->department_id);
            }
        });
    }

    /** Project tempat user adalah PM atau anggota terdaftar */
    public function scopeWhereUserIsMemberOrManager(Builder $query, User $user): Builder
    {
        if ($user->role?->slug === 'super-admin') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('manager_id', $user->id)
                ->orWhereHas('members', fn (Builder $m) => $m->where('user_id', $user->id));
        });
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($filters['priority'] ?? null, fn (Builder $q, string $priority) => $q->where('priority', $priority))
            ->when($filters['division_id'] ?? null, fn (Builder $q, $id) => $q->where('division_id', $id))
            ->when($filters['department_id'] ?? null, fn (Builder $q, $id) => $q->where('department_id', $id))
            ->when($filters['manager_id'] ?? null, fn (Builder $q, $id) => $q->where('manager_id', $id));
    }
}
