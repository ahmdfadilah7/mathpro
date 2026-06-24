<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, required: true },
    initials: { type: String, default: '' },
    src: { type: String, default: null },
    size: { type: String, default: 'md' },
});

const sizeClasses = {
    sm: 'h-7 w-7 text-xs',
    md: 'h-9 w-9 text-sm',
    lg: 'h-11 w-11 text-base',
    xl: 'h-20 w-20 text-2xl',
};

const displayInitials = computed(
    () => props.initials || props.name?.charAt(0)?.toUpperCase() || '?'
);
</script>

<template>
    <div
        class="relative shrink-0 overflow-hidden rounded-full bg-gradient-to-br from-brand-500 to-brand-700 font-semibold text-white"
        :class="sizeClasses[size]"
        :title="name"
    >
        <img
            v-if="src"
            :src="src"
            :alt="name"
            class="h-full w-full object-cover"
        />
        <div
            v-else
            class="flex h-full w-full items-center justify-center"
        >
            {{ displayInitials }}
        </div>
    </div>
</template>
