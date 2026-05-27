<script setup lang="ts">
import AppLayout from '@/components/Layout/AppLayout.vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import UsuarioPerfisPermissoes from '@/components/Usuario/UsuarioPerfisPermissoes.vue'
import UsuarioSistemasTab from '@/components/Usuario/UsuarioSistemasTab.vue'
import AbasNav from '@/components/ui/AbasNav.vue'
import Botao from '@/components/ui/Botao.vue'
import Campo from '@/components/ui/Campo.vue'
import CheckboxCampo from '@/components/ui/CheckboxCampo.vue'
import Entrada from '@/components/ui/Entrada.vue'
import type { AcessoForm, CatalogoSistema, SistemaOpcao } from '@/types/usuario'
import { Link, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const props = defineProps<{
    usuario: {
        id: number
        name: string
        email: string
        ativo: boolean
        administrador_global: boolean
        acessos: AcessoForm[]
    } | null
    sistemas: SistemaOpcao[]
    catalogo: Record<number, CatalogoSistema>
}>()

const aba = ref('dados')
const sistemaSelecionadoId = ref<number | null>(
    props.usuario?.acessos.find((a) => a.sistema_id)?.sistema_id ?? null,
)

const form = useForm({
    name: props.usuario?.name ?? '',
    email: props.usuario?.email ?? '',
    password: '',
    ativo: props.usuario?.ativo ?? true,
    administrador_global: props.usuario?.administrador_global ?? false,
    acessos: (props.usuario?.acessos ?? []) as AcessoForm[],
})

const titulo = computed(() => (props.usuario ? 'Editar usuário' : 'Novo usuário'))
const editando = computed(() => Boolean(props.usuario?.id))

const indiceAcessoSelecionado = computed(() =>
    form.acessos.findIndex((a) => a.sistema_id === sistemaSelecionadoId.value),
)

const acessoSelecionado = computed(() => {
    const idx = indiceAcessoSelecionado.value

    return idx >= 0 ? form.acessos[idx] : null
})

watch(
    () => form.acessos.length,
    () => {
        if (sistemaSelecionadoId.value && form.acessos.some((a) => a.sistema_id === sistemaSelecionadoId.value)) {
            return
        }

        sistemaSelecionadoId.value = form.acessos.find((a) => a.sistema_id)?.sistema_id ?? null
    },
)

function novoAcesso(sistemaId: number): AcessoForm {
    return {
        sistema_id: sistemaId,
        administrador_sistema: false,
        perfis_ids: [],
        permissoes: [],
    }
}

function adicionarSistema(sistemaId: number) {
    form.acessos.push(novoAcesso(sistemaId))
    sistemaSelecionadoId.value = sistemaId
    aba.value = 'perfis'
}

function removerAcesso(index: number) {
    const removido = form.acessos[index]?.sistema_id
    form.acessos.splice(index, 1)

    if (removido === sistemaSelecionadoId.value) {
        sistemaSelecionadoId.value = form.acessos.find((a) => a.sistema_id)?.sistema_id ?? null
    }
}

function configurarSistema(sistemaId: number) {
    sistemaSelecionadoId.value = sistemaId
    aba.value = 'perfis'
}

function atualizarAcessoSelecionado(acesso: AcessoForm) {
    const idx = indiceAcessoSelecionado.value

    if (idx >= 0) {
        form.acessos[idx] = acesso
    }
}

function primeiraAbaComErro(): string | null {
    const chaves = Object.keys(form.errors)

    if (chaves.some((k) => ['name', 'email', 'password'].includes(k))) {
        return 'dados'
    }

    if (chaves.some((k) => k === 'acessos' || k.startsWith('acessos.'))) {
        if (chaves.some((k) => k === 'acessos' || k.match(/^acessos\.\d+\.sistema_id/))) {
            return 'sistemas'
        }

        return 'perfis'
    }

    return null
}

function irParaAbaComErro(): void {
    const destino = primeiraAbaComErro()

    if (destino) {
        aba.value = destino
    }
}

function labelAba(id: string): string {
    const map: Record<string, string> = {
        dados: 'Dados básicos',
        sistemas: 'Sistemas',
        perfis: 'Perfis e permissões',
    }

    return map[id] ?? id
}

function mensagensErro(): string[] {
    return Object.values(form.errors).filter((msg): msg is string => typeof msg === 'string')
}

function submit() {
    const opcoes = {
        preserveScroll: true,
        onSuccess: () => form.reset('password'),
        onError: irParaAbaComErro,
    }

    if (editando.value && props.usuario) {
        form.patch(`/usuario/${props.usuario.id}`, opcoes)

        return
    }

    form.post('/usuario', opcoes)
}
</script>

<template>
    <AppLayout :title="titulo">
        <Breadcrumb :titulo="titulo" link="/usuario" link-titulo="Usuários" />
        <section class="card">
            <div class="card-header">
                <div class="titles">
                    <h1>{{ titulo }}</h1>
                    <p>Atualize dados, sistemas e permissões do usuário.</p>
                </div>
            </div>
            <AbasNav
                :abas="[
                    { id: 'dados', label: 'Dados básicos' },
                    { id: 'sistemas', label: 'Sistemas' },
                    { id: 'perfis', label: 'Perfis e permissões' },
                ]"
                :ativa="aba"
                @selecionar="(id) => (aba = id)"
            />

            <div
                v-if="mensagensErro().length"
                class="mx-4 mt-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200"
                role="alert"
            >
                <strong>Não foi possível salvar.</strong>
                <ul style="margin: 8px 0 0; padding-left: 18px">
                    <li v-for="(msg, i) in mensagensErro()" :key="i">{{ msg }}</li>
                </ul>
                <p v-if="primeiraAbaComErro() && primeiraAbaComErro() !== aba" class="t-caption" style="margin-top: 8px">
                    Confira a aba «{{ labelAba(primeiraAbaComErro()!) }}».
                </p>
            </div>

            <form class="card-body" @submit.prevent="submit">
                <div v-if="aba === 'dados'" class="cols-2">
                    <Campo rotulo="Nome completo" obrigatorio>
                        <Entrada v-model="form.name" :error="!!form.errors.name" />
                        <p v-if="form.errors.name" class="t-caption" style="color: #e5534b; margin-top: 6px">{{ form.errors.name }}</p>
                    </Campo>
                    <Campo rotulo="E-mail" obrigatorio>
                        <Entrada v-model="form.email" type="email" :error="!!form.errors.email" />
                        <p v-if="form.errors.email" class="t-caption" style="color: #e5534b; margin-top: 6px">{{ form.errors.email }}</p>
                    </Campo>
                    <Campo :rotulo="editando ? 'Nova senha' : 'Senha'" :obrigatorio="!editando">
                        <Entrada
                            v-model="form.password"
                            type="password"
                            :placeholder="editando ? 'Deixe em branco para não alterar' : ''"
                            :error="!!form.errors.password"
                        />
                        <p v-if="form.errors.password" class="t-caption" style="color: #e5534b; margin-top: 6px">{{ form.errors.password }}</p>
                    </Campo>
                    <div style="display: flex; align-items: end">
                        <CheckboxCampo v-model="form.ativo" rotulo="Ativo — pode fazer login" />
                    </div>
                </div>

                <UsuarioSistemasTab
                    v-else-if="aba === 'sistemas'"
                    :acessos="form.acessos"
                    :sistemas="sistemas"
                    :administrador-global="form.administrador_global"
                    :sistema-selecionado-id="sistemaSelecionadoId"
                    :erro-acessos="form.errors.acessos"
                    @update:administrador-global="form.administrador_global = $event"
                    @update:sistema-selecionado-id="sistemaSelecionadoId = $event"
                    @adicionar="adicionarSistema"
                    @remover="removerAcesso"
                    @configurar="configurarSistema"
                />

                <UsuarioPerfisPermissoes
                    v-else
                    :acesso="acessoSelecionado"
                    :indice="indiceAcessoSelecionado >= 0 ? indiceAcessoSelecionado : null"
                    :erros="form.errors"
                    :sistemas="sistemas"
                    :catalogo="catalogo"
                    :administrador-global="form.administrador_global"
                    @update:acesso="atualizarAcessoSelecionado"
                />

                <div class="card-footer">
                    <Link href="/usuario"><Botao variante="secondary" type="button">Cancelar</Botao></Link>
                    <Botao variante="primary" type="submit" :loading="form.processing">Salvar alterações</Botao>
                </div>
            </form>
        </section>
    </AppLayout>
</template>
