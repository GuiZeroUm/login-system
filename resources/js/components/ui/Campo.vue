<script setup lang="ts">
defineProps<{
    rotulo: string
    forId?: string
    obrigatorio?: boolean
    dica?: string
    erro?: string | null
}>()
</script>

<template>
    <div class="form-field">
        <label :for="forId">
            {{ rotulo }}
            <span v-if="obrigatorio" class="required">*</span>
        </label>
        <slot />
        <div v-if="dica && !erro" class="hint">{{ dica }}</div>
        <div v-if="erro" class="error-msg">{{ erro }}</div>
    </div>
</template>
