<script setup>
defineProps({
    message: String,
});
</script>

<template>
    <div v-show="message">
        <p class="mt-1.5 text-sm font-medium text-rose-600">
            {{ message }}
        </p>
    </div>
</template>
