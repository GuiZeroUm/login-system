<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import Breadcrumb from '@/components/Breadcrumb.vue'
import AppLayout from '@/components/Layout/AppLayout.vue'
import AbasNav from '@/components/ui/AbasNav.vue'
import BadgePill from '@/components/ui/BadgePill.vue'
import Botao from '@/components/ui/Botao.vue'
import Campo from '@/components/ui/Campo.vue'
import CheckboxCampo from '@/components/ui/CheckboxCampo.vue'
import Entrada from '@/components/ui/Entrada.vue'
import Icone from '@/components/ui/Icone.vue'
import LoginTela, { type LoginTelaConfig } from '@/components/Sistema/LoginTela.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import { useAsset } from '@/composables/useAsset'
import type { Sistema } from '@/types'

type PermissaoCatalogo = {
    id: number
    name: string
    tipo_crud: 'S' | 'N'
}

const props = defineProps<{
    dados?: Sistema | null
    params?: { aba?: string }
    catalogoPermissoes?: PermissaoCatalogo[]
    orgaosDisponiveis?: Array<{ id: number; descricao_orgao: string }>
    hubUrl?: string
}>()

const abaAtiva = ref(props.params?.aba ?? 'dados')
const { storage } = useAsset()
const perfilSelecionadoIndex = ref(0)

const form = useForm({
    _method: props.dados?.id ? 'put' : 'post',
    nome: props.dados?.nome ?? '',
    slug: props.dados?.slug ?? '',
    url: props.dados?.url ?? '',
    url_logout: props.dados?.url_logout ?? '',
    ambiente: props.dados?.ambiente ?? 'desenvolvimento',
    descricao: props.dados?.descricao ?? '',
    login_nome: props.dados?.login_nome ?? props.dados?.nome ?? '',
    tema_login: props.dados?.tema_login ?? 'escuro',
    login_subtitulo: props.dados?.login_subtitulo ?? 'Use sua conta corporativa para acessar.',
    login_painel_eyebrow: props.dados?.login_painel_eyebrow ?? 'VOCÊ ESTÁ ENTRANDO EM',
    login_painel_titulo: props.dados?.login_painel_titulo ?? props.dados?.nome ?? '',
    login_painel_descricao: props.dados?.login_painel_descricao ?? props.dados?.descricao ?? '',
    exibir_logo_topo: props.dados?.exibir_logo_topo ?? true,
    exibir_bloco_inferior: props.dados?.exibir_bloco_inferior ?? true,
    exibir_degrade_ilustracao: props.dados?.exibir_degrade_ilustracao ?? true,
    ativo: props.dados?.ativo ?? true,
    caminho_logo: props.dados?.caminho_logo ?? null,
    caminho_ilustracao: props.dados?.caminho_ilustracao ?? null,
    upload_caminho_logo: null as File | null,
    upload_caminho_ilustracao: null as File | null,
    banco: {
        tipo: props.dados?.banco?.tipo ?? 'postgresql',
        host: props.dados?.banco?.host ?? '',
        porta: String(props.dados?.banco?.porta ?? 5432),
        nome_banco: props.dados?.banco?.nome_banco ?? '',
        usuario: props.dados?.banco?.usuario ?? '',
        senha: '',
    },
    perfis: props.dados?.perfis ?? [],
    orgaos_ids: props.dados?.orgaos_ids ?? [],
})

const titulo = computed(() => (props.dados?.id ? 'Editar sistema' : 'Novo sistema'))
const breadcrumbTitulo = computed(() => (props.dados?.id ? `Editar ${props.dados.nome}` : 'Novo sistema'))
const catalogoPermissoes = computed(() => props.catalogoPermissoes ?? [])
const orgaosDisponiveis = computed(() => props.orgaosDisponiveis ?? [])
const previewLogo = computed(() => {
    if (form.upload_caminho_logo) {
        return URL.createObjectURL(form.upload_caminho_logo)
    }
    if (props.dados?.caminho_logo) {
        return storage(props.dados.caminho_logo, 'assets/images/autenticacao/meuac.png')
    }
    return null
})
const previewIlustracao = computed(() => {
    if (form.upload_caminho_ilustracao) {
        return URL.createObjectURL(form.upload_caminho_ilustracao)
    }
    if (props.dados?.caminho_ilustracao) {
        return storage(props.dados.caminho_ilustracao, 'assets/images/autenticacao/ilustracao.png')
    }
    return null
})

