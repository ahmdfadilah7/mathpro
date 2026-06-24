<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import RichTextEditor from '@/Components/UI/RichTextEditor.vue';
import SearchableSelect from '@/Components/UI/SearchableSelect.vue';
import TextInput from '@/Components/TextInput.vue';
import { notifyValidationErrors } from '@/utils/notify';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    show: Boolean,
    projectId: { type: Number, required: true },
    taskFormOptions: { type: Object, required: true },
    task: { type: Object, default: null },
    editMode: { type: String, default: 'full' },
});

const emit = defineEmits(['close']);

const isEdit = computed(() => !!props.task);
const isContributorEdit = computed(
    () => isEdit.value && props.editMode === 'contributor'
);
const isFullForm = computed(() => !isContributorEdit.value);

const form = useForm({
    title: '',
    description: '',
    status: 'todo',
    priority: 'medium',
    assignee_id: null,
    due_date: '',
    start_date: '',
    estimated_hours: '',
});

const nextTaskNumberPreview = computed(
    () => props.taskFormOptions.next_task_number ?? null
);

const resetForm = () => {
    form.defaults({
        title: '',
        description: '',
        status: 'todo',
        priority: 'medium',
        assignee_id: null,
        due_date: '',
        start_date: '',
        estimated_hours: '',
    });
    form.reset();
};

watch(
    () => props.show,
    (visible) => {
        if (!visible) return;

        if (props.task) {
            form.defaults({
                title: props.task.title,
                description: props.task.description ?? '',
                status: props.task.status,
                priority: props.task.priority,
                assignee_id: props.task.assignee_id ? Number(props.task.assignee_id) : null,
                due_date: props.task.due_date ?? '',
                start_date: props.task.start_date ?? '',
                estimated_hours: props.task.estimated_hours ?? '',
            });
        } else {
            resetForm();
        }
        form.reset();
    }
);

const statusOptions = computed(() =>
    (props.taskFormOptions.statuses ?? []).map((s) => ({
        value: s.value,
        label: s.label,
    }))
);

const priorityOptions = computed(() =>
    (props.taskFormOptions.priorities ?? []).map((p) => ({
        value: p.value,
        label: p.label,
    }))
);

const assigneeOptions = computed(() => [
    ...(props.taskFormOptions.assignees ?? []).map((u) => ({
        value: Number(u.id),
        label: u.name,
    })),
]);

const hasAssigneeOptions = computed(
    () => (props.taskFormOptions.assignees ?? []).length > 0
);

const assigneePlaceholder = computed(() =>
    hasAssigneeOptions.value
        ? 'Pilih user (Contributor / Admin)'
        : 'Tambahkan anggota di tab Tim & Akses'
);

const submit = () => {
    const options = {
        preserveScroll: true,
        onError: (errors) => notifyValidationErrors(errors),
        onSuccess: () => emit('close'),
    };

    if (isEdit.value) {
        if (isContributorEdit.value) {
            form
                .transform(() => ({
                    status: form.status,
                    description: form.description,
                }))
                .put(route('projects.tasks.update', [props.projectId, props.task.id]), options);
        } else {
            form.put(route('projects.tasks.update', [props.projectId, props.task.id]), options);
        }
    } else {
        form.post(route('projects.tasks.store', props.projectId), options);
    }
};

const close = () => {
    form.clearErrors();
    emit('close');
};
</script>

