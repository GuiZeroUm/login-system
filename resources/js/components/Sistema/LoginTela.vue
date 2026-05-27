<script setup lang="ts">
import Botao from '@/components/ui/Botao.vue'
import Campo from '@/components/ui/Campo.vue'
import CheckboxCampo from '@/components/ui/CheckboxCampo.vue'
import Entrada from '@/components/ui/Entrada.vue'
import Icone from '@/components/ui/Icone.vue'
import { useAsset } from '@/composables/useAsset'
import { computed, ref } from 'vue'

export type LoginTelaConfig = {
    nome: string
    descricao?: string | null
    login_nome?: string | null
    tema_login?: 'escuro' | 'claro'
    caminho_logo?: string | null
    caminho_ilustracao?: string | null
    login_subtitulo?: string | null
    login_painel_eyebrow?: string | null
    login_painel_titulo?: string | null
    login_painel_descricao?: string | null
    exibir_logo_topo?: boolean
    exibir_bloco_inferior?: boolean
    exibir_degrade_ilustracao?: boolean
}

const props = withDefaults(
    defineProps<{
        config: LoginTelaConfig
        previewLogo?: string | null
        previewIlustracao?: string | null
        interativo?: boolean
        email?: string
        password?: string
        remember?: boolean
        processing?: boolean
        emailError?: boolean
        passwordError?: boolean
        formError?: string | null
    }>(),
    {
        interativo: false,
        remember: true,
        processing: false,
        emailError: false,
        passwordError: false,
    },
)

const emit = defineEmits<{
    submit: []
    'update:email': [value: string]
    'update:password': [value: string]
    'update:remember': [value: boolean]
}>()

const { storage } = useAsset()
const mostrarSenha = ref(false)

const ehBranded = computed(() => Boolean(props.config.nome || props.previewLogo || props.previewIlustracao))
const temaClaro = computed(() => props.config.tema_login === 'claro')
const tituloLogin = computed(() => props.config.login_nome || props.config.nome || 'Login Universal')
const subtitulo = computed(() => props.config.login_subtitulo || 'Use sua conta corporativa para acessar.')
const painelEyebrow = computed(() => props.config.login_painel_eyebrow || 'VOCÊ ESTÁ ENTRANDO EM')
const painelTitulo = computed(() => props.config.login_painel_titulo || props.config.nome || tituloLogin.value)
const painelDescricao = computed(
    () => props.config.login_painel_descricao || props.config.descricao || 'Sistema centralizado de autenticação e controle de acesso.',
)
const exibirLogoTopo = computed(() => props.config.exibir_logo_topo !== false)
const exibirBlocoInferior = computed(() => props.config.exibir_bloco_inferior !== false)
const exibirDegrade = computed(() => props.config.exibir_degrade_ilustracao !== false)

const logoUrl = computed(() => {
    if (props.previewLogo) {
        return props.previewLogo
    }
    if (props.config.caminho_logo) {
        return storage(props.config.caminho_logo, 'assets/images/autenticacao/meuac.png')
    }
    return null
})

const ilustracaoUrl = computed(() => {
    if (props.previewIlustracao) {
        return props.previewIlustracao
    }
    if (props.config.caminho_ilustracao) {
        return storage(props.config.caminho_ilustracao, 'assets/images/autenticacao/ilustracao.png')
    }
    return null
})

const iniciais = computed(() =>
    (props.config.nome || 'LU')
        .split(' ')
        .map((w) => w[0] ?? '')
        .slice(0, 2)
        .join('')
        .toUpperCase() || 'L',
)
</script>

