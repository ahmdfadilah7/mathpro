<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import SearchableSelect from '@/Components/UI/SearchableSelect.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { ClockIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    logs: Object,
    filters: Object,
    actionOptions: Array,
    userOptions: Array,
});

const localFilters = ref({ ...props.filters });

const actionFilterOptions = computed(() => [
    { value: '', label: 'Semua aksi' },
    ...(props.actionOptions ?? []).map((o) => ({ value: o.value, label: o.label })),
]);

const userFilterOptions = computed(() => [
    { value: '', label: 'Semua pengguna' },
    ...(props.userOptions ?? []).map((u) => ({ value: u.id, label: u.name })),
]);

const applyFilters = () => {
    const params = {};
    if (localFilters.value.search) params.search = localFilters.value.search;
    if (localFilters.value.action) params.action = localFilters.value.action;
    if (localFilters.value.user_id) params.user_id = localFilters.value.user_id;

    router.get(route('activity.index'), params, {
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
    () => props.filters,
    (f) => {
        localFilters.value = { ...f };
    },
    { deep: true }
);
</script>

<template>
    <Head title="Activity Log" />

    <AppLayout title="Activity Log" subtitle="Riwayat aktivitas di seluruh modul MathPro">
        <div class="mb-6 card p-4">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="relative sm:col-span-2">
                    <MagnifyingGlassIcon
                        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                    />
                    <input
                        v-model="localFilters.search"
                        type="search"
                        placeholder="Cari deskripsi atau nama pengguna..."
                        class="input pl-9"
                        @input="onSearchInput"
                    />
                </div>
                <SearchableSelect
                    v-model="localFilters.action"
                    :options="actionFilterOptions"
                    placeholder="Filter aksi"
                    @update:model-value="applyFilters"
                />
                <SearchableSelect
                    v-model="localFilters.user_id"
                    :options="userFilterOptions"
                    placeholder="Filter pengguna"
                    @update:model-value="applyFilters"
                />
            </div>
        </div>

        <div class="card overflow-hidden">
            <div
                v-if="logs.data?.length"
                class="divide-y divide-slate-100"
            >
                <div
                    v-for="log in logs.data"
                    :key="log.id"
                    class="flex gap-4 px-6 py-4 transition hover:bg-slate-50/80"
                >
                    <Avatar
                        v-if="log.user"
                        :name="log.user.name"
                        :initials="log.user.initials"
                        :src="log.user.avatar_url"
                        size="sm"
                    />
                    <div
                        v-else
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs text-slate-500"
                    >
                        ?
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-slate-900">
                                {{ log.user?.name ?? 'Sistem' }}
                            </span>
                            <Badge :color="log.action_color" size="sm">
                                {{ log.action_label }}
                            </Badge>
                            <span
                                v-if="log.project?.code"
                                class="text-xs font-medium text-brand-600"
                            >
                                {{ log.project.code }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-slate-700">
                            {{ log.description }}
                        </p>
                    </div>
                    <span class="shrink-0 text-xs text-slate-400">
                        {{ log.created_at_label }}
                    </span>
                </div>
            </div>

            <EmptyState
                v-else
                :icon="ClockIcon"
                title="Belum ada aktivitas"
                description="Aktivitas akan muncul setelah Anda menggunakan fitur project, task, chat, dan lainnya."
            />

            <div
                v-if="logs.links?.length > 3"
                class="flex flex-wrap items-center justify-center gap-1 border-t border-slate-100 px-4 py-3"
            >
                <template v-for="(link, i) in logs.links" :key="i">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg px-3 py-1.5 text-sm transition"
                        :class="
                            link.active
                                ? 'bg-brand-600 font-medium text-white'
                                : 'text-slate-600 hover:bg-slate-100'
                        "
                        v-html="link.label"
                        preserve-scroll
                    />
                    <span
                        v-else
                        class="px-2 text-sm text-slate-300"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
