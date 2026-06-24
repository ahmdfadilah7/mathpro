<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumbs from '@/Components/UI/Breadcrumbs.vue';
import RoleForm from '@/Components/Roles/RoleForm.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { notifyValidationErrors } from '@/utils/notify';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    formOptions: Object,
});

const form = useForm({
    name: '',
    slug: '',
    description: '',
    permissions: [],
    is_active: true,
});

const submit = () => {
    form.post(route('roles.store'), {
        onError: (errors) => notifyValidationErrors(errors),
    });
};

const breadcrumbs = [
    { label: 'Roles', href: route('roles.index') },
    { label: 'Tambah role' },
];
</script>

<template>
    <Head title="Tambah Role" />

    <AppLayout title="Tambah Role" subtitle="Buat role baru dengan permission">
        <Breadcrumbs :items="breadcrumbs" />

        <form class="card p-6 lg:p-8" @submit.prevent="submit">
            <RoleForm :form="form" :form-options="formOptions" />

            <div class="mt-8 flex justify-end gap-3 border-t border-slate-100 pt-6">
                <Link :href="route('roles.index')">
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan role' }}
                </PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
