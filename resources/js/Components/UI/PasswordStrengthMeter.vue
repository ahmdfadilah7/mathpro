<script setup>
import { analyzePassword } from '@/utils/passwordStrength';
import { computed } from 'vue';

const props = defineProps({
    password: { type: String, default: '' },
});

const analysis = computed(() => analyzePassword(props.password));

const barWidth = computed(() => {
    if (!props.password) return '0%';
    return `${Math.max(analysis.value.score, 8)}%`;
});

const barColorClass = {
    slate: 'bg-slate-300',
    rose: 'bg-rose-500',
    amber: 'bg-amber-400',
    brand: 'bg-brand-500',
    emerald: 'bg-emerald-500',
};
</script>

<template>
    <div v-if="password" class="mt-3 space-y-3">
        <div>
            <div class="mb-1.5 flex items-center justify-between text-xs">
                <span class="font-medium text-slate-600">Kekuatan password</span>
                <span
                    class="font-semibold"
                    :class="{
                        'text-rose-600': analysis.color === 'rose',
                        'text-amber-600': analysis.color === 'amber',
                        'text-brand-600': analysis.color === 'brand',
                        'text-emerald-600': analysis.color === 'emerald',
                        'text-slate-500': analysis.color === 'slate',
                    }"
                >
                    {{ analysis.label }}
                </span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                <div
                    class="h-full rounded-full transition-all duration-300"
                    :class="barColorClass[analysis.color] ?? barColorClass.slate"
                    :style="{ width: barWidth }"
                />
            </div>
        </div>

        <ul class="grid gap-1.5 sm:grid-cols-2">
            <li
                v-for="item in analysis.criteria"
                :key="item.key"
                class="flex items-center gap-2 text-xs"
                :class="item.met ? 'text-emerald-700' : 'text-slate-500'"
            >
                <span
                    class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                    :class="
                        item.met
                            ? 'bg-emerald-100 text-emerald-700'
                            : 'bg-slate-100 text-slate-400'
                    "
                >
                    {{ item.met ? '✓' : '○' }}
                </span>
                {{ item.label }}
            </li>
        </ul>
    </div>
</template>
