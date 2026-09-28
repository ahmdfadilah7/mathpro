<script setup>
import AppHeader from '@/Components/Layout/AppHeader.vue';
import Sidebar from '@/Components/Layout/Sidebar.vue';
import ConfirmDialogHost from '@/Components/UI/ConfirmDialogHost.vue';
import FlashMessage from '@/Components/UI/FlashMessage.vue';
import SuccessDialogHost from '@/Components/UI/SuccessDialogHost.vue';
import { useLiveRefresh } from '@/composables/useLiveRefresh';
import { useSidebar } from '@/composables/useSidebar';
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, watch } from 'vue';

defineProps({
    title: { type: String, default: 'Dashboard' },
    subtitle: { type: String, default: '' },
});

useLiveRefresh({ interval: 45000, only: ['navbar'] });

const { isOpen, close } = useSidebar();

// Tutup drawer setiap kali pindah halaman
const removeNavigateListener = router.on('navigate', () => close());

// Kunci scroll body saat drawer terbuka di mobile
watch(isOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

// Tutup drawer otomatis bila layar diperbesar ke desktop
const desktopQuery = window.matchMedia('(min-width: 1024px)');
const onBreakpointChange = (e) => {
    if (e.matches) close();
};

const onEscape = (e) => {
    if (e.key === 'Escape' && isOpen.value) close();
};

onMounted(() => {
    desktopQuery.addEventListener('change', onBreakpointChange);
    document.addEventListener('keydown', onEscape);
});

onUnmounted(() => {
    removeNavigateListener();
    desktopQuery.removeEventListener('change', onBreakpointChange);
    document.removeEventListener('keydown', onEscape);
    document.body.style.overflow = '';
});
</script>

<template>
    <div class="min-h-screen bg-mesh-app">
        <Sidebar />

        <div class="min-w-0 overflow-x-clip lg:pl-64">
            <AppHeader :title="title" :subtitle="subtitle" />

            <main class="p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>

        <FlashMessage />
        <ConfirmDialogHost />
        <SuccessDialogHost />
    </div>
</template>
