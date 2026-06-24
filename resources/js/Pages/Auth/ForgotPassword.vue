<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout
        title="Lupa password?"
        subtitle="Kami akan kirim link reset ke email Anda"
    >
        <Head title="Forgot Password" />

        <div v-if="status" class="alert-success mb-6">{{ status }}</div>

        <form class="space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="nama@perusahaan.com"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <PrimaryButton class="w-full" :disabled="form.processing">
                Kirim link reset
            </PrimaryButton>
        </form>

        <Link
            :href="route('login')"
            class="mt-6 inline-flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-brand-600"
        >
            <ArrowLeftIcon class="h-4 w-4" />
            Kembali ke login
        </Link>
    </GuestLayout>
</template>
