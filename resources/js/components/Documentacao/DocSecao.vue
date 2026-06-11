<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import Icone from '@/components/ui/Icone.vue'
import DocBlocoCodigo from '@/components/Documentacao/DocBlocoCodigo.vue'
import type { DocPasso } from '@/data/documentacao/tipos'

defineProps<{
    passo: DocPasso
    indice: number
}>()
</script>

<template>
    <section :id="passo.id" class="doc-secao">
        <div class="doc-secao-num">{{ indice + 1 }}</div>
        <div class="doc-secao-body">
            <h2 class="tutorial-step-title">{{ passo.titulo }}</h2>
            <p class="tutorial-step-resumo">{{ passo.resumo }}</p>

            <ul v-if="passo.itens?.length" class="tutorial-list">
                <li v-for="(item, idx) in passo.itens" :key="idx">{{ item }}</li>
            </ul>

            <DocBlocoCodigo v-if="passo.codigo" :bloco="passo.codigo" />

            <div v-if="passo.aviso" class="tutorial-aviso">
                <Icone nome="alert-circle" :tamanho="16" />
                <span>{{ passo.aviso }}</span>
            </div>

            <Link
                v-if="passo.linkInterno"
                :href="passo.linkInterno.href"
                class="tutorial-link-interno"
            >
                <Icone nome="link" :tamanho="14" />
                {{ passo.linkInterno.rotulo }}
            </Link>
        </div>
    </section>
</template>
