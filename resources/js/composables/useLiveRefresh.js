import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

/**
 * Polling ringan untuk refresh data Inertia (notifikasi, chat, dll.)
 * tanpa WebSocket — tab harus aktif (tidak hidden).
 */
export function useLiveRefresh(options = {}) {
    const {
        interval = 45000,
        only = ['navbar'],
        enabled = true,
    } = options;

    let timer = null;

    const tick = () => {
        if (document.hidden) {
            return;
        }

        router.reload({
            only,
            preserveScroll: true,
            preserveState: true,
        });
    };

    onMounted(() => {
        if (!enabled) {
            return;
        }

        timer = window.setInterval(tick, interval);
    });

    onUnmounted(() => {
        if (timer !== null) {
            window.clearInterval(timer);
        }
    });
}
