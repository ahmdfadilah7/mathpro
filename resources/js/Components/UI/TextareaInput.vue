<script setup>
const model = defineModel({ type: String, required: true });

defineProps({
    id: String,
    rows: { type: Number, default: 4 },
    placeholder: String,
});
</script>

<template>
    <textarea
        :id="id"
        v-model="model"
        :rows="rows"
        :placeholder="placeholder"
        class="form-input resize-y"
    />
</template>