<template>
    <div class="login-shell" :class="{ 'login-shell-light': temaClaro }">
        <div
            class="login-left"
            :class="{
                branded: ehBranded,
                'with-illustration': !!ilustracaoUrl,
                'with-illustration-overlay': !!ilustracaoUrl && exibirDegrade,
            }"
        >
            <div class="deco" aria-hidden="true">
                <svg width="100%" height="100%" viewBox="0 0 800 1000" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.5">
                    <defs>
                        <radialGradient id="login-g1" cx="20%" cy="20%" r="60%">
                            <stop offset="0%" stop-color="#5e6ad255" />
                            <stop offset="100%" stop-color="transparent" />
                        </radialGradient>
                        <radialGradient id="login-g2" cx="80%" cy="80%" r="60%">
                            <stop offset="0%" stop-color="#828fff22" />
                            <stop offset="100%" stop-color="transparent" />
                        </radialGradient>
                        <pattern id="login-grid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="1" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#login-grid)" />
                    <rect width="100%" height="100%" fill="url(#login-g1)" />
                    <rect width="100%" height="100%" fill="url(#login-g2)" />
                </svg>
            </div>

            <div v-if="exibirLogoTopo" class="logo-row">
                <div class="brand-mark">
                    <img v-if="logoUrl" :src="logoUrl" :alt="tituloLogin" class="brand-mark-img">
                    <span v-else>{{ iniciais.slice(0, 1) }}</span>
                </div>
                <div class="brand-name">{{ tituloLogin }}</div>
            </div>

            <div v-if="exibirBlocoInferior" class="lower">
                <div class="row" style="margin-bottom: 18px">
                    <div class="login-avatar-mark">
                        <img v-if="logoUrl" :src="logoUrl" :alt="painelTitulo" class="brand-mark-img">
                        <span v-else>{{ iniciais }}</span>
                    </div>
                    <span class="t-eyebrow login-eyebrow">{{ painelEyebrow }}</span>
                </div>
                <h2>{{ painelTitulo }}</h2>
                <p>{{ painelDescricao }}</p>
            </div>

            <div v-if="ilustracaoUrl" class="login-illustration-bg">
                <img :src="ilustracaoUrl" :alt="`Ilustração ${painelTitulo}`">
            </div>
        </div>

        <div class="login-right">
            <form class="login-form" @submit.prevent="interativo ? emit('submit') : undefined">
                <div class="sys-logo" :class="{ branded: ehBranded }">
                    <img v-if="logoUrl" :src="logoUrl" :alt="tituloLogin">
                    <span v-else-if="ehBranded">{{ iniciais }}</span>
                    <Icone v-else nome="shield" :tamanho="20" />
                </div>

                <h1>{{ ehBranded ? tituloLogin : 'Entrar' }}</h1>
                <div class="sub">{{ subtitulo }}</div>

                <div v-if="formError" class="login-error">
                    <Icone nome="alert-circle" :tamanho="16" />
                    <span>{{ formError }}</span>
                </div>

                <Campo rotulo="E-mail" for-id="login-email">
                    <Entrada
                        id="login-email"
                        :model-value="email"
                        type="email"
                        autocomplete="email"
                        placeholder="seu.email@exemplo.com"
                        :readonly="!interativo"
                        :error="emailError"
                        @update:model-value="emit('update:email', String($event ?? ''))"
                    />
                </Campo>

                <Campo rotulo="Senha" for-id="login-password">
                    <div style="position: relative">
                        <Entrada
                            id="login-password"
                            :model-value="password"
                            :type="mostrarSenha ? 'text' : 'password'"
                            autocomplete="current-password"
                            placeholder="••••••••"
                            :readonly="!interativo"
                            :error="passwordError"
                            @update:model-value="emit('update:password', String($event ?? ''))"
                        />
                        <button
                            v-if="interativo"
                            type="button"
                            class="login-password-toggle"
                            @click="mostrarSenha = !mostrarSenha"
                        >
                            <Icone :nome="mostrarSenha ? 'eye-off' : 'eye'" :tamanho="14" />
                        </button>
                    </div>
                </Campo>

                <div class="row">
                    <CheckboxCampo
                        :model-value="remember"
                        rotulo="Lembrar de mim"
                        :disabled="!interativo"
                        @update:model-value="emit('update:remember', $event)"
                    />
                </div>

                <div style="margin-top: 18px">
                    <Botao
                        :tipo="interativo ? 'submit' : 'button'"
                        variante="primary"
                        :bloco="true"
                        :loading="processing"
                        icone-direita="arrow-right"
                    >
                        {{ processing ? 'Entrando...' : 'Entrar' }}
                    </Botao>
                </div>

                <div class="login-help">
                    Problemas para acessar?
                    <a>Contate o administrador</a>
                </div>
            </form>
        </div>
    </div>
</template>
