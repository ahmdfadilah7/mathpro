<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumbs from '@/Components/UI/Breadcrumbs.vue';
import UserForm from '@/Components/Users/UserForm.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { notifyValidationErrors } from '@/utils/notify';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
    formOptions: Object,
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    role_id: props.user.role?.id ?? '',
    division_id: props.user.department?.division?.id ?? '',
    department_id: props.user.department?.id ?? '',
    position: props.user.position ?? '',
    phone: props.user.phone ?? '',
    is_active: props.user.is_active,
});

const submit = () => {
    form.put(route('users.update', props.user.id), {
        onError: (errors) => notifyValidationErrors(errors),
    });
};

const breadcrumbs = [
    { label: 'Users', href: route('users.index') },
    { label: props.user.name },
];
</script>

<template>
    <Head :title="`Edit ${user.name}`" />

    <AppLayout title="Edit User" :subtitle="user.email">
        <Breadcrumbs :items="breadcrumbs" />

        <form class="card p-6 lg:p-8" @submit.prevent="submit">
            <UserForm :form="form" :form-options="formOptions" is-edit />

            <div class="mt-8 flex justify-end gap-3 border-t border-slate-100 pt-6">
                <Link :href="route('users.index')">
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan perubahan' }}
                </PrimaryButton>
            </div>
        </form>
    </AppLayout>
</template>
