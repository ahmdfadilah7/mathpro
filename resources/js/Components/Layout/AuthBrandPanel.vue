<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import {
    CalendarDaysIcon,
    ChartBarIcon,
    Squares2X2Icon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline';

const page = usePage();

const features = [
    { icon: Squares2X2Icon, text: 'Kelola project multi-divisi' },
    { icon: CalendarDaysIcon, text: 'Timeline & kalender terintegrasi' },
    { icon: ChartBarIcon, text: 'Laporan progress real-time' },
    { icon: UserGroupIcon, text: 'Kolaborasi tim per departemen' },
];
</script>

<template>
    <div
        class="relative hidden flex-col justify-between overflow-hidden bg-mesh-auth p-10 lg:flex lg:w-[48%] xl:w-[52%]"
    >
        <div class="absolute -right-20 -top-20 h-72 w-72 rounded-full bg-brand-300/30 blur-3xl" />
        <div class="absolute -bottom-16 -left-16 h-64 w-64 rounded-full bg-sky-300/40 blur-3xl" />

        <div class="relative">
            <Link :href="route('login')" class="inline-flex items-center gap-3">
                <div
                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-500 to-brand-700 text-lg font-bold text-white shadow-glow"
                >
                    M
                </div>
                <div>
                    <p class="text-xl font-bold text-slate-900">{{ page.props.app?.name ?? 'MathPro' }}</p>
                    <p class="text-sm text-slate-600">Project Management</p>
                </div>
            </Link>
        </div>

        <div class="relative max-w-md">
            <h1 class="text-4xl font-bold leading-tight tracking-tight text-slate-900">
                Kelola project
                <span class="bg-gradient-to-r from-brand-600 to-sky-600 bg-clip-text text-transparent">
                    lebih cerdas
                </span>
            </h1>
            <p class="mt-4 text-base leading-relaxed text-slate-600">
                Platform manajemen project untuk perusahaan dengan banyak divisi dan departemen — modern, terstruktur, dan mudah digunakan.
            </p>

            <ul class="mt-8 space-y-3">
                <li
                    v-for="(feature, i) in features"
                    :key="i"
                    class="flex items-center gap-3 rounded-xl border border-white/60 bg-white/50 px-4 py-3 backdrop-blur-sm"
                >
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-600"
                    >
                        <component :is="feature.icon" class="h-5 w-5" />
                    </div>
                    <span class="text-sm font-medium text-slate-700">{{ feature.text }}</span>
                </li>
            </ul>
        </div>

        <p class="relative text-xs text-slate-500">
            © {{ new Date().getFullYear() }} {{ page.props.app?.name ?? 'MathPro' }}. All rights reserved.
        </p>
    </div>
</template>
