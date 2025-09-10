<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard, login } from '@/routes';
import posts from '@/routes/posts';
import users from '@/routes/users';
import questions from '@/routes/questions';

import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, FileText, UsersRound, ClipboardList, HelpCircle, FolderOpen, Tags } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Tags publicaciones',
        href: '/tags',
        icon: Folder,
    },
    {
        title: 'Publicaciones',
        href: posts.admin(),
        icon: FileText,
    },
    {
        title: 'usuarios',
        href: users.index(),
        icon: UsersRound,
    },
    {
        title: 'Evaluaciones',
        href: '/evaluations',
        icon: ClipboardList,
    },
    {
        title: 'Categorías de Evaluación',
        href: '/question-categories',
        icon: Tags,
    },
    {
        title: 'Preguntas',
        href: '/admin/questions',
        icon: HelpCircle,
    },
        {
        title: 'membresias',
        href: dashboard(),
        icon: LayoutGrid,
    },
        {
        title: 'login',
        href: login(),
        icon: LayoutGrid,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Github Repo',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
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
