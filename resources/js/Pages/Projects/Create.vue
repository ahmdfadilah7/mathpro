<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumbs from '@/Components/UI/Breadcrumbs.vue';
import ProjectForm from '@/Components/Projects/ProjectForm.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { notifyValidationErrors } from '@/utils/notify';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    formOptions: Object,
});

const form = useForm({
    name: '',
    code: '',
    description: '',
    division_id: '',
    department_id: '',
    manager_id: '',
    status: 'planning',
    priority: 'medium',
    progress: 0,
    start_date: '',
    due_date: '',
    budget: '',
    color: '#14b8a6',
});

const submit = () => {
    form.post(route('projects.store'), {
        onError: (errors) => notifyValidationErrors(errors),
    });
};

const breadcrumbs = [
    { label: 'Projects', href: route('projects.index') },
    { label: 'Buat project' },
];
</script>

<template>
    <Head title="Buat Project" />

    <AppLayout title="Buat Project" subtitle="Lengkapi informasi project baru">
        <Breadcrumbs :items="breadcrumbs" />

        <form class="card p-6 lg:p-8" @submit.prevent="submit">
            <ProjectForm :form="form" :form-options="formOptions" />

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                <Link :href="route('projects.index')">
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan project' }}
                </PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
