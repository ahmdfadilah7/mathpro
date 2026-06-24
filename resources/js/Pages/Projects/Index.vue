<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import SearchableSelect from '@/Components/UI/SearchableSelect.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    FunnelIcon,
    MagnifyingGlassIcon,
    PlusIcon,
    Squares2X2Icon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    projects: Object,
    filters: Object,
    stats: Object,
    filterOptions: Object,
});

const localFilters = ref({ ...props.filters });

const filteredDepartments = computed(() => {
    if (!localFilters.value.division_id) return props.filterOptions.departments;
    return props.filterOptions.departments.filter(
        (d) => d.division_id === Number(localFilters.value.division_id)
    );
});

const statusFilterOptions = computed(() => [
    { value: '', label: 'Semua status' },
    ...(props.filterOptions.statuses ?? []).map((s) => ({ value: s.value, label: s.label })),
]);

const priorityFilterOptions = computed(() => [
    { value: '', label: 'Semua prioritas' },
    ...(props.filterOptions.priorities ?? []).map((p) => ({ value: p.value, label: p.label })),
]);

const divisionFilterOptions = computed(() => [
    { value: '', label: 'Semua divisi' },
    ...(props.filterOptions.divisions ?? []).map((d) => ({ value: d.id, label: d.name })),
]);

const departmentFilterOptions = computed(() => [
    { value: '', label: 'Semua departemen' },
    ...filteredDepartments.value.map((d) => ({ value: d.id, label: d.name })),
]);

const applyFilters = () => {
    router.get(route('projects.index'), localFilters.value, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    localFilters.value = {
        search: '',
        status: '',
        priority: '',
        division_id: '',
        department_id: '',
        manager_id: '',
    };
    applyFilters();
};

watch(
    () => localFilters.value.division_id,
    () => {
        if (
            localFilters.value.department_id &&
            !filteredDepartments.value.some(
                (d) => d.id === Number(localFilters.value.department_id)
            )
        ) {
            localFilters.value.department_id = null;
        }
    }
);

let searchTimeout;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
};
</script>

<template>
    <Head title="Projects" />

    <AppLayout title="Projects" subtitle="Kelola semua project per divisi dan departemen">
        <!-- Stats -->
        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase text-slate-400">Total</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ stats.total }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase text-emerald-600">Active</p>
                <p class="mt-1 text-2xl font-bold text-emerald-700">{{ stats.active }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">Planning</p>
                <p class="mt-1 text-2xl font-bold text-slate-700">{{ stats.planning }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase text-indigo-600">Completed</p>
                <p class="mt-1 text-2xl font-bold text-indigo-700">{{ stats.completed }}</p>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="card mb-6 p-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="relative flex-1">
                    <MagnifyingGlassIcon
                        class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                    />
                    <input
                        v-model="localFilters.search"
                        type="search"
                        placeholder="Cari nama atau kode project..."
                        class="form-input w-full pl-10"
                        @input="onSearchInput"
                    />
                </div>
                <Link :href="route('projects.create')" class="btn-primary shrink-0">
                    <PlusIcon class="h-5 w-5" />
                    Project baru
                </Link>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <SearchableSelect
                    v-model="localFilters.status"
                    :options="statusFilterOptions"
                    placeholder="Filter status..."
                    @change="applyFilters"
                />
                <SearchableSelect
                    v-model="localFilters.priority"
                    :options="priorityFilterOptions"
                    placeholder="Filter prioritas..."
                    @change="applyFilters"
                />
                <SearchableSelect
                    v-model="localFilters.division_id"
                    :options="divisionFilterOptions"
                    placeholder="Filter divisi..."
                    @change="applyFilters"
                />
                <SearchableSelect
                    v-model="localFilters.department_id"
                    :options="departmentFilterOptions"
                    :disabled="!localFilters.division_id"
                    placeholder="Filter departemen..."
                    @change="applyFilters"
                />
                <button type="button" class="btn-secondary text-sm" @click="resetFilters">
                    <FunnelIcon class="h-4 w-4" />
                    Reset filter
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="card overflow-hidden">
            <div v-if="projects.data?.length" class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 font-semibold text-slate-600">Project</th>
                            <th class="px-4 py-3 font-semibold text-slate-600">Divisi / Dept</th>
                            <th class="px-4 py-3 font-semibold text-slate-600">Manager</th>
                            <th class="px-4 py-3 font-semibold text-slate-600">Status</th>
                            <th class="px-4 py-3 font-semibold text-slate-600">Progress</th>
                            <th class="px-4 py-3 font-semibold text-slate-600">Due date</th>
                            <th class="px-6 py-3" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="project in projects.data"
                            :key="project.id"
                            class="transition hover:bg-slate-50/80"
                        >
                            <td class="px-6 py-4">
                                <Link
                                    :href="route('projects.show', project.id)"
                                    class="group flex items-center gap-3"
                                >
                                    <div
                                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-xs font-bold text-white"
                                        :style="{ backgroundColor: project.color }"
                                    >
                                        {{ project.code.split('-')[1]?.slice(0, 2) ?? 'P' }}
                                    </div>
                                    <div>
                                        <p
                                            class="font-semibold text-slate-900 group-hover:text-brand-600"
                                        >
                                            {{ project.name }}
                                        </p>
                                        <p class="font-mono text-xs text-slate-400">
                                            {{ project.code }}
                                        </p>
                                    </div>
                                </Link>
                            </td>
                            <td class="px-4 py-4">
                                <p class="text-slate-700">{{ project.division?.name }}</p>
                                <p class="text-xs text-slate-400">{{ project.department?.name }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <div v-if="project.manager" class="flex items-center gap-2">
                                    <Avatar
                                        :name="project.manager.name"
                                        :initials="project.manager.initials"
                                        size="sm"
                                    />
                                    <span class="text-slate-700">{{ project.manager.name }}</span>
                                </div>
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td class="px-4 py-4">
                                <Badge :color="project.status_color" size="sm">
                                    {{ project.status_label }}
                                </Badge>
                            </td>
                            <td class="px-4 py-4">
                                <div class="w-28">
                                    <ProgressBar
                                        :value="project.progress"
                                        :color="project.color"
                                        :show-label="true"
                                    />
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span
                                    :class="
                                        project.is_overdue
                                            ? 'font-medium text-rose-600'
                                            : 'text-slate-600'
                                    "
                                >
                                    {{ project.due_date_formatted ?? '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <Link
                                    :href="route('projects.edit', project.id)"
                                    class="text-sm font-medium text-brand-600 hover:underline"
                                >
                                    Edit
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-else
                title="Belum ada project"
                description="Mulai dengan membuat project pertama untuk tim Anda."
                action-label="Buat project"
                :action-href="route('projects.create')"
                :icon="Squares2X2Icon"
            />

            <!-- Pagination -->
            <div
                v-if="projects.data?.length && projects.links?.length > 3"
                class="flex items-center justify-between border-t border-slate-100 px-6 py-4"
            >
                <p class="text-sm text-slate-500">
                    Menampilkan {{ projects.from }}–{{ projects.to }} dari {{ projects.total }}
                </p>
                <div class="flex gap-1">
                    <Link
                        v-for="(link, i) in projects.links"
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
    </AppLayout>
</template>
