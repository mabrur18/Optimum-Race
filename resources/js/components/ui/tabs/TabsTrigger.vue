<script setup lang="ts">
import { computed, inject, type WritableComputedRef } from 'vue';

const props = defineProps<{
    value: string;
    disabled?: boolean;
    class?: string;
}>();

const activeTab = inject<WritableComputedRef<string>>('activeTab')!;
const setActiveTab = inject<(v: string) => void>('setActiveTab')!;

const isActive = computed(() => activeTab.value === props.value);

const triggerClass = computed(() => {
    const base = [
        'inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1.5 text-sm font-medium ring-offset-white transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
    ];
    if (isActive.value) {
        base.push('bg-white text-slate-950 shadow-sm dark:bg-slate-950 dark:text-slate-50');
    } else {
        base.push('hover:bg-white/60 dark:hover:bg-slate-900/60 text-slate-600 dark:text-slate-400');
    }
    if (props.class) {
        base.push(props.class);
    }
    return base.join(' ');
});
</script>

<template>
    <button
        :class="triggerClass"
        role="tab"
        :aria-selected="isActive"
        :disabled="disabled"
        :data-state="isActive ? 'active' : 'inactive'"
        type="button"
        @click="!disabled && setActiveTab(value)"
    >
        <slot />
    </button>
</template>
