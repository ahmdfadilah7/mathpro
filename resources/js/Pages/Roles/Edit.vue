<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumbs from '@/Components/UI/Breadcrumbs.vue';
import RoleForm from '@/Components/Roles/RoleForm.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { notifyValidationErrors } from '@/utils/notify';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    role: Object,
    formOptions: Object,
});

const form = useForm({
    name: props.role.name,
    slug: props.role.slug,
    description: props.role.description ?? '',
    permissions: [...(props.role.permissions ?? [])],
    is_active: props.role.is_active,
});

const submit = () => {
    form.put(route('roles.update', props.role.id), {
        onError: (errors) => notifyValidationErrors(errors),
    });
};

const breadcrumbs = [
    { label: 'Roles', href: route('roles.index') },
    { label: props.role.name },
];
</script>

<template>
    <Head :title="`Edit ${role.name}`" />

    <AppLayout title="Edit Role" :subtitle="role.name">
        <Breadcrumbs :items="breadcrumbs" />

        <form class="card p-6 lg:p-8" @submit.prevent="submit">
            <RoleForm
                :form="form"
                :form-options="formOptions"
                :is-system="role.is_system"
            />

            <div class="mt-8 flex justify-end gap-3 border-t border-slate-100 pt-6">
                <Link :href="route('roles.index')">
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan perubahan' }}
                </PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
