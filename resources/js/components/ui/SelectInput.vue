<script setup lang="ts">
const model = defineModel<string | number | null>()

defineProps<{
    id?: string
    opcoes: Array<{ label: string; value: string | number }>
}>()
</script>

<template>
    <select :id="id" v-model="model" class="select">
        <option v-for="opcao in opcoes" :key="opcao.value" :value="opcao.value">{{ opcao.label }}</option>
    </select>
</template>
