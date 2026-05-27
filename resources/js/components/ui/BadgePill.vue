<script setup lang="ts">
withDefaults(
    defineProps<{
        variante?: 'success' | 'warning' | 'danger' | 'info' | 'neutral'
        dot?: boolean
    }>(),
    {
        variante: 'neutral',
        dot: false,
    },
)
</script>

<template>
    <span :class="['badge', `badge-${variante}`]">
        <span v-if="dot" class="dot" />
        <slot />
    </span>
</template>
