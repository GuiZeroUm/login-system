<script setup lang="ts">
import DocLayout from '@/components/Documentacao/DocLayout.vue'
import { guiaReactNext } from '@/data/documentacao'
</script>

<template>
    <DocLayout :guia="guiaReactNext" />
</template>
