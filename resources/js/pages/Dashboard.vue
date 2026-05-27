<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import AppLayout from '@/components/Layout/AppLayout.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import Icone from '@/components/ui/Icone.vue'
import type { Auth } from '@/types'

const page = usePage<{ auth: Auth }>()

const user = computed(() => page.props.auth?.user)

const primeiroNome = computed(() => user.value?.name?.split(' ')[0] ?? 'Usuário')

const hora = new Date().getHours()
const saudacao = hora < 12 ? 'Bom dia' : hora < 18 ? 'Boa tarde' : 'Boa noite'

const dataAtual = new Intl.DateTimeFormat('pt-BR', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
}).format(new Date())

const horaAtual = new Intl.DateTimeFormat('pt-BR', {
    hour: '2-digit',
    minute: '2-digit',
}).format(new Date())

const modulos = [
    {
        route: '/sistema',
        label: 'Sistemas',
        icon: 'monitor',
        count: 6,
        sub: 'Aplicações conectadas',
    },
    {
        route: '/usuario',
        label: 'Usuários',
        icon: 'users',
        count: 8,
        sub: 'Contas cadastradas',
    },
    {
        route: '/sessoes',
        label: 'Sessões',
        icon: 'activity',
        count: 6,
        sub: 'Sessões ativas no momento',
    },
    {
        route: '/sistema',
        label: 'Perfis',
        icon: 'key',
        count: 3,
        sub: 'Perfis em todos os sistemas',
    },
    {
        route: '/sistema',
        label: 'Permissões',
        icon: 'shield',
        count: 10,
        sub: 'Permissões sincronizadas',
    },
] as const
</script>

<template>
    <AppLayout title="Dashboard">
        <div class="greeting">
            <h1>{{ saudacao }}, {{ primeiroNome }} 👋</h1>
            <div class="when">{{ dataAtual.charAt(0).toUpperCase() + dataAtual.slice(1) }} · {{ horaAtual }}</div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 14px; margin-bottom: 14px">
            <div style="background: linear-gradient(135deg, rgba(94,105,210,0.18), rgba(94,105,210,0.04)); border: 1px solid rgba(94,105,210,0.3); border-radius: 12px; padding: 24px; display: flex; align-items: center; gap: 20px">
                <div style="width: 48px; height: 48px; border-radius: 10px; background: var(--primary); display: grid; place-items: center; flex-shrink: 0">
                    <Icone nome="shield" :tamanho="22" style="color: white" />
                </div>
                <div style="flex: 1">
                    <div class="t-card-title">Sistema operacional</div>
                    <div style="font-size: 13px; color: var(--ink-muted); margin-top: 4px">Todos os serviços de autenticação respondendo normalmente. Última verificação há 2 minutos.</div>
                </div>
                <BadgePill variante="success" :dot="true">Online</BadgePill>
            </div>

            <div style="background: var(--surface-1); border: 1px solid var(--hairline); border-radius: 12px; padding: 20px">
                <div class="t-eyebrow">SESSÕES AGORA</div>
                <div style="font-family: var(--font-display); font-size: 32px; font-weight: 600; letter-spacing: -1px; margin: 6px 0 4px">6</div>
                <div style="font-size: 12px; color: var(--ink-subtle)">
                    <span style="color: #4ade80">+2</span> nas últimas 24h
                </div>
            </div>
        </div>

        <div class="dash-grid">
            <a v-for="item in modulos" :key="item.label" :href="item.route" class="module-card">
                <div class="row-between">
                    <div class="ic-wrap"><Icone :nome="item.icon" :tamanho="18" /></div>
                    <Icone nome="arrow-right" :tamanho="14" style="color: var(--ink-tertiary)" />
                </div>
                <div>
                    <div class="label">{{ item.label }}</div>
                    <div class="sub">{{ item.sub }}</div>
                </div>
                <div class="count">{{ item.count }}</div>
            </a>
        </div>
    </AppLayout>
</template>
