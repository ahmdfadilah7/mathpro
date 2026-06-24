<script setup>
import AuthBrandPanel from '@/Components/Layout/AuthBrandPanel.vue';
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
});

const page = usePage();
</script>

<template>
    <div class="flex min-h-screen bg-white">
        <AuthBrandPanel />

        <div class="flex flex-1 flex-col justify-center px-6 py-10 sm:px-10 lg:px-16 xl:px-20">
            <!-- Mobile logo -->
            <div class="mb-8 lg:hidden">
                <Link :href="route('login')" class="inline-flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white shadow-glow"
                    >
                        M
                    </div>
                    <span class="text-lg font-bold text-slate-900">{{ page.props.app?.name ?? 'MathPro' }}</span>
                </Link>
            </div>

            <div class="mx-auto w-full max-w-md">
                <div v-if="title" class="mb-8">
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">{{ title }}</h2>
                    <p v-if="subtitle" class="mt-2 text-sm text-slate-500">{{ subtitle }}</p>
                </div>

                <div class="card border-slate-200/80 p-8 shadow-card">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
