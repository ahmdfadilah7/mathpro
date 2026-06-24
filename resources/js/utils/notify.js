import { toast } from 'vue-sonner';

const toastOptions = {
    classNames: {
        toast: '!rounded-xl !border !border-slate-200/80 !bg-white !shadow-card !font-sans',
        title: '!text-sm !font-semibold !text-slate-900',
        description: '!text-sm !text-slate-600',
        success: '!border-emerald-200/80',
        error: '!border-rose-200/80',
        warning: '!border-amber-200/80',
        actionButton:
            '!rounded-lg !bg-brand-600 !text-white !font-semibold hover:!bg-brand-700',
        cancelButton: '!rounded-lg !border-slate-200 !text-slate-600',
    },
};

/**
 * Notifikasi sukses (toast).
 */
export function notifySuccess(message, title = 'Berhasil!') {
    return toast.success(title, {
        ...toastOptions,
        description: message,
        duration: 4000,
    });
}

/**
 * Notifikasi error (toast).
 */
export function notifyError(message, title = 'Gagal') {
    return toast.error(title, {
        ...toastOptions,
        description: message,
        duration: 5000,
    });
}

/**
 * Notifikasi peringatan validasi form (toast).
 */
export function notifyValidationErrors(errors, title = 'Validasi gagal') {
    const messages = Object.values(errors ?? {}).flat().filter(Boolean);

    if (!messages.length) {
        return notifyError('Periksa kembali data yang Anda isi.', title);
    }

    toast.warning(title, {
        ...toastOptions,
        description: messages[0],
        duration: 5000,
    });

    messages.slice(1).forEach((message) => {
        toast.warning(message, { ...toastOptions, duration: 4000 });
    });
}
