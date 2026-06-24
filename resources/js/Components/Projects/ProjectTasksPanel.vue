<script setup>
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import TaskDetailModal from '@/Components/Projects/TaskDetailModal.vue';
import TaskFormModal from '@/Components/Projects/TaskFormModal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { confirmDelete } from '@/utils/confirm';
import { getTaskPriorityCardClass, getTaskPriorityTextClass } from '@/utils/taskPriority';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    CalendarIcon,
    ClipboardDocumentListIcon,
    ClockIcon,
    PencilSquareIcon,
    PlusIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    project: { type: Object, required: true },
    tasks: { type: Array, default: () => [] },
    taskStats: { type: Object, required: true },
    taskFormOptions: { type: Object, required: true },
    permissions: { type: Object, required: true },
});

const showDetail = ref(false);
const showEditModal = ref(false);
const selectedTask = ref(null);
const modalEditMode = ref('full');

const openDetail = (task) => {
    selectedTask.value = task;
    showDetail.value = true;
};

const closeDetail = () => {
    showDetail.value = false;
};

const openCreate = () => {
    selectedTask.value = null;
    modalEditMode.value = 'full';
    showEditModal.value = true;
};

const openEdit = (task) => {
    selectedTask.value = task;
    modalEditMode.value = task.edit_mode ?? 'full';
    showEditModal.value = true;
};

const openEditFromDetail = (task) => {
    closeDetail();
    openEdit(task);
};

const closeEditModal = () => {
    showEditModal.value = false;
    selectedTask.value = null;
    modalEditMode.value = 'full';
};

