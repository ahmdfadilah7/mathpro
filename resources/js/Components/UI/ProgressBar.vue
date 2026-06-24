<script setup>
defineProps({
    value: { type: Number, default: 0 },
    color: { type: String, default: '#6366f1' },
    showLabel: { type: Boolean, default: true },
});
</script>

<template>
    <div class="w-full">
        <div class="mb-1 flex items-center justify-between" v-if="showLabel">
            <span class="text-xs text-slate-500">Progress</span>
            <span class="text-xs font-semibold text-slate-700">{{ value }}%</span>
        </div>
        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
            <div
                class="h-full rounded-full transition-all duration-500"
                :style="{ width: `${Math.min(100, Math.max(0, value))}%`, backgroundColor: color }"
            />
        </div>
    </div>
</template>
