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
    ClipboardDocumentListIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    MagnifyingGlassIcon,
    UserPlusIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    tasks: Object,
    filters: Object,
    stats: Object,
    tabs: Array,
    filterOptions: Object,
    assigneesByProject: { type: Object, default: () => ({}) },
});

const isDoneTab = computed(() => localFilters.value.tab === 'done');

const localFilters = ref({ ...props.filters });
const assignSelections = ref({});
const assigningId = ref(null);
const selectedIds = ref([]);
const bulkAssigneeId = ref('');
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

const applyFilters = () => {
    router.get(route('unassigned.index'), localFilters.value, {
        preserveState: true,
        replace: true,
    });
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

const assigneeOptionsFor = (task) => {
    const users = props.assigneesByProject[task.project_id] ?? [];
    return users.map((u) => ({ value: u.id, label: u.name }));
};

const canAssignTask = (task) =>
    task.can_assign && assigneeOptionsFor(task).length > 0 && !isDoneTab.value;

const assignablePageTaskIds = computed(() =>
    (props.tasks.data ?? []).filter(canAssignTask).map((t) => t.id)
);

const selectedCount = computed(() => selectedIds.value.length);

const allPageSelected = computed(
    () =>
        assignablePageTaskIds.value.length > 0 &&
        assignablePageTaskIds.value.every((id) => selectedIds.value.includes(id))
);

const selectedTasks = computed(() =>
    (props.tasks.data ?? []).filter((t) => selectedIds.value.includes(t.id))
);

const bulkAssigneeOptions = computed(() => {
    const assignable = selectedTasks.value.filter((t) => t.can_assign);
    if (!assignable.length) {
        return [];
    }

    const lists = assignable.map((t) =>
        (props.assigneesByProject[t.project_id] ?? []).map((u) => u.id)
    );

    let commonIds = lists[0] ?? [];
    for (let i = 1; i < lists.length; i++) {
        const set = new Set(lists[i]);
        commonIds = commonIds.filter((id) => set.has(id));
    }

    const users = props.assigneesByProject[assignable[0].project_id] ?? [];

    return users
        .filter((u) => commonIds.includes(u.id))
        .map((u) => ({ value: u.id, label: u.name }));
});

const canBulkAssign = computed(
    () =>
        selectedCount.value > 0 &&
        bulkAssigneeId.value &&
        bulkAssigneeOptions.value.length > 0 &&
        !bulkProcessing.value
);

const hasMixedProjectsWithoutCommonAssignee = computed(
    () =>
        selectedCount.value > 1 &&
        selectedTasks.value.some((t) => t.can_assign) &&
        bulkAssigneeOptions.value.length === 0
);

watch(
    () => props.tasks.data,
    () => {
        selectedIds.value = selectedIds.value.filter((id) =>
            assignablePageTaskIds.value.includes(id)
        );
    }
);

watch(selectedCount, (count) => {
    if (count === 0) {
        bulkAssigneeId.value = '';
    }
});

const switchTab = (tabId) => {
    if (localFilters.value.tab === tabId) return;
    localFilters.value.tab = tabId;
    if (tabId === 'done') {
        localFilters.value.status = '';
    }
    clearSelection();
    applyFilters();
};

const toggleSelectAll = () => {
    if (allPageSelected.value) {
        selectedIds.value = selectedIds.value.filter(
            (id) => !assignablePageTaskIds.value.includes(id)
        );
    } else {
        selectedIds.value = [
            ...new Set([...selectedIds.value, ...assignablePageTaskIds.value]),
        ];
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
    bulkAssigneeId.value = '';
};

const applyBulkAssign = () => {
    if (!canBulkAssign.value) {
        return;
    }

    bulkProcessing.value = true;

    router.patch(
        route('unassigned.bulk-assign'),
        {
            task_ids: selectedIds.value,
            assignee_id: bulkAssigneeId.value,
        },
        {
            preserveScroll: true,
            onError: (errors) => {
                notifyError(
                    errors.assignee_id ??
                        errors.task_ids ??
                        'Gagal menugaskan task secara bulk.'
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

const assignTask = (task) => {
    const assigneeId = assignSelections.value[task.id];
    if (!canAssignTask(task) || !assigneeId || assigningId.value) {
        return;
    }

    assigningId.value = task.id;

    router.patch(
        route('unassigned.assign', task.id),
        { assignee_id: assigneeId },
        {
            preserveScroll: true,
            onError: (errors) => {
                notifyError(errors.assignee_id ?? 'Gagal menetapkan assignee.');
            },
            onFinish: () => {
                assigningId.value = null;
            },
        }
    );
};
</script>

<template>
    <Head title="Unassigned" />

    <AppLayout
        title="Unassigned"
        subtitle="Task tanpa assignee di project tempat Anda PM atau anggota"
    >
        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase text-slate-400">Total</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ stats.total }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase text-amber-600">Perlu assignee</p>
                <p class="mt-1 text-2xl font-bold text-amber-700">{{ stats.pending }}</p>
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

        <div class="card overflow-hidden">
            <div
                v-if="tasks.data?.length && !isDoneTab"
                class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-slate-50/80 px-5 py-3"
            >
                <label
                    v-if="assignablePageTaskIds.length"
                    class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700"
                >
                    <input
                        type="checkbox"
                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500/30"
                        :checked="allPageSelected"
                        @change="toggleSelectAll"
                    />
                    Pilih semua yang dapat ditugaskan
                </label>
                <p v-else class="text-sm text-slate-500">
                    Tidak ada task di halaman ini yang dapat Anda tugaskan.
                </p>

                <template v-if="selectedCount > 0">
                    <span class="text-sm text-slate-500">{{ selectedCount }} dipilih</span>
                    <SearchableSelect
                        v-model="bulkAssigneeId"
                        :options="bulkAssigneeOptions"
                        placeholder="Tugaskan ke…"
                        class="w-full sm:w-auto sm:min-w-[200px]"
                    />
                    <PrimaryButton
                        type="button"
                        class="!py-2 !text-xs"
                        :disabled="!canBulkAssign"
                        @click="applyBulkAssign"
                    >
                        {{ bulkProcessing ? 'Memproses…' : 'Tugaskan terpilih' }}
                    </PrimaryButton>
                    <button
                        type="button"
                        class="text-sm font-medium text-slate-500 hover:text-slate-800"
                        @click="clearSelection"
                    >
                        Batal
                    </button>
                    <p
                        v-if="hasMixedProjectsWithoutCommonAssignee"
                        class="w-full text-xs text-amber-700"
                    >
                        Assignee harus valid di semua project task terpilih. Kurangi pilihan
                        atau pilih task dari project yang sama.
                    </p>
                </template>
            </div>

            <div
                v-if="tasks.data?.length"
                class="divide-y divide-slate-100"
            >
                <div
                    v-for="task in tasks.data"
                    :key="task.id"
                    class="flex flex-wrap gap-3 p-4 transition sm:flex-nowrap sm:items-center sm:justify-between sm:p-5"
                    :class="[
                        getTaskPriorityCardClass(task),
                        bulkProcessing || assigningId === task.id
                            ? 'animate-pulse opacity-80'
                            : '',
                        !isDoneTab && selectedIds.includes(task.id)
                            ? 'ring-2 ring-inset ring-brand-400/50'
                            : '',
                    ]"
                >
                    <div
                        v-if="!isDoneTab && canAssignTask(task)"
                        class="flex shrink-0 items-start pt-1"
                    >
                        <input
                            type="checkbox"
                            class="rounded border-slate-300 text-brand-600 focus:ring-brand-500/30"
                            :checked="selectedIds.includes(task.id)"
                            @change="toggleTask(task.id)"
                        />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="h-2.5 w-2.5 rounded-full"
                                :style="{ backgroundColor: task.project?.color ?? '#14b8a6' }"
                            />
                            <Link
                                :href="projectShowUrl(task)"
                                class="text-xs font-bold uppercase tracking-wide text-brand-700 hover:underline"
                                :class="getTaskPriorityTextClass(task).meta"
                            >
                                {{ task.project?.code }}
                            </Link>
                            <span
                                class="font-mono text-xs font-bold"
                                :class="getTaskPriorityTextClass(task).faint"
                            >
                                {{ task.task_number }}
                            </span>
                            <Badge :color="task.status_color">{{ task.status_label }}</Badge>
                            <Badge :color="task.priority_color">{{ task.priority_label }}</Badge>
                            <Badge
                                v-if="task.is_overdue"
                                color="rose"
                            >
                                Terlambat
                            </Badge>
                        </div>
                        <h3
                            class="mt-2 text-base font-bold"
                            :class="getTaskPriorityTextClass(task).title"
                        >
                            {{ task.title }}
                        </h3>
                        <div
                            class="mt-2 flex flex-wrap gap-4 text-xs"
                            :class="getTaskPriorityTextClass(task).body"
                        >
                            <span
                                v-if="task.due_date_formatted"
                                class="inline-flex items-center gap-1"
                            >
                                <CalendarIcon class="h-3.5 w-3.5" />
                                {{ task.due_date_formatted }}
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
                        class="flex w-full shrink-0 flex-col gap-2 sm:w-72 sm:items-stretch"
                    >
                        <template v-if="canAssignTask(task)">
                            <SearchableSelect
                                v-model="assignSelections[task.id]"
                                :options="assigneeOptionsFor(task)"
                                placeholder="Pilih assignee…"
                                class="w-full"
                            />
                            <PrimaryButton
                                type="button"
                                class="w-full justify-center"
                                :disabled="
                                    !assignSelections[task.id] ||
                                    assigningId === task.id
                                "
                                @click="assignTask(task)"
                            >
                                <UserPlusIcon class="h-4 w-4" />
                                {{
                                    assigningId === task.id
                                        ? 'Menugaskan…'
                                        : 'Tugaskan'
                                }}
                            </PrimaryButton>
                        </template>
                        <p
                            v-else-if="task.can_assign && !isDoneTab"
                            class="text-center text-xs text-slate-500"
                        >
                            Tidak ada anggota yang dapat ditugaskan
                        </p>
                        <p
                            v-else-if="!isDoneTab && !task.can_assign"
                            class="text-center text-xs text-slate-500"
                        >
                            Hanya PM / Admin yang dapat menugaskan
                        </p>
                        <Link
                            :href="projectShowUrl(task)"
                            class="text-center text-sm font-semibold text-brand-700 hover:underline"
                        >
                            Buka di project →
                        </Link>
                    </div>
                </div>
            </div>

            <EmptyState
                v-else
                :title="
                    isDoneTab
                        ? 'Tidak ada task selesai tanpa assignee'
                        : 'Semua task sudah ditugaskan'
                "
                :description="
                    isDoneTab
                        ? 'Task Done tanpa assignee akan muncul di tab ini.'
                        : 'Task tanpa assignee di project Anda akan muncul di sini.'
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
                Ada <strong>{{ stats.overdue }}</strong> task tanpa assignee yang sudah melewati
                deadline. Segera tugaskan ke anggota tim.
            </p>
        </div>
    </AppLayout>
</template>
