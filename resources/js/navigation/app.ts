import {
    Bell,
    FileOutput,
    FileSpreadsheet,
    FolderOpen,
    GitBranch,
    LayoutGrid,
    Settings2,
    Shield,
    ScrollText,
    UserCheck,
    Users,
} from 'lucide-vue-next';
import { dashboard } from '@/routes';
import { index as activityLogsIndex } from '@/routes/activity-logs';
import { edit as adminSettingsEdit } from '@/routes/admin-settings';
import { index as exportsIndex } from '@/routes/exports';
import { index as mediaIndex } from '@/routes/media';
import { index as notificationsIndex } from '@/routes/notifications';
import { index as rolesIndex } from '@/routes/roles';
import { index as usersIndex } from '@/routes/users';
import type { NavGroup } from '@/types';

export const appNavigation: NavGroup[] = [
    {
        title: 'Core',
        labelKey: 'nav.core',
        items: [
            {
                title: 'Dashboard',
                labelKey: 'nav.dashboard',
                href: dashboard(),
                icon: LayoutGrid,
                ability: 'viewDashboard',
            },
            {
                title: 'Hierarchy Directory',
                labelKey: 'nav.hierarchy',
                href: '/hierarchy',
                icon: GitBranch,
                ability: 'viewHierarchy',
            },
            {
                title: 'Reports',
                labelKey: 'nav.reports',
                href: '/reports',
                icon: FileSpreadsheet,
                ability: 'viewReports',
            },
            {
                title: 'My Member Profile',
                labelKey: 'nav.my_profile',
                href: '/my-profile',
                icon: UserCheck,
                ability: 'viewMyProfile',
            },
        ],
    },
    {
        title: 'Insight',
        labelKey: 'nav.insight',
        items: [
            {
                title: 'Notifications',
                labelKey: 'nav.notifications',
                href: notificationsIndex(),
                icon: Bell,
                permission: 'notifications.view',
            },
            {
                title: 'Activity logs',
                labelKey: 'nav.activity_logs',
                href: activityLogsIndex(),
                icon: ScrollText,
                permission: 'activity-logs.view',
            },
        ],
    },
    {
        title: 'Management',
        labelKey: 'nav.management',
        items: [
            {
                title: 'Export center',
                labelKey: 'nav.exports',
                href: exportsIndex(),
                icon: FileOutput,
                permission: 'exports.view',
            },
            {
                title: 'System Settings',
                labelKey: 'nav.system_settings',
                href: adminSettingsEdit(),
                icon: Settings2,
                permission: 'settings.view',
            },
            {
                title: 'Media',
                labelKey: 'nav.media',
                href: mediaIndex(),
                icon: FolderOpen,
                permission: 'media.view',
            },
            {
                title: 'Users',
                labelKey: 'nav.users',
                href: usersIndex(),
                icon: Users,
                permission: 'users.view',
            },
            {
                title: 'Roles',
                labelKey: 'nav.roles',
                href: rolesIndex(),
                icon: Shield,
                permission: 'roles.view',
            },
        ],
    },
];
