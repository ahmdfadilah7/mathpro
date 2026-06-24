<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: String,
    description: String,
    actionLabel: String,
    actionHref: String,
    icon: Object,
});
</script>

<template>
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div
            v-if="icon"
            class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50"
        >
            <component :is="icon" class="h-7 w-7 text-brand-600" />
        </div>
        <h3 class="text-lg font-bold text-slate-900">{{ title }}</h3>
        <p class="mt-2 max-w-sm text-sm text-slate-500">{{ description }}</p>
        <Link
            v-if="actionHref && actionLabel"
            :href="actionHref"
            class="btn-primary mt-6"
        >
            {{ actionLabel }}
        </Link>
    </div>
</template>
