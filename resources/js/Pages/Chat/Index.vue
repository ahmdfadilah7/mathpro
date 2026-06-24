<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import MessageBubble from '@/Components/Chat/MessageBubble.vue';
import MessageComposer from '@/Components/Chat/MessageComposer.vue';
import { confirmDelete } from '@/utils/confirm';
import { notifyError, notifySuccess } from '@/utils/notify';
import { useLiveRefresh } from '@/composables/useLiveRefresh';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch, nextTick } from 'vue';
import {
    ChatBubbleLeftRightIcon,
    ClipboardDocumentListIcon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    mode: { type: String, default: 'team' },
    teamProjects: Array,
    taskProjects: Array,
    taskThreads: Array,
    activePanel: Object,
    messages: Array,
    filters: Object,
});

const sending = ref(false);
const deletingMessageId = ref(null);
const messagesEnd = ref(null);
const expandedProjectId = ref(
    props.filters?.task_project_id ?? props.filters?.project_id ?? null
);

const isTaskMode = computed(() => props.mode === 'task');

useLiveRefresh({
    interval: 30000,
    only: ['messages', 'teamProjects', 'taskThreads', 'activePanel', 'navbar'],
});

const scrollToBottom = () => {
    nextTick(() => {
        messagesEnd.value?.scrollIntoView({ behavior: 'smooth' });
    });
};

watch(
    () => props.messages,
    () => scrollToBottom(),
    { immediate: true }
);

const switchMode = (mode) => {
    router.get(
        route('chat.index'),
        mode === 'task' ? { mode: 'task' } : { mode: 'team' },
        { preserveState: false }
    );
};

const openProjectChat = (projectId) => {
    router.get(
        route('chat.index'),
        { mode: 'team', project: projectId },
        { preserveState: true, replace: true }
    );
};

const openTaskChat = (projectId, taskId) => {
    router.get(
        route('chat.index'),
        { mode: 'task', task_project: projectId, task: taskId },
        { preserveState: true, replace: true }
    );
};

const sendTeamMessage = ({ body, files }) => {
    if (props.activePanel?.type !== 'team' || !props.activePanel?.id || sending.value) {
        return;
    }

    sending.value = true;
    const formData = new FormData();
    if (body) formData.append('body', body);
    files.forEach((file, i) => formData.append(`attachments[${i}]`, file));

    router.post(route('chat.messages.store', props.activePanel.id), formData, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            sending.value = false;
        },
    });
};

const sendTaskComment = async ({ body, files }) => {
    const panel = props.activePanel;
    if (panel?.type !== 'task' || !panel?.id || sending.value) {
        return;
    }

    sending.value = true;
    const formData = new FormData();
    if (body) formData.append('body', body);
    files.forEach((file, i) => formData.append(`attachments[${i}]`, file));

    try {
        await window.axios.post(
            route('projects.tasks.comments.store', [panel.project.id, panel.id]),
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );
        notifySuccess('Komentar terkirim.');
        router.get(
            route('chat.index'),
            { mode: 'task', task_project: panel.project.id, task: panel.id },
            { preserveScroll: true, preserveState: false }
        );
    } catch (error) {
        notifyError(
            error.response?.data?.errors?.body?.[0] ??
                error.response?.data?.message ??
                'Gagal mengirim komentar.'
        );
    } finally {
        sending.value = false;
    }
};

const onSubmit = (payload) => {
    if (isTaskMode.value) {
        sendTaskComment(payload);
    } else {
        sendTeamMessage(payload);
    }
};

