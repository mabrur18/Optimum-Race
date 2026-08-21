<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { BookOpen, FolderGit2, LayoutGrid, Calendar, Layers, Users, ClipboardList, Mail, FileSpreadsheet, Settings as SettingsIcon } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();

const userRole = computed(() => {
    const role = (page.props.auth as any)?.user?.role;
    return typeof role === 'string' ? role : (role?.value ?? '');
});

const rolePrefix = computed(() => {
    const r = userRole.value;
    if (r === 'superadmin') return '/superadmin';
    if (r === 'staff') return '/staff';
    if (r === 'rider') return '/rider';
    return '/admin';
});

const modulePrefix = computed(() => {
    const r = userRole.value;
    if (r === 'staff') return '/staff';
    return '/admin';
});

const mainNavItems = computed<NavItem[]>(() => {
    const base: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    const role = userRole.value;

    if (role === 'admin' || role === 'staff' || role === 'superadmin') {
        const prefix = modulePrefix.value;
        base.push(
            {
                title: 'Events',
                href: `${prefix}/events`,
                icon: Calendar,
            },
            {
                title: 'Kelas Race',
                href: `${prefix}/race-classes`,
                icon: Layers,
            },
            {
                title: 'Pendaftaran',
                href: `${prefix}/registrations`,
                icon: ClipboardList,
            },
            {
                title: 'Entry List',
                href: `${prefix}/entry-list`,
                icon: FileSpreadsheet,
            }
        );
        // Khusus admin / superadmin: tampilkan menu SMTP Settings
        if (role === 'admin' || role === 'superadmin') {
            base.push(
                {
                    title: 'Pengaturan SMTP',
                    href: `${prefix}/settings/smtp`,
                    icon: Mail,
                }
            );
        }
    }

    if (role === 'rider') {
        base.push(
            {
                title: 'Registrasi Saya',
                href: '/rider/registrations',
                icon: ClipboardList,
            }
        );
    }

    return base;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Panduan ORS',
        href: 'https://optimumrace.com/docs',
        icon: BookOpen,
    },
    {
        title: 'Repo Source',
        href: 'https://github.com/optimumrace',
        icon: FolderGit2,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