const handleDelete = async (task) => {
    const confirmed = await confirmDelete({
        title: 'Hapus task?',
        text: `Task "${task.title}" akan dihapus permanen.`,
        confirmText: 'Ya, hapus task',
    });

    if (confirmed) {
        router.delete(route('projects.tasks.destroy', [props.project.id, task.id]), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <div class="space-y-6">
        <div class="grid grid-cols-3 gap-3">
            <div class="card py-4 text-center">
                <p class="text-2xl font-bold text-slate-900">{{ taskStats.total }}</p>
                <p class="text-xs text-slate-500">Total task</p>
            </div>
            <div class="card bg-emerald-50/50 py-4 text-center">
                <p class="text-2xl font-bold text-emerald-700">{{ taskStats.done }}</p>
                <p class="text-xs text-emerald-600">Selesai</p>
            </div>
            <div class="card bg-amber-50/50 py-4 text-center">
                <p class="text-2xl font-bold text-amber-700">{{ taskStats.pending }}</p>
                <p class="text-xs text-amber-600">Pending</p>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-6 py-4">
                <ClipboardDocumentListIcon class="h-5 w-5 text-brand-600" />
                <div>
                    <h2 class="font-bold text-slate-900">Daftar task</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Klik baris task untuk melihat detail.</p>
                </div>
                <PrimaryButton
                    v-if="permissions.can_create_task"
                    class="ml-auto"
                    @click="openCreate"
                >
                    <PlusIcon class="h-4 w-4" />
                    Tambah task
                </PrimaryButton>
            </div>

            <div v-if="tasks.length" class="space-y-2 p-2">
                <div
                    v-for="task in tasks"
                    :key="task.id"
                    role="button"
                    tabindex="0"
                    class="mx-3 my-2 flex cursor-pointer flex-wrap items-center gap-4 rounded-xl px-4 py-4 shadow-sm transition"
                    :class="getTaskPriorityCardClass(task)"
                    @click="openDetail(task)"
                    @keydown.enter="openDetail(task)"
                >
                    <div class="min-w-0 flex-1">
                        <p
                            v-if="task.task_number"
                            class="font-mono text-xs font-bold uppercase tracking-wide"
                            :class="getTaskPriorityTextClass(task).meta"
                        >
                            {{ task.task_number }}
                        </p>
                        <p
                            class="font-semibold"
                            :class="getTaskPriorityTextClass(task).title"
                        >
                            {{ task.title }}
                        </p>
                        <p
                            v-if="task.description_excerpt"
                            class="mt-1 line-clamp-1 text-xs font-medium"
                            :class="getTaskPriorityTextClass(task).body"
                        >
                            {{ task.description_excerpt }}
                        </p>
                        <div class="mt-1.5 flex flex-wrap gap-2">
                            <Badge :color="task.status_color">{{ task.status_label }}</Badge>
                            <Badge :color="task.priority_color">{{ task.priority_label }}</Badge>
                        </div>
                        <div
                            v-if="
                                task.start_date_formatted ||
                                task.due_date_formatted ||
                                task.estimated_hours_label
                            "
                            class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1 text-xs font-medium"
                            :class="getTaskPriorityTextClass(task).meta"
                        >
                            <span
                                v-if="task.start_date_formatted"
                                class="inline-flex items-center gap-1"
                            >
                                <CalendarIcon
                                    class="h-3.5 w-3.5"
                                    :class="getTaskPriorityTextClass(task).icon"
                                />
                                Mulai: {{ task.start_date_formatted }}
                            </span>
                            <span
                                v-if="task.due_date_formatted"
                                class="inline-flex items-center gap-1"
                            >
                                <CalendarIcon
                                    class="h-3.5 w-3.5"
                                    :class="getTaskPriorityTextClass(task).icon"
                                />
                                Selesai: {{ task.due_date_formatted }}
                            </span>
                            <span
                                v-if="task.estimated_hours_label"
                                class="inline-flex items-center gap-1"
                            >
                                <ClockIcon
                                    class="h-3.5 w-3.5"
                                    :class="getTaskPriorityTextClass(task).icon"
                                />
                                {{ task.estimated_hours_label }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3" @click.stop>
                        <div v-if="task.assignee" class="flex items-center gap-2">
                            <Avatar
                                :name="task.assignee.name"
                                :initials="task.assignee.initials"
                                size="sm"
                            />
                            <span
                                class="hidden text-sm font-medium sm:inline"
                                :class="getTaskPriorityTextClass(task).meta"
                            >
                                {{ task.assignee.name }}
                            </span>
                        </div>
                        <span
                            v-else
                            class="text-xs font-medium"
                            :class="getTaskPriorityTextClass(task).faint"
                        >
                            Unassigned
                        </span>

                        <div
                            v-if="task.can_edit || task.can_delete"
                            class="flex items-center gap-1 border-l pl-3"
                            :class="getTaskPriorityTextClass(task).border"
                        >
                            <button
                                v-if="task.can_edit"
                                type="button"
                                class="rounded-lg p-2 transition"
                                :class="getTaskPriorityTextClass(task).action"
                                :title="
                                    task.edit_mode === 'contributor'
                                        ? 'Perbarui status & deskripsi'
                                        : 'Edit task'
                                "
                                @click="openEdit(task)"
                            >
                                <PencilSquareIcon class="h-4 w-4" />
                            </button>
                            <button
                                v-if="task.can_delete"
                                type="button"
                                class="rounded-lg p-2 transition"
                                :class="getTaskPriorityTextClass(task).delete"
                                title="Hapus"
                                @click="handleDelete(task)"
                            >
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <p v-else class="px-6 py-12 text-center text-sm text-slate-400">
                Belum ada task.
                <span v-if="permissions.can_create_task">
                    Klik "Tambah task" untuk memulai.
                </span>
            </p>
        </div>

        <TaskDetailModal
            :show="showDetail"
            :task="selectedTask"
            @close="closeDetail"
            @edit="openEditFromDetail"
        />

        <TaskFormModal
            :show="showEditModal"
            :project-id="project.id"
            :task-form-options="taskFormOptions"
            :task="selectedTask"
            :edit-mode="modalEditMode"
            @close="closeEditModal"
        />
    </div>
</template>
