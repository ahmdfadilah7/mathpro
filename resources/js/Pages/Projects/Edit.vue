<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumbs from '@/Components/UI/Breadcrumbs.vue';
import ProjectForm from '@/Components/Projects/ProjectForm.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { notifyValidationErrors } from '@/utils/notify';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    project: Object,
    formOptions: Object,
});

const form = useForm({
    name: props.project.name,
    code: props.project.code,
    description: props.project.description ?? '',
    division_id: props.project.division_id ?? props.project.department?.division_id ?? '',
    department_id: props.project.department_id ?? props.project.department?.id ?? '',
    manager_id: props.project.manager_id ?? props.project.manager?.id ?? null,
    status: props.project.status,
    priority: props.project.priority,
    progress: props.project.progress,
    start_date: props.project.start_date ?? '',
    due_date: props.project.due_date ?? '',
    budget: props.project.budget ?? '',
    color: props.project.color,
});

const submit = () => {
    form.put(route('projects.update', props.project.id), {
        onError: (errors) => notifyValidationErrors(errors),
    });
};

const breadcrumbs = [
    { label: 'Projects', href: route('projects.index') },
    { label: props.project.name, href: route('projects.show', props.project.id) },
    { label: 'Edit' },
];
</script>

<template>
    <Head :title="`Edit — ${project.name}`" />

    <AppLayout :title="`Edit: ${project.name}`" subtitle="Perbarui informasi project">
        <Breadcrumbs :items="breadcrumbs" />

        <form class="card p-6 lg:p-8" @submit.prevent="submit">
            <ProjectForm :form="form" :form-options="formOptions" is-edit />

            <div class="mt-8 flex items-center justify-end gap-3 border-t border-slate-100 pt-6">
                <Link :href="route('projects.show', project.id)">
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan perubahan' }}
                </PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
