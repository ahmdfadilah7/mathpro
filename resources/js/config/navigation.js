import {
    CalendarDaysIcon,
    ChartBarIcon,
    ChatBubbleLeftRightIcon,
    ClipboardDocumentListIcon,
    ClockIcon,
    HomeIcon,
    InboxIcon,
    ShieldCheckIcon,
    Squares2X2Icon,
    UsersIcon,
} from '@heroicons/vue/24/outline';

/**
 * ability: key from auth.abilities shared via HandleInertiaRequests
 * null = always visible for authenticated users
 */
export const navigation = [
    {
        name: 'Dashboard',
        route: 'dashboard',
        icon: HomeIcon,
        section: 'main',
        ability: null,
    },
    {
        name: 'Projects',
        route: 'projects.index',
        icon: Squares2X2Icon,
        section: 'main',
        ability: 'can_view_projects',
    },
    {
        name: 'My Tasks',
        route: 'my-tasks.index',
        icon: ClipboardDocumentListIcon,
        section: 'main',
        ability: 'can_manage_tasks',
    },
    {
        name: 'Calendar',
        route: 'calendar.index',
        icon: CalendarDaysIcon,
        section: 'main',
        ability: 'can_view_projects',
    },
    {
        name: 'Unassigned',
        route: 'unassigned.index',
        icon: InboxIcon,
        section: 'main',
        ability: 'can_assign_tasks',
    },
    {
        name: 'Chat',
        route: 'chat.index',
        icon: ChatBubbleLeftRightIcon,
        section: 'main',
        ability: 'can_view_projects',
    },
    {
        name: 'Reports',
        route: 'reports.index',
        icon: ChartBarIcon,
        section: 'main',
        ability: 'can_view_reports',
    },
    {
        name: 'Activity Log',
        route: 'activity.index',
        icon: ClockIcon,
        section: 'main',
        ability: 'can_view_projects',
    },
];

export const adminNavigation = [
    {
        name: 'Roles',
        route: 'roles.index',
        icon: ShieldCheckIcon,
        section: 'admin',
        ability: 'is_super_admin',
    },
    {
        name: 'Users',
        route: 'users.index',
        icon: UsersIcon,
        section: 'admin',
        ability: 'is_super_admin',
    },
];

export const allNavigation = [...navigation, ...adminNavigation];
