<script setup lang="ts">
import AuthLayout from '@/components/Layout/AuthLayout.vue'
import LoginTela, { type LoginTelaConfig } from '@/components/Sistema/LoginTela.vue'
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import login from '@/routes/login'
import type { Sistema } from '@/types'

const props = defineProps<{
    sistema: Pick<
        Sistema,
        | 'nome'
        | 'slug'
        | 'descricao'
        | 'caminho_logo'
        | 'caminho_logo_url'
        | 'caminho_ilustracao'
        | 'caminho_ilustracao_url'
        | 'login_nome'
        | 'tema_login'
        | 'login_subtitulo'
        | 'login_painel_eyebrow'
        | 'login_painel_titulo'
        | 'login_painel_descricao'
        | 'exibir_logo_topo'
        | 'exibir_bloco_inferior'
        | 'exibir_degrade_ilustracao'
    > | null
}>()

const form = useForm({
    email: '',
    password: '',
    remember: true,
})

const loginConfig = computed<LoginTelaConfig>(() => {
    if (!props.sistema) {
        return {
            nome: '',
            login_subtitulo: 'Entre com suas credenciais para continuar.',
            login_painel_eyebrow: 'HUB DE IDENTIDADE',
            login_painel_titulo: 'Um login para todos os sistemas internos.',
            login_painel_descricao: 'Autenticação centralizada com controle granular de acesso por sistema, órgão e lotação.',
            exibir_logo_topo: true,
            exibir_bloco_inferior: true,
            exibir_degrade_ilustracao: true,
        }
    }

    return {
        nome: props.sistema.nome,
        descricao: props.sistema.descricao,
        login_nome: props.sistema.login_nome,
        tema_login: props.sistema.tema_login,
        caminho_logo: props.sistema.caminho_logo,
        caminho_ilustracao: props.sistema.caminho_ilustracao,
        login_subtitulo: props.sistema.login_subtitulo,
        login_painel_eyebrow: props.sistema.login_painel_eyebrow,
        login_painel_titulo: props.sistema.login_painel_titulo,
        login_painel_descricao: props.sistema.login_painel_descricao,
        exibir_logo_topo: props.sistema.exibir_logo_topo,
        exibir_bloco_inferior: props.sistema.exibir_bloco_inferior,
        exibir_degrade_ilustracao: props.sistema.exibir_degrade_ilustracao,
    }
})

const formError = computed(() => form.errors.email || form.errors.password || null)

function submit() {
    form.post(login.store.url(), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <AuthLayout title="Login" :favicon-url="props.sistema?.caminho_logo_url ?? null">
        <LoginTela
            :config="loginConfig"
            interativo
            :email="form.email"
            :password="form.password"
            :remember="form.remember"
            :processing="form.processing"
            :email-error="!!form.errors.email"
            :password-error="!!form.errors.password"
            :form-error="formError"
            @submit="submit"
            @update:email="form.email = $event"
            @update:password="form.password = $event"
            @update:remember="form.remember = $event"
        />
    </AuthLayout>
</template>
