<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import AppSidebar from '@/components/Layout/App/AppSidebar.vue'
import TutorialIntegracaoModal from '@/components/Tutorial/TutorialIntegracaoModal.vue'
import ToastFlash from '@/components/ui/ToastFlash.vue'
import { provideTutorialIntegracao } from '@/composables/usarTutorialIntegracao'

defineProps<{
    title?: string
}>()

provideTutorialIntegracao()
</script>

<template>
    <div class="min-h-screen bg-canvas text-ink antialiased">
        <Head :title="title" />
        <ToastFlash />
        <TutorialIntegracaoModal />
        <div class="app-shell">
            <AppSidebar />
            <main class="main">
                <slot />
            </main>
        </div>
    </div>
</template>
