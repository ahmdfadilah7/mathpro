<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { BellIcon } from '@heroicons/vue/24/outline';

const page = usePage();

const notifications = computed(() => page.props.navbar?.notifications ?? []);
const count = computed(() => page.props.navbar?.notificationCount ?? 0);

const colorClass = {
    rose: 'bg-rose-50 text-rose-700',
    amber: 'bg-amber-50 text-amber-700',
    sky: 'bg-sky-50 text-sky-700',
    brand: 'bg-brand-50 text-brand-700',
    emerald: 'bg-emerald-50 text-emerald-700',
    slate: 'bg-slate-100 text-slate-600',
};

const dismissOne = (notificationKey, href) => {
    router.post(
        route('navbar.notifications.dismiss'),
        { notification_key: notificationKey },
        {
            preserveScroll: true,
            only: ['navbar'],
            onFinish: () => router.visit(href),
        }
    );
};

const dismissAll = () => {
    router.post(route('navbar.notifications.dismiss-all'), {}, {
        preserveScroll: true,
        only: ['navbar'],
    });
};
</script>

<template>
    <Dropdown align="right" width="96" content-classes="rounded-xl border border-slate-100 bg-white py-0 shadow-card">
        <template #trigger>
            <button
                type="button"
                class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200/80 bg-white text-slate-500 transition hover:border-brand-200 hover:text-brand-600"
                aria-label="Notifikasi"
            >
                <BellIcon class="h-5 w-5" />
                <span
                    v-if="count > 0"
                    class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent-500 px-1 text-[10px] font-bold text-white ring-2 ring-white"
                >
                    {{ count > 9 ? '9+' : count }}
                </span>
            </button>
        </template>
        <template #content>
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <div>
                    <p class="text-sm font-bold text-slate-900">Notifikasi</p>
                    <p class="text-xs text-slate-500">
                        {{ count ? `${count} perlu perhatian` : 'Semua sudah dibaca' }}
                    </p>
                </div>
                <button
                    v-if="count > 0"
                    type="button"
                    class="text-xs font-medium text-brand-600 hover:text-brand-700"
                    @click.stop="dismissAll"
                >
                    Tandai semua
                </button>
            </div>

            <div v-if="notifications.length" class="max-h-96 overflow-y-auto">
                <button
                    v-for="item in notifications"
                    :key="item.id"
                    type="button"
                    class="flex w-full gap-3 border-b border-slate-50 px-4 py-3 text-left transition last:border-0 hover:bg-slate-50"
                    @click="dismissOne(item.id, item.href)"
                >
                    <span
                        class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                        :class="colorClass[item.color] ?? colorClass.slate"
                    >
                        {{
                            item.type === 'chat'
                                ? '💬'
                                : item.type === 'unassigned'
                                  ? '📋'
                                  : '!'
                        }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-slate-800">
                            {{ item.title }}
                        </span>
                        <span class="mt-0.5 block text-xs leading-snug text-slate-500">
                            {{ item.body }}
                        </span>
                        <span class="mt-1 block text-[10px] text-slate-400">
                            {{ item.created_at_label }}
                        </span>
                    </span>
                </button>
            </div>

            <div v-else class="px-4 py-8 text-center text-sm text-slate-500">
                Tidak ada notifikasi saat ini.
            </div>

            <div class="border-t border-slate-100 px-4 py-2">
                <Link
                    :href="route('my-tasks.index')"
                    class="block rounded-lg py-2 text-center text-xs font-medium text-brand-600 hover:bg-brand-50"
                >
                    Lihat My Tasks
                </Link>
            </div>
        </template>
    </Dropdown>
</template>