<template>
    <Modal :show="show" max-width="lg" @close="close">
        <form class="overflow-visible p-6" @submit.prevent="submit">
            <h2 class="text-lg font-bold text-slate-900">
                {{
                    isContributorEdit
                        ? 'Perbarui task Anda'
                        : isEdit
                          ? 'Edit task'
                          : 'Tambah task'
                }}
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                <template v-if="isContributorEdit">
                    Anda hanya dapat mengubah status dan deskripsi task yang ditugaskan kepada Anda.
                </template>
                <template v-else-if="isEdit">
                    Perbarui detail task project.
                </template>
                <template v-else>
                    Kode task digenerate otomatis saat disimpan
                    <span v-if="nextTaskNumberPreview" class="font-mono font-semibold text-brand-700">
                        ({{ nextTaskNumberPreview }})
                    </span>.
                </template>
            </p>

            <div v-if="isContributorEdit && task" class="mt-4 rounded-xl bg-slate-50 px-4 py-3">
                <p
                    v-if="task.task_number"
                    class="font-mono text-xs font-bold uppercase tracking-wide text-slate-500"
                >
                    {{ task.task_number }}
                </p>
                <p class="text-sm font-semibold text-slate-800">{{ task.title }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    {{ task.priority_label }} · {{ task.status_label }}
                </p>
            </div>

            <div class="mt-6 space-y-4 overflow-visible">
                <template v-if="isFullForm">
                    <div v-if="isEdit && task?.task_number">
                        <InputLabel value="Kode task" />
                        <TextInput
                            :model-value="task.task_number"
                            class="mt-1 font-mono uppercase"
                            disabled
                        />
                    </div>

                    <div>
                        <InputLabel for="task_title" value="Judul task" />
                        <TextInput
                            id="task_title"
                            v-model="form.title"
                            class="mt-1 block w-full"
                            placeholder="Contoh: Implementasi modul login"
                        />
                        <InputError class="mt-1" :message="form.errors.title" />
                    </div>
                </template>

                <div>
                    <InputLabel value="Deskripsi" />
                    <div class="mt-1">
                        <RichTextEditor
                            v-model="form.description"
                            placeholder="Tulis deskripsi task..."
                            min-height="120px"
                        />
                    </div>
                    <InputError class="mt-1" :message="form.errors.description" />
                </div>

                <div
                    class="overflow-visible"
                    :class="isFullForm ? 'grid gap-4 sm:grid-cols-2' : ''"
                >
                    <div class="overflow-visible">
                        <InputLabel value="Status" />
                        <SearchableSelect
                            v-model="form.status"
                            :options="statusOptions"
                            placeholder="Pilih status"
                            :searchable="false"
                            class="mt-1"
                        />
                        <InputError class="mt-1" :message="form.errors.status" />
                    </div>
                    <div v-if="isFullForm" class="overflow-visible">
                        <InputLabel value="Prioritas" />
                        <SearchableSelect
                            v-model="form.priority"
                            :options="priorityOptions"
                            placeholder="Pilih prioritas"
                            :searchable="false"
                            class="mt-1"
                        />
                        <InputError class="mt-1" :message="form.errors.priority" />
                    </div>
                </div>

                <template v-if="isFullForm">
                    <div class="overflow-visible">
                        <InputLabel value="Assignee" />
                        <SearchableSelect
                            v-model="form.assignee_id"
                            :options="assigneeOptions"
                            :disabled="!hasAssigneeOptions"
                            :can-clear="true"
                            :placeholder="assigneePlaceholder"
                            class="mt-1"
                        />
                        <p
                            v-if="!hasAssigneeOptions"
                            class="mt-1.5 text-xs text-amber-700"
                        >
                            Belum ada user dengan akses Contributor atau Admin di project ini.
                            Project Manager dapat menambahkannya di tab Tim &amp; Akses.
                        </p>
                        <p v-else class="mt-1.5 text-xs text-slate-500">
                            Hanya user dengan akses Contributor atau Admin (termasuk PM).
                        </p>
                        <InputError class="mt-1" :message="form.errors.assignee_id" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="task_start" value="Tanggal mulai" />
                            <TextInput
                                id="task_start"
                                v-model="form.start_date"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError class="mt-1" :message="form.errors.start_date" />
                        </div>
                        <div>
                            <InputLabel for="task_due" value="Tanggal selesai" />
                            <TextInput
                                id="task_due"
                                v-model="form.due_date"
                                type="date"
                                class="mt-1 block w-full"
                            />
                            <InputError class="mt-1" :message="form.errors.due_date" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="task_hours" value="Estimasi jam" />
                        <TextInput
                            id="task_hours"
                            v-model="form.estimated_hours"
                            type="number"
                            min="0"
                            step="0.5"
                            class="mt-1 block w-full"
                            placeholder="Opsional"
                        />
                        <InputError class="mt-1" :message="form.errors.estimated_hours" />
                    </div>
                </template>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-6">
                <SecondaryButton type="button" @click="close">Batal</SecondaryButton>
                <PrimaryButton :disabled="form.processing">
                    {{ isEdit ? 'Simpan perubahan' : 'Tambah task' }}
                </PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
