import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';

export const successRedirectState = reactive({
    show: false,
    title: 'Berhasil!',
    message: '',
    redirect: null,
});

let resolvePromise = null;

/**
 * Dialog sukses — tampil dulu, navigasi setelah klik Lanjutkan.
 */
export function notifySuccessThenRedirect(
    message,
    redirect,
    title = 'Berhasil!'
) {
    return new Promise((resolve) => {
        resolvePromise = resolve;
        successRedirectState.title = title;
        successRedirectState.message = message;
        successRedirectState.redirect = redirect;
        successRedirectState.show = true;
    });
}

export async function completeSuccessRedirect() {
    const redirect = successRedirectState.redirect;
    successRedirectState.show = false;
    resolvePromise?.();
    resolvePromise = null;

    if (redirect) {
        const current = window.location.pathname + window.location.search + window.location.hash;
        const target = new URL(redirect, window.location.origin);
        const targetFull = target.pathname + target.search + target.hash;

        if (current !== targetFull) {
            router.visit(redirect);
        } else {
            router.reload({ preserveScroll: true });
        }
    }
}

export function cancelSuccessRedirect() {
    successRedirectState.show = false;
    resolvePromise?.();
    resolvePromise = null;
}
