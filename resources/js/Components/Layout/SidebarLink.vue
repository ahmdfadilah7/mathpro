<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    href: String,
    active: Boolean,
    icon: Object,
    label: String,
});

const classes = computed(() =>
    props.active
        ? 'bg-brand-50 text-brand-700 shadow-sm ring-1 ring-brand-200/80'
        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
);

const iconClasses = computed(() =>
    props.active ? 'text-brand-600' : 'text-slate-400 group-hover:text-brand-500'
);
</script>

<template>
    <Link
        :href="href"
        class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200"
        :class="classes"
    >
        <component :is="icon" class="h-5 w-5 shrink-0 transition-colors" :class="iconClasses" />
        <span>{{ label }}</span>
        <span
            v-if="active"
            class="ml-auto h-1.5 w-1.5 rounded-full bg-brand-500"
        />
    </Link>
</template>
