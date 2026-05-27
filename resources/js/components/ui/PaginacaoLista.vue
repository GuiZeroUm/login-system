<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

defineProps<{
    links: { url: string | null; label: string; active: boolean }[]
}>()

function limpar(label: string): string {
    return label.replaceAll('&laquo;', '«').replaceAll('&raquo;', '»').replaceAll('&amp;', '&')
}
</script>

<template>
    <div class="pagination">
        <div class="t-caption">Paginação</div>
        <div class="controls">
            <Link
                v-for="link in links"
                :key="link.label"
                :href="link.url || '#'"
                class="page-btn"
                :class="{ active: link.active }"
                :disabled="!link.url"
            >
                {{ limpar(link.label) }}
            </Link>
        </div>
    </div>
</template>
