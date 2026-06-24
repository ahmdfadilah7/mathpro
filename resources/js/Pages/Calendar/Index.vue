<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import FullYearCalendar from '@/Components/Calendar/FullYearCalendar.vue';
import TimelineBar from '@/Components/Calendar/TimelineBar.vue';
import TaskDetailModal from '@/Components/Projects/TaskDetailModal.vue';
import SearchableSelect from '@/Components/UI/SearchableSelect.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    CalendarDaysIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChartBarIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    filters: Object,
    calendar: Object,
    timeline: Object,
    filterOptions: Object,
    stats: Object,
});

const localFilters = ref({ ...props.filters });
const selectedTask = ref(null);
const showTaskDetail = ref(false);
const expandedProjects = ref(new Set());

const activeTab = computed(() => props.filters.tab ?? 'calendar');
const timelineView = computed(() => props.timeline.view ?? 'month');
const scaleUnits = computed(() => props.timeline.units ?? []);
const totalUnits = computed(() => props.timeline.total_units ?? 1);
const timelineProjects = computed(() => props.timeline.projects ?? []);

const scaleGridStyle = computed(() => ({
    gridTemplateColumns: scaleUnits.value.map((u) => `${u.span}fr`).join(' '),
}));

const timelineMinWidth = computed(() => {
    if (timelineView.value === 'year') return '960px';
    if (timelineView.value === 'month') {
        return `${Math.max(720, totalUnits.value * 22)}px`;
    }
    return '560px';
});

const timelineTaskCount = computed(() =>
    timelineProjects.value.reduce((n, p) => n + (p.tasks?.length ?? 0), 0)
);

const projectFilterOptions = [
    { value: '', label: 'Semua project' },
    ...(props.filterOptions.projects ?? []).map((p) => ({
        value: p.id,
        label: `${p.code} — ${p.name}`,
    })),
];

const tabs = [
    { id: 'calendar', label: 'Calendar', icon: CalendarDaysIcon },
    { id: 'timeline', label: 'Timeline', icon: ChartBarIcon },
];

watch(
    timelineProjects,
    (projects) => {
        expandedProjects.value = new Set(projects.map((p) => p.id));
    },
    { immediate: true }
);

const applyFilters = () => {
    router.get(route('calendar.index'), localFilters.value, {
        preserveState: true,
        replace: true,
    });
};

const setTab = (tab) => {
    if (localFilters.value.tab === tab) return;
    localFilters.value.tab = tab;
    applyFilters();
};

const setTimelineView = (view) => {
    if (localFilters.value.timeline_view === view) return;
    localFilters.value.timeline_view = view;
    applyFilters();
};

const shiftYear = (delta) => {
    localFilters.value.year = Number(localFilters.value.year) + delta;
    applyFilters();
};

const shiftMonth = (delta) => {
    let month = Number(localFilters.value.month) + delta;
    let year = Number(localFilters.value.year);
    if (month > 12) {
        month = 1;
        year++;
    } else if (month < 1) {
        month = 12;
        year--;
    }
    localFilters.value.month = month;
    localFilters.value.year = year;
    applyFilters();
};

const shiftWeek = (delta) => {
    localFilters.value.week = Number(localFilters.value.week) + delta;
    if (localFilters.value.week > 53) {
        localFilters.value.week = 1;
        localFilters.value.year++;
    } else if (localFilters.value.week < 1) {
        localFilters.value.week = 53;
        localFilters.value.year--;
    }
    applyFilters();
};

const shiftTimeline = () => {
    if (timelineView.value === 'week') shiftWeek(-1);
    else if (timelineView.value === 'month') shiftMonth(-1);
    else shiftYear(-1);
};

const shiftTimelineForward = () => {
    if (timelineView.value === 'week') shiftWeek(1);
    else if (timelineView.value === 'month') shiftMonth(1);
    else shiftYear(1);
};

const goThisYear = () => {
    localFilters.value.year = new Date().getFullYear();
    applyFilters();
};

const toggleProject = (id) => {
    if (expandedProjects.value.has(id)) {
        expandedProjects.value.delete(id);
    } else {
        expandedProjects.value.add(id);
    }
    expandedProjects.value = new Set(expandedProjects.value);
};

const openTaskDetail = (task) => {
    selectedTask.value = task;
    showTaskDetail.value = true;
};

const closeTaskDetail = () => {
    showTaskDetail.value = false;
    selectedTask.value = null;
};

const onProjectCalendarClick = (href) => {
    router.visit(href);
};

