<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    CheckCircleIcon,
    ClipboardDocumentListIcon,
    ClockIcon,
    InboxIcon,
    Squares2X2Icon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    stats: Object,
    projectsByStatus: Array,
    recentProjects: Array,
    myUpcomingTasks: Array,
    activityFeed: Array,
});

const priorityColor = {
    low: 'slate',
    medium: 'brand',
    high: 'amber',
    urgent: 'rose',
};

const totalProjectsForChart = (items) =>
    items.reduce((sum, item) => sum + item.count, 0) || 1;
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout
        title="Dashboard"
        :subtitle="`Welcome back, ${$page.props.auth.user?.name?.split(' ')[0] ?? 'User'}!`"
    >
        <!-- Stats Grid -->
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                title="Total Projects"
                :value="stats.total_projects"
                :subtitle="`${stats.active_projects} active`"
                :icon="Squares2X2Icon"
                color="brand"
            />
            <StatCard
                title="My Tasks"
                :value="stats.my_tasks"
                :subtitle="`${stats.my_pending_tasks} pending`"
                :icon="ClipboardDocumentListIcon"
                color="sky"
            />
            <StatCard
                title="Unassigned"
                :value="stats.unassigned_tasks"
                subtitle="Tasks without assignee"
                :icon="InboxIcon"
                color="amber"
            />
            <StatCard
                title="Team Members"
                :value="stats.team_members"
                :subtitle="`${stats.overdue_tasks} overdue tasks`"
                :icon="UserGroupIcon"
                color="emerald"
            />
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <!-- Recent Projects -->
            <div class="card xl:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h2 class="font-semibold text-slate-900">Recent Projects</h2>
                        <p class="text-sm text-slate-500">Latest project updates</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    <div
                        v-for="project in recentProjects"
                        :key="project.id"
                        class="flex items-center gap-4 px-6 py-4 transition hover:bg-slate-50/80"
                    >
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-xs font-bold text-white"
                            :style="{ backgroundColor: project.color }"
                        >
                            {{ project.code.split('-')[1]?.slice(0, 2) ?? 'PR' }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate font-semibold text-slate-900">
                                    {{ project.name }}
                                </p>
                                <Badge :color="project.status_color" size="sm">
                                    {{ project.status_label }}
                                </Badge>
                            </div>
                            <p class="text-xs text-slate-500">
                                {{ project.department }} · Due {{ project.due_date ?? '—' }}
                            </p>
                            <div class="mt-2 max-w-xs">
                                <ProgressBar
                                    :value="project.progress"
                                    :color="project.color"
                                />
                            </div>
                        </div>
                        <div v-if="project.manager" class="flex items-center gap-2">
                            <Avatar
                                :name="project.manager.name"
                                :initials="project.manager.initials"
                                size="sm"
                            />
                        </div>
                    </div>
                    <div
                        v-if="!recentProjects.length"
                        class="px-6 py-8 text-center text-sm text-slate-400"
                    >
                        No projects yet.
                    </div>
                </div>
            </div>

            <!-- Projects by Status -->
            <div class="card">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h2 class="font-semibold text-slate-900">Projects by Status</h2>
                    <p class="text-sm text-slate-500">Distribution overview</p>
                </div>
                <div class="space-y-4 p-6">
                    <div v-for="item in projectsByStatus" :key="item.status">
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="font-medium text-slate-700">{{ item.label }}</span>
                            <span class="font-semibold text-slate-900">{{ item.count }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full transition-all duration-700"
                                :class="{
                                    'bg-slate-400': item.color === 'slate',
                                    'bg-emerald-500': item.color === 'emerald',
                                    'bg-amber-400': item.color === 'amber',
                                    'bg-indigo-500': item.color === 'indigo',
                                    'bg-rose-400': item.color === 'rose',
                                }"
                                :style="{
                                    width: `${(item.count / totalProjectsForChart(projectsByStatus)) * 100}%`,
                                }"
                            />
                        </div>
                    </div>
                    <div
                        v-if="!projectsByStatus.length"
                        class="text-center text-sm text-slate-400"
                    >
                        No data available.
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- My Upcoming Tasks -->
            <div class="card">
                <div class="flex items-center gap-2 border-b border-slate-100 px-6 py-4">
                    <ClockIcon class="h-5 w-5 text-brand-600" />
                    <div>
                        <h2 class="font-semibold text-slate-900">My Upcoming Tasks</h2>
                        <p class="text-sm text-slate-500">Tasks assigned to you</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    <div
                        v-for="task in myUpcomingTasks"
                        :key="task.id"
                        class="flex items-start gap-3 px-6 py-3.5 transition hover:bg-slate-50/80"
                    >
                        <div
                            class="mt-0.5 h-2 w-2 shrink-0 rounded-full"
                            :class="task.is_overdue ? 'bg-rose-500' : 'bg-brand-500'"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-slate-800">
                                {{ task.title }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ task.project?.name }}
                            </p>
                            <div class="mt-1.5 flex items-center gap-2">
                                <Badge :color="priorityColor[task.priority] ?? 'slate'" size="sm">
                                    {{ task.priority_label }}
                                </Badge>
                                <span
                                    class="text-xs"
                                    :class="task.is_overdue ? 'font-medium text-rose-600' : 'text-slate-400'"
                                >
                                    {{ task.due_date ?? 'No due date' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div
                        v-if="!myUpcomingTasks.length"
                        class="flex flex-col items-center gap-2 px-6 py-8 text-slate-400"
                    >
                        <CheckCircleIcon class="h-8 w-8 text-emerald-400" />
                        <p class="text-sm">All caught up! No pending tasks.</p>
                    </div>
                </div>
            </div>

            <!-- Activity Feed -->
            <div class="card">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <div>
                        <h2 class="font-semibold text-slate-900">Recent Activity</h2>
                        <p class="text-sm text-slate-500">Aktivitas terbaru di sistem</p>
                    </div>
                    <Link
                        :href="route('activity.index')"
                        class="text-xs font-medium text-brand-600 hover:text-brand-700"
                    >
                        Lihat semua
                    </Link>
                </div>
                <div class="divide-y divide-slate-100">
                    <div
                        v-for="activity in activityFeed"
                        :key="activity.id"
                        class="flex items-center gap-3 px-6 py-3.5"
                    >
                        <Avatar
                            v-if="activity.user"
                            :name="activity.user.name"
                            :initials="activity.user.initials"
                            :src="activity.user.avatar_url"
                            size="sm"
                        />
                        <div
                            v-else
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs text-slate-500"
                        >
                            ?
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm text-slate-800">
                                <span class="font-medium">{{ activity.user?.name ?? 'Sistem' }}</span>
                                <span class="text-slate-400"> · </span>
                                {{ activity.action_label }}
                            </p>
                            <p class="truncate text-xs text-slate-500">
                                {{ activity.description }}
                            </p>
                        </div>
                        <span class="shrink-0 text-xs text-slate-400">
                            {{ activity.created_at_label }}
                        </span>
                    </div>
                    <div
                        v-if="!activityFeed?.length"
                        class="px-6 py-8 text-center text-sm text-slate-400"
                    >
                        Belum ada aktivitas tercatat.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
