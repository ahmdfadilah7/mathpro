<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { SparklesIcon } from '@heroicons/vue/24/outline';

defineProps({
    title: String,
    description: String,
});
</script>

<template>
    <Head :title="title" />

    <AppLayout :title="title" :subtitle="description">
        <div class="flex min-h-[55vh] items-center justify-center">
            <div class="card max-w-lg p-10 text-center">
                <div
                    class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-100 to-sky-100"
                >
                    <SparklesIcon class="h-8 w-8 text-brand-600" />
                </div>
                <h2 class="text-2xl font-bold text-slate-900">{{ title }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-500">{{ description }}</p>
                <div
                    class="mt-6 inline-flex items-center gap-2 rounded-full bg-brand-50 px-4 py-2 text-xs font-semibold text-brand-700"
                >
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-brand-500" />
                    Segera hadir — tahap pengembangan berikutnya
                </div>
            </div>
        </div>
    </AppLayout>
</template>