const hubUrlBase = computed(() => props.hubUrl ?? window.location.origin)

const urlLoginIntegracao = computed(
    () => `${hubUrlBase.value}/login/${form.slug || 'seu-slug'}`,
)

const previewLoginConfig = computed<LoginTelaConfig>(() => ({
    nome: form.nome || 'Nome do sistema',
    descricao: form.descricao,
    login_nome: form.login_nome,
    tema_login: form.tema_login,
    caminho_logo: props.dados?.caminho_logo ?? null,
    caminho_ilustracao: props.dados?.caminho_ilustracao ?? null,
    login_subtitulo: form.login_subtitulo,
    login_painel_eyebrow: form.login_painel_eyebrow,
    login_painel_titulo: form.login_painel_titulo || form.nome,
    login_painel_descricao: form.login_painel_descricao || form.descricao,
    exibir_logo_topo: form.exibir_logo_topo,
    exibir_bloco_inferior: form.exibir_bloco_inferior,
    exibir_degrade_ilustracao: form.exibir_degrade_ilustracao,
}))

function bancoPreenchido(data: { host: string; nome_banco: string; usuario: string }): boolean {
    return [data.host, data.nome_banco, data.usuario].some((value) => String(value).trim() !== '')
}

function submit() {
    if (abaAtiva.value === 'login' && props.dados?.id) {
        router.post(
            `/sistema/${props.dados.id}/personalizacao`,
            {
                _method: 'patch',
                upload_caminho_logo: form.upload_caminho_logo,
                upload_caminho_ilustracao: form.upload_caminho_ilustracao,
                login_nome: form.login_nome,
                tema_login: form.tema_login,
                login_subtitulo: form.login_subtitulo,
                login_painel_eyebrow: form.login_painel_eyebrow,
                login_painel_titulo: form.login_painel_titulo,
                login_painel_descricao: form.login_painel_descricao,
                exibir_logo_topo: form.exibir_logo_topo ? 1 : 0,
                exibir_bloco_inferior: form.exibir_bloco_inferior ? 1 : 0,
                exibir_degrade_ilustracao: form.exibir_degrade_ilustracao ? 1 : 0,
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onError: () => {
                    abaAtiva.value = 'login'
                },
            },
        )
        return
    }

    form.transform((data) => ({
        ...data,
        banco: bancoPreenchido(data.banco)
            ? {
                  ...data.banco,
                  porta: Number(data.banco.porta),
              }
            : null,
    }))

    if (props.dados?.id) {
        form.post(`/sistema/${props.dados.id}`, {
            forceFormData: true,
            onError: irParaAbaComErro,
        })
    } else {
        form.post('/sistema', {
            forceFormData: true,
            onError: irParaAbaComErro,
        })
    }
}

watch(
    () => form.nome,
    (nome, anterior) => {
        if (!form.slug || form.slug === slugify(anterior ?? '')) {
            form.slug = slugify(nome)
        }
    },
)

function slugify(texto: string): string {
    return texto.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
}

function adicionarPerfil(): void {
    form.perfis.push({
        id: Math.floor(Math.random() * 1000000) * -1,
        name: 'Novo perfil',
        permissoes: [],
    })
}

function removerPerfil(idx: number): void {
    form.perfis.splice(idx, 1)
    if (perfilSelecionadoIndex.value >= form.perfis.length) {
        perfilSelecionadoIndex.value = Math.max(0, form.perfis.length - 1)
    }
}

function alternarPermissao(perfilIndex: number, permissionId: number, tipo: number): void {
    const perfil = form.perfis[perfilIndex]
    if (!perfil) {
        return
    }
    const idx = perfil.permissoes.findIndex((item) => item.permission_id === permissionId && item.tipo === tipo)
    if (idx >= 0) {
        perfil.permissoes.splice(idx, 1)
        return
    }
    perfil.permissoes.push({ permission_id: permissionId, tipo })
}

