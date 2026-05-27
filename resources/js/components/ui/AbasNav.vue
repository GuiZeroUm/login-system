<script setup lang="ts">
defineProps<{
    abas: Array<{ id: string; label: string }>
    ativa: string
}>()

const emit = defineEmits<{
    selecionar: [id: string]
}>()
</script>

<template>
    <div class="tabs-nav">
        <button
            v-for="aba in abas"
            :key="aba.id"
            type="button"
            class="tab"
            :class="{ active: ativa === aba.id }"
            @click="emit('selecionar', aba.id)"
        >
            {{ aba.label }}
        </button>
    </div>
</template>
