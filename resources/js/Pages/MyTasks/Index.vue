<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SearchableSelect from '@/Components/UI/SearchableSelect.vue';
import { getTaskPriorityCardClass, getTaskPriorityTextClass } from '@/utils/taskPriority';
import { notifyError } from '@/utils/notify';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    CalendarIcon,
    CheckCircleIcon,
    ClipboardDocumentListIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    tasks: Object,
    filters: Object,
    stats: Object,
    tabs: Array,
    filterOptions: Object,
});

const isDoneTab = computed(() => localFilters.value.tab === 'done');

const localFilters = ref({ ...props.filters });
const selectedIds = ref([]);
const bulkStatus = ref('');
const savingStatusId = ref(null);
const bulkProcessing = ref(false);

const statusFilterOptions = [
    { value: '', label: 'Semua status' },
    ...(props.filterOptions.statuses ?? []).map((s) => ({
        value: s.value,
        label: s.label,
    })),
];

const priorityFilterOptions = [
    { value: '', label: 'Semua prioritas' },
    ...(props.filterOptions.priorities ?? []).map((p) => ({
        value: p.value,
        label: p.label,
    })),
];

const projectFilterOptions = [
    { value: '', label: 'Semua project' },
    ...(props.filterOptions.projects ?? []).map((p) => ({
        value: p.id,
        label: `${p.code} — ${p.name}`,
    })),
];

const pageTaskIds = computed(() => (props.tasks.data ?? []).map((t) => t.id));

const selectedCount = computed(() => selectedIds.value.length);

const allPageSelected = computed(
    () =>
        pageTaskIds.value.length > 0 &&
        pageTaskIds.value.every((id) => selectedIds.value.includes(id))
);

const selectedTasks = computed(() =>
    (props.tasks.data ?? []).filter((t) => selectedIds.value.includes(t.id))
);

const bulkStatusOptions = computed(() => {
    const selected = selectedTasks.value;
    if (!selected.length) {
        return [];
    }

    let options = selected[0].status_options ?? [];

    for (const task of selected.slice(1)) {
        const values = new Set((task.status_options ?? []).map((o) => o.value));
        options = options.filter((o) => values.has(o.value));
    }

    return options.map((o) => ({ value: o.value, label: o.label }));
});

const canBulkUpdate = computed(
    () => selectedCount.value > 0 && bulkStatus.value && !bulkProcessing.value
);

watch(
    () => props.tasks.data,
    () => {
        selectedIds.value = selectedIds.value.filter((id) => pageTaskIds.value.includes(id));
    }
);

const applyFilters = () => {
    router.get(route('my-tasks.index'), localFilters.value, {
        preserveState: true,
        replace: true,
    });
};

const switchTab = (tabId) => {
    if (localFilters.value.tab === tabId) {
        return;
    }
    localFilters.value.tab = tabId;
    if (tabId === 'done') {
        localFilters.value.status = '';
    }
    clearSelection();
    applyFilters();
};

const resetFilters = () => {
    localFilters.value = {
        tab: localFilters.value.tab ?? 'active',
        search: '',
        status: '',
        priority: '',
        project_id: '',
    };
    applyFilters();
};

let searchTimeout;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
};

const projectShowUrl = (task) => route('projects.show', task.project_id) + '#tasks';

const toggleSelectAll = () => {
    if (allPageSelected.value) {
        selectedIds.value = selectedIds.value.filter((id) => !pageTaskIds.value.includes(id));
    } else {
        const merged = new Set([...selectedIds.value, ...pageTaskIds.value]);
        selectedIds.value = [...merged];
    }
};

const toggleTask = (taskId) => {
    if (selectedIds.value.includes(taskId)) {
        selectedIds.value = selectedIds.value.filter((id) => id !== taskId);
    } else {
        selectedIds.value = [...selectedIds.value, taskId];
    }
};

const clearSelection = () => {
    selectedIds.value = [];
    bulkStatus.value = '';
};

const statusSelectOptions = (task) =>
    (task.status_options ?? []).map((s) => ({
        value: s.value,
        label: s.label,
    }));

