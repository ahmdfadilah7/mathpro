<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'department_id',
        'position',
        'phone',
        'avatar',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'initials',
        'avatar_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function getInitialsAttribute(): string
    {
        $parts = explode(' ', $this->name);

        return strtoupper(
            collect($parts)
                ->take(2)
                ->map(fn (string $part) => mb_substr($part, 0, 1))
                ->join('')
        );
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function managedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'manager_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    public function projectMemberships(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }

    /**
     * Brief representation for API/Inertia (initials is computed, not a DB column).
     *
     * @return array{id: int, name: string, initials: string, avatar_url: string|null}
     */
    public function toBrief(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'initials' => $this->initials,
            'avatar_url' => $this->avatar_url,
        ];
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%");
                });
            })
            ->when($filters['role_id'] ?? null, fn (Builder $q, int $id) => $q->where('role_id', $id))
            ->when($filters['department_id'] ?? null, fn (Builder $q, int $id) => $q->where('department_id', $id))
            ->when($filters['division_id'] ?? null, function (Builder $q, int $divisionId) {
                $q->whereHas('department', fn (Builder $d) => $d->where('division_id', $divisionId));
            })
            ->when(
                ($filters['is_active'] ?? '') !== '',
                fn (Builder $q) => $q->where('is_active', (bool) $filters['is_active'])
            );
    }
}
