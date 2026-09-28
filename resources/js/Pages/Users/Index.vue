<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import SearchableSelect from '@/Components/UI/SearchableSelect.vue';
import { confirmDelete } from '@/utils/confirm';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    MagnifyingGlassIcon,
    PencilSquareIcon,
    PlusIcon,
    TrashIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    users: Object,
    filters: Object,
    stats: Object,
    filterOptions: Object,
});

const page = usePage();
const localFilters = ref({ ...props.filters });

const roleFilterOptions = computed(() => [
    { value: '', label: 'Semua role' },
    ...(props.filterOptions?.roles ?? []).map((r) => ({ value: r.id, label: r.name })),
]);

const divisionFilterOptions = computed(() => [
    { value: '', label: 'Semua divisi' },
    ...(props.filterOptions?.divisions ?? []).map((d) => ({ value: d.id, label: d.name })),
]);

const departmentFilterOptions = computed(() => {
    if (!localFilters.value.division_id) return [{ value: '', label: 'Semua departemen' }];
    return [
        { value: '', label: 'Semua departemen' },
        ...(props.filterOptions?.departments ?? [])
            .filter((d) => d.division_id === Number(localFilters.value.division_id))
            .map((d) => ({ value: d.id, label: d.name })),
    ];
});

const statusFilterOptions = [
    { value: '', label: 'Semua status' },
    { value: '1', label: 'Aktif' },
    { value: '0', label: 'Nonaktif' },
];

const applyFilters = () => {
    router.get(route('users.index'), localFilters.value, {
        preserveState: true,
        replace: true,
    });
};

let searchTimeout;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
};

watch(
    () => localFilters.value.division_id,
    () => {
        if (
            localFilters.value.department_id &&
            !departmentFilterOptions.value.some(
                (d) => d.value === Number(localFilters.value.department_id)
            )
        ) {
            localFilters.value.department_id = '';
        }
        applyFilters();
    }
);

const deleteUser = async (user) => {
    const confirmed = await confirmDelete({
        title: 'Hapus user?',
        text: `Akun "${user.name}" akan dihapus permanen.`,
        confirmText: 'Ya, hapus user',
    });
    if (!confirmed) return;
    router.delete(route('users.destroy', user.id), { preserveScroll: true });
};

const canDelete = (user) => user.id !== page.props.auth.user?.id;
</script>

