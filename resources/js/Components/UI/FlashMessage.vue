<script setup>
import { notifyError, notifySuccess } from '@/utils/notify';
import { notifySuccessThenRedirect } from '@/utils/successRedirect';
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

const page = usePage();
let lastSwalKey = null;
let lastSuccess = null;
let lastError = null;

watch(
    () => page.props.flash,
    async (flash) => {
        if (!flash) return;

        if (flash.swal) {
            const swalKey = JSON.stringify(flash.swal);
            if (swalKey === lastSwalKey) return;
            lastSwalKey = swalKey;

            await notifySuccessThenRedirect(
                flash.swal.message,
                flash.swal.redirect,
                flash.swal.title ?? 'Berhasil!'
            );
            return;
        }

        if (flash.success && flash.success !== lastSuccess) {
            lastSuccess = flash.success;
            notifySuccess(flash.success);
        }

        if (flash.error && flash.error !== lastError) {
            lastError = flash.error;
            notifyError(flash.error);
        }
    },
    { deep: true, immediate: true }
);
</script>

<template>
    <!-- Toast & dialog menangani notifikasi -->
</template>
