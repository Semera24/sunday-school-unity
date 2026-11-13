<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" :class="className" v-bind="$attrs" aria-hidden="true">
        <rect x="1" y="1" width="38" height="38" rx="9" fill="currentColor" />
        <text x="20" y="25" text-anchor="middle" font-family="sans-serif" font-size="14" font-weight="700" class="fill-background">SSU</text>
    </svg>
</template>
