<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { computed } from 'vue';

interface Props {
    user: User;
    showEmail?: boolean;
    variant?: 'dark' | 'light';
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
    variant: 'dark',
});

const { getInitials } = useInitials();

// Compute whether we should show the avatar image
const showAvatar = computed(
    () => props.user.avatar && props.user.avatar !== '',
);
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
        <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
        <AvatarFallback class="rounded-lg bg-[#1e3a5f] text-white">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span 
            class="truncate font-medium"
            :class="variant === 'dark' ? 'text-white' : 'text-gray-900'"
        >{{ user.name }}</span>
        <span 
            v-if="showEmail" 
            class="truncate text-xs"
            :class="variant === 'dark' ? 'text-blue-200' : 'text-gray-500'"
        >{{ user.email }}</span>
    </div>
</template>
