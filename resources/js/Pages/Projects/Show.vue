<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import Breadcrumbs from '@/Components/UI/Breadcrumbs.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import ProjectKanbanBoard from '@/Components/Projects/ProjectKanbanBoard.vue';
import ProjectMembersPanel from '@/Components/Projects/ProjectMembersPanel.vue';
import ProjectTasksPanel from '@/Components/Projects/ProjectTasksPanel.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { confirmDelete as confirmDeleteDialog } from '@/utils/confirm';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import {
    CalendarIcon,
    PencilSquareIcon,
    TrashIcon,
    UserGroupIcon,
    UserIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    project: Object,
    permissions: Object,
    taskStats: Object,
    tasks: Array,
    members: Array,
    memberAccessOptions: Array,
    taskFormOptions: Object,
    availableUsers: Array,
});

const activeTab = ref('overview');

const tabs = computed(() => {
    const items = [{ id: 'overview', label: 'Ringkasan' }];

    if (props.permissions.can_view) {
        items.push({ id: 'tasks', label: 'Tasks' });
    }

    if (props.permissions.can_manage_project) {
        items.push({ id: 'team', label: 'Tim & Akses' });
    }

    return items;
});

const setTabFromHash = () => {
    const hash = window.location.hash.replace('#', '');
    if (tabs.value.some((t) => t.id === hash)) {
        activeTab.value = hash;
    }
};

onMounted(setTabFromHash);

watch(activeTab, (tab) => {
    const hash = `#${tab}`;
    if (window.location.hash !== hash) {
        history.replaceState(null, '', `${window.location.pathname}${hash}`);
    }
});

const handleDelete = async () => {
    const confirmed = await confirmDeleteDialog({
        title: 'Hapus project?',
        text: `Project "${props.project.name}" dan semua task terkait akan dihapus permanen.`,
        confirmText: 'Ya, hapus project',
    });

    if (confirmed) {
        router.delete(route('projects.destroy', props.project.id));
    }
};

const breadcrumbs = computed(() => [
    { label: 'Projects', href: route('projects.index') },
    { label: props.project.name },
]);
</script>

