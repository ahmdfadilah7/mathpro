<script setup>
defineProps({
    value: String,
});
</script>

<template>
    <label v-bind="$attrs" class="form-label">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
