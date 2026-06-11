<script setup lang="ts">
import DocLayout from '@/components/Documentacao/DocLayout.vue'
import { guiaConfiguracao } from '@/data/documentacao'
</script>

<template>
    <DocLayout :guia="guiaConfiguracao" />
</template>
