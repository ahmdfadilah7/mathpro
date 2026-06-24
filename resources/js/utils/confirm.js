import { reactive } from 'vue';

export const confirmState = reactive({
    show: false,
    title: 'Yakin?',
    text: '',
    confirmText: 'Ya, lanjutkan',
    cancelText: 'Batal',
    variant: 'danger',
});

let resolvePromise = null;

/**
 * Dialog konfirmasi (promise boolean).
 */
export function confirmDialog({
    title = 'Yakin?',
    text = '',
    confirmText = 'Ya, lanjutkan',
    cancelText = 'Batal',
    variant = 'warning',
} = {}) {
    return new Promise((resolve) => {
        resolvePromise = resolve;
        confirmState.title = title;
        confirmState.text = text;
        confirmState.confirmText = confirmText;
        confirmState.cancelText = cancelText;
        confirmState.variant = variant;
        confirmState.show = true;
    });
}

export function resolveConfirm(confirmed) {
    confirmState.show = false;
    resolvePromise?.(confirmed);
    resolvePromise = null;
}

/**
 * Konfirmasi hapus data.
 */
export async function confirmDelete({
    title = 'Yakin ingin menghapus?',
    text = 'Data yang dihapus tidak dapat dikembalikan.',
    confirmText = 'Ya, hapus',
} = {}) {
    return confirmDialog({
        title,
        text,
        confirmText,
        cancelText: 'Batal',
        variant: 'danger',
    });
}
