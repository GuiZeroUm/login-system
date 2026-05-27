<script setup lang="ts">
import { Head } from '@inertiajs/vue3'

defineProps<{
    title?: string
    faviconUrl?: string | null
}>()
</script>

<template>
    <div class="min-h-screen bg-canvas text-ink antialiased">
        <Head :title="title">
            <link v-if="faviconUrl" rel="icon" :href="faviconUrl" sizes="any" />
            <link v-if="faviconUrl" rel="apple-touch-icon" :href="faviconUrl" />
        </Head>
        <main>
            <slot />
        </main>
    </div>
</template>
