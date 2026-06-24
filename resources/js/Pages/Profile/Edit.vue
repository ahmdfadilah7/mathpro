<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import {
    BuildingOffice2Icon,
    EnvelopeIcon,
    BriefcaseIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Profile" />

    <AppLayout title="Profile" subtitle="Kelola informasi akun dan keamanan Anda">
        <!-- Profile hero -->
        <div class="card mb-6 overflow-hidden">
            <div class="h-24 bg-gradient-to-r from-brand-500 via-brand-400 to-sky-400" />
            <div class="relative px-6 pb-6">
                <div
                    class="absolute -top-10 h-20 w-20 overflow-hidden rounded-2xl border-4 border-white bg-gradient-to-br from-brand-500 to-brand-700 shadow-card"
                >
                    <img
                        v-if="user.avatar_url"
                        :src="user.avatar_url"
                        :alt="user.name"
                        class="h-full w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex h-full w-full items-center justify-center text-2xl font-bold text-white"
                    >
                        {{ user.initials }}
                    </div>
                </div>
                <div class="pt-12">
                    <h2 class="text-xl font-bold text-slate-900">{{ user.name }}</h2>
                    <p class="text-sm text-slate-500">{{ user.position ?? '—' }}</p>
                    <div class="mt-4 flex flex-wrap gap-4 text-sm text-slate-600">
                        <span class="inline-flex items-center gap-1.5">
                            <EnvelopeIcon class="h-4 w-4 text-brand-500" />
                            {{ user.email }}
                        </span>
                        <span
                            v-if="user.department"
                            class="inline-flex items-center gap-1.5"
                        >
                            <BuildingOffice2Icon class="h-4 w-4 text-brand-500" />
                            {{ user.department.name }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <BriefcaseIcon class="h-4 w-4 text-brand-500" />
                            {{ user.role?.name ?? 'Member' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <UpdateProfileInformationForm
                :must-verify-email="mustVerifyEmail"
                :status="status"
            />
            <UpdatePasswordForm />
            <DeleteUserForm />
        </div>
    </AppLayout>
</template>
