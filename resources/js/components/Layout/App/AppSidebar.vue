<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import Icone from '@/components/ui/Icone.vue'
import { usarTutorialIntegracao } from '@/composables/usarTutorialIntegracao'
import type { Auth } from '@/types'

const tutorial = usarTutorialIntegracao()

const page = usePage<{
    auth: Auth
    navCounts?: { sistemas: number; usuarios: number; sessoes: number }
}>()
const user = computed(() => page.props.auth?.user)
const rotaAtual = computed(() => page.url)
const navCounts = computed(() => page.props.navCounts)

const initials = computed(() => {
    if (!user.value?.name) {
        return 'U'
    }

    return user.value.name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase()
})

const itens = computed(() => [
    { rota: '/dashboard', label: 'Dashboard', icon: 'grid', count: null as number | null },
    { rota: '/sistema', label: 'Sistemas', icon: 'monitor', count: navCounts.value?.sistemas ?? null },
    { rota: '/usuario', label: 'Usuários', icon: 'users', count: navCounts.value?.usuarios ?? null },
    { rota: '/sessoes', label: 'Sessões', icon: 'activity', count: navCounts.value?.sessoes ?? null },
])

function ativo(rota: string): boolean {
    if (rota === '/dashboard') {
        return rotaAtual.value === '/dashboard'
    }

    return rotaAtual.value.startsWith(rota)
}
</script>

<template>
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark">L</div>
            <div>
                <div class="brand-name">Login Universal</div>
                <div class="t-caption">ACL · v1.0</div>
            </div>
        </div>

        <div v-if="user" class="user-pill">
            <div class="avatar">{{ initials }}</div>
            <div>
                <div class="name">{{ user.name }}</div>
                <div class="role">{{ user.email }}</div>
            </div>
        </div>

        <div class="nav-section-label">NAVEGAÇÃO</div>

        <Link
            v-for="item in itens"
            :key="item.rota"
            :href="item.rota"
            class="nav-item"
            :class="{ active: ativo(item.rota) }"
        >
            <Icone :nome="item.icon" class="ico" />
            <span>{{ item.label }}</span>
            <span v-if="item.count !== null" class="count">{{ item.count }}</span>
        </Link>

        <div class="sidebar-spacer" />

        <div class="sidebar-footer">
            <button type="button" class="nav-item" @click="tutorial.abrir()">
                <Icone nome="info" class="ico" />
                <span>Tutorial</span>
            </button>
            <Link href="/logout" class="nav-item">
                <Icone nome="activity" class="ico" />
                <span>Sair</span>
            </Link>
        </div>
    </aside>
</template>
