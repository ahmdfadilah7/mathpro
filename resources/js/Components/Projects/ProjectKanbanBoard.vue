<script setup>
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import TaskDetailModal from '@/Components/Projects/TaskDetailModal.vue';
import TaskFormModal from '@/Components/Projects/TaskFormModal.vue';
import { confirmDelete } from '@/utils/confirm';
import { getTaskPriorityCardClass, getTaskPriorityTextClass } from '@/utils/taskPriority';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    CalendarIcon,
    ClockIcon,
    TrashIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    projectId: { type: Number, required: true },
    tasks: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    taskFormOptions: { type: Object, default: () => ({}) },
});

const boardTasks = ref([]);
const draggingTaskId = ref(null);
const dragOverColumn = ref(null);
const savingTaskId = ref(null);
const suppressClickUntil = ref(0);

const showDetail = ref(false);
const showEdit = ref(false);
const selectedTask = ref(null);
const editMode = ref('full');

watch(
    () => props.tasks,
    (tasks) => {
        boardTasks.value = tasks.map((t) => ({ ...t }));
        if (selectedTask.value) {
            const updated = boardTasks.value.find((t) => t.id === selectedTask.value.id);
            if (updated) {
                selectedTask.value = updated;
            }
        }
    },
    { immediate: true, deep: true }
);

const columns = computed(() =>
    (props.statuses ?? []).map((col) => ({
        ...col,
        tasks: boardTasks.value.filter((t) => t.status === col.value),
    }))
);

const canDragAny = computed(() => boardTasks.value.some((t) => t.can_edit));

/** Kolom status — satu palet netral agar board terlihat rapi & profesional */
const columnClass =
    'border border-slate-200/90 bg-slate-50/70 shadow-sm';
const columnDragOverClass =
    'border-slate-300 bg-white ring-2 ring-slate-200/70';

const onDragStart = (event, task) => {
    if (!task.can_edit || savingTaskId.value) {
        event.preventDefault();
        return;
    }
    draggingTaskId.value = task.id;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(task.id));
};

const onDragEnd = () => {
    draggingTaskId.value = null;
    dragOverColumn.value = null;
    suppressClickUntil.value = Date.now() + 250;
};

const onDragOver = (event, statusValue) => {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';
    dragOverColumn.value = statusValue;
};

const onDragLeave = (statusValue) => {
    if (dragOverColumn.value === statusValue) {
        dragOverColumn.value = null;
    }
};

const openDetail = (task) => {
    if (Date.now() < suppressClickUntil.value) {
        return;
    }
    selectedTask.value = task;
    showDetail.value = true;
};

const closeDetail = () => {
    showDetail.value = false;
};

const openEditFromDetail = (task) => {
    selectedTask.value = task;
    editMode.value = task.edit_mode ?? 'full';
    showEdit.value = true;
};

const closeEdit = () => {
    showEdit.value = false;
};

const handleDelete = async (task, event) => {
    event?.stopPropagation();

    const confirmed = await confirmDelete({
        title: 'Hapus task?',
        text: `Task "${task.title}" akan dihapus permanen.`,
        confirmText: 'Ya, hapus task',
    });

    if (!confirmed) {
        return;
    }

    if (selectedTask.value?.id === task.id) {
        closeDetail();
    }
    if (showEdit.value && selectedTask.value?.id === task.id) {
        closeEdit();
    }

    router.delete(route('projects.tasks.destroy', [props.projectId, task.id]), {
        preserveScroll: true,
        preserveState: true,
        only: ['tasks', 'taskStats'],
    });
};

