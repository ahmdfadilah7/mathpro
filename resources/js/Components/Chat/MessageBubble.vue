<script setup>
import Avatar from '@/Components/UI/Avatar.vue';
import { ArrowDownTrayIcon, TrashIcon } from '@heroicons/vue/24/outline';

defineProps({
    message: { type: Object, required: true },
    deleting: { type: Boolean, default: false },
});

const emit = defineEmits(['delete']);
</script>

<template>
    <div
        class="flex gap-2"
        :class="message.is_mine ? 'flex-row-reverse' : ''"
    >
        <Avatar
            v-if="!message.is_mine && message.user"
            :name="message.user.name"
            :src="message.user.avatar_url"
            :initials="message.user.initials"
            size="sm"
            class="mt-1 shrink-0"
        />
        <div
            class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm shadow-sm"
            :class="
                message.is_mine
                    ? 'rounded-br-md bg-brand-600 text-white'
                    : 'rounded-bl-md border border-slate-100 bg-white text-slate-800'
            "
        >
            <p
                v-if="!message.is_mine && message.user"
                class="mb-1 text-[10px] font-bold uppercase tracking-wide opacity-70"
            >
                {{ message.user.name }}
            </p>
            <p
                v-if="message.body"
                class="whitespace-pre-wrap break-words"
            >
                {{ message.body }}
            </p>
            <ul
                v-if="message.attachments?.length"
                class="mt-2 space-y-2"
            >
                <li
                    v-for="file in message.attachments"
                    :key="file.id"
                >
                    <a
                        v-if="file.is_image"
                        :href="file.url || file.download_url"
                        class="block overflow-hidden rounded-lg"
                        target="_blank"
                        rel="noopener"
                    >
                        <img
                            :src="file.url || file.download_url"
                            :alt="file.original_name"
                            class="max-h-48 max-w-full rounded-lg object-cover"
                            loading="lazy"
                        />
                    </a>
                    <a
                        :href="file.url || file.download_url"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-xs font-semibold transition"
                        :class="
                            message.is_mine
                                ? 'bg-white/15 hover:bg-white/25'
                                : 'bg-slate-100 text-brand-700 hover:bg-slate-200'
                        "
                        target="_blank"
                        rel="noopener"
                    >
                        <ArrowDownTrayIcon class="h-3.5 w-3.5" />
                        {{ file.original_name }}
                        <span class="opacity-70">({{ file.size_label }})</span>
                    </a>
                </li>
            </ul>
            <div
                class="mt-1.5 flex items-center gap-2"
                :class="message.is_mine ? 'justify-end' : ''"
            >
                <p class="text-[10px] opacity-60">
                    {{ message.created_at_label }}
                </p>
                <button
                    v-if="message.can_delete"
                    type="button"
                    class="inline-flex items-center gap-0.5 rounded px-1 py-0.5 text-[10px] font-semibold opacity-70 transition hover:opacity-100 disabled:opacity-40"
                    :class="
                        message.is_mine
                            ? 'hover:bg-white/15'
                            : 'text-rose-600 hover:bg-rose-50'
                    "
                    :disabled="deleting"
                    title="Hapus pesan"
                    @click="emit('delete')"
                >
                    <TrashIcon class="h-3 w-3" />
                    Hapus
                </button>
            </div>
        </div>
    </div>
</template>
