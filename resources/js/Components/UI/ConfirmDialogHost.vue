<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { confirmState, resolveConfirm } from '@/utils/confirm';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';
</script>

<template>
    <Modal :show="confirmState.show" :closeable="true" max-width="md" @close="resolveConfirm(false)">
        <div class="p-6">
            <div
                class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl"
                :class="
                    confirmState.variant === 'danger'
                        ? 'bg-rose-100 text-rose-600'
                        : 'bg-amber-100 text-amber-600'
                "
            >
                <ExclamationTriangleIcon class="h-7 w-7" />
            </div>

            <h2 class="mt-4 text-center text-lg font-bold text-slate-900">
                {{ confirmState.title }}
            </h2>
            <p v-if="confirmState.text" class="mt-2 text-center text-sm text-slate-600">
                {{ confirmState.text }}
            </p>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-center">
                <SecondaryButton class="w-full sm:w-auto" @click="resolveConfirm(false)">
                    {{ confirmState.cancelText }}
                </SecondaryButton>
                <DangerButton
                    v-if="confirmState.variant === 'danger'"
                    class="w-full sm:w-auto"
                    @click="resolveConfirm(true)"
                >
                    {{ confirmState.confirmText }}
                </DangerButton>
                <button
                    v-else
                    type="button"
                    class="btn-primary w-full sm:w-auto"
                    @click="resolveConfirm(true)"
                >
                    {{ confirmState.confirmText }}
                </button>
            </div>
        </div>
    </Modal>
</template>
