<script setup>
const model = defineModel({ type: [String, Number], default: '' });

defineProps({
    id: String,
    disabled: Boolean,
});
</script>

<template>
    <select
        :id="id"
        v-model="model"
        :disabled="disabled"
        class="form-input cursor-pointer disabled:cursor-not-allowed disabled:opacity-60"
    >
        <slot />
    </select>
</template>
