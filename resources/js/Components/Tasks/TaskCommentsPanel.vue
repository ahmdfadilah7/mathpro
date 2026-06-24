<script setup>
import { ChatBubbleLeftRightIcon } from '@heroicons/vue/24/outline';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    projectId: { type: Number, required: true },
    taskId: { type: Number, default: null },
});
</script>

<template>
    <div class="mt-6 rounded-xl border border-brand-100 bg-brand-50/40 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <ChatBubbleLeftRightIcon class="h-5 w-5 text-brand-600" />
                <p class="text-sm font-semibold text-slate-800">
                    Diskusi & komentar ada di menu Chat
                </p>
            </div>
            <Link
                v-if="taskId"
                :href="
                    route('chat.index', {
                        mode: 'task',
                        task_project: projectId,
                        task: taskId,
                    })
                "
                class="btn-primary text-xs"
            >
                Buka diskusi task
            </Link>
        </div>
        <p class="mt-2 text-xs text-slate-600">
            Komentar dan lampiran untuk task ini ditampilkan di tab
            <strong>Chat → Task</strong>, dengan tampilan chat yang sama seperti pesan tim.
        </p>
    </div>
</template>
