<script setup lang="ts">
import DocLayout from '@/components/Documentacao/DocLayout.vue'
import { guiaLaravel } from '@/data/documentacao'
</script>

<template>
    <DocLayout :guia="guiaLaravel" />
</template>
