<script setup lang="ts">
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
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Users, Settings } from 'lucide-vue-next';

const mainNavItems: NavItem[] = [
    {
        title: 'Grup Arisan',
        href: '/arisan',
        icon: LayoutGrid,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="bg-[#1e3a5f] border-[#1e3a5f]">
        <SidebarHeader class="bg-[#1e3a5f]">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="hover:bg-[#2d5a87]">
                        <Link href="/arisan" class="flex items-center gap-3">
                            <div class="bg-white rounded-lg p-1.5">
                                <Users class="w-5 h-5 text-[#1e3a5f]" />
                            </div>
                            <span class="text-white font-bold text-lg">Arisan</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="bg-[#1e3a5f]">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter class="bg-[#1e3a5f]">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
