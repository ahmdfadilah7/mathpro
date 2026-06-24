<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ShieldCheckIcon } from '@heroicons/vue/24/outline';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout
        title="Konfirmasi password"
        subtitle="Area aman — masukkan password untuk melanjutkan"
    >
        <Head title="Confirm Password" />

        <div class="mb-6 flex justify-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-100">
                <ShieldCheckIcon class="h-7 w-7 text-brand-600" />
            </div>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <PrimaryButton class="w-full" :disabled="form.processing">
                Konfirmasi
            </PrimaryButton>
        </form>
    </GuestLayout>
</template>
