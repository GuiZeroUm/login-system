<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import AppLayout from '@/components/Layout/AppLayout.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import Botao from '@/components/ui/Botao.vue'
import Icone from '@/components/ui/Icone.vue'
import PaginacaoLista from '@/components/ui/PaginacaoLista.vue'
import { useAsset } from '@/composables/useAsset'
import { usePermissions } from '@/composables/usePermissions'
import type { Sistema } from '@/types'

type Paginated<T> = {
    data: T[]
    links: { url: string | null; label: string; active: boolean }[]
}

const props = defineProps<{
    dados: Paginated<Sistema>
    filtros: Record<string, string | number | undefined>
}>()

const { can } = usePermissions()
const { storage } = useAsset()

const search = ref(String(props.filtros.search ?? ''))
const ambiente = ref(String(props.filtros.ambiente ?? ''))

function pesquisar() {
    router.get('/sistema', { search: search.value, ambiente: ambiente.value || undefined }, { preserveState: true })
}

const ambienteMap: Record<string, { label: string; variante: 'success' | 'warning' | 'info' | 'neutral' }> = {
    production: { label: 'Produção', variante: 'success' },
    homologacao: { label: 'Homologação', variante: 'warning' },
    desenvolvimento: { label: 'Desenvolvimento', variante: 'neutral' },
}

function badgeAmbiente(ambiente: string) {
    return ambienteMap[ambiente] ?? { label: ambiente, variante: 'neutral' as const }
}

function permissoesSistema(id: number): number {
    const sistema = props.dados.data.find((item) => item.id === id)

    return sistema?.permissions_count ?? 0
}

function inativar(id: number): void {
    router.delete(`/sistema/${id}`)
}

function reativar(id: number): void {
    router.patch(`/sistema/${id}/reativar`)
}

function sincronizarPermissoes(id: number): void {
    router.post(`/sistema/${id}/sincronizar-permissoes`)
}

function ultimaSincronizacao(valor: string | null | undefined): string {
    if (!valor) {
        return 'nunca'
    }

    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(valor))
}
</script>

<template>
    <AppLayout title="Sistemas">
        <Breadcrumb titulo="Sistemas" />

        <section class="card">
            <div class="card-header">
                <div class="titles">
                    <h1>Sistemas</h1>
                    <p>Aplicações externas conectadas ao Login Universal.</p>
                </div>
                <Link v-if="can('1.1')" href="/sistema/create">
                    <Botao variante="primary" icone="plus">Novo sistema</Botao>
                </Link>
            </div>

            <div class="toolbar">
                <form class="toolbar-left" @submit.prevent="pesquisar">
                    <div class="search input-icon">
                        <Icone nome="search" class="icon-left" />
                        <input v-model="search" class="input" type="search" placeholder="Buscar por nome ou slug..." />
                    </div>
                    <select v-model="ambiente" class="select" style="max-width: 180px">
                        <option value="">Todos os ambientes</option>
                        <option value="production">Produção</option>
                        <option value="homologacao">Homologação</option>
                        <option value="desenvolvimento">Desenvolvimento</option>
                    </select>
                    <Botao variante="secondary" tipo="submit">Filtrar</Botao>
                </form>
                <div class="t-caption">{{ dados.data.length }} resultados</div>
            </div>

            <div v-if="dados.data.length === 0" class="empty-state">
                <div class="illu"><Icone nome="monitor" /></div>
                <h3>Nenhum sistema encontrado</h3>
                <p>Ajuste os filtros ou cadastre um novo sistema.</p>
            </div>

            <div v-else class="table-wrap">
                <table class="dt">
                    <thead>
                        <tr>
                            <th>Sistema</th>
                            <th>URL</th>
                            <th>Ambiente</th>
                            <th>Permissões</th>
                            <th>Última sincronização</th>
                            <th style="text-align: right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sistema in dados.data" :key="sistema.id">
                            <td>
                                <div class="user-cell">
                                    <div class="avatar-sm" :class="{ 'avatar-sm--logo': !!sistema.caminho_logo }">
                                        <img
                                            v-if="sistema.caminho_logo"
                                            :src="storage(sistema.caminho_logo, '')"
                                            :alt="`Logo ${sistema.nome}`"
                                        >
                                        <span v-else>{{ sistema.nome.slice(0, 2).toUpperCase() }}</span>
                                    </div>
                                    <div>
                                        <div class="cell-primary">{{ sistema.nome }}</div>
                                        <div class="cell-sub">/{{ sistema.slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a :href="sistema.url" target="_blank" rel="noopener" class="text-muted">{{ sistema.url.replace('https://', '') }}</a>
                            </td>
                            <td>
                                <BadgePill :variante="badgeAmbiente(sistema.ambiente).variante">{{ badgeAmbiente(sistema.ambiente).label }}</BadgePill>
                            </td>
                            <td><BadgePill variante="info">{{ permissoesSistema(sistema.id) }} permissões</BadgePill></td>
                            <td class="text-subtle">{{ ultimaSincronizacao(sistema.permissions_synced_at) }}</td>
                            <td>
                                <div class="row-actions">
                                    <button
                                        v-if="!sistema.ativo"
                                        class="btn-icon"
                                        type="button"
                                        title="Reativar"
                                        @click="reativar(sistema.id)"
                                    >
                                        <Icone nome="refresh" :tamanho="14" />
                                    </button>
                                    <Link v-if="can('1.2')" :href="`/sistema/${sistema.id}/edit`" class="btn-icon">
                                        <Icone nome="info" :tamanho="14" />
                                    </Link>
                                    <button
                                        class="btn btn-secondary btn-sm"
                                        type="button"
                                        @click="sincronizarPermissoes(sistema.id)"
                                    >
                                        <Icone nome="refresh" :tamanho="14" />
                                        Sincronizar
                                    </button>
                                    <button
                                        v-if="sistema.ativo"
                                        class="btn-icon"
                                        type="button"
                                        title="Inativar"
                                        @click="inativar(sistema.id)"
                                    >
                                        <Icone nome="trash" :tamanho="14" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginacaoLista v-if="dados.links?.length > 3" :links="dados.links" />
        </section>
    </AppLayout>
</template>