<template>
    <Head title="Users" />

    <AppLayout title="Users" subtitle="Kelola akun pengguna per departemen">
        <div class="mb-6 grid grid-cols-3 gap-3 sm:gap-4">
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase text-slate-400">Total</p>
                <p class="mt-1 text-2xl font-bold text-slate-900">{{ stats.total }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase text-slate-400">Aktif</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ stats.active }}</p>
            </div>
            <div class="card p-4">
                <p class="text-xs font-semibold uppercase text-slate-400">Nonaktif</p>
                <p class="mt-1 text-2xl font-bold text-slate-500">{{ stats.inactive }}</p>
            </div>
        </div>

        <div class="card mb-6 p-4">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-5">
                <div class="relative lg:col-span-2">
                    <MagnifyingGlassIcon
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    />
                    <input
                        v-model="localFilters.search"
                        type="search"
                        placeholder="Cari nama, email, jabatan..."
                        class="form-input w-full pl-9"
                        @input="onSearchInput"
                    />
                </div>
                <SearchableSelect
                    v-model="localFilters.role_id"
                    :options="roleFilterOptions"
                    placeholder="Role"
                    @update:model-value="applyFilters"
                />
                <SearchableSelect
                    v-model="localFilters.division_id"
                    :options="divisionFilterOptions"
                    placeholder="Divisi"
                    @update:model-value="applyFilters"
                />
                <SearchableSelect
                    v-model="localFilters.department_id"
                    :options="departmentFilterOptions"
                    placeholder="Departemen"
                    :disabled="!localFilters.division_id"
                    @update:model-value="applyFilters"
                />
                <SearchableSelect
                    v-model="localFilters.is_active"
                    :options="statusFilterOptions"
                    placeholder="Status"
                    @update:model-value="applyFilters"
                />
            </div>
        </div>

        <div class="mb-4 flex justify-end">
            <Link
                :href="route('users.create')"
                class="btn-primary inline-flex w-full items-center gap-2 sm:w-auto"
            >
                <PlusIcon class="h-4 w-4" />
                Tambah user
            </Link>
        </div>

        <div class="card overflow-hidden">
            <!-- Mobile: daftar kartu -->
            <ul v-if="users.data?.length" class="divide-y divide-slate-100 md:hidden">
                <li v-for="user in users.data" :key="`m-${user.id}`" class="p-4">
                    <div class="flex items-start gap-3">
                        <Avatar :name="user.name" :initials="user.initials" size="sm" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-slate-900">{{ user.name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ user.email }}</p>
                            <p v-if="user.position" class="truncate text-xs text-slate-400">
                                {{ user.position }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <Link
                                :href="route('users.edit', user.id)"
                                class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-brand-700"
                                :aria-label="`Edit ${user.name}`"
                            >
                                <PencilSquareIcon class="h-4 w-4" />
                            </Link>
                            <button
                                v-if="canDelete(user)"
                                type="button"
                                class="rounded-lg p-2 text-slate-500 transition hover:bg-rose-50 hover:text-rose-600"
                                :aria-label="`Hapus ${user.name}`"
                                @click="deleteUser(user)"
                            >
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2 pl-11 text-xs text-slate-500">
                        <Badge color="brand" size="sm">{{ user.role?.name ?? '—' }}</Badge>
                        <Badge :color="user.is_active ? 'emerald' : 'slate'" size="sm">
                            {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                        </Badge>
                        <span v-if="user.department" class="truncate">
                            {{ user.department.name }}
                            <template v-if="user.department.division?.name">
                                · {{ user.department.division.name }}
                            </template>
                        </span>
                    </div>
                </li>
            </ul>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-3">User</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Departemen</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="transition hover:bg-slate-50/80"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <Avatar
                                        :name="user.name"
                                        :initials="user.initials"
                                        size="sm"
                                    />
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ user.name }}</p>
                                        <p class="text-xs text-slate-500">{{ user.email }}</p>
                                        <p v-if="user.position" class="text-xs text-slate-400">
                                            {{ user.position }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <Badge color="brand" size="sm">
                                    {{ user.role?.name ?? '—' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-4 text-slate-600">
                                <template v-if="user.department">
                                    {{ user.department.name }}
                                    <span class="block text-xs text-slate-400">
                                        {{ user.department.division?.name }}
                                    </span>
                                </template>
                                <span v-else class="text-slate-400">—</span>
                            </td>
                            <td class="px-4 py-4">
                                <Badge :color="user.is_active ? 'emerald' : 'slate'" size="sm">
                                    {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <Link
                                        :href="route('users.edit', user.id)"
                                        class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-brand-700"
                                    >
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </Link>
                                    <button
                                        v-if="canDelete(user)"
                                        type="button"
                                        class="rounded-lg p-2 text-slate-500 transition hover:bg-rose-50 hover:text-rose-600"
                                        @click="deleteUser(user)"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState
                v-if="!users.data?.length"
                :icon="UsersIcon"
                title="Tidak ada user"
                description="Sesuaikan filter atau tambah user baru."
            />

            <div
                v-if="users.links?.length > 3"
                class="flex flex-wrap items-center justify-center gap-1 border-t border-slate-100 px-4 py-3"
            >
                <Link
                    v-for="link in users.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                    :class="
                        link.active
                            ? 'bg-brand-600 text-white'
                            : link.url
                              ? 'text-slate-600 hover:bg-slate-100'
                              : 'cursor-not-allowed text-slate-300'
                    "
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>
