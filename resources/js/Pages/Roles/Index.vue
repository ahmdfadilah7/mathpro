<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { confirmDelete } from '@/utils/confirm';
import { Head, Link, router } from '@inertiajs/vue3';
import { PlusIcon, PencilSquareIcon, TrashIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';

defineProps({
    roles: Array,
});

const deleteRole = async (role) => {
    const confirmed = await confirmDelete({
        title: 'Hapus role?',
        text: `Role "${role.name}" akan dihapus permanen.`,
        confirmText: 'Ya, hapus role',
    });
    if (!confirmed) return;
    router.delete(route('roles.destroy', role.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Roles" />

    <AppLayout title="Roles" subtitle="Kelola role dan permission pengguna">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">
                {{ roles?.length ?? 0 }} role terdaftar
            </p>
            <Link
                :href="route('roles.create')"
                class="btn-primary inline-flex w-full items-center gap-2 sm:w-auto"
            >
                <PlusIcon class="h-4 w-4" />
                Tambah role
            </Link>
        </div>

        <div class="card overflow-hidden">
            <!-- Mobile: daftar kartu -->
            <ul v-if="roles?.length" class="divide-y divide-slate-100 md:hidden">
                <li v-for="role in roles" :key="`m-${role.id}`" class="p-4">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600"
                        >
                            <ShieldCheckIcon class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-slate-900">{{ role.name }}</p>
                            <p class="line-clamp-2 text-xs text-slate-500">
                                {{ role.description || '—' }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <Link
                                :href="route('roles.edit', role.id)"
                                class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-brand-700"
                                :aria-label="`Edit ${role.name}`"
                            >
                                <PencilSquareIcon class="h-4 w-4" />
                            </Link>
                            <button
                                v-if="!role.is_system"
                                type="button"
                                class="rounded-lg p-2 text-slate-500 transition hover:bg-rose-50 hover:text-rose-600 disabled:opacity-40"
                                :aria-label="`Hapus ${role.name}`"
                                :disabled="role.users_count > 0"
                                @click="deleteRole(role)"
                            >
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2 pl-12 text-xs text-slate-500">
                        <span class="font-mono">{{ role.slug }}</span>
                        <Badge v-if="role.is_system" color="slate" size="sm">Sistem</Badge>
                        <Badge :color="role.is_active ? 'emerald' : 'slate'" size="sm">
                            {{ role.is_active ? 'Aktif' : 'Nonaktif' }}
                        </Badge>
                        <span>{{ role.users_count }} user</span>
                    </div>
                </li>
            </ul>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="border-b border-slate-100 bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Role</th>
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3 text-center">User</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="role in roles"
                            :key="role.id"
                            class="transition hover:bg-slate-50/80"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-50 text-brand-600"
                                    >
                                        <ShieldCheckIcon class="h-5 w-5" />
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ role.name }}</p>
                                        <p class="text-xs text-slate-500 line-clamp-1">
                                            {{ role.description || '—' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 font-mono text-xs text-slate-600">
                                {{ role.slug }}
                                <Badge v-if="role.is_system" color="slate" size="sm" class="ml-1">
                                    Sistem
                                </Badge>
                            </td>
                            <td class="px-4 py-4 text-center font-medium">
                                {{ role.users_count }}
                            </td>
                            <td class="px-4 py-4">
                                <Badge :color="role.is_active ? 'emerald' : 'slate'" size="sm">
                                    {{ role.is_active ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center justify-end gap-1">
                                    <Link
                                        :href="route('roles.edit', role.id)"
                                        class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-brand-700"
                                        title="Edit"
                                    >
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </Link>
                                    <button
                                        v-if="!role.is_system"
                                        type="button"
                                        class="rounded-lg p-2 text-slate-500 transition hover:bg-rose-50 hover:text-rose-600"
                                        title="Hapus"
                                        :disabled="role.users_count > 0"
                                        @click="deleteRole(role)"
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
                v-if="!roles?.length"
                title="Belum ada role"
                description="Buat role pertama untuk mengatur hak akses."
            />
        </div>
    </AppLayout>
</template>
