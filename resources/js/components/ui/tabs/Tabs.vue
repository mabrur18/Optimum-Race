<script setup lang="ts">
import { provide, computed } from 'vue';

const props = defineProps<{
    modelValue?: string;
    defaultValue?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const activeTab = computed({
    get: () => props.modelValue ?? props.defaultValue ?? '',
    set: (v: string) => emit('update:modelValue', v),
});

provide('activeTab', activeTab);
provide('setActiveTab', (value: string) => {
    activeTab.value = value;
});
</script>

<template>
    <div class="w-full">
        <slot />
    </div>
</template>
