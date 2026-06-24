<script setup>
defineProps({
    title: String,
    description: String,
    danger: { type: Boolean, default: false },
});
</script>

<template>
    <section
        class="card overflow-hidden"
        :class="danger ? 'border-rose-200/80' : ''"
    >
        <header
            class="border-b px-6 py-5"
            :class="danger ? 'border-rose-100 bg-rose-50/50' : 'border-slate-100 bg-slate-50/30'"
        >
            <h2
                class="text-lg font-bold"
                :class="danger ? 'text-rose-900' : 'text-slate-900'"
            >
                {{ title }}
            </h2>
            <p
                class="mt-1 text-sm"
                :class="danger ? 'text-rose-600/90' : 'text-slate-500'"
            >
                {{ description }}
            </p>
        </header>
        <div class="p-6">
            <slot />
        </div>
    </section>
</template>
