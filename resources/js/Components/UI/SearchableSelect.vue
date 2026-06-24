<script setup>
import {
    ChevronDownIcon,
    MagnifyingGlassIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';

const emit = defineEmits(['change']);

const model = defineModel({
    type: [String, Number, null],
    default: null,
});

const props = defineProps({
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Pilih...' },
    disabled: { type: Boolean, default: false },
    canClear: { type: Boolean, default: false },
    searchable: { type: Boolean, default: true },
    teleport: { type: Boolean, default: true },
});

const root = ref(null);
const isOpen = ref(false);
const search = ref('');
const dropdownStyle = ref({});

const selectedOption = computed(() =>
    props.options.find((o) => String(o.value) === String(model.value ?? ''))
);

const filteredOptions = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.options;
    return props.options.filter((o) => o.label?.toLowerCase().includes(q));
});

const displayLabel = computed(() => selectedOption.value?.label ?? props.placeholder);

const hasValue = computed(
    () => model.value !== null && model.value !== undefined && model.value !== ''
);

const updateDropdownPosition = () => {
    if (!root.value) return;

    const rect = root.value.getBoundingClientRect();
    const maxHeight = 280;
    const spaceBelow = window.innerHeight - rect.bottom - 8;
    const spaceAbove = rect.top - 8;
    const openUp = spaceBelow < 200 && spaceAbove > spaceBelow;

    dropdownStyle.value = {
        position: 'fixed',
        left: `${rect.left}px`,
        width: `${rect.width}px`,
        zIndex: 250,
        ...(openUp
            ? { bottom: `${window.innerHeight - rect.top + 6}px`, maxHeight: `${Math.min(maxHeight, spaceAbove)}px` }
            : { top: `${rect.bottom + 6}px`, maxHeight: `${Math.min(maxHeight, spaceBelow)}px` }),
    };
};

const toggle = async () => {
    if (props.disabled) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        search.value = '';
        await nextTick();
        updateDropdownPosition();
    }
};

const close = () => {
    isOpen.value = false;
    search.value = '';
};

const select = (option) => {
    model.value = option.value;
    close();
    emit('change');
};

const clear = (e) => {
    e.stopPropagation();
    model.value = props.canClear ? null : '';
    emit('change');
};

const onClickOutside = (e) => {
    if (root.value && !root.value.contains(e.target)) {
        const panel = document.getElementById(dropdownId.value);
        if (panel?.contains(e.target)) return;
        close();
    }
};

const onKeydown = (e) => {
    if (e.key === 'Escape') close();
};

const onScrollOrResize = () => {
    if (isOpen.value) updateDropdownPosition();
};

const dropdownId = ref(`searchable-select-${Math.random().toString(36).slice(2, 9)}`);

watch(isOpen, (open) => {
    if (open) {
        document.addEventListener('click', onClickOutside);
        document.addEventListener('keydown', onKeydown);
        window.addEventListener('scroll', onScrollOrResize, true);
        window.addEventListener('resize', onScrollOrResize);
    } else {
        document.removeEventListener('click', onClickOutside);
        document.removeEventListener('keydown', onKeydown);
        window.removeEventListener('scroll', onScrollOrResize, true);
        window.removeEventListener('resize', onScrollOrResize);
    }
});

onUnmounted(() => {
    document.removeEventListener('click', onClickOutside);
    document.removeEventListener('keydown', onKeydown);
    window.removeEventListener('scroll', onScrollOrResize, true);
    window.removeEventListener('resize', onScrollOrResize);
});
</script>

