<script setup>
import { Toaster } from 'vue-sonner';
</script>

<template>
    <Toaster
        position="top-right"
        :expand="true"
        :rich-colors="false"
        :toast-options="{
            classNames: {
                toast: '!rounded-xl !border !border-slate-200/80 !bg-white !shadow-card !font-sans',
                title: '!text-sm !font-semibold !text-slate-900',
                description: '!text-sm !text-slate-600',
                success: '!border-emerald-200/80 !text-emerald-800',
                error: '!border-rose-200/80 !text-rose-800',
                warning: '!border-amber-200/80 !text-amber-800',
                actionButton:
                    '!rounded-lg !bg-brand-600 !px-3 !py-1.5 !text-xs !font-semibold !text-white hover:!bg-brand-700',
            },
        }"
    />
</template>
