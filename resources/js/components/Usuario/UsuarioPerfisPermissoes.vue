<script setup lang="ts">
import CheckboxCampo from '@/components/ui/CheckboxCampo.vue'
import type { AcessoForm, CatalogoSistema, SistemaOpcao } from '@/types/usuario'
import { computed } from 'vue'

const props = defineProps<{
    acesso: AcessoForm | null
    indice: number | null
    erros?: Record<string, string>
    sistemas: SistemaOpcao[]
    catalogo: Record<number, CatalogoSistema>
    administradorGlobal: boolean
}>()

const emit = defineEmits<{
    'update:acesso': [value: AcessoForm]
}>()

function erroCampo(campo: string): string | undefined {
    if (props.indice === null || !props.erros) {
        return undefined
    }

    return props.erros[`acessos.${props.indice}.${campo}`]
}

const catalogoAtual = computed(() =>
    props.acesso?.sistema_id ? props.catalogo[props.acesso.sistema_id] : null,
)

const nomeSistema = computed(() => {
    if (!props.acesso?.sistema_id) {
        return null
    }

    const s = props.sistemas.find((x) => x.id === props.acesso!.sistema_id)

    return s ? `${s.nome} (${s.slug})` : null
})

function atualizar(partial: Partial<AcessoForm>) {
    if (!props.acesso) {
        return
    }

    emit('update:acesso', { ...props.acesso, ...partial })
}

function alternarPerfil(roleId: number) {
    if (!props.acesso) {
        return
    }

    const ids = [...props.acesso.perfis_ids]
    const idx = ids.indexOf(roleId)

    if (idx >= 0) {
        ids.splice(idx, 1)
    } else {
        ids.push(roleId)
    }

    atualizar({ perfis_ids: ids })
}

function possuiPermissao(permissionId: number, tipo: number): boolean {
    return (
        props.acesso?.permissoes.some(
            (p) => p.permission_id === permissionId && p.tipo === tipo,
        ) ?? false
    )
}

function alternarPermissao(permissionId: number, tipo: number) {
    if (!props.acesso) {
        return
    }

    const lista = [...props.acesso.permissoes]
    const idx = lista.findIndex((p) => p.permission_id === permissionId && p.tipo === tipo)

    if (idx >= 0) {
        lista.splice(idx, 1)
    } else {
        lista.push({ permission_id: permissionId, tipo })
    }

    atualizar({ permissoes: lista })
}
</script>

<template>
    <div>
        <template v-if="administradorGlobal">
            <p class="t-caption">Usuário administrador global — perfis e permissões por sistema não se aplicam.</p>
        </template>

        <template v-else-if="!acesso || !acesso.sistema_id">
            <p class="t-caption">
                Selecione um sistema na aba «Sistemas» para configurar perfis e permissões.
            </p>
        </template>

        <template v-else>
            <div class="mb-md">
                <div class="t-card-title">{{ nomeSistema }}</div>
                <div class="t-caption">Perfis e permissões apenas neste sistema.</div>
            </div>

            <div
                style="background: var(--surface-2); border: 1px solid var(--hairline); border-radius: 10px; padding: 14px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center"
            >
                <div>
                    <div class="t-card-title">Administrador do sistema</div>
                    <div class="t-caption">Acesso total apenas neste sistema (não é global).</div>
                </div>
                <CheckboxCampo
                    :model-value="acesso.administrador_sistema"
                    rotulo=""
                    @update:model-value="atualizar({ administrador_sistema: $event })"
                />
            </div>

            <template v-if="catalogoAtual">
                <div class="t-eyebrow">PERFIS DISPONÍVEIS NESTE SISTEMA</div>
                <div class="row mt-sm" style="flex-wrap: wrap; gap: 8px">
                    <button
                        v-for="role in catalogoAtual.roles"
                        :key="role.id"
                        type="button"
                        class="badge"
                        :class="acesso.perfis_ids.includes(role.id) ? 'badge-info' : ''"
                        style="cursor: pointer; border: 1px solid var(--hairline)"
                        @click="alternarPerfil(role.id)"
                    >
                        {{ role.name }}
                    </button>
                    <span v-if="catalogoAtual.roles.length === 0" class="t-caption">Nenhum perfil cadastrado.</span>
                </div>
                <p v-if="erroCampo('perfis_ids')" class="t-caption" style="color: #e5534b; margin-top: 6px">
                    {{ erroCampo('perfis_ids') }}
                </p>

                <div class="perm-tier mt-md">
                    <div class="perm-tier-head">
                        <div class="name">Permissões</div>
                    </div>
                    <div class="perm-tier-body">
                        <div class="perm-grid">
                            <div
                                v-for="permissao in catalogoAtual.permissions"
                                :key="permissao.id"
                                class="perm-item"
                            >
                                <div class="perm-name">{{ permissao.name }}</div>
                                <div class="perm-ops">
                                    <template v-if="permissao.tipo_crud === 'S'">
                                        <button
                                            v-for="op in [
                                                { label: 'Adicionar', tipo: 1 },
                                                { label: 'Editar', tipo: 2 },
                                                { label: 'Excluir', tipo: 3 },
                                                { label: 'Visualizar', tipo: 4 },
                                            ]"
                                            :key="op.tipo"
                                            type="button"
                                            class="btn btn-secondary btn-sm"
                                            :style="
                                                possuiPermissao(permissao.id, op.tipo)
                                                    ? 'border-color: var(--primary); color: var(--primary)'
                                                    : ''
                                            "
                                            @click="alternarPermissao(permissao.id, op.tipo)"
                                        >
                                            {{ op.label }}
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button
                                            type="button"
                                            class="btn btn-secondary btn-sm"
                                            :style="
                                                possuiPermissao(permissao.id, 0)
                                                    ? 'border-color: var(--primary); color: var(--primary)'
                                                    : ''
                                            "
                                            @click="alternarPermissao(permissao.id, 0)"
                                        >
                                            Acessar
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <p v-if="catalogoAtual.permissions.length === 0" class="t-caption mt-sm">
                            Sincronize as permissões do sistema para exibir o catálogo.
                        </p>
                        <p
                            v-else-if="
                                !acesso.administrador_sistema &&
                                acesso.perfis_ids.length === 0 &&
                                acesso.permissoes.length === 0
                            "
                            class="t-caption mt-sm"
                        >
                            Sem perfis ou permissões selecionados: o usuário terá acesso somente leitura neste sistema.
                        </p>
                    </div>
                </div>
            </template>
        </template>
    </div>
</template>
