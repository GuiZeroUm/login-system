<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import Breadcrumb from '@/components/Breadcrumb.vue'
import DocSecao from '@/components/Documentacao/DocSecao.vue'
import AppLayout from '@/components/Layout/AppLayout.vue'
import Icone from '@/components/ui/Icone.vue'
import { guiasDocumentacao } from '@/data/documentacao'
import type { DocGuia } from '@/data/documentacao/tipos'

const props = defineProps<{
    guia: DocGuia
}>()

function scrollPara(id: string) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}
</script>

<template>
    <AppLayout :title="guia.titulo">
        <Breadcrumb
            :titulo="guia.titulo"
            link="/documentacao"
            link-titulo="Documentação"
        />

        <section class="card doc-page">
            <div class="card-header doc-page-header">
                <div class="titles">
                    <h1>{{ guia.titulo }}</h1>
                    <p>{{ guia.descricao }}</p>
                </div>
                <span class="doc-tempo-badge">{{ guia.tempoEstimado }}</span>
            </div>

            <div class="doc-page-layout">
                <nav class="doc-sidebar" aria-label="Índice do guia">
                    <p class="doc-sidebar-label">Neste guia</p>
                    <button
                        v-for="(passo, idx) in guia.passos"
                        :key="passo.id"
                        type="button"
                        class="tutorial-nav-item doc-sidebar-item"
                        @click="scrollPara(passo.id)"
                    >
                        <span class="tutorial-nav-step">{{ idx + 1 }}</span>
                        <span class="tutorial-nav-label">{{ passo.titulo }}</span>
                    </button>

                    <p class="doc-sidebar-label doc-sidebar-label--outros">Outros guias</p>
                    <Link
                        v-for="outro in guiasDocumentacao.filter((g) => g.id !== props.guia.id)"
                        :key="outro.id"
                        :href="outro.rota"
                        class="tutorial-nav-item doc-sidebar-item doc-sidebar-link"
                    >
                        <Icone nome="arrow-right" :tamanho="14" />
                        <span class="tutorial-nav-label">{{ outro.titulo }}</span>
                    </Link>
                </nav>

                <div class="doc-content">
                    <DocSecao
                        v-for="(passo, idx) in guia.passos"
                        :key="passo.id"
                        :passo="passo"
                        :indice="idx"
                    />
                </div>
            </div>
        </section>
    </AppLayout>
</template>
