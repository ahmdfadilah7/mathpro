<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@heroicons/vue/24/outline';

defineProps({
    items: {
        type: Array,
        required: true,
        // { label, href? }
    },
});
</script>

<template>
    <nav class="mb-4 flex items-center gap-1 text-sm">
        <template v-for="(item, index) in items" :key="index">
            <ChevronRightIcon v-if="index > 0" class="h-4 w-4 text-slate-300" />
            <Link
                v-if="item.href"
                :href="item.href"
                class="font-medium text-slate-500 transition hover:text-brand-600"
            >
                {{ item.label }}
            </Link>
            <span v-else class="font-semibold text-slate-800">{{ item.label }}</span>
        </template>
    </nav>
</template>
