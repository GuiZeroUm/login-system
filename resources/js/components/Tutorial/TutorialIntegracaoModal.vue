<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { onKeyStroke } from '@vueuse/core'
import { computed } from 'vue'
import Botao from '@/components/ui/Botao.vue'
import Icone from '@/components/ui/Icone.vue'
import { usarTutorialIntegracao } from '@/composables/usarTutorialIntegracao'
import { secoesTutorialIntegracao } from '@/data/tutorialIntegracao'

const tutorial = usarTutorialIntegracao()

const progressoPercentual = computed(
    () => ((tutorial.indiceGlobal.value + 1) / tutorial.totalPassos) * 100,
)

onKeyStroke('Escape', () => {
    if (tutorial.aberto.value) {
        tutorial.fechar()
    }
})
</script>

<template>
    <Teleport to="body">
        <div
            v-if="tutorial.aberto.value"
            class="modal-overlay tutorial-overlay"
            role="dialog"
            aria-modal="true"
            aria-labelledby="tutorial-titulo"
            @click.self="tutorial.fechar()"
        >
            <div class="tutorial-modal" @click.stop>
                <header class="tutorial-modal-header">
                    <div>
                        <div class="t-eyebrow" style="color: var(--primary-hover); margin-bottom: 6px">
                            Guia de integração
                        </div>
                        <h3 id="tutorial-titulo" class="tutorial-modal-title">
                            Do cadastro ao login no seu sistema
                        </h3>
                        <p class="tutorial-modal-subtitle">
                            Passo a passo com base em <code>docs/06-INTEGRATION.md</code> e
                            <code>docs/08-SISTEMA-EXTERNO-INTEGRACAO.md</code>.
                        </p>
                    </div>
                    <button type="button" class="btn-icon" aria-label="Fechar tutorial" @click="tutorial.fechar()">
                        <Icone nome="close" :tamanho="18" />
                    </button>
                </header>

                <div class="tutorial-modal-layout">
                    <nav class="tutorial-nav" aria-label="Seções do tutorial">
                        <button
                            v-for="(secao, indice) in secoesTutorialIntegracao"
                            :key="secao.id"
                            type="button"
                            class="tutorial-nav-item"
                            :class="{ active: tutorial.secaoAtual.value === indice }"
                            @click="tutorial.irParaSecao(indice)"
                        >
                            <span class="tutorial-nav-step">{{ indice + 1 }}</span>
                            <span>
                                <span class="tutorial-nav-label">{{ secao.titulo }}</span>
                                <span class="tutorial-nav-meta">{{ secao.passos.length }} passos</span>
                            </span>
                        </button>
                    </nav>

                    <div class="tutorial-content">
                        <div class="tutorial-progress">
                            <div class="tutorial-progress-track">
                                <div class="tutorial-progress-fill" :style="{ width: `${progressoPercentual}%` }" />
                            </div>
                            <span class="t-caption">
                                Passo {{ tutorial.indiceGlobal.value + 1 }} de {{ tutorial.totalPassos }}
                            </span>
                        </div>

                        <div class="tutorial-step-badge">
                            {{ tutorial.secao.value.titulo }}
                        </div>

                        <h4 class="tutorial-step-title">{{ tutorial.passo.value.titulo }}</h4>
                        <p class="tutorial-step-resumo">{{ tutorial.passo.value.resumo }}</p>

                        <ul v-if="tutorial.passo.value.itens?.length" class="tutorial-list">
                            <li v-for="(item, idx) in tutorial.passo.value.itens" :key="idx">{{ item }}</li>
                        </ul>

                        <div v-if="tutorial.passo.value.codigo" class="tutorial-code">
                            <div class="tutorial-code-label">{{ tutorial.passo.value.codigo.rotulo }}</div>
                            <pre><code>{{ tutorial.passo.value.codigo.conteudo }}</code></pre>
                        </div>

                        <div v-if="tutorial.passo.value.aviso" class="tutorial-aviso">
                            <Icone nome="alert-circle" :tamanho="16" />
                            <span>{{ tutorial.passo.value.aviso }}</span>
                        </div>

                        <Link
                            v-if="tutorial.passo.value.linkInterno"
                            :href="tutorial.passo.value.linkInterno.href"
                            class="tutorial-link-interno"
                            @click="tutorial.fechar()"
                        >
                            <Icone nome="link" :tamanho="14" />
                            {{ tutorial.passo.value.linkInterno.rotulo }}
                        </Link>

                        <div class="tutorial-dots" aria-hidden="true">
                            <button
                                v-for="(_, idx) in tutorial.secao.value.passos"
                                :key="idx"
                                type="button"
                                class="tutorial-dot"
                                :class="{ active: tutorial.passoAtual.value === idx }"
                                :aria-label="`Passo ${idx + 1}`"
                                @click="tutorial.irParaPasso(idx)"
                            />
                        </div>
                    </div>
                </div>

                <footer class="tutorial-modal-footer">
                    <Botao variante="tertiary" :disabled="!tutorial.podeVoltar.value" @click="tutorial.voltar()">
                        Anterior
                    </Botao>
                    <div class="tutorial-footer-actions">
                        <Botao variante="tertiary" @click="tutorial.fechar()">Fechar</Botao>
                        <Botao
                            v-if="tutorial.podeAvancar.value"
                            variante="primary"
                            icone-direita="arrow-right"
                            @click="tutorial.avancar()"
                        >
                            Próximo
                        </Botao>
                        <Botao v-else variante="primary" @click="tutorial.fechar()">Concluir</Botao>
                    </div>
                </footer>
            </div>
        </div>
    </Teleport>
</template>