const moveTask = (taskId, newStatus) => {
    const task = boardTasks.value.find((t) => t.id === taskId);
    if (!task || task.status === newStatus || !task.can_edit) {
        return;
    }

    const previousStatus = task.status;
    task.status = newStatus;
    task.status_label = props.statuses.find((s) => s.value === newStatus)?.label ?? task.status_label;
    task.status_color = props.statuses.find((s) => s.value === newStatus)?.color ?? task.status_color;
    savingTaskId.value = taskId;

    router.patch(
        route('projects.tasks.update-status', [props.projectId, taskId]),
        { status: newStatus },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['tasks', 'taskStats'],
            onError: () => {
                task.status = previousStatus;
                const prevCol = props.statuses.find((s) => s.value === previousStatus);
                if (prevCol) {
                    task.status_label = prevCol.label;
                    task.status_color = prevCol.color;
                }
            },
            onFinish: () => {
                savingTaskId.value = null;
            },
        }
    );
};

const onDrop = (event, statusValue) => {
    event.preventDefault();
    dragOverColumn.value = null;
    const taskId = draggingTaskId.value ?? Number(event.dataTransfer.getData('text/plain'));
    draggingTaskId.value = null;
    suppressClickUntil.value = Date.now() + 250;
    if (taskId) {
        moveTask(taskId, statusValue);
    }
};
</script>

