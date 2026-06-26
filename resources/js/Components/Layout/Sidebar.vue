<script setup>
import SidebarLink from '@/Components/Layout/SidebarLink.vue';
import { adminNavigation, navigation } from '@/config/navigation';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const abilities = computed(() => page.props.auth?.abilities ?? {});

const canShow = (item) => {
    if (!item.ability) {
        return true;
    }

    return Boolean(abilities.value[item.ability]);
};

const visibleNavigation = computed(() => navigation.filter(canShow));
const visibleAdminNavigation = computed(() =>
    abilities.value.is_super_admin ? adminNavigation : []
);

const isActive = (routeName) => {
    const current = route().current();
    if (!current) return false;
    if (routeName === 'dashboard') return current === 'dashboard';
    if (routeName === 'profile.edit') return current === 'profile.edit';
    if (routeName === 'projects.index') return current?.startsWith('projects.');
    if (routeName === 'roles.index') return current?.startsWith('roles.');
    if (routeName === 'users.index') return current?.startsWith('users.');
    if (routeName === 'documentation.index') return current?.startsWith('documentation.');
    const base = routeName.replace('.index', '');
    return current === routeName || current?.startsWith(`${base}.`);
};
</script>

<template>
    <aside
        class="fixed inset-y-0 left-0 z-30 flex w-64 flex-col border-r border-slate-200/80 bg-white"
    >
        <div class="flex h-16 items-center gap-3 border-b border-slate-100 px-5">
            <Link :href="route('dashboard')" class="flex items-center gap-3">
                <div
                    class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white shadow-glow"
                >
                    M
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-900">{{ page.props.app.name }}</p>
                    <p class="text-[10px] font-medium text-brand-600">Project Management</p>
                </div>
            </Link>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
            <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                Menu
            </p>
            <SidebarLink
                v-for="item in visibleNavigation"
                :key="item.route"
                :href="route(item.route)"
                :active="isActive(item.route)"
                :icon="item.icon"
                :label="item.name"
            />

            <template v-if="visibleAdminNavigation.length">
                <p class="mb-2 mt-6 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                    Administration
                </p>
                <SidebarLink
                    v-for="item in visibleAdminNavigation"
                    :key="item.route"
                    :href="route(item.route)"
                    :active="isActive(item.route)"
                    :icon="item.icon"
                    :label="item.name"
                />
            </template>
        </nav>

        <div class="border-t border-slate-100 p-4">
            <Link
                :href="route('profile.edit')"
                class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/80 p-3 transition hover:border-brand-200 hover:bg-brand-50/50"
                :class="{ 'border-brand-200 bg-brand-50/80 ring-1 ring-brand-200/60': isActive('profile.edit') }"
            >
                <div
                    class="relative h-9 w-9 shrink-0 overflow-hidden rounded-full bg-gradient-to-br from-brand-400 to-brand-600 text-sm font-bold text-white"
                >
                    <img
                        v-if="page.props.auth.user?.avatar_url"
                        :src="page.props.auth.user.avatar_url"
                        :alt="page.props.auth.user?.name"
                        class="h-full w-full object-cover"
                    />
                    <span
                        v-else
                        class="flex h-full w-full items-center justify-center"
                    >
                        {{ page.props.auth.user?.initials }}
                    </span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-slate-800">
                        {{ page.props.auth.user?.name }}
                    </p>
                    <p class="truncate text-xs text-slate-500">
                        {{ page.props.auth.user?.role?.name ?? 'Member' }}
                        <span
                            v-if="abilities.managed_projects_count > 0"
                            class="text-brand-600"
                        >
                            · PM {{ abilities.managed_projects_count }} project
                        </span>
                    </p>
                </div>
            </Link>
        </div>
    </aside>
</template>
