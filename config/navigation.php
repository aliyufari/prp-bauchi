<?php

return [
    'management' => [
        ['group' => null, 'items' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'path' => '/dashboard', 'icon' => 'LayoutDashboard', 'can' => 'dashboard.view', 'exact' => true, 'featured' => true],
        ]],

        ['group' => 'People & Structure', 'items' => [
            ['label' => 'Party Agents', 'route' => 'party-agents.index', 'icon' => 'UserCheck', 'can' => 'party-agents.view'],
            ['label' => 'Coordinators', 'route' => 'coordinators.index', 'icon' => 'Briefcase', 'can' => 'coordinators.view'],
            ['label' => 'Stakeholders', 'route' => 'stakeholders.index', 'icon' => 'Handshake', 'can' => 'stakeholders.view'],
            ['label' => 'Returning Officers', 'route' => 'returning-officers.index', 'icon' => 'RotateCcw', 'can' => 'returning-officers.view'],
            ['label' => 'Supervisory Agents', 'route' => 'supervisory-agents.index', 'icon' => 'Shield', 'can' => 'supervisory-agents.view'],
            ['label' => 'Members', 'route' => 'members.index', 'icon' => 'Users', 'can' => 'members.view'],
        ]],

        ['group' => 'Election Management', 'items' => [
            ['label' => 'Aspirant (Governor)', 'route' => 'aspirant.show', 'icon' => 'Vote', 'can' => 'aspirants.view'],
            ['label' => 'All Aspirants', 'route' => 'aspirants.index', 'icon' => 'Users', 'can' => 'aspirants.view'],
            ['label' => 'Election Calendar', 'route' => 'election-calendar.index', 'path' => '/dashboard/election-calendar', 'icon' => 'Calendar', 'can' => 'dashboard.view'],
            ['label' => 'Polling Units', 'route' => 'polling-units.index', 'icon' => 'MapPin', 'can' => 'polling-units.view'],
            ['label' => 'Campaign Teams', 'route' => 'campaign-teams.index', 'icon' => 'Users2', 'can' => 'campaign-teams.view'],
            ['label' => 'PU Reports', 'route' => 'pu-reports.index', 'icon' => 'ClipboardCheck', 'can' => 'pu-tasks.view'],
            ['label' => 'Results Management', 'route' => 'results.index', 'icon' => 'BarChart3', 'can' => 'results.view'],
            ['label' => 'Election Analysis', 'route' => 'election-analysis.index', 'icon' => 'BarChart3', 'can' => 'results.analyse'],
        ]],

        ['group' => 'Campaign & Activities', 'items' => [
            ['label' => 'Campaign Activities', 'route' => 'campaign-activities.index', 'path' => '/dashboard/campaign-activities', 'icon' => 'Activity', 'can' => 'campaign.view'],
            ['label' => 'Events & Meetings', 'route' => 'events.index', 'path' => '/dashboard/events', 'icon' => 'Calendar', 'can' => 'campaign.view'],
            ['label' => 'Mobilization Activities', 'route' => 'mobilization.index', 'path' => '/dashboard/mobilization', 'icon' => 'Megaphone', 'can' => 'campaign.view'],
            ['label' => 'Media & Publicity', 'route' => 'media.index', 'path' => '/dashboard/media', 'icon' => 'Star', 'can' => 'campaign.view'],
            ['label' => 'Voter Education', 'route' => 'voter-education.index', 'path' => '/dashboard/voter-education', 'icon' => 'BookOpen', 'can' => 'campaign.view'],
            ['label' => 'Youth & Women Wing', 'route' => 'youth-women.index', 'path' => '/dashboard/youth-women', 'icon' => 'Baby', 'can' => 'campaign.view'],
            ['label' => 'Door to Door Tracking', 'route' => 'door-tracking.index', 'path' => '/dashboard/door-tracking', 'icon' => 'DoorClosed', 'can' => 'campaign.view'],
            ['label' => 'Volunteers Management', 'route' => 'volunteers.index', 'path' => '/dashboard/volunteers', 'icon' => 'UserCog', 'can' => 'campaign.view'],
        ]],

        ['group' => 'Financial Management', 'items' => [
            ['label' => 'Donations', 'route' => 'donations.index', 'path' => '/dashboard/donations', 'icon' => 'HandCoins', 'can' => 'finance.view'],
            ['label' => 'Expenditures', 'route' => 'expenditures.index', 'path' => '/dashboard/expenditures', 'icon' => 'Receipt', 'can' => 'finance.view'],
            ['label' => 'Fundraising', 'route' => 'fundraising.index', 'path' => '/dashboard/fundraising', 'icon' => 'CreditCard', 'can' => 'finance.view'],
            ['label' => 'Financial Reports', 'route' => 'financial-reports.index', 'path' => '/dashboard/financial-reports', 'icon' => 'FileText', 'can' => 'finance.view'],
        ]],

        ['group' => 'Reports & Analytics', 'items' => [
            ['label' => 'Membership Reports', 'route' => 'reports.membership', 'path' => '/dashboard/reports/membership', 'icon' => 'ClipboardList', 'can' => 'reports.view'],
            ['label' => 'Agent Reports', 'route' => 'reports.agents', 'path' => '/dashboard/reports/agents', 'icon' => 'Bot', 'can' => 'reports.view'],
            ['label' => 'Election Reports', 'route' => 'reports.election', 'path' => '/dashboard/reports/election', 'icon' => 'BarChart3', 'can' => 'reports.view'],
            ['label' => 'Activity Reports', 'route' => 'reports.activity', 'path' => '/dashboard/reports/activity', 'icon' => 'TrendingUp', 'can' => 'reports.view'],
            ['label' => 'Survey & Feedback', 'route' => 'surveys.index', 'path' => '/dashboard/surveys', 'icon' => 'MessageSquare', 'can' => 'reports.view'],
        ]],

        ['group' => 'System', 'items' => [
            ['label' => 'User Management', 'route' => 'users.index', 'path' => '/dashboard/users', 'icon' => 'Users', 'can' => 'users.manage'],
            ['label' => 'Login Activity', 'route' => 'login-activity.index', 'icon' => 'Monitor', 'can' => 'users.manage'],
            ['label' => 'Roles & Permissions', 'route' => 'roles.index', 'path' => '/dashboard/roles', 'icon' => 'Key', 'can' => 'roles.manage'],
            ['label' => 'Settings', 'route' => 'settings.index', 'path' => '/dashboard/settings', 'icon' => 'Settings', 'can' => 'settings.manage'],
            ['label' => 'Audit Logs', 'route' => 'audit-logs.index', 'path' => '/dashboard/audit-logs', 'icon' => 'ScrollText', 'can' => 'audit-logs.view'],
            ['label' => 'Backup & Restore', 'route' => 'backups.index', 'path' => '/dashboard/backups', 'icon' => 'HardDrive', 'can' => 'backups.manage'],
        ]],
    ],

    'portal' => [
        ['group' => null, 'items' => [
            ['label' => 'Dashboard', 'route' => 'portal.home', 'icon' => 'Home', 'can' => 'portal.access', 'exact' => true],
            ['label' => 'Polling Units', 'route' => 'portal.pus', 'icon' => 'MapPin', 'can' => 'pu-tasks.manage', 'level' => 'ward'],
            ['label' => 'My Agents', 'route' => 'portal.agents', 'icon' => 'Users2', 'can' => 'pu-tasks.manage', 'level' => 'ward'],
            ['label' => 'My Team', 'route' => 'portal.team', 'icon' => 'Users2', 'can' => 'campaign-teams.view'],
            ['label' => 'Profile', 'route' => 'portal.profile', 'icon' => 'User', 'can' => 'portal.access'],
        ]],
    ],
];