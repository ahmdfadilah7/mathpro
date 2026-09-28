import { ref } from 'vue';

/**
 * Shared state for the mobile/tablet sidebar drawer.
 * On desktop (lg+) the sidebar is always visible, so this only affects < lg.
 */
const isOpen = ref(false);

export function useSidebar() {
    const open = () => {
        isOpen.value = true;
    };

    const close = () => {
        isOpen.value = false;
    };

    const toggle = () => {
        isOpen.value = !isOpen.value;
    };

    return { isOpen, open, close, toggle };
}
