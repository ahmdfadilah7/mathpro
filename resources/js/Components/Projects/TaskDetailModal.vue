<script setup>
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TaskCommentsPanel from '@/Components/Tasks/TaskCommentsPanel.vue';
import { computed } from 'vue';
import {
    CalendarIcon,
    ClockIcon,
    PencilSquareIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    show: Boolean,
    task: { type: Object, default: null },
});

const emit = defineEmits(['close', 'edit']);

const hasDescription = computed(() => {
    const d = props.task?.description;
    return d && d !== '<p><br></p>' && d.trim() !== '';
});

const close = () => emit('close');

const openEdit = () => {
    emit('edit', props.task);
    close();
};
</script>

<template>
    <Modal :show="show && !!task" max-width="2xl" @close="close">
        <div v-if="task" class="p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <p
                        v-if="task.task_number"
                        class="font-mono text-sm font-bold uppercase tracking-wide text-slate-500"
                    >
                        {{ task.task_number }}
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <Badge :color="task.status_color">{{ task.status_label }}</Badge>
                        <Badge :color="task.priority_color">{{ task.priority_label }}</Badge>
                    </div>
                    <h2 class="mt-3 text-xl font-bold text-slate-900">
                        {{ task.title }}
                    </h2>
                </div>
                <button
                    v-if="task.can_edit"
                    type="button"
                    class="btn-secondary shrink-0"
                    @click="openEdit"
                >
                    <PencilSquareIcon class="h-4 w-4" />
                    Edit
                </button>
            </div>

            <div
                v-if="hasDescription"
                class="rich-content mt-5 rounded-xl border border-slate-100 bg-slate-50/50 p-4 text-sm leading-relaxed text-slate-600"
                v-html="task.description"
            />
            <p v-else class="mt-5 text-sm italic text-slate-400">Tidak ada deskripsi.</p>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-100 bg-white p-4">
                    <dt class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        <UserIcon class="h-4 w-4" />
                        Assignee
                    </dt>
                    <dd class="mt-2">
                        <div v-if="task.assignee" class="flex items-center gap-2">
                            <Avatar
                                :name="task.assignee.name"
                                :initials="task.assignee.initials"
                                size="sm"
                            />
                            <span class="font-semibold text-slate-800">{{ task.assignee.name }}</span>
                        </div>
                        <span v-else class="text-sm text-slate-500">Belum ditugaskan</span>
                    </dd>
                </div>

                <div
                    v-if="task.creator"
                    class="rounded-xl border border-slate-100 bg-white p-4"
                >
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Dibuat oleh
                    </dt>
                    <dd class="mt-2 flex items-center gap-2">
                        <Avatar
                            :name="task.creator.name"
                            :initials="task.creator.initials"
                            size="sm"
                        />
                        <span class="font-semibold text-slate-800">{{ task.creator.name }}</span>
                    </dd>
                </div>

                <div
                    v-if="task.start_date_formatted"
                    class="rounded-xl border border-slate-100 bg-white p-4"
                >
                    <dt class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        <CalendarIcon class="h-4 w-4" />
                        Tanggal mulai
                    </dt>
                    <dd class="mt-2 text-sm font-semibold text-slate-800">
                        {{ task.start_date_formatted }}
                    </dd>
                </div>

                <div
                    v-if="task.due_date_formatted"
                    class="rounded-xl border border-slate-100 bg-white p-4"
                >
                    <dt class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        <CalendarIcon class="h-4 w-4" />
                        Tanggal selesai
                    </dt>
                    <dd class="mt-2 text-sm font-semibold text-slate-800">
                        {{ task.due_date_formatted }}
                    </dd>
                </div>

                <div
                    v-if="task.estimated_hours_label"
                    class="rounded-xl border border-slate-100 bg-white p-4"
                >
                    <dt class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        <ClockIcon class="h-4 w-4" />
                        Estimasi waktu
                    </dt>
                    <dd class="mt-2 text-sm font-semibold text-slate-800">
                        {{ task.estimated_hours_label }}
                    </dd>
                </div>

                <div
                    v-if="task.actual_hours"
                    class="rounded-xl border border-slate-100 bg-white p-4"
                >
                    <dt class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        <ClockIcon class="h-4 w-4" />
                        Aktual waktu
                    </dt>
                    <dd class="mt-2 text-sm font-semibold text-slate-800">
                        {{ task.actual_hours }} jam
                    </dd>
                </div>
            </dl>

            <p class="mt-6 text-xs text-slate-400">
                Dibuat {{ task.created_at ?? '—' }}
                <span v-if="task.updated_at"> · Diperbarui {{ task.updated_at }}</span>
            </p>

            <TaskCommentsPanel
                v-if="task.project_id"
                :project-id="task.project_id"
                :task-id="task.id"
            />

            <div class="mt-6 flex justify-end border-t border-slate-100 pt-4">
                <SecondaryButton type="button" @click="close">Tutup</SecondaryButton>
                <PrimaryButton
                    v-if="task.can_edit"
                    type="button"
                    class="ml-3"
                    @click="openEdit"
                >
                    Edit task
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
