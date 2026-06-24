<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CalendarService
{
    public function __construct(
        private readonly ProjectAccessService $accessService
    ) {}

    /** @return array<string, mixed> */
    public function getIndexData(array $filters, User $user): array
    {
        $filters = $this->normalizeFilters($filters);
        [$timelineStart, $timelineEnd] = $this->resolveTimelineRange($filters);

        $projects = Project::query()
            ->accessibleBy($user)
            ->with([
                'manager:id,name',
                'tasks' => fn ($q) => $q
                    ->with(['assignee:id,name', 'creator:id,name'])
                    ->orderBy('order')
                    ->orderBy('id'),
            ])
            ->when($filters['project_id'], fn (Builder $q, $id) => $q->where('id', $id))
            ->orderBy('name')
            ->get();

        $scaleUnits = $this->buildScaleUnits($timelineStart, $timelineEnd, $filters['timeline_view']);

        return [
            'filters' => $filters,
            'calendar' => $this->buildYearCalendar($filters['year'], $projects, $user),
            'timeline' => [
                'view' => $filters['timeline_view'],
                'range' => [
                    'start' => $timelineStart->format('Y-m-d'),
                    'end' => $timelineEnd->format('Y-m-d'),
                    'start_formatted' => $timelineStart->format('d M Y'),
                    'end_formatted' => $timelineEnd->format('d M Y'),
                    'label' => $this->timelineRangeLabel($filters, $timelineStart, $timelineEnd),
                ],
                'units' => $scaleUnits,
                'total_units' => array_sum(array_column($scaleUnits, 'span')),
                'projects' => $this->buildTimelineProjects($projects, $user, $timelineStart, $timelineEnd),
            ],
            'filterOptions' => [
                'projects' => Project::query()
                    ->accessibleBy($user)
                    ->orderBy('name')
                    ->get(['id', 'name', 'code', 'color'])
                    ->map(fn (Project $p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'code' => $p->code,
                        'color' => $p->color,
                    ])
                    ->values()
                    ->all(),
                'timeline_views' => [
                    ['value' => 'year', 'label' => 'Tahun'],
                    ['value' => 'month', 'label' => 'Bulan'],
                    ['value' => 'week', 'label' => 'Minggu'],
                ],
            ],
            'stats' => [
                'projects' => $projects->count(),
                'tasks' => $projects->sum(fn (Project $p) => $p->tasks->count()),
            ],
        ];
    }

    /** @param  array<string, mixed>  $filters */
    private function normalizeFilters(array $filters): array
    {
        $year = (int) ($filters['year'] ?? now()->year);
        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->year;
        }

        $month = max(1, min(12, (int) ($filters['month'] ?? now()->month)));
        $week = max(1, min(53, (int) ($filters['week'] ?? now()->isoWeek())));
        $timelineView = in_array($filters['timeline_view'] ?? '', ['year', 'month', 'week'], true)
            ? $filters['timeline_view']
            : 'month';
        $tab = in_array($filters['tab'] ?? '', ['calendar', 'timeline'], true)
            ? $filters['tab']
            : 'calendar';

        return [
            'tab' => $tab,
            'year' => $year,
            'month' => $month,
            'week' => $week,
            'timeline_view' => $timelineView,
            'project_id' => $filters['project_id'] ?? '',
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolveTimelineRange(array $filters): array
    {
        return match ($filters['timeline_view']) {
            'week' => [
                Carbon::now()->setISODate($filters['year'], $filters['week'])->startOfWeek(Carbon::MONDAY),
                Carbon::now()->setISODate($filters['year'], $filters['week'])->endOfWeek(Carbon::SUNDAY),
            ],
            'month' => [
                Carbon::create($filters['year'], $filters['month'], 1)->startOfMonth(),
                Carbon::create($filters['year'], $filters['month'], 1)->endOfMonth(),
            ],
            default => [
                Carbon::create($filters['year'], 1, 1)->startOfDay(),
                Carbon::create($filters['year'], 12, 31)->endOfDay(),
            ],
        };
    }

    private function timelineRangeLabel(array $filters, Carbon $start, Carbon $end): string
    {
        return match ($filters['timeline_view']) {
            'week' => 'Minggu '.$filters['week'].' · '.$start->format('d M').' – '.$end->format('d M Y'),
            'month' => $start->format('F Y'),
            default => (string) $filters['year'],
        };
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return list<array<string, mixed>>
     */
    private function buildTimelineProjects(
        Collection $projects,
        User $user,
        Carbon $rangeStart,
        Carbon $rangeEnd
    ): array {
        $rows = [];

        foreach ($projects as $project) {
            $tasks = $project->tasks
                ->map(fn (Task $task) => $this->mapTaskCalendar($task, $project, $user, $rangeStart, $rangeEnd))
                ->values()
                ->all();

            if ($tasks === []) {
                continue;
            }

            $rows[] = [
                'id' => $project->id,
                'name' => $project->name,
                'code' => $project->code,
                'color' => $project->color ?? '#14b8a6',
                'task_count' => count($tasks),
                'tasks' => $tasks,
            ];
        }

        return $rows;
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return array<string, mixed>
     */
    private function buildYearCalendar(int $year, Collection $projects, User $user): array
    {
        return [
            'year' => $year,
            'label' => (string) $year,
            'events' => $this->buildFullCalendarEvents($year, $projects, $user),
        ];
    }

    /**
     * @param  Collection<int, Project>  $projects
     * @return list<array<string, mixed>>
     */
    private function buildFullCalendarEvents(int $year, Collection $projects, User $user): array
    {
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->endOfDay();
        $events = [];

        foreach ($projects as $project) {
            if ($project->start_date) {
                $events[] = $this->mapProjectMilestoneEvent(
                    $project,
                    'project-start-'.$project->id,
                    $project->start_date,
                    "{$project->code} mulai"
                );
            }
            if ($project->due_date) {
                $events[] = $this->mapProjectMilestoneEvent(
                    $project,
                    'project-due-'.$project->id,
                    $project->due_date,
                    "{$project->code} selesai"
                );
            }

            foreach ($project->tasks as $task) {
                [$start, $end] = $this->resolveTaskDates($task);

                if ($end->lt($yearStart) || $start->gt($yearEnd)) {
                    continue;
                }

                $taskPayload = $this->mapTaskCalendar($task, $project, $user);

                $events[] = [
                    'id' => 'task-'.$task->id,
                    'title' => ($task->task_number ?? 'Task').' · '.$task->title,
                    'start' => $start->format('Y-m-d'),
                    'end' => $end->copy()->addDay()->format('Y-m-d'),
                    'allDay' => true,
                    'backgroundColor' => $this->priorityHex($task->priority->color()),
                    'borderColor' => $this->priorityHex($task->priority->color()),
                    'extendedProps' => [
                        'type' => 'task',
                        'task' => $taskPayload,
                    ],
                ];
            }
        }

        return $events;
    }

    /** @return array<string, mixed> */
    private function mapProjectMilestoneEvent(
        Project $project,
        string $id,
        Carbon $date,
        string $title
    ): array {
        $color = $project->color ?? '#14b8a6';

        return [
            'id' => $id,
            'title' => $title,
            'start' => $date->format('Y-m-d'),
            'allDay' => true,
            'backgroundColor' => $color,
            'borderColor' => $color,
            'extendedProps' => [
                'type' => 'project',
                'project_id' => $project->id,
                'href' => route('projects.show', $project->id),
            ],
        ];
    }

    private function priorityHex(string $color): string
    {
        return match ($color) {
            'brand' => '#14b8a6',
            'amber' => '#f59e0b',
            'rose' => '#f43f5e',
            'emerald' => '#10b981',
            default => '#64748b',
        };
    }

    /** @return array<string, mixed> */
    private function mapTaskCalendar(
        Task $task,
        Project $project,
        User $user,
        ?Carbon $rangeStart = null,
        ?Carbon $rangeEnd = null
    ): array {
        [$start, $end] = $this->resolveTaskDates($task);

        $data = array_merge(
            (new TaskResource($task))->resolve(),
            $this->accessService->taskPermissionsFor($user, $project, $task),
            [
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'code' => $project->code,
                    'color' => $project->color ?? '#14b8a6',
                ],
                'start_date' => $start->format('Y-m-d'),
                'due_date' => $end->format('Y-m-d'),
                'start_date_formatted' => $start->format('d M Y'),
                'due_date_formatted' => $end->format('d M Y'),
                'is_overdue' => $task->status !== TaskStatus::Done
                    && $task->due_date?->isPast(),
            ]
        );

        if ($rangeStart && $rangeEnd) {
            $data['bar'] = $this->mapBar($start, $end, $rangeStart, $rangeEnd);
        }

        return $data;
    }

    /** @return list<array{key: string, label: string, span: int, sub?: string}> */
    private function buildScaleUnits(Carbon $rangeStart, Carbon $rangeEnd, string $view): array
    {
        if ($view === 'year') {
            return $this->buildMonthHeaders($rangeStart, $rangeEnd);
        }

        if ($view === 'month') {
            $units = [];
            $cursor = $rangeStart->copy();

            while ($cursor->lte($rangeEnd)) {
                $units[] = [
                    'key' => $cursor->format('Y-m-d'),
                    'label' => $cursor->format('j'),
                    'span' => 1,
                ];
                $cursor->addDay();
            }

            return $units;
        }

        $units = [];
        $cursor = $rangeStart->copy();
        $dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

        while ($cursor->lte($rangeEnd)) {
            $units[] = [
                'key' => $cursor->format('Y-m-d'),
                'label' => $dayNames[$cursor->dayOfWeekIso - 1],
                'sub' => $cursor->format('j M'),
                'span' => 1,
            ];
            $cursor->addDay();
        }

        return $units;
    }

    /** @return list<array{key: string, label: string, span: int}> */
    private function buildMonthHeaders(Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $headers = [];
        $cursor = $rangeStart->copy()->startOfMonth();

        while ($cursor->lte($rangeEnd)) {
            $monthEnd = $cursor->copy()->endOfMonth();
            $segmentEnd = $monthEnd->lt($rangeEnd) ? $monthEnd : $rangeEnd;
            $days = $cursor->diffInDays($segmentEnd) + 1;

            $headers[] = [
                'key' => $cursor->format('Y-m'),
                'label' => $cursor->format('M'),
                'span' => $days,
            ];

            $cursor = $cursor->copy()->addMonth()->startOfMonth();
        }

        return $headers;
    }

    /** @return array{left: float, width: float}|null */
    private function mapBar(Carbon $start, Carbon $end, Carbon $rangeStart, Carbon $rangeEnd): ?array
    {
        if ($end->lt($rangeStart) || $start->gt($rangeEnd)) {
            return null;
        }

        $visibleStart = $start->lt($rangeStart) ? $rangeStart : $start;
        $visibleEnd = $end->gt($rangeEnd) ? $rangeEnd : $end;
        $totalDays = max(1, $rangeStart->diffInDays($rangeEnd) + 1);
        $offsetDays = $rangeStart->diffInDays($visibleStart);
        $durationDays = max(1, $visibleStart->diffInDays($visibleEnd) + 1);

        $left = ($offsetDays / $totalDays) * 100;
        $width = ($durationDays / $totalDays) * 100;

        return [
            'left' => round($left, 2),
            'width' => round(min($width, 100 - $left), 2),
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolveTaskDates(Task $task): array
    {
        $start = $task->start_date
            ? $task->start_date->copy()
            : ($task->due_date
                ? $task->due_date->copy()->subDays(7)
                : $task->created_at->copy()->startOfDay());

        $end = $task->due_date
            ? $task->due_date->copy()
            : $start->copy()->addDays(7);

        if ($end->lt($start)) {
            $end = $start->copy()->addDay();
        }

        return [$start, $end];
    }
}
