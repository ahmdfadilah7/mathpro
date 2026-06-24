<script setup>
defineProps({
    label: { type: String, required: true },
    sublabel: { type: String, default: '' },
    color: { type: String, default: '#14b8a6' },
    bar: { type: Object, required: true },
    height: { type: String, default: 'h-7' },
    compact: { type: Boolean, default: false },
    clickable: { type: Boolean, default: false },
});

const emit = defineEmits(['click']);
</script>

<template>
    <button
        v-if="clickable"
        type="button"
        class="absolute top-1/2 z-10 -translate-y-1/2 overflow-hidden rounded-lg shadow-sm transition hover:brightness-95 hover:ring-2 hover:ring-white/80"
        :class="height"
        :style="{
            left: `${bar.left}%`,
            width: `${bar.width}%`,
            backgroundColor: color,
            minWidth: compact ? '4px' : '8px',
        }"
        :title="`${label}${sublabel ? ` · ${sublabel}` : ''}`"
        @click="emit('click')"
    >
        <span
            v-if="!compact && bar.width > 8"
            class="block truncate px-2 text-[10px] font-semibold text-white"
        >
            {{ label }}
        </span>
    </button>
    <div
        v-else
        class="absolute top-1/2 -translate-y-1/2 overflow-hidden rounded-lg shadow-sm transition hover:brightness-95"
        :class="height"
        :style="{
            left: `${bar.left}%`,
            width: `${bar.width}%`,
            backgroundColor: color,
            minWidth: compact ? '4px' : '8px',
        }"
        :title="`${label}${sublabel ? ` · ${sublabel}` : ''}`"
    >
        <span
            v-if="!compact && bar.width > 8"
            class="block truncate px-2 text-[10px] font-semibold text-white"
        >
            {{ label }}
        </span>
    </div>
</template>
