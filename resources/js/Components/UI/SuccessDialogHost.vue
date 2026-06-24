<script setup>
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { completeSuccessRedirect, successRedirectState } from '@/utils/successRedirect';
import { CheckCircleIcon } from '@heroicons/vue/24/outline';
</script>

<template>
    <Modal :show="successRedirectState.show" :closeable="false" max-width="md">
        <div class="p-6 text-center">
            <div
                class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600"
            >
                <CheckCircleIcon class="h-7 w-7" />
            </div>

            <h2 class="mt-4 text-lg font-bold text-slate-900">
                {{ successRedirectState.title }}
            </h2>
            <p class="mt-2 text-sm text-slate-600">
                {{ successRedirectState.message }}
            </p>

            <PrimaryButton class="mt-6 w-full sm:w-auto" @click="completeSuccessRedirect">
                Lanjutkan
            </PrimaryButton>
        </div>
    </Modal>
</template>
