<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { EnvelopeIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout
        title="Verifikasi email"
        subtitle="Cek inbox Anda untuk melanjutkan"
    >
        <Head title="Email Verification" />

        <div class="mb-6 flex justify-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-100">
                <EnvelopeIcon class="h-7 w-7 text-brand-600" />
            </div>
        </div>

        <p class="text-center text-sm leading-relaxed text-slate-600">
            Terima kasih telah mendaftar! Klik link verifikasi di email Anda. Jika belum menerima email, kami bisa mengirim ulang.
        </p>

        <div v-if="verificationLinkSent" class="alert-success mt-4">
            Link verifikasi baru telah dikirim ke email Anda.
        </div>

        <form class="mt-6 space-y-4" @submit.prevent="submit">
            <PrimaryButton class="w-full" :disabled="form.processing">
                Kirim ulang email verifikasi
            </PrimaryButton>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="block w-full text-center text-sm font-medium text-slate-500 transition hover:text-brand-600"
            >
                Keluar
            </Link>
        </form>
    </GuestLayout>
</template>
