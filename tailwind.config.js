import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],
    safelist: [
        // Kartu task (priority) — dipanggil dinamis dari utils/taskPriority.js
        'border-l-4',
        'border-slate-200/90',
        'bg-slate-50/90',
        'bg-slate-100/90',
        'border-l-slate-400',
        'border-l-slate-500',
        'hover:border-slate-300',
        'hover:border-slate-400',
        'border-brand-200/90',
        'bg-brand-50/70',
        'bg-brand-100/80',
        'border-l-brand-500',
        'border-l-brand-600',
        'hover:border-brand-300',
        'hover:border-brand-400',
        'hover:shadow-brand-500/10',
        'border-amber-200/90',
        'bg-amber-50/80',
        'bg-amber-100/80',
        'border-l-amber-500',
        'hover:border-amber-300',
        'hover:border-amber-400',
        'hover:shadow-amber-500/10',
        'border-rose-200/90',
        'bg-rose-50/80',
        'bg-rose-100/80',
        'border-l-rose-500',
        'hover:border-rose-300',
        'hover:border-rose-400',
        'hover:shadow-rose-500/10',
        'text-slate-800',
        'text-slate-700',
        'text-brand-900',
        'text-brand-800',
        'text-amber-950',
        'text-amber-900',
        'text-amber-800',
        'text-rose-950',
        'text-rose-900',
        'text-rose-800',
        'border-slate-300/80',
        'border-brand-300/80',
        'border-amber-300/80',
        'border-rose-300/80',
        'hover:bg-slate-200/80',
        'hover:bg-brand-200/60',
        'hover:bg-amber-200/70',
        'hover:bg-rose-200/70',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#f0fdfa',
                    100: '#ccfbf1',
                    200: '#99f6e4',
                    300: '#5eead4',
                    400: '#2dd4bf',
                    500: '#14b8a6',
                    600: '#0d9488',
                    700: '#0f766e',
                    800: '#115e59',
                    900: '#134e4a',
                },
                accent: {
                    400: '#fb923c',
                    500: '#f97316',
                    600: '#ea580c',
                },
            },
            boxShadow: {
                soft: '0 1px 3px 0 rgb(15 118 110 / 0.06), 0 1px 2px -1px rgb(15 118 110 / 0.06)',
                card: '0 4px 24px -4px rgb(15 118 110 / 0.12), 0 2px 8px -2px rgb(0 0 0 / 0.04)',
                glow: '0 0 40px -8px rgb(20 184 166 / 0.45)',
            },
            backgroundImage: {
                'mesh-auth':
                    'radial-gradient(at 40% 20%, rgb(204 251 241) 0px, transparent 50%), radial-gradient(at 80% 0%, rgb(186 230 253) 0px, transparent 50%), radial-gradient(at 0% 50%, rgb(254 243 199) 0px, transparent 50%)',
                'mesh-app':
                    'radial-gradient(at 0% 0%, rgb(204 251 241 / 0.5) 0px, transparent 50%), radial-gradient(at 100% 0%, rgb(224 242 254 / 0.6) 0px, transparent 50%)',
            },
        },
    },

    plugins: [forms],
};