const taskBarColor = {
    slate: '#64748b',
    brand: '#14b8a6',
    amber: '#f59e0b',
    emerald: '#10b981',
    rose: '#f43f5e',
};
</script>

<template>
    <Head title="Calendar & Timeline" />

    <AppLayout
        title="Calendar & Timeline"
        subtitle="Kalender tahunan (12 bulan) dan timeline task per project"
    >
        <div class="card mb-6 p-4">
            <div class="flex flex-col gap-4">
                <div class="inline-flex w-fit rounded-xl border border-slate-200 bg-slate-50 p-1">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition"
                        :class="
                            activeTab === tab.id
                                ? 'bg-white text-brand-700 shadow-sm'
                                : 'text-slate-600 hover:text-slate-900'
                        "
                        @click="setTab(tab.id)"
                    >
                        <component :is="tab.icon" class="h-4 w-4" />
                        {{ tab.label }}
                    </button>
                </div>

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-200 p-2 text-slate-600 transition hover:bg-slate-50"
                            @click="
                                activeTab === 'calendar' ? shiftYear(-1) : shiftTimeline()
                            "
                        >
                            <ChevronLeftIcon class="h-5 w-5" />
                        </button>
                        <div class="min-w-[160px] text-center">
                            <p class="text-lg font-bold text-slate-900">
                                {{
                                    activeTab === 'calendar'
                                        ? calendar.label
                                        : timeline.range.label
                                }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{
                                    activeTab === 'calendar'
                                        ? '12 bulan · FullCalendar'
                                        : `Timeline · ${timelineView}`
                                }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="rounded-xl border border-slate-200 p-2 text-slate-600 transition hover:bg-slate-50"
                            @click="
                                activeTab === 'calendar'
                                    ? shiftYear(1)
                                    : shiftTimelineForward()
                            "
                        >
                            <ChevronRightIcon class="h-5 w-5" />
                        </button>
                        <button
                            v-if="activeTab === 'calendar'"
                            type="button"
                            class="btn-secondary ml-1 text-xs"
                            @click="goThisYear"
                        >
                            Tahun ini
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <SearchableSelect
                            v-model="localFilters.project_id"
                            :options="projectFilterOptions"
                            placeholder="Filter project"
                            class="min-w-[220px]"
                            @update:model-value="applyFilters"
                        />
                        <div class="flex gap-4 text-sm text-slate-500">
                            <span>{{ stats.projects }} project</span>
                            <span>{{ stats.tasks }} task</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: Calendar (FullCalendar 12 bulan) -->
        <div v-show="activeTab === 'calendar'" class="card overflow-hidden">
            <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-4">
                <CalendarDaysIcon class="h-5 w-5 text-brand-600" />
                <div>
                    <h2 class="font-semibold text-slate-900">
                        Kalender {{ calendar.year }}
                    </h2>
                    <p class="text-sm text-slate-500">
                        Tampilan 12 bulan · klik task untuk detail · milestone project
                        untuk buka halaman project
                    </p>
                </div>
            </div>

            <FullYearCalendar
                :year="calendar.year"
                :events="calendar.events ?? []"
                @task-click="openTaskDetail"
                @project-click="onProjectCalendarClick"
            />
        </div>

        <!-- Tab: Timeline — per project, semua task -->
        <div v-show="activeTab === 'timeline'" class="card overflow-hidden">
            <div
                class="flex flex-col gap-4 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-2">
                    <ChartBarIcon class="h-5 w-5 text-brand-600" />
                    <div>
                        <h2 class="font-semibold text-slate-900">Timeline Task</h2>
                        <p class="text-sm text-slate-500">
                            {{ timeline.range.label }} · {{ timelineTaskCount }} task ·
                            klik bar untuk detail
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex rounded-xl border border-slate-200 bg-slate-50 p-1">
                        <button
                            v-for="opt in filterOptions.timeline_views"
                            :key="opt.value"
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                            :class="
                                timelineView === opt.value
                                    ? 'bg-white text-brand-700 shadow-sm'
                                    : 'text-slate-600 hover:text-slate-900'
                            "
                            @click="setTimelineView(opt.value)"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="timelineProjects.length" class="overflow-x-auto">
                <div
                    class="grid border-b border-slate-100 bg-slate-50/80"
                    :style="[scaleGridStyle, { minWidth: timelineMinWidth }]"
                >
                    <div
                        v-for="unit in scaleUnits"
                        :key="unit.key"
                        class="border-r border-slate-100 px-0.5 py-2 text-center text-[10px] font-semibold text-slate-500 last:border-r-0"
                        :class="timelineView === 'month' ? 'min-w-[18px]' : ''"
                    >
                        <span>{{ unit.label }}</span>
                        <span v-if="unit.sub" class="block text-[8px] font-normal text-slate-400">
                            {{ unit.sub }}
                        </span>
                    </div>
                </div>

                <div class="divide-y divide-slate-100" :style="{ minWidth: timelineMinWidth }">
                    <template v-for="project in timelineProjects" :key="project.id">
                        <div class="flex bg-slate-50/60">
                            <button
                                type="button"
                                class="flex w-56 shrink-0 items-center gap-2 border-r border-slate-100 px-3 py-2.5 text-left hover:bg-slate-100/80"
                                @click="toggleProject(project.id)"
                            >
                                <span
                                    class="h-2.5 w-2.5 shrink-0 rounded-full"
                                    :style="{ backgroundColor: project.color }"
                                />
                                <span class="truncate text-sm font-bold text-slate-900">
                                    {{ project.code }}
                                </span>
                                <span class="text-xs text-slate-400">
                                    {{ expandedProjects.has(project.id) ? '▼' : '▶' }}
                                </span>
                                <span
                                    class="ml-auto rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold text-slate-600 ring-1 ring-slate-200"
                                >
                                    {{ project.task_count }}
                                </span>
                            </button>
                            <div
                                class="relative min-h-[36px] flex-1"
                                :style="{
                                    display: 'grid',
                                    gridTemplateColumns: `repeat(${totalUnits}, 1fr)`,
                                }"
                            >
                                <div
                                    v-for="(_, i) in totalUnits"
                                    :key="i"
                                    class="border-r border-slate-100/50 last:border-r-0"
                                />
                            </div>
                        </div>

                        <template v-if="expandedProjects.has(project.id)">
                            <div
                                v-for="task in project.tasks"
                                :key="task.id"
                                class="flex border-t border-slate-100/80 hover:bg-slate-50/50"
                            >
                                <button
                                    type="button"
                                    class="flex w-56 shrink-0 flex-col gap-1 border-r border-slate-100 px-3 py-3 pl-6 text-left transition hover:bg-slate-50"
                                    @click="openTaskDetail(task)"
                                >
                                    <span class="font-mono text-xs font-bold text-slate-600">
                                        {{ task.task_number }}
                                    </span>
                                    <p class="line-clamp-2 text-sm font-semibold text-slate-900">
                                        {{ task.title }}
                                    </p>
                                    <Badge :color="task.status_color" class="!w-fit !text-[10px]">
                                        {{ task.status_label }}
                                    </Badge>
                                </button>
                                <div
                                    class="relative min-h-[48px] flex-1"
                                    :style="{
                                        display: 'grid',
                                        gridTemplateColumns: `repeat(${totalUnits}, 1fr)`,
                                    }"
                                >
                                    <div
                                        v-for="(_, i) in totalUnits"
                                        :key="i"
                                        class="border-r border-slate-100/50 last:border-r-0"
                                    />
                                    <TimelineBar
                                        v-if="task.bar"
                                        clickable
                                        :label="task.task_number"
                                        :sublabel="`${task.start_date_formatted} – ${task.due_date_formatted}`"
                                        :color="taskBarColor[task.priority_color] ?? '#64748b'"
                                        :bar="task.bar"
                                        height="h-6"
                                        @click="openTaskDetail(task)"
                                    />
                                    <span
                                        v-else
                                        class="absolute inset-0 flex items-center px-3 text-[10px] italic text-slate-400"
                                    >
                                        Di luar periode tampilan
                                    </span>
                                </div>
                            </div>
                        </template>
                    </template>
                </div>
            </div>

            <p v-else class="px-6 py-12 text-center text-sm text-slate-500">
                Tidak ada task pada filter ini.
            </p>

            <div
                class="flex flex-wrap gap-4 border-t border-slate-100 px-5 py-3 text-xs text-slate-500"
            >
                <span>Setiap task = satu baris terpisah</span>
                <span>Warna bar = prioritas</span>
                <span>Tampilan: {{ timelineView }}</span>
            </div>
        </div>

        <TaskDetailModal
            :show="showTaskDetail"
            :task="selectedTask"
            @close="closeTaskDetail"
            @edit="
                (t) =>
                    router.visit(route('projects.show', t.project_id) + '#tasks')
            "
        />
    </AppLayout>
</template>
