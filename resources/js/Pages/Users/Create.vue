<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumbs from '@/Components/UI/Breadcrumbs.vue';
import UserForm from '@/Components/Users/UserForm.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { notifyValidationErrors } from '@/utils/notify';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    formOptions: Object,
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role_id: '',
    division_id: '',
    department_id: '',
    position: '',
    phone: '',
    is_active: true,
});

const submit = () => {
    form.post(route('users.store'), {
        onError: (errors) => notifyValidationErrors(errors),
    });
};

const breadcrumbs = [
    { label: 'Users', href: route('users.index') },
    { label: 'Tambah user' },
];
</script>

<template>
    <Head title="Tambah User" />

    <AppLayout title="Tambah User" subtitle="Buat akun pengguna baru">
        <Breadcrumbs :items="breadcrumbs" />

        <form class="card p-6 lg:p-8" @submit.prevent="submit">
            <UserForm :form="form" :form-options="formOptions" />

            <div class="mt-8 flex justify-end gap-3 border-t border-slate-100 pt-6">
                <Link :href="route('users.index')">
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan user' }}
                </PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