<template>
    <Head :title="project.name" />

    <AppLayout :title="project.name" :subtitle="project.code">
        <Breadcrumbs :items="breadcrumbs" />

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <Badge v-if="permissions.access_label" color="brand">
                    Akses Anda: {{ permissions.access_label }}
                </Badge>
                <Badge v-if="permissions.is_manager" color="indigo">Project Manager</Badge>
                <Badge v-if="permissions.is_department_peer" color="slate">Tim divisi</Badge>
            </div>
            <div v-if="permissions.can_manage_project" class="flex flex-wrap gap-3">
                <Link
                    :href="route('projects.edit', project.id)"
                    class="btn-secondary"
                >
                    <PencilSquareIcon class="h-4 w-4" />
                    Edit project
                </Link>
                <button type="button" class="btn-danger" @click="handleDelete">
                    <TrashIcon class="h-4 w-4" />
                    Hapus
                </button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6 border-b border-slate-200">
            <nav class="-mb-px flex gap-1 overflow-x-auto">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    class="whitespace-nowrap border-b-2 px-4 py-3 text-sm font-semibold transition"
                    :class="
                        activeTab === tab.id
                            ? 'border-brand-500 text-brand-600'
                            : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'
                    "
                    @click="activeTab = tab.id"
                >
                    {{ tab.label }}
                </button>
            </nav>
        </div>

        <!-- Overview: ringkasan ringkas + kanban utama -->
        <div v-show="activeTab === 'overview'" class="space-y-5">
            <div class="card overflow-hidden">
                <div class="h-1.5" :style="{ backgroundColor: project.color }" />

                <div class="p-5 lg:p-6">
                    <div
                        class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between"
                    >
                        <div class="min-w-0 flex-1 space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <Badge :color="project.status_color">
                                    {{ project.status_label }}
                                </Badge>
                                <Badge :color="project.priority_color">
                                    {{ project.priority_label }}
                                </Badge>
                                <Badge v-if="project.is_overdue" color="rose">Overdue</Badge>
                            </div>

                            <div
                                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-2 2xl:grid-cols-3"
                            >
                                <div
                                    class="rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2.5"
                                >
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                        Divisi / Dept
                                    </p>
                                    <p class="mt-0.5 text-sm font-semibold text-slate-800">
                                        {{ project.division?.name }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ project.department?.name }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2.5"
                                >
                                    <p
                                        class="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400"
                                    >
                                        <UserIcon class="h-3.5 w-3.5" />
                                        Project manager
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-slate-800">
                                        {{ project.manager?.name ?? '—' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2.5"
                                >
                                    <p
                                        class="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400"
                                    >
                                        <CalendarIcon class="h-3.5 w-3.5" />
                                        Timeline
                                    </p>
                                    <p class="mt-1 text-sm font-medium text-slate-800">
                                        {{ project.start_date_formatted ?? '—' }}
                                        <span class="text-slate-400">→</span>
                                        {{ project.due_date_formatted ?? '—' }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2.5"
                                >
                                    <p
                                        class="flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400"
                                    >
                                        <UserGroupIcon class="h-3.5 w-3.5" />
                                        Tim project
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-slate-800">
                                        {{ members.length }} anggota
                                        <span v-if="project.manager" class="font-normal text-slate-500">
                                            + PM
                                        </span>
                                    </p>
                                </div>

                                <div
                                    v-if="project.budget_formatted"
                                    class="rounded-xl border border-slate-100 bg-slate-50/60 px-3 py-2.5"
                                >
                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400">
                                        Budget
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-slate-800">
                                        {{ project.budget_formatted }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="project.description && project.description !== '<p><br></p>'"
                                class="rounded-xl border border-slate-100 bg-white p-4"
                            >
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">
                                    Deskripsi
                                </p>
                                <div
                                    class="rich-content max-h-28 overflow-y-auto text-sm leading-relaxed text-slate-600"
                                    v-html="project.description"
                                />
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-col gap-4 xl:w-56">
                            <div class="grid grid-cols-3 gap-2 xl:grid-cols-1">
                                <div class="rounded-xl bg-slate-50 px-3 py-2.5 text-center xl:text-left">
                                    <p class="text-lg font-bold text-slate-900">
                                        {{ taskStats.total }}
                                    </p>
                                    <p class="text-[10px] font-medium uppercase text-slate-500">
                                        Total task
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl bg-emerald-50 px-3 py-2.5 text-center xl:text-left"
                                >
                                    <p class="text-lg font-bold text-emerald-700">
                                        {{ taskStats.done }}
                                    </p>
                                    <p class="text-[10px] font-medium uppercase text-emerald-600">
                                        Selesai
                                    </p>
                                </div>
                                <div
                                    class="rounded-xl bg-amber-50 px-3 py-2.5 text-center xl:text-left"
                                >
                                    <p class="text-lg font-bold text-amber-700">
                                        {{ taskStats.pending }}
                                    </p>
                                    <p class="text-[10px] font-medium uppercase text-amber-600">
                                        Pending
                                    </p>
                                </div>
                            </div>

                            <div>
                                <div
                                    class="mb-1.5 flex items-center justify-between text-xs font-medium text-slate-500"
                                >
                                    <span>Progress project</span>
                                    <span class="font-bold text-slate-800">{{ project.progress }}%</span>
                                </div>
                                <ProgressBar :value="project.progress" :color="project.color" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <ProjectKanbanBoard
                :project-id="project.id"
                :tasks="tasks"
                :statuses="taskFormOptions.statuses"
                :task-form-options="taskFormOptions"
            />
        </div>

        <!-- Tasks -->
        <ProjectTasksPanel
            v-show="activeTab === 'tasks'"
            :project="project"
            :tasks="tasks"
            :task-stats="taskStats"
            :task-form-options="taskFormOptions"
            :permissions="permissions"
        />

        <!-- Team -->
        <ProjectMembersPanel
            v-show="activeTab === 'team'"
            :project="project"
            :members="members"
            :available-users="availableUsers"
            :member-access-options="memberAccessOptions"
            :permissions="permissions"
        />
    </AppLayout>
</template>
