<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import {
    DEMO_PASSWORD,
    demoAccounts,
    demoProjects,
    demoScenarios,
    usageSections,
} from '@/config/demoGuide';
import { notifySuccess } from '@/utils/notify';
import { Head } from '@inertiajs/vue3';
import { BookOpenIcon, ClipboardDocumentIcon, UserGroupIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const activeTab = ref('guide');

const tabs = [
    { id: 'guide', label: 'Panduan', icon: BookOpenIcon },
    { id: 'accounts', label: 'Akun Demo', icon: UserGroupIcon },
];

const projectStatusColor = {
    Active: 'emerald',
    Planning: 'sky',
    'On Hold': 'amber',
    Completed: 'slate',
};

const copyPassword = async () => {
    try {
        await navigator.clipboard.writeText(DEMO_PASSWORD);
        notifySuccess('Password disalin ke clipboard.', 'Password demo');
    } catch {
        notifySuccess(`Password demo: ${DEMO_PASSWORD}`, 'Password demo');
    }
};

const copyEmail = async (email) => {
    try {
        await navigator.clipboard.writeText(email);
        notifySuccess(`${email} — password: ${DEMO_PASSWORD}`, 'Email disalin');
    } catch {
        notifySuccess(`${email} — password: ${DEMO_PASSWORD}`, 'Akun demo');
    }
};
</script>

<template>
    <Head title="Dokumentasi" />

    <AppLayout title="Dokumentasi" subtitle="Panduan penggunaan & akun demo MathPro">
        <div class="card overflow-hidden">
            <div class="flex gap-1 border-b border-slate-100 px-6 pt-4">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-t-lg px-4 py-2.5 text-sm font-semibold transition"
                    :class="
                        activeTab === tab.id
                            ? 'border-b-2 border-brand-500 text-brand-600'
                            : 'text-slate-500 hover:text-slate-700'
                    "
                    @click="activeTab = tab.id"
                >
                    <component :is="tab.icon" class="h-4 w-4" />
                    {{ tab.label }}
                </button>
            </div>

            <div class="p-6">
                <div v-show="activeTab === 'guide'" class="space-y-6">
                    <div
                        class="flex flex-col gap-3 rounded-xl border border-brand-200/80 bg-brand-50/50 p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="text-sm font-semibold text-brand-800">
                                Password semua akun demo
                            </p>
                            <p class="mt-1 font-mono text-lg font-bold tracking-wide text-brand-900">
                                {{ DEMO_PASSWORD }}
                            </p>
                        </div>
                        <button type="button" class="btn-secondary shrink-0 text-sm" @click="copyPassword">
                            <ClipboardDocumentIcon class="h-4 w-4" />
                            Salin password
                        </button>
                    </div>

                    <div class="space-y-4">
                        <section
                            v-for="section in usageSections"
                            :key="section.id"
                            class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4"
                        >
                            <h3 class="text-sm font-bold text-slate-800">{{ section.title }}</h3>
                            <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-sm text-slate-600">
                                <li v-for="(step, index) in section.steps" :key="index">
                                    {{ step.replace(/\*\*(.+?)\*\*/g, '$1') }}
                                </li>
                            </ol>
                        </section>
                    </div>

                    <section>
                        <h3 class="mb-3 text-sm font-bold text-slate-800">Skenario uji cepat</h3>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <button
                                v-for="scenario in demoScenarios"
                                :key="scenario.email"
                                type="button"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-left text-sm transition hover:border-brand-300 hover:bg-brand-50/40"
                                @click="copyEmail(scenario.email)"
                            >
                                <span class="font-medium text-slate-800">{{ scenario.label }}</span>
                                <span class="mt-0.5 block font-mono text-xs text-brand-600">{{
                                    scenario.email
                                }}</span>
                            </button>
                        </div>
                    </section>
                </div>

                <div v-show="activeTab === 'accounts'" class="space-y-5">
                    <p class="text-sm text-slate-600">
                        Klik baris akun untuk menyalin email. Password semua akun:
                        <button
                            type="button"
                            class="font-mono font-semibold text-brand-600 hover:underline"
                            @click="copyPassword"
                        >
                            {{ DEMO_PASSWORD }}
                        </button>
                    </p>

                    <div class="space-y-3">
                        <button
                            v-for="account in demoAccounts"
                            :key="account.email"
                            type="button"
                            class="w-full rounded-xl border border-slate-200 bg-white p-4 text-left transition hover:border-brand-300 hover:bg-brand-50/30 hover:shadow-sm"
                            @click="copyEmail(account.email)"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ account.name }}</p>
                                    <p class="mt-0.5 font-mono text-sm text-brand-600">
                                        {{ account.email }}
                                    </p>
                                </div>
                                <Badge :color="account.roleColor">{{ account.role }}</Badge>
                            </div>
                            <div class="mt-3 grid gap-2 text-xs text-slate-600 sm:grid-cols-2">
                                <p>
                                    <span class="font-semibold text-slate-700">Dept:</span>
                                    {{ account.department }}
                                </p>
                                <p>
                                    <span class="font-semibold text-slate-700">Menu:</span>
                                    {{ account.menu }}
                                </p>
                                <p class="sm:col-span-2">
                                    <span class="font-semibold text-slate-700">Project:</span>
                                    {{ account.projects }}
                                </p>
                            </div>
                        </button>
                    </div>

                    <section>
                        <h3 class="mb-3 text-sm font-bold text-slate-800">Project seed data</h3>
                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left font-semibold text-slate-700">
                                            Kode
                                        </th>
                                        <th class="px-4 py-2.5 text-left font-semibold text-slate-700">
                                            Nama
                                        </th>
                                        <th class="px-4 py-2.5 text-left font-semibold text-slate-700">
                                            PM
                                        </th>
                                        <th class="px-4 py-2.5 text-left font-semibold text-slate-700">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr v-for="project in demoProjects" :key="project.code">
                                        <td class="px-4 py-2.5 font-mono text-xs text-slate-600">
                                            {{ project.code }}
                                        </td>
                                        <td class="px-4 py-2.5 text-slate-800">{{ project.name }}</td>
                                        <td class="px-4 py-2.5 text-slate-600">{{ project.pm }}</td>
                                        <td class="px-4 py-2.5">
                                            <Badge
                                                :color="projectStatusColor[project.status] ?? 'slate'"
                                                size="sm"
                                            >
                                                {{ project.status }}
                                            </Badge>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
