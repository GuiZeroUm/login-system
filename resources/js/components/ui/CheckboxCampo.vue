<script setup lang="ts">
const model = defineModel<boolean>({ default: false })
defineProps<{ rotulo: string }>()
</script>

<template>
    <label class="checkbox">
        <input v-model="model" type="checkbox">
        <span class="box">
            <svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="1.5,6 5,9.5 10.5,2.5" />
            </svg>
        </span>
        <span>{{ rotulo }}</span>
    </label>
</template>
