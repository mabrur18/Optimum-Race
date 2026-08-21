<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    class?: string;
}>(), {
    class: '',
});

const containerClass = computed(() => [
    'inline-flex h-10 items-center justify-center rounded-lg bg-slate-100 p-1 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
    props.class,
]);
</script>

<template>
    <div :class="containerClass" role="tablist">
        <slot />
    </div>
</template>
