<script setup lang="ts">
import Botao from '@/components/ui/Botao.vue'
import CheckboxCampo from '@/components/ui/CheckboxCampo.vue'
import Icone from '@/components/ui/Icone.vue'
import SelectInput from '@/components/ui/SelectInput.vue'
import type { AcessoForm, SistemaOpcao } from '@/types/usuario'
import { computed, ref } from 'vue'

const props = defineProps<{
    acessos: AcessoForm[]
    sistemas: SistemaOpcao[]
    administradorGlobal: boolean
    sistemaSelecionadoId: number | null
    erroAcessos?: string
}>()

const emit = defineEmits<{
    'update:administradorGlobal': [value: boolean]
    'update:sistemaSelecionadoId': [id: number | null]
    adicionar: [sistemaId: number]
    remover: [index: number]
    configurar: [sistemaId: number]
}>()

const sistemaParaAdicionar = ref<number | ''>('')

const sistemasDisponiveis = computed(() => {
    const idsUsados = new Set(props.acessos.map((a) => a.sistema_id).filter(Boolean))

    return props.sistemas.filter((s) => !idsUsados.has(s.id))
})

function nomeSistema(sistemaId: number | null): string {
    if (!sistemaId) {
        return 'Sistema'
    }

    const s = props.sistemas.find((x) => x.id === sistemaId)

    return s ? `${s.nome} (${s.slug})` : `Sistema #${sistemaId}`
}

function adicionar() {
    if (sistemaParaAdicionar.value === '') {
        return
    }

    emit('adicionar', Number(sistemaParaAdicionar.value))
    sistemaParaAdicionar.value = ''
}

function selecionar(sistemaId: number) {
    emit('update:sistemaSelecionadoId', sistemaId)
    emit('configurar', sistemaId)
}
</script>

<template>
    <div>
        <div
            style="background: var(--surface-2); border: 1px solid var(--hairline); border-radius: 10px; padding: 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center"
        >
            <div>
                <div class="t-card-title">Administrador global</div>
                <div class="t-caption">Concede acesso total a todos os sistemas conectados.</div>
            </div>
            <CheckboxCampo
                :model-value="administradorGlobal"
                rotulo=""
                @update:model-value="emit('update:administradorGlobal', $event)"
            />
        </div>

        <template v-if="!administradorGlobal">
            <p v-if="erroAcessos" class="t-caption" style="color: #e5534b; margin-bottom: 12px">
                {{ erroAcessos }}
            </p>

            <div class="row-between mb-md">
                <div>
                    <div class="t-card-title">Sistemas com acesso</div>
                    <div class="t-caption">Adicione ou remova sistemas. Clique em um sistema para configurar perfis e permissões.</div>
                </div>
            </div>

            <div class="row mb-md" style="gap: 8px; flex-wrap: wrap; align-items: end">
                <SelectInput
                    v-model="sistemaParaAdicionar"
                    style="min-width: 280px; flex: 1"
                    :opcoes="[
                        { label: 'Selecione um sistema', value: '' },
                        ...sistemasDisponiveis.map((s) => ({
                            label: `${s.nome} (${s.slug})`,
                            value: s.id,
                        })),
                    ]"
                />
                <Botao
                    variante="secondary"
                    icone="plus"
                    type="button"
                    :disabled="sistemaParaAdicionar === '' || sistemasDisponiveis.length === 0"
                    @click="adicionar"
                >
                    Adicionar sistema
                </Botao>
            </div>

            <div v-if="acessos.length === 0" class="t-caption">
                Nenhum sistema vinculado. Adicione ao menos um sistema ou marque administrador global.
            </div>

            <div
                v-for="(acesso, idx) in acessos"
                :key="acesso.sistema_id ?? idx"
                class="perm-tier"
                style="margin-bottom: 10px; cursor: pointer"
                :style="acesso.sistema_id === sistemaSelecionadoId ? 'border-color: var(--primary)' : ''"
                @click="acesso.sistema_id && selecionar(acesso.sistema_id)"
            >
                <div class="perm-tier-head">
                    <div class="name">{{ nomeSistema(acesso.sistema_id) }}</div>
                    <div class="row" style="gap: 8px; align-items: center">
                        <span v-if="acesso.administrador_sistema" class="badge badge-info">Admin do sistema</span>
                        <button
                            type="button"
                            class="btn-icon"
                            title="Remover sistema"
                            @click.stop="emit('remover', idx)"
                        >
                            <Icone nome="trash" :tamanho="14" />
                        </button>
                    </div>
                </div>
                <div class="perm-tier-body">
                    <p class="t-caption">
                        {{ acesso.perfis_ids.length }} perfil(is) · {{ acesso.permissoes.length }} permissão(ões) direta(s)
                    </p>
                </div>
            </div>
        </template>
    </div>
</template>
