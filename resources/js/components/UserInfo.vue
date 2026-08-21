<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';

type Props = {
    user?: User | null;
    showEmail?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
    user: null,
});

const { getInitials } = useInitials();

const showAvatar = computed(
    () => !!props.user?.avatar,
);

const fallbackName = computed(() => props.user?.name ?? 'Guest User');
const fallbackEmail = computed(() => props.user?.email ?? '');
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
        <AvatarImage v-if="showAvatar" :src="user?.avatar!" :alt="fallbackName" />
        <AvatarFallback class="rounded-lg text-black dark:text-white">
            {{ getInitials(fallbackName) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ fallbackName }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">
            {{ fallbackEmail }}
        </span>
    </div>
</template>
