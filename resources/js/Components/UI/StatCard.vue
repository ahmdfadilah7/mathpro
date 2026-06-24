<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: String,
    value: [String, Number],
    subtitle: String,
    icon: Object,
    trend: String,
    color: {
        type: String,
        default: 'brand',
    },
});

const iconBg = computed(() => ({
    brand: 'bg-brand-50 text-brand-600',
    emerald: 'bg-emerald-50 text-emerald-600',
    amber: 'bg-amber-50 text-amber-600',
    rose: 'bg-rose-50 text-rose-600',
    sky: 'bg-sky-50 text-sky-600',
    slate: 'bg-slate-100 text-slate-600',
}[props.color] ?? 'bg-brand-50 text-brand-600'));
</script>

<template>
    <div class="card p-5 transition hover:shadow-card">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500">{{ title }}</p>
                <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                    {{ value }}
                </p>
                <p v-if="subtitle" class="mt-1 text-xs text-slate-400">
                    {{ subtitle }}
                </p>
            </div>
            <div
                v-if="icon"
                class="flex h-11 w-11 items-center justify-center rounded-xl"
                :class="iconBg"
            >
                <component :is="icon" class="h-5 w-5" />
            </div>
        </div>
        <p v-if="trend" class="mt-3 text-xs font-medium text-emerald-600">
            {{ trend }}
        </p>
    </div>
</template>
