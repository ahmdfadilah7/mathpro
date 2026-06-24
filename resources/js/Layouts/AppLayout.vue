<script setup>
import AppHeader from '@/Components/Layout/AppHeader.vue';
import Sidebar from '@/Components/Layout/Sidebar.vue';
import ConfirmDialogHost from '@/Components/UI/ConfirmDialogHost.vue';
import FlashMessage from '@/Components/UI/FlashMessage.vue';
import SuccessDialogHost from '@/Components/UI/SuccessDialogHost.vue';

defineProps({
    title: { type: String, default: 'Dashboard' },
    subtitle: { type: String, default: '' },
});
</script>

<template>
    <div class="min-h-screen bg-mesh-app">
        <Sidebar />

        <div class="pl-64">
            <AppHeader :title="title" :subtitle="subtitle" />

            <main class="p-6 lg:p-8">
                <slot />
            </main>
        </div>

        <FlashMessage />
        <ConfirmDialogHost />
        <SuccessDialogHost />
    </div>
</template>