function alternarOrgao(orgaoId: number): void {
    const idx = form.orgaos_ids.indexOf(orgaoId)
    if (idx >= 0) {
        form.orgaos_ids.splice(idx, 1)
        return
    }
    form.orgaos_ids.push(orgaoId)
}

function sincronizarPermissoes(): void {
    if (!props.dados?.id) {
        return
    }

    router.post(`/sistema/${props.dados.id}/sincronizar-permissoes`)
}

const testandoBanco = ref(false)
const resultadoTesteBanco = ref<{ ok: boolean; message: string } | null>(null)

function primeiraAbaComErro(): string | null {
    const chaves = Object.keys(form.errors)
    if (chaves.some((k) => ['nome', 'slug', 'url', 'ambiente', 'descricao', 'ativo'].includes(k))) {
        return 'dados'
    }
    if (chaves.some((k) => k.startsWith('banco.'))) {
        return 'banco'
    }
    if (chaves.some((k) => k.startsWith('upload_caminho'))) {
        return 'login'
    }
    if (chaves.some((k) => k.startsWith('perfis.'))) {
        return 'perfis'
    }
    if (chaves.some((k) => k.startsWith('orgaos_ids'))) {
        return 'relacionamento'
    }
    return null
}

function irParaAbaComErro(): void {
    const aba = primeiraAbaComErro()
    if (aba) {
        abaAtiva.value = aba
    }
}

async function testarConexaoBanco(): Promise<void> {
    resultadoTesteBanco.value = null

    if (!bancoPreenchido(form.banco)) {
        resultadoTesteBanco.value = {
            ok: false,
            message: 'Preencha host, nome do banco e usuário antes de testar.',
        }
        return
    }

    testandoBanco.value = true

    try {
        const xsrf = decodeURIComponent(
            document.cookie
                .split('; ')
                .find((row) => row.startsWith('XSRF-TOKEN='))
                ?.split('=')[1] ?? '',
        )

        const response = await fetch('/sistema/testar-banco', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': xsrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                banco: {
                    ...form.banco,
                    porta: Number(form.banco.porta) || 5432,
                },
                sistema_id: props.dados?.id ?? null,
            }),
        })

        const data = (await response.json()) as { ok: boolean; message: string }
        resultadoTesteBanco.value = { ok: data.ok, message: data.message }
    } catch {
        resultadoTesteBanco.value = {
            ok: false,
            message: 'Não foi possível executar o teste. Tente novamente.',
        }
    } finally {
        testandoBanco.value = false
    }
}

const abas = computed(() => {
    const base = [
        { id: 'dados', label: 'Sistema' },
        { id: 'login', label: 'Tela de login' },
        { id: 'banco', label: 'Banco de dados' },
    ]
    if (props.dados?.id) {
        base.push({ id: 'perfis', label: 'Perfis' }, { id: 'relacionamento', label: 'Relacionamento' })
    }
    return base
})
</script>

