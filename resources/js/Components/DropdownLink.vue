<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: { type: String, required: true },
    method: { type: String, default: 'get' },
    as: { type: String, default: 'a' },
});
</script>

<template>
    <Link
        :href="href"
        :method="method"
        :as="as"
        class="block w-full px-4 py-2.5 text-start text-sm font-medium text-slate-700 transition hover:bg-brand-50 hover:text-brand-700 focus:bg-brand-50 focus:outline-none"
    >
        <slot />
    </Link>
</template>
