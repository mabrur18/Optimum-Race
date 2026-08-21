<script setup lang="ts">
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';
import type { FlashToast } from '@/types/ui';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<any>();
const lastShownToastKey = ref<string>('');

watch(
    () => (page.props as any)?.flash?.toast as FlashToast | undefined | null,
    (toastData) => {
        if (!toastData || !toastData.type || !toastData.message) return;
        const key = `${toastData.type}:${toastData.message}`;
        if (lastShownToastKey.value === key) return;
        lastShownToastKey.value = key;
        toast[toastData.type](toastData.message);
    },
    { immediate: true }
);
</script>

<template>
    <AppShell variant="header">
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent variant="header">
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
