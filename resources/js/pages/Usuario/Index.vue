<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/components/Layout/AppLayout.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import Botao from '@/components/ui/Botao.vue'
import Icone from '@/components/ui/Icone.vue'
import { ref, watch } from 'vue'

type UsuarioItem = {
    id: number
    nome: string
    email: string
    status: string
    administrador_global: boolean
    acesso: string
    ultimo_login: string | null
}

const props = defineProps<{
    usuarios: {
        data: UsuarioItem[]
        links: { url: string | null; label: string; active: boolean }[]
    }
    filtros: { busca?: string }
}>()

const busca = ref(props.filtros.busca ?? '')

watch(busca, (valor) => {
    router.get(
        '/usuario',
        { busca: valor || undefined },
        { preserveState: true, replace: true },
    )
})

function iniciais(nome: string): string {
    return nome
        .split(' ')
        .map((w) => w[0])
        .slice(0, 2)
        .join('')
        .toUpperCase()
}

function excluir(id: number, nome: string) {
    if (!confirm(`Excluir o usuário "${nome}"?`)) {
        return
    }

    router.delete(`/usuario/${id}`)
}
</script>

<template>
    <AppLayout title="Usuários">
        <Breadcrumb titulo="Usuários" />
        <section class="card">
            <div class="card-header">
                <div class="titles">
                    <h1>Usuários</h1>
                    <p>Contas que podem se autenticar nos sistemas conectados.</p>
                </div>
                <Link href="/usuario/create"><Botao variante="primary" icone="plus">Novo usuário</Botao></Link>
            </div>

            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search input-icon">
                        <Icone nome="search" class="icon-left" />
                        <input v-model="busca" class="input" placeholder="Buscar por nome ou e-mail..." />
                    </div>
                </div>
                <div class="t-caption">{{ usuarios.data.length }} nesta página</div>
            </div>

            <div class="table-wrap">
                <table class="dt">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>Acesso</th>
                            <th>Status</th>
                            <th>Último acesso</th>
                            <th style="text-align: right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in usuarios.data" :key="u.id">
                            <td>
                                <div class="user-cell">
                                    <div class="avatar-sm">{{ iniciais(u.nome) }}</div>
                                    <div>
                                        <div class="cell-primary">{{ u.nome }}</div>
                                        <div class="cell-sub">{{ u.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <BadgePill v-if="u.administrador_global" variante="info">Administrador global</BadgePill>
                                <span v-else>{{ u.acesso }}</span>
                            </td>
                            <td>
                                <BadgePill :variante="u.status === 'Ativo' ? 'success' : 'neutral'" :dot="true">{{ u.status }}</BadgePill>
                            </td>
                            <td class="t-caption">{{ u.ultimo_login ?? '—' }}</td>
                            <td style="text-align: right">
                                <Link :href="`/usuario/${u.id}/edit`" class="btn btn-secondary btn-sm">Editar</Link>
                                <button type="button" class="btn btn-secondary btn-sm" style="margin-left: 6px" @click="excluir(u.id, u.nome)">
                                    Excluir
                                </button>
                            </td>
                        </tr>
                        <tr v-if="usuarios.data.length === 0">
                            <td colspan="5" class="t-caption" style="text-align: center; padding: 24px">Nenhum usuário encontrado.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AppLayout>
</template>
