<script setup lang="ts">
import Icone from '@/components/ui/Icone.vue'
import { computed } from 'vue'

const props = withDefaults(
    defineProps<{
        variante?: 'primary' | 'secondary' | 'tertiary' | 'danger'
        tipo?: 'button' | 'submit' | 'reset'
        tamanho?: 'md' | 'sm'
        loading?: boolean
        disabled?: boolean
        bloco?: boolean
        icone?: string
        iconeDireita?: string
    }>(),
    {
        variante: 'primary',
        tipo: 'button',
        tamanho: 'md',
        loading: false,
        disabled: false,
        bloco: false,
    },
)

const classes = computed(() => [
    'btn',
    `btn-${props.variante}`,
    props.tamanho === 'sm' ? 'btn-sm' : '',
    props.bloco ? 'btn-full' : '',
])
</script>

<template>
    <button :type="tipo" :disabled="disabled || loading" :class="classes">
        <span v-if="loading" class="spinner" />
        <Icone v-else-if="icone" :nome="icone" :tamanho="14" />
        <slot />
        <Icone v-if="iconeDireita && !loading" :nome="iconeDireita" :tamanho="14" />
    </button>
</template>
