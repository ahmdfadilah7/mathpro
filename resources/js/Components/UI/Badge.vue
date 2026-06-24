<script setup>
defineProps({
    color: {
        type: String,
        default: 'slate',
    },
    size: {
        type: String,
        default: 'sm',
    },
});

const colorClasses = {
    slate: 'bg-slate-100 text-slate-700',
    emerald: 'bg-emerald-50 text-emerald-700',
    amber: 'bg-amber-50 text-amber-700',
    indigo: 'bg-indigo-50 text-indigo-700',
    rose: 'bg-rose-50 text-rose-700',
    sky: 'bg-sky-50 text-sky-700',
    brand: 'bg-brand-50 text-brand-700',
};

const sizeClasses = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-1 text-sm',
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-full font-medium"
        :class="[colorClasses[color] ?? colorClasses.slate, sizeClasses[size]]"
    >
        <slot />
    </span>
</template>
