import '../css/app.css';
import './bootstrap';

import AppToaster from '@/Components/UI/AppToaster.vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import 'vue-sonner/style.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/** Setelah login/logout session & CSRF token berubah — sinkronkan meta tag. */
router.on('navigate', () => {
    const token = document.head.querySelector('meta[name="csrf-token"]');
    const cookie = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='));
    if (token && cookie) {
        token.content = decodeURIComponent(cookie.split('=').slice(1).join('='));
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({
            render: () => h('div', [h(App, props), h(AppToaster)]),
        })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#14b8a6',
    },
});
