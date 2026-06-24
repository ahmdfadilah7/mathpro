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

export const navigation = [
    {
        name: 'Dashboard',
        route: 'dashboard',
        icon: HomeIcon,
        section: 'main',
    },
    {
        name: 'Projects',
        route: 'projects.index',
        icon: Squares2X2Icon,
        section: 'main',
    },
    {
        name: 'My Tasks',
        route: 'my-tasks.index',
        icon: ClipboardDocumentListIcon,
        section: 'main',
    },
    {
        name: 'Calendar',
        route: 'calendar.index',
        icon: CalendarDaysIcon,
        section: 'main',
    },
    {
        name: 'Unassigned',
        route: 'unassigned.index',
        icon: InboxIcon,
        section: 'main',
    },
    {
        name: 'Chat',
        route: 'chat.index',
        icon: ChatBubbleLeftRightIcon,
        section: 'main',
    },
    {
        name: 'Reports',
        route: 'reports.index',
        icon: ChartBarIcon,
        section: 'main',
    },
    {
        name: 'Activity Log',
        route: 'activity.index',
        icon: ClockIcon,
        section: 'main',
    },
];

export const adminNavigation = [
    {
        name: 'Roles',
        route: 'roles.index',
        icon: ShieldCheckIcon,
        section: 'admin',
    },
    {
        name: 'Users',
        route: 'users.index',
        icon: UsersIcon,
        section: 'admin',
    },
];

export const allNavigation = [...navigation, ...adminNavigation];