<template>
    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
            <p class="text-sm text-slate-500">
                <template v-if="canDragAny">
                    Klik kartu untuk detail · geser untuk ubah status · PM dapat hapus task
                </template>
                <template v-else>
                    Klik kartu untuk detail task
                </template>
            </p>
            <p class="text-xs font-semibold text-slate-400">
                {{ boardTasks.length }} task
            </p>
        </div>

        <div class="overflow-x-auto p-4">
            <div class="flex min-w-max gap-4">
                <div
                    v-for="column in columns"
                    :key="column.value"
                    class="flex w-72 shrink-0 flex-col rounded-xl transition"
                    :class="[
                        columnClass,
                        dragOverColumn === column.value ? columnDragOverClass : '',
                    ]"
                    @dragover="onDragOver($event, column.value)"
                    @dragleave="onDragLeave(column.value)"
                    @drop="onDrop($event, column.value)"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-200/80 px-3.5 py-3"
                    >
                        <h3 class="text-sm font-semibold tracking-tight text-slate-800">
                            {{ column.label }}
                        </h3>
                        <span
                            class="rounded-full border border-slate-200/90 bg-white px-2.5 py-0.5 text-xs font-semibold tabular-nums text-slate-700"
                        >
                            {{ column.tasks.length }}
                        </span>
                    </div>

                    <div class="flex min-h-[200px] flex-1 flex-col gap-2 p-2">
                        <div
                            v-for="task in column.tasks"
                            :key="task.id"
                            role="button"
                            tabindex="0"
                            class="rounded-xl p-3 text-left shadow-sm transition"
                            :class="[
                                getTaskPriorityCardClass(task, {
                                    draggable: task.can_edit && !savingTaskId,
                                }),
                                task.can_edit && !savingTaskId
                                    ? 'cursor-grab active:cursor-grabbing'
                                    : 'cursor-pointer',
                                draggingTaskId === task.id ? 'opacity-40' : '',
                                savingTaskId === task.id ? 'animate-pulse opacity-70' : '',
                            ]"
                            :draggable="task.can_edit && !savingTaskId"
                            @click="openDetail(task)"
                            @keydown.enter="openDetail(task)"
                            @dragstart="onDragStart($event, task)"
                            @dragend="onDragEnd"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p
                                        v-if="task.task_number"
                                        class="font-mono text-[10px] font-bold uppercase tracking-wide opacity-90"
                                        :class="getTaskPriorityTextClass(task).meta"
                                    >
                                        {{ task.task_number }}
                                    </p>
                                    <p
                                        class="text-sm font-semibold leading-snug"
                                        :class="getTaskPriorityTextClass(task).title"
                                    >
                                        {{ task.title }}
                                    </p>
                                </div>
                                <button
                                    v-if="task.can_delete"
                                    type="button"
                                    class="shrink-0 rounded-lg p-1 transition"
                                    :class="getTaskPriorityTextClass(task).delete"
                                    title="Hapus task"
                                    @click="handleDelete(task, $event)"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>

                            <p
                                v-if="task.description_excerpt"
                                class="mt-2 line-clamp-2 text-xs font-medium leading-relaxed"
                                :class="getTaskPriorityTextClass(task).body"
                            >
                                {{ task.description_excerpt }}
                            </p>

                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <Badge :color="task.priority_color" class="!text-[10px]">
                                    {{ task.priority_label }}
                                </Badge>
                            </div>

                            <div
                                v-if="
                                    task.start_date_formatted ||
                                    task.due_date_formatted ||
                                    task.estimated_hours_label
                                "
                                class="mt-2 space-y-1 border-t pt-2"
                                :class="getTaskPriorityTextClass(task).border"
                            >
                                <p
                                    v-if="task.start_date_formatted"
                                    class="flex items-center gap-1 text-[11px] font-medium"
                                    :class="getTaskPriorityTextClass(task).meta"
                                >
                                    <CalendarIcon
                                        class="h-3 w-3 shrink-0"
                                        :class="getTaskPriorityTextClass(task).icon"
                                    />
                                    Mulai: {{ task.start_date_formatted }}
                                </p>
                                <p
                                    v-if="task.due_date_formatted"
                                    class="flex items-center gap-1 text-[11px] font-medium"
                                    :class="getTaskPriorityTextClass(task).meta"
                                >
                                    <CalendarIcon
                                        class="h-3 w-3 shrink-0"
                                        :class="getTaskPriorityTextClass(task).icon"
                                    />
                                    Selesai: {{ task.due_date_formatted }}
                                </p>
                                <p
                                    v-if="task.estimated_hours_label"
                                    class="flex items-center gap-1 text-[11px] font-medium"
                                    :class="getTaskPriorityTextClass(task).meta"
                                >
                                    <ClockIcon
                                        class="h-3 w-3 shrink-0"
                                        :class="getTaskPriorityTextClass(task).icon"
                                    />
                                    {{ task.estimated_hours_label }}
                                </p>
                            </div>

                            <div
                                class="mt-2 flex items-center justify-between gap-2"
                                :class="[
                                    task.start_date_formatted ||
                                    task.due_date_formatted ||
                                    task.estimated_hours_label
                                        ? ''
                                        : 'border-t pt-2',
                                    task.start_date_formatted ||
                                    task.due_date_formatted ||
                                    task.estimated_hours_label
                                        ? ''
                                        : getTaskPriorityTextClass(task).border,
                                ]"
                            >
                                <div v-if="task.assignee" class="flex min-w-0 items-center gap-1.5">
                                    <Avatar
                                        :name="task.assignee.name"
                                        :initials="task.assignee.initials"
                                        size="sm"
                                    />
                                    <span
                                        class="truncate text-xs font-medium"
                                        :class="getTaskPriorityTextClass(task).meta"
                                    >
                                        {{ task.assignee.name }}
                                    </span>
                                </div>
                                <span
                                    v-else
                                    class="flex items-center gap-1 text-xs font-medium"
                                    :class="getTaskPriorityTextClass(task).faint"
                                >
                                    <UserIcon class="h-3.5 w-3.5" />
                                    Unassigned
                                </span>
                            </div>
                        </div>

                        <p
                            v-if="!column.tasks.length"
                            class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-slate-200 py-8 text-center text-xs font-medium text-slate-500"
                        >
                            {{
                                canDragAny && dragOverColumn === column.value
                                    ? 'Lepaskan di sini'
                                    : 'Kosong'
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <TaskDetailModal
            :show="showDetail"
            :task="selectedTask"
            @close="closeDetail"
            @edit="openEditFromDetail"
        />

        <TaskFormModal
            :show="showEdit"
            :project-id="projectId"
            :task-form-options="taskFormOptions"
            :task="selectedTask"
            :edit-mode="editMode"
            @close="closeEdit"
        />
    </div>
</template>
