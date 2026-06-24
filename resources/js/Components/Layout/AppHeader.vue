<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import GlobalSearch from '@/Components/Layout/GlobalSearch.vue';
import NotificationBell from '@/Components/Layout/NotificationBell.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

defineProps({
    title: String,
    subtitle: String,
});

const page = usePage();
const mobileSearchOpen = ref(false);
</script>

<template>
    <header
        class="sticky top-0 z-20 border-b border-slate-200/60 bg-white/90 backdrop-blur-xl"
    >
        <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6">
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-lg font-bold tracking-tight text-slate-900 sm:text-xl">
                    {{ title }}
                </h1>
                <p v-if="subtitle" class="truncate text-sm text-slate-500">{{ subtitle }}</p>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                <div class="hidden w-56 xl:block xl:w-72">
                    <GlobalSearch />
                </div>

                <button
                    type="button"
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-500 transition hover:border-brand-200 hover:text-brand-600 md:hidden"
                    aria-label="Cari"
                    @click="mobileSearchOpen = !mobileSearchOpen"
                >
                    <MagnifyingGlassIcon class="h-5 w-5" />
                </button>

                <NotificationBell />

                <Dropdown align="right" width="52">
                    <template #trigger>
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-xl border border-slate-200/80 bg-white py-1.5 pl-1.5 pr-3 transition hover:border-brand-200 hover:shadow-soft"
                        >
                            <div
                                class="relative h-8 w-8 overflow-hidden rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-bold text-white"
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
                            <span class="hidden text-sm font-semibold text-slate-700 sm:block">
                                {{ page.props.auth.user?.name?.split(' ')[0] }}
                            </span>
                        </button>
                    </template>
                    <template #content>
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="text-sm font-bold text-slate-800">
                                {{ page.props.auth.user?.name }}
                            </p>
                            <p class="text-xs text-slate-500">{{ page.props.auth.user?.email }}</p>
                        </div>
                        <DropdownLink :href="route('profile.edit')">Profile Settings</DropdownLink>
                        <DropdownLink :href="route('logout')" method="post" as="button">
                            Log Out
                        </DropdownLink>
                    </template>
                </Dropdown>
            </div>
        </div>

        <div
            v-if="mobileSearchOpen"
            class="border-t border-slate-100 px-4 py-3 md:hidden"
        >
            <GlobalSearch />
        </div>
    </header>
</template>
