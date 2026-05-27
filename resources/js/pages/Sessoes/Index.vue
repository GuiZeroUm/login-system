<script setup lang="ts">
import AppLayout from '@/components/Layout/AppLayout.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import Botao from '@/components/ui/Botao.vue'
import Icone from '@/components/ui/Icone.vue'
import { computed, ref } from 'vue'

const busca = ref('')
const periodo = ref('7')
const sessoes = ref([
    { id: 1, usuario: 'Ana Carolina Souza', email: 'ana.souza@gov.br', ip: '187.34.21.18', dispositivo: 'Chrome / macOS', ultimo: 'há 2 minutos', atual: false },
    { id: 2, usuario: 'Bruno Lima Pereira', email: 'bruno.lima@gov.br', ip: '187.34.21.45', dispositivo: 'Firefox / Linux', ultimo: 'há 12 minutos', atual: false },
    { id: 3, usuario: 'Camila Rocha', email: 'camila.rocha@gov.br', ip: '208.156.92.18', dispositivo: 'Safari / iOS', ultimo: 'há 25 minutos', atual: false },
    { id: 4, usuario: 'Você (admin)', email: 'admin@gov.br', ip: '127.0.0.1', dispositivo: 'Chrome / macOS', ultimo: 'agora', atual: true },
    { id: 5, usuario: 'Eduarda Martins', email: 'eduarda.m@gov.br', ip: '187.34.21.78', dispositivo: 'Chrome / Windows', ultimo: 'há 1 hora', atual: false },
    { id: 6, usuario: 'Heitor Cardoso', email: 'heitor.rc@gov.br', ip: '187.34.22.9', dispositivo: 'Edge / Windows', ultimo: 'há 3 horas', atual: false },
])

const filtradas = computed(() => sessoes.value.filter((s) => !busca.value || s.usuario.toLowerCase().includes(busca.value.toLowerCase()) || s.email.toLowerCase().includes(busca.value.toLowerCase())))

function iniciais(nome: string): string {
    return nome.split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase()
}
</script>

<template>
    <AppLayout title="Sessões ativas">
        <Breadcrumb titulo="Sessões ativas" />
        <section class="card">
            <div class="card-header">
                <div class="titles">
                    <h1>Sessões ativas</h1>
                    <p>Usuários autenticados no momento. Revogar uma sessão desconecta o usuário imediatamente.</p>
                </div>
                <Botao variante="danger" icone="x">Revogar todas</Botao>
            </div>

            <div class="toolbar">
                <div class="toolbar-left">
                    <div class="search input-icon">
                        <Icone nome="search" class="icon-left" />
                        <input v-model="busca" class="input" placeholder="Buscar por usuário ou e-mail..." />
                    </div>
                    <select v-model="periodo" class="select" style="max-width: 150px">
                        <option value="1">Último dia</option>
                        <option value="7">Últimos 7 dias</option>
                        <option value="30">Últimos 30 dias</option>
                    </select>
                </div>
                <div class="t-caption">{{ filtradas.length }} sessões ativas</div>
            </div>

            <div class="table-wrap">
                <table class="dt">
                    <thead>
                        <tr>
                            <th>Usuário</th>
                            <th>IP</th>
                            <th>Dispositivo</th>
                            <th>Último acesso</th>
                            <th style="text-align: right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in filtradas" :key="s.id">
                            <td>
                                <div class="user-cell">
                                    <div class="avatar-sm">{{ iniciais(s.usuario) }}</div>
                                    <div>
                                        <div class="cell-primary">{{ s.usuario }}</div>
                                        <div class="cell-sub">{{ s.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ s.ip }}</td>
                            <td>{{ s.dispositivo }}</td>
                            <td>{{ s.ultimo }}</td>
                            <td style="text-align: right">
                                <span v-if="s.atual" class="badge">Sessão atual</span>
                                <Botao v-else variante="danger" tamanho="sm" icone="x">Revogar</Botao>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </AppLayout>
</template>
