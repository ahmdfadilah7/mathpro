<script setup>
defineProps({
    type: { type: String, default: 'button' },
    disabled: { type: Boolean, default: false },
});
</script>

<template>
    <button :type="type" class="btn-secondary" :disabled="disabled">
        <slot />
    </button>
</template>
