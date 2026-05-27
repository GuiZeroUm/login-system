<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

// Uso: <Breadcrumb titulo="Sistemas" link="/dashboard" />
// → Início / Sistemas     (link = onde "Início" aponta)
//
// Uso: <Breadcrumb titulo="Novo Sistema" link="/sistema" linkTitulo="Sistemas" />
// → Início / Sistemas / Novo Sistema
defineProps<{
    titulo: string
    link?: string       // href do item intermediário (ou de "Início" se não houver linkTitulo)
    linkTitulo?: string // rótulo do item intermediário; se ausente, o link é o passo de "Início"
}>()
</script>

<template>
    <nav class="breadcrumb" aria-label="Breadcrumb">

        <!-- Início sempre aponta para /dashboard -->
        <Link href="/dashboard">
            Início
        </Link>

        <!-- Separador -->
        <svg class="sep" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="9 18 15 12 9 6" />
        </svg>

        <!-- Item intermediário (ex: "Sistemas") — clicável se há um terceiro nível -->
        <template v-if="linkTitulo && link">
            <Link :href="link">
                {{ linkTitulo }}
            </Link>
            <svg class="sep" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6" />
            </svg>
            <span class="current">{{ titulo }}</span>
        </template>

        <!-- Apenas dois níveis: Início / titulo -->
        <template v-else>
            <span class="current">{{ titulo }}</span>
        </template>
    </nav>
</template>
