<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import Breadcrumb from '@/components/Breadcrumb.vue'
import AppLayout from '@/components/Layout/AppLayout.vue'
import Icone from '@/components/ui/Icone.vue'
import { cardsDocumentacao } from '@/data/documentacao'
</script>

<template>
    <AppLayout title="Documentação">
        <Breadcrumb titulo="Documentação" />

        <section class="card">
            <div class="card-header">
                <div class="titles">
                    <h1>Documentação de integração</h1>
                    <p>
                        Protocolo SSO simples: redirect no browser, troca de token no servidor.
                        Você recebe os dados do usuário e decide o que armazenar — como no Clerk,
                        sem SDK obrigatório.
                    </p>
                </div>
            </div>

            <div class="card-body">
                <div class="doc-cards-grid">
                    <Link
                        v-for="card in cardsDocumentacao"
                        :key="card.id"
                        :href="card.rota"
                        class="doc-card"
                    >
                        <div class="doc-card-icon">
                            <Icone :nome="card.icon" :tamanho="22" />
                        </div>
                        <h2 class="doc-card-title">{{ card.titulo }}</h2>
                        <p class="doc-card-resumo">{{ card.resumo }}</p>
                        <span class="doc-card-tempo">{{ card.tempoEstimado }}</span>
                        <span class="doc-card-link">
                            Abrir guia
                            <Icone nome="arrow-right" :tamanho="14" />
                        </span>
                    </Link>
                </div>

                <div class="doc-api-box">
                    <h3 class="doc-api-title">Contrato da API</h3>
                    <p class="doc-api-desc">
                        Após o login, o app cliente troca o callback por dados do usuário em uma única chamada server-side:
                    </p>
                    <div class="tutorial-code">
                        <div class="tutorial-code-label">Back-channel (obrigatório)</div>
                        <pre><code>GET {ACL_API_URL}/api/v1/login/{slug}?token={callback}

Resposta 200: { id, nome, email, orgaos, acesso_sistema, acl_token, ... }
Erros: 401 (token inválido/expirado), 403 (sem permissão)</code></pre>
                    </div>
                    <p class="doc-api-desc">
                        Front-channel (browser): redirect para
                        <code>{ACL_URL}/login/{slug}</code> e retorno com
                        <code>?callback=</code> na URL cadastrada do sistema.
                    </p>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
