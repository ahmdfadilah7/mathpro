<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import {
    ClipboardDocumentListIcon,
    MagnifyingGlassIcon,
    Squares2X2Icon,
    UserIcon,
} from '@heroicons/vue/24/outline';

const query = ref('');
const open = ref(false);
const loading = ref(false);
const results = ref({ projects: [], tasks: [], users: [] });
const activeIndex = ref(-1);

let debounceTimer;

const flatResults = computed(() => [
    ...results.value.projects.map((r) => ({ ...r, group: 'Projects' })),
    ...results.value.tasks.map((r) => ({ ...r, group: 'Tasks' })),
    ...results.value.users.map((r) => ({ ...r, group: 'Users' })),
]);

const hasResults = computed(() => flatResults.value.length > 0);
const showPanel = computed(
    () => open.value && (loading.value || query.value.trim().length >= 2)
);

const fetchResults = async () => {
    const q = query.value.trim();
    if (q.length < 2) {
        results.value = { projects: [], tasks: [], users: [] };
        activeIndex.value = -1;
        return;
    }

    loading.value = true;
    try {
        const { data } = await window.axios.get(route('navbar.search'), { params: { q } });
        results.value = data;
        activeIndex.value = -1;
    } catch {
        results.value = { projects: [], tasks: [], users: [] };
    } finally {
        loading.value = false;
    }
};

watch(query, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchResults, 300);
});

watch(flatResults, (list) => {
    if (list.length && activeIndex.value < 0) {
        activeIndex.value = 0;
    }
    if (!list.length) {
        activeIndex.value = -1;
    }
});

const onFocus = () => {
    open.value = true;
    if (query.value.trim().length >= 2) {
        fetchResults();
    }
};

const close = () => {
    open.value = false;
    activeIndex.value = -1;
};

const iconFor = (type) => {
    if (type === 'project') return Squares2X2Icon;
    if (type === 'user') return UserIcon;
    return ClipboardDocumentListIcon;
};

const onKeydown = (event) => {
    if (!showPanel.value && event.key !== 'Escape') return;

    if (event.key === 'Escape') {
        close();
        return;
    }

    if (!flatResults.value.length) return;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeIndex.value = (activeIndex.value + 1) % flatResults.value.length;
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeIndex.value =
            activeIndex.value <= 0
                ? flatResults.value.length - 1
                : activeIndex.value - 1;
    } else if (event.key === 'Enter' && activeIndex.value >= 0) {
        event.preventDefault();
        const item = flatResults.value[activeIndex.value];
        if (item?.url) {
            window.location.href = item.url;
        }
    }
};

const onGlobalKeydown = (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        event.preventDefault();
        document.getElementById('global-search-input')?.focus();
    }
};

onMounted(() => document.addEventListener('keydown', onGlobalKeydown));
onUnmounted(() => {
    document.removeEventListener('keydown', onGlobalKeydown);
    clearTimeout(debounceTimer);
});
</script>

<template>
    <div class="relative w-full">
        <MagnifyingGlassIcon
            class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
        />
        <input
            id="global-search-input"
            v-model="query"
            type="search"
            placeholder="Cari project, task... (Ctrl+K)"
            class="w-full rounded-xl border border-slate-200/80 bg-slate-50/80 py-2 pl-9 pr-16 text-sm transition focus:border-brand-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/15"
            autocomplete="off"
            @focus="onFocus"
            @keydown="onKeydown"
        />
        <kbd
            class="pointer-events-none absolute right-2 top-1/2 hidden -translate-y-1/2 rounded border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-medium text-slate-400 lg:inline"
        >
            Ctrl+K
        </kbd>

        <div
            v-show="open"
            class="fixed inset-0 z-40"
            @click="close"
        />

        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="showPanel"
                class="absolute left-0 right-0 z-50 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-card"
            >
                <div v-if="loading" class="px-4 py-6 text-center text-sm text-slate-500">
                    Mencari...
                </div>
                <div
                    v-else-if="!hasResults"
                    class="px-4 py-6 text-center text-sm text-slate-500"
                >
                    Tidak ada hasil untuk "{{ query.trim() }}"
                </div>
                <ul v-else class="max-h-80 overflow-y-auto py-1">
                    <template v-for="(item, index) in flatResults" :key="`${item.type}-${item.id}`">
                        <li
                            v-if="index === 0 || flatResults[index - 1].group !== item.group"
                            class="px-3 pb-1 pt-2 text-[10px] font-bold uppercase tracking-widest text-slate-400"
                        >
                            {{ item.group }}
                        </li>
                        <li>
                            <Link
                                :href="item.url"
                                class="flex items-start gap-3 px-3 py-2.5 text-sm transition"
                                :class="
                                    activeIndex === index
                                        ? 'bg-brand-50 text-brand-900'
                                        : 'text-slate-700 hover:bg-slate-50'
                                "
                                @click="close"
                            >
                                <span
                                    class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                    :class="
                                        item.color
                                            ? 'text-white'
                                            : 'bg-slate-100 text-slate-600'
                                    "
                                    :style="
                                        item.color
                                            ? { backgroundColor: item.color }
                                            : undefined
                                    "
                                >
                                    <component :is="iconFor(item.type)" class="h-4 w-4" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-medium">{{ item.title }}</span>
                                    <span class="block truncate text-xs text-slate-500">
                                        {{ item.subtitle }}
                                    </span>
                                </span>
                                <span class="shrink-0 text-xs text-slate-400">{{ item.meta }}</span>
                            </Link>
                        </li>
                    </template>
                </ul>
            </div>
        </Transition>
    </div>
</template>