const deleteMessage = async (msg) => {
    const panel = props.activePanel;
    if (!panel || deletingMessageId.value) {
        return;
    }

    const confirmed = await confirmDelete({
        title: panel.type === 'task' ? 'Hapus komentar?' : 'Hapus pesan?',
        text:
            panel.type === 'task'
                ? 'Komentar dan lampirannya akan dihapus permanen.'
                : 'Pesan dan lampirannya akan dihapus permanen.',
        confirmText: panel.type === 'task' ? 'Ya, hapus komentar' : 'Ya, hapus pesan',
    });

    if (!confirmed) {
        return;
    }

    deletingMessageId.value = msg.id;

    const options = {
        preserveScroll: true,
        onSuccess: () => notifySuccess(panel.type === 'task' ? 'Komentar dihapus.' : 'Pesan dihapus.'),
        onError: () => notifyError('Gagal menghapus.'),
        onFinish: () => {
            deletingMessageId.value = null;
        },
    };

    if (panel.type === 'team') {
        router.delete(route('chat.messages.destroy', [panel.id, msg.id]), options);
    } else {
        router.delete(
            route('projects.tasks.comments.destroy', [panel.project.id, panel.id, msg.id]),
            options
        );
    }
};

const toggleProject = (projectId) => {
    expandedProjectId.value =
        expandedProjectId.value === projectId ? null : projectId;
};

const headerTitle = computed(() => {
    if (!props.activePanel) return 'Chat';
    if (props.activePanel.type === 'task') {
        return props.activePanel.task_number ?? props.activePanel.title;
    }
    return props.activePanel.title ?? props.activePanel.project?.name ?? 'Chat';
});

const headerSubtitle = computed(() => {
    if (!props.activePanel) return '';
    if (props.activePanel.type === 'task') {
        return `${props.activePanel.project?.code} · ${props.activePanel.title}`;
    }
    return props.activePanel.subtitle ?? props.activePanel.project?.code ?? '';
});

const composerPlaceholder = computed(() => {
    if (props.activePanel?.type === 'task') {
        return 'Tulis komentar task…';
    }
    if (props.activePanel?.type === 'team') {
        return `Tulis pesan ke tim ${props.activePanel.project?.code ?? 'project'}…`;
    }
    return 'Tulis pesan…';
});

const isActiveTask = (task) =>
    props.activePanel?.type === 'task' && props.activePanel?.id === task.id;

const isActiveProject = (project) =>
    props.activePanel?.type === 'team' &&
    props.activePanel?.project?.id === project.id;
</script>