<template>
    <AppLayout :title="titulo">
        <Breadcrumb :titulo="breadcrumbTitulo" link="/sistema" link-titulo="Sistemas" />
        <section class="card">
            <div class="card-header">
                <div class="titles">
                    <h1>{{ titulo }}</h1>
                    <p>Atualize dados, banco, identidade visual e perfis.</p>
                </div>
            </div>

            <AbasNav :abas="abas" :ativa="abaAtiva" @selecionar="(id) => (abaAtiva = id)" />

            <div
                v-if="Object.keys(form.errors).length"
                class="mx-4 mt-4 rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200"
                role="alert"
            >
                <strong>Não foi possível salvar.</strong>
                Verifique os campos destacados
                <template v-if="primeiraAbaComErro()"> na aba correspondente</template>.
            </div>

            <div class="card-body">
                <form v-if="abaAtiva === 'dados'" class="cols-2" @submit.prevent="submit">
                    <Campo rotulo="Nome" obrigatorio>
                        <Entrada v-model="form.nome" placeholder="Sistema Financeiro" :error="!!form.errors.nome" />
                    </Campo>
                    <Campo rotulo="Slug" obrigatorio>
                        <Entrada v-model="form.slug" placeholder="financeiro" :error="!!form.errors.slug" />
                        <div class="t-caption" style="margin-top: 6px">
                            Deve ser idêntico ao <code>ACL_SLUG</code> do sistema cliente.
                        </div>
                    </Campo>
                    <Campo rotulo="URL de callback" obrigatorio>
                        <Entrada v-model="form.url" placeholder="http://app.test/login" :error="!!form.errors.url" />
                        <div class="t-caption" style="margin-top: 6px">
                            Rota do app que recebe <code>?callback=</code> após o login (ex.: <code>http://teste.test/login</code>).
                        </div>
                    </Campo>
                    <Campo rotulo="URL de logout">
                        <Entrada v-model="form.url_logout" placeholder="http://app.test/logout" :error="!!form.errors.url_logout" />
                    </Campo>
                    <div style="grid-column: 1 / -1" class="rounded-lg border border-white/10 bg-white/3 px-3 py-2">
                        <div class="t-caption" style="margin-bottom: 4px">URL de login no Login Universal (somente leitura)</div>
                        <code class="text-sm" style="word-break: break-all">{{ urlLoginIntegracao }}</code>
                    </div>
                    <Campo rotulo="Ambiente" obrigatorio>
                        <SelectInput
                            v-model="form.ambiente"
                            :opcoes="[
                                { label: 'Produção', value: 'production' },
                                { label: 'Homologação', value: 'homologacao' },
                                { label: 'Desenvolvimento', value: 'desenvolvimento' },
                            ]"
                        />
                    </Campo>
                    <Campo rotulo="Descrição">
                        <textarea v-model="form.descricao" class="textarea" placeholder="Descrição do sistema..." />
                    </Campo>
                    <div style="grid-column: 1 / -1">
                        <CheckboxCampo v-model="form.ativo" rotulo="Ativo — sistema aceita logins" />
                    </div>
                </form>

                <div v-else-if="abaAtiva === 'login'">
                    <div style="margin: -16px -16px 16px; border-bottom: 1px solid var(--hairline); overflow: hidden">
                        <LoginTela
                            :config="previewLoginConfig"
                            :preview-logo="previewLogo"
                            :preview-ilustracao="previewIlustracao"
                            style="min-height: 520px; height: 520px"
                        />
                    </div>
                    <div class="cols-2" style="margin-bottom: 16px">
                        <Campo rotulo="Subtítulo do formulário">
                            <Entrada v-model="form.login_subtitulo" placeholder="Use sua conta corporativa para acessar." />
                        </Campo>
                        <Campo rotulo="Nome exibido no login">
                            <Entrada v-model="form.login_nome" placeholder="Login Universal" />
                        </Campo>
                        <Campo rotulo="Eyebrow do painel esquerdo">
                            <Entrada v-model="form.login_painel_eyebrow" placeholder="VOCÊ ESTÁ ENTRANDO EM" />
                        </Campo>
                        <Campo rotulo="Título do painel esquerdo">
                            <Entrada v-model="form.login_painel_titulo" placeholder="Nome do sistema" />
                        </Campo>
                        <Campo rotulo="Descrição do painel esquerdo" style="grid-column: 1 / -1">
                            <textarea v-model="form.login_painel_descricao" class="textarea" placeholder="Descrição exibida no painel esquerdo..." />
                        </Campo>
                        <Campo rotulo="Tema da tela de login">
                            <SelectInput
                                v-model="form.tema_login"
                                :opcoes="[
                                    { label: 'Escuro', value: 'escuro' },
                                    { label: 'Claro', value: 'claro' },
                                ]"
                            />
                        </Campo>
                        <div style="display: flex; flex-direction: column; gap: 10px; justify-content: center">
                            <CheckboxCampo v-model="form.exibir_logo_topo" rotulo="Exibir logo e nome no topo da ilustração" />
                            <CheckboxCampo v-model="form.exibir_bloco_inferior" rotulo="Exibir bloco de texto inferior na ilustração" />
                            <CheckboxCampo v-model="form.exibir_degrade_ilustracao" rotulo="Exibir degradê sobre a ilustração" />
                        </div>
                    </div>
                    <div class="upload-box">
                        <div class="upload-preview">
                            <img v-if="previewLogo" :src="previewLogo" alt="Logo do sistema">
                            <span v-else>Logo do sistema</span>
                        </div>
                        <div>
                            <div class="t-card-title">Logo do sistema</div>
                            <div class="t-caption">Exibido acima do formulário de login. PNG/JPG/SVG.</div>
                            <div v-if="form.errors.upload_caminho_logo" class="t-caption" style="color: var(--danger); margin-top: 6px">
                                {{ form.errors.upload_caminho_logo }}
                            </div>
                            <input type="file" class="input mt-md" @change="(e) => form.upload_caminho_logo = (e.target as HTMLInputElement).files?.[0] ?? null">
                        </div>
                    </div>
                    <div class="upload-box mt-md">
                        <div class="upload-preview">
                            <img v-if="previewIlustracao" :src="previewIlustracao" alt="Ilustração do sistema">
                            <span v-else>Ilustração (fundo)</span>
                        </div>
                        <div>
                            <div class="t-card-title">Ilustração do painel esquerdo</div>
                            <div class="t-caption">Recomendado 800x600px.</div>
                            <div v-if="form.errors.upload_caminho_ilustracao" class="t-caption" style="color: var(--danger); margin-top: 6px">
                                {{ form.errors.upload_caminho_ilustracao }}
                            </div>
                            <input type="file" class="input mt-md" @change="(e) => form.upload_caminho_ilustracao = (e.target as HTMLInputElement).files?.[0] ?? null">
                        </div>
                    </div>
                </div>

                <div v-else-if="abaAtiva === 'banco'" class="cols-2">
                    <div style="grid-column: 1 / -1; padding: 12px; border: 1px solid rgba(94,105,210,0.3); border-radius: 8px; background: rgba(94,105,210,0.08)">
                        <div class="t-body-sm">Estas credenciais permitem ao Login Universal sincronizar permissões deste sistema.</div>
                    </div>
                    <Campo rotulo="Tipo de conexão" obrigatorio>
                        <SelectInput v-model="form.banco.tipo" :opcoes="[{label:'PostgreSQL',value:'postgresql'}]" />
                    </Campo>
                    <Campo rotulo="Endereço do host" obrigatorio>
                        <Entrada v-model="form.banco.host" placeholder="localhost" :error="!!form.errors['banco.host']" />
                        <div class="t-caption" style="margin-top: 6px">
                            IP ou hostname do servidor PostgreSQL do sistema externo (ex.: <code>localhost</code>,
                            <code>127.0.0.1</code>, <code>lerd-postgres</code> em Docker).
                        </div>
                    </Campo>
                    <Campo rotulo="Porta" obrigatorio>
                        <Entrada v-model="form.banco.porta" :error="!!form.errors['banco.porta']" />
                    </Campo>
                    <Campo rotulo="Nome do banco" obrigatorio>
                        <Entrada v-model="form.banco.nome_banco" placeholder="nome_do_banco" :error="!!form.errors['banco.nome_banco']" />
                        <div class="t-caption" style="margin-top: 6px">Nome do database PostgreSQL, não o usuário.</div>
                    </Campo>
                    <Campo rotulo="Usuário" obrigatorio>
                        <Entrada v-model="form.banco.usuario" :error="!!form.errors['banco.usuario']" />
                    </Campo>
                    <Campo rotulo="Senha"><Entrada v-model="form.banco.senha" type="password" /></Campo>
                    <div style="grid-column: 1 / -1; display: flex; justify-content: space-between">
                        <div>
                            <div class="t-card-title">Testar conexão</div>
                            <div class="t-caption">Tenta se conectar ao banco com as credenciais acima.</div>
                        </div>
                        <Botao
                            variante="secondary"
                            icone="activity"
                            type="button"
                            :loading="testandoBanco"
                            @click="testarConexaoBanco"
                        >
                            Testar agora
                        </Botao>
                    </div>
                    <div
                        v-if="resultadoTesteBanco"
                        style="grid-column: 1 / -1"
                        class="rounded-lg px-3 py-2 text-sm"
                        :class="resultadoTesteBanco.ok ? 'bg-emerald-500/10 text-emerald-200' : 'bg-red-500/10 text-red-200'"
                    >
                        {{ resultadoTesteBanco.message }}
                    </div>
                </div>

                <div v-else-if="abaAtiva === 'perfis'" style="display: grid; grid-template-columns: 280px 1fr; gap: 16px">
                    <div class="perm-tier">
                        <div class="perm-tier-head">
                            <div class="name">Perfis</div>
                            <Botao variante="tertiary" tamanho="sm" icone="plus" type="button" @click="adicionarPerfil">Novo</Botao>
                        </div>
                        <div class="perm-tier-body">
                            <div v-if="form.perfis.length === 0" class="t-caption">Nenhum perfil cadastrado.</div>
                                <div
                                    v-for="(perfil, idx) in form.perfis"
                                    :key="`${perfil.id}-${idx}`"
                                    class="perm-item"
                                    :style="`margin-bottom: 8px; border-color: ${perfilSelecionadoIndex === idx ? 'var(--primary)' : 'var(--hairline)'}`"
                                    @click="perfilSelecionadoIndex = idx"
                                >
                                <div class="row-between">
                                    <input v-model="perfil.name" class="input" style="height: 32px">
                                    <button class="btn-icon" type="button" @click="removerPerfil(idx)">
                                        <Icone nome="trash" :tamanho="14" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="perm-tier">
                        <div class="perm-tier-head">
                            <div class="name">Permissões</div>
                            <Botao variante="secondary" tamanho="sm" icone="refresh" type="button" @click="sincronizarPermissoes">
                                Sincronizar
                            </Botao>
                        </div>
                        <div class="perm-tier-body">
                            <div class="perm-grid">
                                <div v-for="permissao in catalogoPermissoes" :key="permissao.id" class="perm-item">
                                    <div class="perm-name">{{ permissao.name }}</div>
                                    <div class="perm-ops">
                                        <template v-if="permissao.tipo_crud === 'S'">
                                            <button
                                                v-for="op in [
                                                    { label: 'Criar', tipo: 1 },
                                                    { label: 'Editar', tipo: 2 },
                                                    { label: 'Excluir', tipo: 3 },
                                                    { label: 'Visualizar', tipo: 4 },
                                                ]"
                                                :key="op.tipo"
                                                type="button"
                                                class="btn btn-secondary btn-sm"
                                                @click="alternarPermissao(perfilSelecionadoIndex, permissao.id, op.tipo)"
                                            >
                                                {{ op.label }}
                                            </button>
                                        </template>
                                        <template v-else>
                                            <button type="button" class="btn btn-secondary btn-sm" @click="alternarPermissao(perfilSelecionadoIndex, permissao.id, 0)">Acessar</button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="perm-tier">
                    <div class="perm-tier-head">
                        <div class="name">Relacionamento com órgãos</div>
                    </div>
                    <div class="perm-tier-body">
                        <div v-if="orgaosDisponiveis.length === 0" class="t-caption">Nenhum órgão disponível.</div>
                        <div v-for="orgao in orgaosDisponiveis" :key="orgao.id" class="row" style="margin-bottom: 8px">
                            <label class="checkbox">
                                <input
                                    type="checkbox"
                                    :checked="form.orgaos_ids.includes(orgao.id)"
                                    @change="alternarOrgao(orgao.id)"
                                >
                                <span class="box">
                                    <svg width="10" height="10" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="1.5,6 5,9.5 10.5,2.5" />
                                    </svg>
                                </span>
                                <span>{{ orgao.descricao_orgao }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <a href="/sistema"><Botao variante="secondary">Cancelar</Botao></a>
                <Botao variante="primary" :loading="form.processing" @click="submit">Salvar alterações</Botao>
            </div>
        </section>
    </AppLayout>
</template>
