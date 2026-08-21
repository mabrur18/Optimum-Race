<script setup lang="ts">
import { computed, inject, type WritableComputedRef } from 'vue';

const props = defineProps<{
    value: string;
    class?: string;
}>();

const activeTab = inject<WritableComputedRef<string>>('activeTab')!;

const isActive = computed(() => activeTab.value === props.value);

const contentClass = computed(() => [
    'mt-4 ring-offset-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2',
    props.class ?? '',
]);
</script>

<template>
    <div
        v-if="isActive"
        role="tabpanel"
        :data-state="isActive ? 'active' : 'inactive'"
        :aria-hidden="!isActive"
        :class="contentClass"
    >
        <slot />
    </div>
</template>
