<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import SearchableSelect from '@/Components/UI/SearchableSelect.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    ArrowDownTrayIcon,
    ChartBarIcon,
    CheckCircleIcon,
    ClipboardDocumentListIcon,
    ExclamationTriangleIcon,
    InboxIcon,
    Squares2X2Icon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    summary: Object,
    tasksByStatus: Array,
    tasksByPriority: Array,
    projectsByStatus: Array,
    projectReports: Array,
    teamWorkload: Array,
    filters: Object,
    filterOptions: Object,
});

const localProjectId = ref(props.filters?.project_id ?? '');

const projectFilterOptions = computed(() => [
    { value: '', label: 'Semua project' },
    ...(props.filterOptions?.projects ?? []).map((p) => ({
        value: p.id,
        label: `${p.code} — ${p.name}`,
    })),
]);

watch(
    () => props.filters?.project_id,
    (id) => {
        localProjectId.value = id ?? '';
    }
);

const applyProjectFilter = () => {
    const params = {};
    if (localProjectId.value) {
        params.project = localProjectId.value;
    }
    router.get(route('reports.index'), params, {
        preserveState: true,
        replace: true,
    });
};

const barTotal = (items) => items.reduce((sum, i) => sum + i.count, 0) || 1;

const statusBarClass = {
    slate: 'bg-slate-400',
    brand: 'bg-brand-500',
    amber: 'bg-amber-400',
    emerald: 'bg-emerald-500',
    indigo: 'bg-indigo-500',
    rose: 'bg-rose-400',
};

const priorityBarClass = {
    slate: 'bg-slate-400',
    brand: 'bg-brand-500',
    amber: 'bg-amber-400',
    rose: 'bg-rose-500',
};

const exportQuery = computed(() => {
    const params = new URLSearchParams();
    if (localProjectId.value) {
        params.set('project', localProjectId.value);
    }
    const qs = params.toString();
    return qs ? `?${qs}` : '';
});
</script>