const updateTaskStatus = (task, newStatus) => {
    if (!task.can_update_status || newStatus === task.status || savingStatusId.value) {
        return;
    }

    savingStatusId.value = task.id;

    router.patch(
        route('projects.tasks.update-status', [task.project_id, task.id]),
        { status: newStatus },
        {
            preserveScroll: true,
            onError: (errors) => {
                notifyError(errors.status ?? 'Gagal memperbarui status task.');
            },
            onFinish: () => {
                savingStatusId.value = null;
            },
        }
    );
};

const applyBulkStatus = () => {
    if (!canBulkUpdate.value) {
        return;
    }

    bulkProcessing.value = true;

    router.patch(
        route('my-tasks.bulk-status'),
        {
            task_ids: selectedIds.value,
            status: bulkStatus.value,
        },
        {
            preserveScroll: true,
            onError: (errors) => {
                notifyError(
                    errors.status ?? errors.task_ids ?? 'Gagal memperbarui status task.'
                );
            },
            onSuccess: () => {
                clearSelection();
            },
            onFinish: () => {
                bulkProcessing.value = false;
            },
        }
    );
};
</script>

<template>
    <Head title="My Tasks" />

    <AppLayout
        title="My Tasks"
        subtitle="Daftar task yang ditugaskan kepada Anda"
    >
        <!-- Stats -->
        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase text-slate-400">Total</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ stats.total }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase text-brand-600">Belum selesai</p>
                <p class="mt-1 text-2xl font-bold text-brand-700">{{ stats.pending }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase text-rose-600">Terlambat</p>
                <p class="mt-1 text-2xl font-bold text-rose-700">{{ stats.overdue }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase text-emerald-600">Selesai</p>
                <p class="mt-1 text-2xl font-bold text-emerald-700">{{ stats.done }}</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-6 p-4">
            <div class="flex flex-col gap-4">
                <div class="relative">
                    <MagnifyingGlassIcon
                        class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                    />
                    <input
                        v-model="localFilters.search"
                        type="search"
                        placeholder="Cari nomor, judul task, atau nama project…"
                        class="form-input pl-10"
                        @input="onSearchInput"
                    />
                </div>
                <div
                    class="grid grid-cols-1 gap-3"
                    :class="isDoneTab ? 'sm:grid-cols-2' : 'sm:grid-cols-3'"
                >
                    <SearchableSelect
                        v-if="!isDoneTab"
                        v-model="localFilters.status"
                        :options="statusFilterOptions"
                        placeholder="Status"
                        @update:model-value="applyFilters"
                    />
                    <SearchableSelect
                        v-model="localFilters.priority"
                        :options="priorityFilterOptions"
                        placeholder="Prioritas"
                        @update:model-value="applyFilters"
                    />
                    <SearchableSelect
                        v-model="localFilters.project_id"
                        :options="projectFilterOptions"
                        placeholder="Project"
                        @update:model-value="applyFilters"
                    />
                </div>
                <div class="flex justify-end">
                    <button
                        type="button"
                        class="text-sm font-medium text-slate-500 hover:text-slate-800"
                        @click="resetFilters"
                    >
                        Reset filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-4 flex flex-wrap gap-2">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition"
                :class="
                    localFilters.tab === tab.id
                        ? 'bg-brand-600 text-white shadow-md shadow-brand-500/25'
                        : 'border border-slate-200/80 bg-white text-slate-600 hover:bg-slate-50'
                "
                @click="switchTab(tab.id)"
            >
                {{ tab.label }}
                <span
                    class="rounded-full px-2 py-0.5 text-xs font-bold"
                    :class="
                        localFilters.tab === tab.id
                            ? 'bg-white/20 text-white'
                            : 'bg-slate-100 text-slate-600'
                    "
                >
                    {{ tab.count }}
                </span>
            </button>
        </div>

        <!-- Task list -->
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-3.5">
                <p v-if="isDoneTab" class="text-sm text-slate-500">
                    Task yang sudah <strong>Done</strong> — hanya lihat, status tidak dapat diubah
                    dari halaman ini.
                </p>
                <p v-else class="text-sm text-slate-500">
                    Task aktif (belum Done) dengan <strong>assignee</strong> Anda. Perbarui status
                    hingga <strong>Review</strong>; <strong>Done</strong> hanya jika Anda Project
                    Manager project terkait.
                </p>
            </div>

            <!-- Bulk actions bar (hanya tab Aktif) -->
            <div
                v-if="tasks.data?.length && !isDoneTab"
                class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-slate-50/80 px-5 py-3"
            >
                <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                    <input
                        type="checkbox"
                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500/30"
                        :checked="allPageSelected"
                        @change="toggleSelectAll"
                    />
                    Pilih semua di halaman
                </label>

                <template v-if="selectedCount > 0">
                    <span class="text-sm text-slate-500">
                        {{ selectedCount }} dipilih
                    </span>
                    <SearchableSelect
                        v-model="bulkStatus"
                        :options="bulkStatusOptions"
                        placeholder="Ubah status ke…"
                        :searchable="false"
                        class="w-full sm:w-auto sm:min-w-[180px]"
                    />
                    <PrimaryButton
                        type="button"
                        class="!py-2 !text-xs"
                        :disabled="!canBulkUpdate"
                        @click="applyBulkStatus"
                    >
                        {{ bulkProcessing ? 'Memproses…' : 'Terapkan' }}
                    </PrimaryButton>
                    <button
                        type="button"
                        class="text-sm font-medium text-slate-500 hover:text-slate-800"
                        @click="clearSelection"
                    >
                        Batal
                    </button>
                </template>
            </div>

            <div v-if="tasks.data?.length" class="space-y-2 p-3">
                <div
                    v-for="task in tasks.data"
                    :key="task.id"
                    class="rounded-xl px-4 py-4 shadow-sm transition"
                    :class="[
                        getTaskPriorityCardClass(task),
                        !isDoneTab ? 'flex gap-3 sm:px-4' : '',
                        savingStatusId === task.id || bulkProcessing
                            ? 'animate-pulse opacity-80'
                            : '',
                        !isDoneTab && selectedIds.includes(task.id)
                            ? 'ring-2 ring-brand-400/50'
                            : '',
                    ]"
                >
                    <div v-if="!isDoneTab" class="flex shrink-0 items-start pt-1">
                        <input
                            type="checkbox"
                            class="rounded border-slate-300 text-brand-600 focus:ring-brand-500/30"
                            :checked="selectedIds.includes(task.id)"
                            @change="toggleTask(task.id)"
                        />
                    </div>

                    <div
                        class="flex min-w-0 flex-1 flex-col gap-3 sm:flex-row sm:items-start sm:gap-4"
                        :class="isDoneTab ? 'flex' : ''"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start gap-2">
                                <div class="min-w-0 flex-1">
                                    <p
                                        v-if="task.task_number"
                                        class="font-mono text-xs font-bold uppercase tracking-wide"
                                        :class="getTaskPriorityTextClass(task).meta"
                                    >
                                        {{ task.task_number }}
                                    </p>
                                    <Link
                                        :href="projectShowUrl(task)"
                                        class="font-semibold hover:underline"
                                        :class="getTaskPriorityTextClass(task).title"
                                    >
                                        {{ task.title }}
                                    </Link>
                                </div>
                                <Badge v-if="task.is_overdue" color="rose" size="sm">
                                    Terlambat
                                </Badge>
                            </div>
                            <p
                                v-if="task.description_excerpt"
                                class="mt-1 line-clamp-1 text-xs font-medium"
                                :class="getTaskPriorityTextClass(task).body"
                            >
                                {{ task.description_excerpt }}
                            </p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200/80 bg-white/80 px-2 py-0.5 text-xs font-semibold text-slate-700"
                                >
                                    <span
                                        class="h-2 w-2 shrink-0 rounded-full"
                                        :style="{
                                            backgroundColor: task.project?.color ?? '#14b8a6',
                                        }"
                                    />
                                    {{ task.project?.code }} — {{ task.project?.name }}
                                </span>
                                <Badge :color="task.status_color" size="sm">
                                    {{ task.status_label }}
                                </Badge>
                                <Badge :color="task.priority_color" size="sm">
                                    {{ task.priority_label }}
                                </Badge>
                                <Badge
                                    v-if="task.project?.is_manager"
                                    color="indigo"
                                    size="sm"
                                >
                                    Anda PM
                                </Badge>
                            </div>
                            <div
                                v-if="
                                    task.due_date_formatted ||
                                    task.start_date_formatted ||
                                    task.estimated_hours_label
                                "
                                class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs font-medium"
                                :class="getTaskPriorityTextClass(task).meta"
                            >
                                <span
                                    v-if="task.start_date_formatted"
                                    class="inline-flex items-center gap-1"
                                >
                                    <CalendarIcon class="h-3.5 w-3.5" />
                                    Mulai: {{ task.start_date_formatted }}
                                </span>
                                <span
                                    v-if="task.due_date_formatted"
                                    class="inline-flex items-center gap-1"
                                    :class="task.is_overdue ? 'text-rose-700' : ''"
                                >
                                    <CalendarIcon class="h-3.5 w-3.5" />
                                    Selesai: {{ task.due_date_formatted }}
                                </span>
                                <span
                                    v-if="task.estimated_hours_label"
                                    class="inline-flex items-center gap-1"
                                >
                                    <ClockIcon class="h-3.5 w-3.5" />
                                    {{ task.estimated_hours_label }}
                                </span>
                            </div>
                        </div>

                        <div
                            class="flex shrink-0 flex-col gap-2 sm:items-end"
                            @click.stop
                        >
                            <SearchableSelect
                                v-if="task.can_update_status"
                                :model-value="task.status"
                                :options="statusSelectOptions(task)"
                                placeholder="Status"
                                :searchable="false"
                                class="w-full sm:w-44"
                                @update:model-value="updateTaskStatus(task, $event)"
                            />
                            <p
                                v-else-if="isDoneTab"
                                class="text-right text-xs font-medium text-slate-500"
                            >
                                Status terkunci
                            </p>
                            <p
                                v-else-if="task.can_update_status && !task.can_set_done"
                                class="max-w-[11rem] text-right text-[10px] font-medium leading-snug text-slate-500"
                            >
                                Done → hubungi PM
                            </p>
                            <Link
                                :href="projectShowUrl(task)"
                                class="text-sm font-semibold text-brand-700 hover:underline"
                                :class="getTaskPriorityTextClass(task).meta"
                            >
                                Detail →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <EmptyState
                v-else
                :title="
                    isDoneTab
                        ? 'Belum ada task selesai'
                        : 'Belum ada task aktif'
                "
                :description="
                    isDoneTab
                        ? 'Task yang sudah Done akan muncul di tab ini.'
                        : 'Task ditugaskan kepada Anda (selain status Done) akan muncul di tab Aktif.'
                "
                :icon="ClipboardDocumentListIcon"
            />

            <div
                v-if="tasks.data?.length && tasks.links?.length > 3"
                class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4"
            >
                <p class="text-sm text-slate-500">
                    Menampilkan {{ tasks.from }}–{{ tasks.to }} dari {{ tasks.total }}
                </p>
                <div class="flex flex-wrap gap-1">
                    <Link
                        v-for="(link, i) in tasks.links"
                        :key="i"
                        :href="link.url ?? '#'"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                        :class="
                            link.active
                                ? 'bg-brand-600 text-white'
                                : link.url
                                  ? 'text-slate-600 hover:bg-slate-100'
                                  : 'cursor-not-allowed text-slate-300'
                        "
                        v-html="link.label"
                        preserve-scroll
                    />
                </div>
            </div>
        </div>

        <div
            v-if="!isDoneTab && stats.overdue > 0"
            class="mt-4 flex items-start gap-3 rounded-xl border border-rose-200/80 bg-rose-50/80 px-4 py-3 text-sm text-rose-900"
        >
            <ExclamationTriangleIcon class="mt-0.5 h-5 w-5 shrink-0 text-rose-600" />
            <p>
                Anda memiliki <strong>{{ stats.overdue }}</strong> task terlambat. Segera perbarui
                status task tersebut.
            </p>
        </div>
        <div
            v-else-if="!isDoneTab && stats.pending === 0 && stats.total > 0"
            class="mt-4 flex items-center gap-3 rounded-xl border border-emerald-200/80 bg-emerald-50/80 px-4 py-3 text-sm text-emerald-900"
        >
            <CheckCircleIcon class="h-5 w-5 shrink-0 text-emerald-600" />
            <p>Semua task Anda sudah selesai. Bagus!</p>
        </div>
    </AppLayout>
</template>