<template>
    <div ref="root" class="relative w-full">
        <button
            type="button"
            class="form-input flex min-h-[42px] w-full items-center gap-2 text-left"
            :class="[
                disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer',
                isOpen ? '!border-brand-400 !bg-white !ring-2 !ring-brand-500/20' : '',
            ]"
            :disabled="disabled"
            @click="toggle"
        >
            <span
                class="min-w-0 flex-1 truncate text-sm"
                :class="hasValue ? 'font-medium text-slate-800' : 'text-slate-400'"
            >
                {{ displayLabel }}
            </span>

            <span class="flex shrink-0 items-center gap-0.5">
                <button
                    v-if="canClear && hasValue && !disabled"
                    type="button"
                    class="rounded-md p-0.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                    tabindex="-1"
                    @click="clear"
                >
                    <XMarkIcon class="h-4 w-4" />
                </button>
                <ChevronDownIcon
                    class="h-4 w-4 text-slate-400 transition-transform duration-200"
                    :class="{ 'rotate-180 text-brand-500': isOpen }"
                />
            </span>
        </button>

        <Teleport v-if="teleport" to="body">
            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="scale-95 opacity-0"
                enter-to-class="scale-100 opacity-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="scale-100 opacity-100"
                leave-to-class="scale-95 opacity-0"
            >
                <div
                    v-show="isOpen"
                    :id="dropdownId"
                    :style="dropdownStyle"
                    class="flex origin-top flex-col overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-card ring-1 ring-slate-900/5"
                style="z-index: 9999"
                >
                    <div
                        v-if="searchable"
                        class="shrink-0 border-b border-slate-100 bg-slate-50/50 p-2"
                    >
                        <div class="relative">
                            <MagnifyingGlassIcon
                                class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            />
                            <input
                                v-model="search"
                                type="text"
                                class="w-full rounded-lg border-0 bg-white py-2 pl-8 pr-3 text-sm text-slate-800 shadow-sm ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-500/25"
                                placeholder="Ketik untuk mencari..."
                                @click.stop
                            />
                        </div>
                    </div>

                    <ul class="min-h-0 flex-1 overflow-y-auto py-1">
                        <li
                            v-for="option in filteredOptions"
                            :key="String(option.value)"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center px-3 py-2.5 text-left text-sm transition"
                                :class="
                                    String(option.value) === String(model ?? '')
                                        ? 'bg-brand-50 font-semibold text-brand-700'
                                        : 'text-slate-700 hover:bg-slate-50'
                                "
                                @click="select(option)"
                            >
                                <span class="whitespace-normal break-words">{{ option.label }}</span>
                            </button>
                        </li>
                        <li
                            v-if="filteredOptions.length === 0"
                            class="px-3 py-6 text-center text-sm text-slate-400"
                        >
                            Tidak ada hasil
                        </li>
                    </ul>
                </div>
            </Transition>
        </Teleport>

        <Transition
            v-else
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                v-show="isOpen"
                class="absolute z-[100] mt-1.5 w-full origin-top overflow-hidden rounded-xl border border-slate-200/90 bg-white shadow-card"
            >
                <div
                    v-if="searchable"
                    class="border-b border-slate-100 bg-slate-50/50 p-2"
                >
                    <div class="relative">
                        <MagnifyingGlassIcon
                            class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                        />
                        <input
                            v-model="search"
                            type="text"
                            class="w-full rounded-lg border-0 bg-white py-2 pl-8 pr-3 text-sm text-slate-800 shadow-sm ring-1 ring-slate-200/80 placeholder:text-slate-400 focus:ring-2 focus:ring-brand-500/25"
                            placeholder="Ketik untuk mencari..."
                            @click.stop
                        />
                    </div>
                </div>

                <ul class="max-h-60 overflow-y-auto py-1">
                    <li
                        v-for="option in filteredOptions"
                        :key="String(option.value)"
                    >
                        <button
                            type="button"
                            class="flex w-full items-center px-3 py-2.5 text-left text-sm transition"
                            :class="
                                String(option.value) === String(model ?? '')
                                    ? 'bg-brand-50 font-semibold text-brand-700'
                                    : 'text-slate-700 hover:bg-slate-50'
                            "
                            @click="select(option)"
                        >
                            <span class="whitespace-normal break-words">{{ option.label }}</span>
                        </button>
                    </li>
                    <li
                        v-if="filteredOptions.length === 0"
                        class="px-3 py-6 text-center text-sm text-slate-400"
                    >
                        Tidak ada hasil
                    </li>
                </ul>
            </div>
        </Transition>
    </div>
</template>