<template>
    <Head title="Reports" />

    <AppLayout
        title="Reports"
        subtitle="Laporan progress project dan performa tim"
    >
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-md flex-1">
                <label class="mb-1 block text-xs font-semibold text-slate-600">
                    Filter project
                </label>
                <SearchableSelect
                    v-model="localProjectId"
                    :options="projectFilterOptions"
                    placeholder="Semua project"
                    @update:model-value="applyProjectFilter"
                />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a
                    :href="route('reports.export.projects') + exportQuery"
                    class="btn-secondary inline-flex items-center gap-1.5 text-sm"
                >
                    <ArrowDownTrayIcon class="h-4 w-4" />
                    Export Project (CSV)
                </a>
                <a
                    :href="route('reports.export.tasks') + exportQuery"
                    class="btn-secondary inline-flex items-center gap-1.5 text-sm"
                >
                    <ArrowDownTrayIcon class="h-4 w-4" />
                    Export Task (CSV)
                </a>
            </div>
        </div>

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                title="Project"
                :value="summary.projects"
                subtitle="Dalam cakupan laporan"
                :icon="Squares2X2Icon"
                color="brand"
            />
            <StatCard
                title="Total Task"
                :value="summary.tasks_total"
                :subtitle="`${summary.tasks_open} masih terbuka`"
                :icon="ClipboardDocumentListIcon"
                color="sky"
            />
            <StatCard
                title="Tingkat Selesai"
                :value="`${summary.completion_rate}%`"
                :subtitle="`${summary.tasks_done} task selesai`"
                :icon="CheckCircleIcon"
                color="emerald"
            />
            <StatCard
                title="Terlambat"
                :value="summary.tasks_overdue"
                :subtitle="`${summary.tasks_unassigned} belum ditugaskan`"
                :icon="ExclamationTriangleIcon"
                color="rose"
            />
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="card lg:col-span-1">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="font-semibold text-slate-900">Task per Status</h2>
                    <p class="text-sm text-slate-500">Distribusi seluruh task</p>
                </div>
                <div class="space-y-4 p-6">
                    <div v-for="item in tasksByStatus" :key="item.value">
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-700">{{ item.label }}</span>
                            <span class="font-semibold text-slate-900">{{ item.count }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full transition-all duration-700"
                                :class="statusBarClass[item.color] ?? 'bg-slate-400'"
                                :style="{
                                    width: `${(item.count / barTotal(tasksByStatus)) * 100}%`,
                                }"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card lg:col-span-1">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="font-semibold text-slate-900">Task per Prioritas</h2>
                    <p class="text-sm text-slate-500">Beban berdasarkan prioritas</p>
                </div>
                <div class="space-y-4 p-6">
                    <div v-for="item in tasksByPriority" :key="item.value">
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-700">{{ item.label }}</span>
                            <span class="font-semibold text-slate-900">{{ item.count }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full transition-all duration-700"
                                :class="priorityBarClass[item.color] ?? 'bg-slate-400'"
                                :style="{
                                    width: `${(item.count / barTotal(tasksByPriority)) * 100}%`,
                                }"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="projectsByStatus.length"
                class="card lg:col-span-1"
            >
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="font-semibold text-slate-900">Project per Status</h2>
                    <p class="text-sm text-slate-500">Semua project terakses</p>
                </div>
                <div class="space-y-4 p-6">
                    <div v-for="item in projectsByStatus" :key="item.value">
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-700">{{ item.label }}</span>
                            <span class="font-semibold text-slate-900">{{ item.count }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full transition-all duration-700"
                                :class="statusBarClass[item.color] ?? 'bg-slate-400'"
                                :style="{
                                    width: `${(item.count / barTotal(projectsByStatus)) * 100}%`,
                                }"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="card xl:col-span-1">
                <div class="flex items-center gap-2 border-b border-slate-100 px-6 py-4">
                    <ChartBarIcon class="h-5 w-5 text-brand-600" />
                    <div>
                        <h2 class="font-semibold text-slate-900">Progress per Project</h2>
                        <p class="text-sm text-slate-500">Ringkasan task & penyelesaian</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-left text-sm">
                        <thead class="border-b border-slate-100 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-6 py-3">Project</th>
                                <th class="px-4 py-3 text-center">Task</th>
                                <th class="px-4 py-3 text-center">Selesai</th>
                                <th class="px-4 py-3 text-center">Terlambat</th>
                                <th class="px-4 py-3">Progress</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="project in projectReports"
                                :key="project.id"
                                class="transition hover:bg-slate-50/80"
                            >
                                <td class="px-6 py-3">
                                    <Link
                                        :href="route('projects.show', project.id)"
                                        class="group flex items-center gap-3"
                                    >
                                        <span
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[10px] font-bold text-white"
                                            :style="{ backgroundColor: project.color }"
                                        >
                                            {{ project.code?.slice(0, 2) }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-slate-900 group-hover:text-brand-700">
                                                {{ project.name }}
                                            </p>
                                            <p class="text-xs text-slate-500">
                                                {{ project.code }}
                                                <Badge
                                                    :color="project.status_color"
                                                    size="sm"
                                                    class="ml-1"
                                                >
                                                    {{ project.status_label }}
                                                </Badge>
                                            </p>
                                        </div>
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-center font-medium text-slate-800">
                                    {{ project.tasks_total }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-medium text-emerald-700">
                                        {{ project.tasks_done }}
                                    </span>
                                    <span class="text-xs text-slate-400">
                                        ({{ project.completion_rate }}%)
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        :class="
                                            project.tasks_overdue
                                                ? 'font-semibold text-rose-600'
                                                : 'text-slate-400'
                                        "
                                    >
                                        {{ project.tasks_overdue }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="min-w-[100px]">
                                        <ProgressBar
                                            :value="project.progress"
                                            :color="project.color"
                                        />
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!projectReports.length">
                                <td
                                    colspan="5"
                                    class="px-6 py-10 text-center text-sm text-slate-400"
                                >
                                    Tidak ada data project.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card xl:col-span-1">
                <div class="flex items-center gap-2 border-b border-slate-100 px-6 py-4">
                    <UserGroupIcon class="h-5 w-5 text-brand-600" />
                    <div>
                        <h2 class="font-semibold text-slate-900">Beban Tim</h2>
                        <p class="text-sm text-slate-500">Task per anggota (assignee)</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    <div
                        v-for="member in teamWorkload"
                        :key="member.user_id"
                        class="flex items-center gap-4 px-6 py-4"
                    >
                        <Avatar
                            :name="member.name"
                            :initials="member.initials"
                            size="sm"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-slate-900">{{ member.name }}</p>
                            <div class="mt-1 flex flex-wrap gap-3 text-xs text-slate-500">
                                <span>{{ member.tasks_total }} total</span>
                                <span class="text-brand-700">{{ member.tasks_open }} terbuka</span>
                                <span class="text-emerald-600">{{ member.tasks_done }} selesai</span>
                                <span
                                    v-if="member.tasks_overdue"
                                    class="font-medium text-rose-600"
                                >
                                    {{ member.tasks_overdue }} terlambat
                                </span>
                            </div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full bg-brand-500 transition-all"
                                    :style="{
                                        width: `${
                                            member.tasks_total
                                                ? (member.tasks_open / member.tasks_total) * 100
                                                : 0
                                        }%`,
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="!teamWorkload.length"
                        class="flex flex-col items-center gap-2 px-6 py-10 text-slate-400"
                    >
                        <InboxIcon class="h-8 w-8" />
                        <p class="text-sm">Belum ada task dengan assignee.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