<template>
    <Head title="Chat" />

    <AppLayout
        title="Chat"
        subtitle="Percakapan per project dan diskusi komentar per task"
    >
        <div class="card flex h-[calc(100vh-12rem)] min-h-[520px] overflow-hidden">
            <aside
                class="flex w-full shrink-0 flex-col border-r border-slate-100 bg-slate-50/50 md:w-80 lg:w-96"
            >
                <div class="border-b border-slate-100 p-2">
                    <div class="inline-flex w-full rounded-xl bg-slate-100 p-1">
                        <button
                            type="button"
                            class="flex flex-1 items-center justify-center gap-1.5 rounded-lg py-2 text-xs font-semibold transition"
                            :class="
                                !isTaskMode
                                    ? 'bg-white text-brand-700 shadow-sm'
                                    : 'text-slate-600'
                            "
                            @click="switchMode('team')"
                        >
                            <UserGroupIcon class="h-4 w-4" />
                            Project
                        </button>
                        <button
                            type="button"
                            class="flex flex-1 items-center justify-center gap-1.5 rounded-lg py-2 text-xs font-semibold transition"
                            :class="
                                isTaskMode
                                    ? 'bg-white text-brand-700 shadow-sm'
                                    : 'text-slate-600'
                            "
                            @click="switchMode('task')"
                        >
                            <ClipboardDocumentListIcon class="h-4 w-4" />
                            Task
                        </button>
                    </div>
                </div>

                <!-- Sidebar: Percakapan project -->
                <div v-if="!isTaskMode" class="flex flex-1 flex-col overflow-hidden">
                    <div class="border-b border-slate-100 px-4 py-2">
                        <h2 class="text-sm font-bold text-slate-900">Percakapan project</h2>
                        <p class="text-[10px] text-slate-500">
                            Satu ruang chat untuk seluruh tim project
                        </p>
                    </div>
                    <div class="flex-1 overflow-y-auto p-2">
                        <button
                            v-for="project in teamProjects"
                            :key="project.id"
                            type="button"
                            class="mb-1 flex w-full items-start gap-3 rounded-xl px-3 py-3 text-left transition"
                            :class="
                                isActiveProject(project)
                                    ? 'bg-brand-100 text-brand-900'
                                    : 'hover:bg-white'
                            "
                            @click="openProjectChat(project.id)"
                        >
                            <span
                                class="mt-1 h-3 w-3 shrink-0 rounded-full"
                                :style="{ backgroundColor: project.color }"
                            />
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="truncate text-sm font-bold">
                                        {{ project.code }}
                                    </span>
                                    <span
                                        v-if="project.unread"
                                        class="h-2 w-2 shrink-0 rounded-full bg-brand-500"
                                    />
                                </div>
                                <p class="truncate text-xs text-slate-600">
                                    {{ project.name }}
                                </p>
                                <p class="text-[10px] text-slate-400">
                                    {{ project.member_count }} anggota
                                </p>
                                <p
                                    v-if="project.last_message"
                                    class="mt-1 truncate text-xs text-slate-500"
                                >
                                    {{ project.last_message.preview }}
                                </p>
                                <p
                                    v-else
                                    class="mt-1 text-xs italic text-slate-400"
                                >
                                    Belum ada pesan
                                </p>
                            </div>
                        </button>
                        <p
                            v-if="!teamProjects?.length"
                            class="px-3 py-8 text-center text-xs text-slate-400"
                        >
                            Anda belum tergabung di project manapun sebagai PM atau
                            anggota.
                        </p>
                    </div>
                </div>

                <!-- Sidebar: Task -->
                <div v-else class="flex flex-1 flex-col overflow-hidden">
                    <div class="border-b border-slate-100 px-4 py-2">
                        <h2 class="text-sm font-bold text-slate-900">Diskusi task</h2>
                        <p class="text-[10px] text-slate-500">
                            Komentar task tampil seperti chat
                        </p>
                    </div>
                    <div class="flex-1 overflow-y-auto">
                        <div v-if="taskThreads?.length" class="border-b border-slate-100 p-2">
                            <p
                                class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400"
                            >
                                Ada komentar
                            </p>
                            <button
                                v-for="task in taskThreads"
                                :key="`thread-${task.id}`"
                                type="button"
                                class="flex w-full flex-col rounded-xl px-3 py-2.5 text-left transition"
                                :class="
                                    isActiveTask(task)
                                        ? 'bg-brand-100 text-brand-900'
                                        : 'hover:bg-white'
                                "
                                @click="openTaskChat(task.project_id, task.id)"
                            >
                                <span class="font-mono text-[10px] font-bold">
                                    {{ task.task_number }}
                                </span>
                                <span class="line-clamp-1 text-sm font-semibold">
                                    {{ task.title }}
                                </span>
                                <span class="text-[10px] text-slate-500">
                                    {{ task.project?.code }} · {{ task.comments_count }}
                                    komentar
                                </span>
                                <span
                                    v-if="task.last_message"
                                    class="mt-0.5 truncate text-xs text-slate-500"
                                >
                                    {{ task.last_message.user_name }}:
                                    {{ task.last_message.preview }}
                                </span>
                            </button>
                        </div>

                        <div class="p-2">
                            <p
                                class="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400"
                            >
                                Semua task aktif
                            </p>
                            <div
                                v-for="project in taskProjects"
                                :key="project.id"
                                class="mb-1"
                            >
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm font-semibold text-slate-800 hover:bg-white"
                                    @click="toggleProject(project.id)"
                                >
                                    <span
                                        class="h-2.5 w-2.5 rounded-full"
                                        :style="{ backgroundColor: project.color }"
                                    />
                                    <span class="truncate">{{ project.code }}</span>
                                    <span class="ml-auto text-xs text-slate-400">
                                        {{ expandedProjectId === project.id ? '▼' : '▶' }}
                                    </span>
                                </button>
                                <div
                                    v-if="expandedProjectId === project.id"
                                    class="ml-2 space-y-0.5 pb-2"
                                >
                                    <button
                                        v-for="task in project.tasks"
                                        :key="task.id"
                                        type="button"
                                        class="w-full rounded-lg px-2 py-2 text-left text-sm transition hover:bg-white"
                                        :class="
                                            isActiveTask(task) ? 'bg-brand-50 ring-1 ring-brand-200' : ''
                                        "
                                        @click="openTaskChat(project.id, task.id)"
                                    >
                                        <span class="font-mono text-[10px] font-bold text-slate-500">
                                            {{ task.task_number }}
                                        </span>
                                        <p class="line-clamp-2 font-medium text-slate-800">
                                            {{ task.title }}
                                        </p>
                                        <p class="text-[10px] text-slate-500">
                                            {{ task.comments_count }} komentar
                                        </p>
                                    </button>
                                    <p
                                        v-if="!project.tasks?.length"
                                        class="px-2 py-2 text-xs text-slate-400"
                                    >
                                        Tidak ada task aktif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="flex min-w-0 flex-1 flex-col bg-white">
                <template v-if="activePanel">
                    <div
                        class="flex items-center gap-3 border-b border-slate-100 px-5 py-4"
                    >
                        <div
                            v-if="activePanel.type === 'team'"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
                            :style="{ backgroundColor: activePanel.project?.color ?? '#14b8a6' }"
                        >
                            {{ activePanel.project?.code?.charAt(0) }}
                        </div>
                        <div
                            v-else
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 text-brand-700"
                        >
                            <ClipboardDocumentListIcon class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 class="truncate font-bold text-slate-900">
                                {{ headerTitle }}
                            </h2>
                            <p class="truncate text-xs text-slate-500">
                                {{ headerSubtitle }}
                            </p>
                            <p
                                v-if="activePanel.type === 'task'"
                                class="mt-0.5 text-[10px] text-brand-600"
                            >
                                Diskusi komentar · semua anggota project
                            </p>
                            <p
                                v-else-if="activePanel.type === 'team'"
                                class="mt-0.5 text-[10px] text-brand-600"
                            >
                                Semua PM & anggota project dapat mengirim pesan di sini
                            </p>
                        </div>
                        <Link
                            v-if="activePanel.type === 'task'"
                            :href="
                                route('projects.show', activePanel.project.id) + '#tasks'
                            "
                            class="shrink-0 text-xs font-semibold text-brand-700 hover:underline"
                        >
                            Buka task
                        </Link>
                    </div>

                    <div class="flex-1 space-y-4 overflow-y-auto p-5">
                        <p
                            v-if="!messages?.length"
                            class="py-12 text-center text-sm text-slate-400"
                        >
                            {{
                                activePanel.type === 'task'
                                    ? 'Belum ada komentar. Mulai diskusi di bawah.'
                                    : 'Belum ada pesan di project ini. Kirim pesan pertama.'
                            }}
                        </p>
                        <MessageBubble
                            v-for="msg in messages"
                            :key="`${activePanel.type}-${msg.id}`"
                            :message="msg"
                            :deleting="deletingMessageId === msg.id"
                            @delete="deleteMessage(msg)"
                        />
                        <div ref="messagesEnd" />
                    </div>

                    <MessageComposer
                        :processing="sending"
                        :placeholder="composerPlaceholder"
                        @submit="onSubmit"
                    />
                </template>

                <div
                    v-else
                    class="flex flex-1 flex-col items-center justify-center gap-3 p-8 text-center text-slate-500"
                >
                    <ChatBubbleLeftRightIcon class="h-12 w-12 text-slate-300" />
                    <p class="text-sm font-medium">
                        {{
                            isTaskMode
                                ? 'Pilih task di sidebar untuk melihat diskusi komentar'
                                : 'Pilih project untuk membuka percakapan tim'
                        }}
                    </p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
