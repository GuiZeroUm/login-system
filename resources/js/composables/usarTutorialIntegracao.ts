import { computed, inject, provide, ref, type InjectionKey, type Ref } from 'vue'
import { secoesTutorialIntegracao } from '@/data/tutorialIntegracao'

export type TutorialIntegracaoApi = {
    aberto: Ref<boolean>
    secaoAtual: Ref<number>
    passoAtual: Ref<number>
    secao: Ref<(typeof secoesTutorialIntegracao)[number]>
    passo: Ref<(typeof secoesTutorialIntegracao)[number]['passos'][number]>
    indiceGlobal: Ref<number>
    totalPassos: number
    podeVoltar: Ref<boolean>
    podeAvancar: Ref<boolean>
    abrir: () => void
    fechar: () => void
    irParaSecao: (indice: number) => void
    irParaPasso: (indice: number) => void
    avancar: () => void
    voltar: () => void
}

const chaveTutorialIntegracao: InjectionKey<TutorialIntegracaoApi> = Symbol('tutorialIntegracao')

function criarEstado(): TutorialIntegracaoApi {
    const aberto = ref(false)
    const secaoAtual = ref(0)
    const passoAtual = ref(0)

    const secao = computed(() => secoesTutorialIntegracao[secaoAtual.value])
    const passo = computed(() => secao.value.passos[passoAtual.value])

    const indiceGlobal = computed(() => {
        let indice = 0
        for (let s = 0; s < secaoAtual.value; s++) {
            indice += secoesTutorialIntegracao[s].passos.length
        }
        return indice + passoAtual.value
    })

    const totalPassos = secoesTutorialIntegracao.reduce((acc, item) => acc + item.passos.length, 0)

    const podeVoltar = computed(() => secaoAtual.value > 0 || passoAtual.value > 0)
    const podeAvancar = computed(() => {
        const ultimaSecao = secoesTutorialIntegracao.length - 1
        const ultimoPasso = secoesTutorialIntegracao[ultimaSecao].passos.length - 1
        return secaoAtual.value < ultimaSecao || passoAtual.value < ultimoPasso
    })

    function abrir(): void {
        secaoAtual.value = 0
        passoAtual.value = 0
        aberto.value = true
    }

    function fechar(): void {
        aberto.value = false
    }

    function irParaSecao(indice: number): void {
        if (indice < 0 || indice >= secoesTutorialIntegracao.length) {
            return
        }
        secaoAtual.value = indice
        passoAtual.value = 0
    }

    function irParaPasso(indice: number): void {
        if (indice < 0 || indice >= secao.value.passos.length) {
            return
        }
        passoAtual.value = indice
    }

    function avancar(): void {
        if (passoAtual.value < secao.value.passos.length - 1) {
            passoAtual.value++
            return
        }
        if (secaoAtual.value < secoesTutorialIntegracao.length - 1) {
            secaoAtual.value++
            passoAtual.value = 0
        }
    }

    function voltar(): void {
        if (passoAtual.value > 0) {
            passoAtual.value--
            return
        }
        if (secaoAtual.value > 0) {
            secaoAtual.value--
            passoAtual.value = secoesTutorialIntegracao[secaoAtual.value].passos.length - 1
        }
    }

    return {
        aberto,
        secaoAtual,
        passoAtual,
        secao,
        passo,
        indiceGlobal,
        totalPassos,
        podeVoltar,
        podeAvancar,
        abrir,
        fechar,
        irParaSecao,
        irParaPasso,
        avancar,
        voltar,
    }
}

export function provideTutorialIntegracao(): TutorialIntegracaoApi {
    const api = criarEstado()
    provide(chaveTutorialIntegracao, api)
    return api
}

export function usarTutorialIntegracao(): TutorialIntegracaoApi {
    const api = inject(chaveTutorialIntegracao)
    if (!api) {
        throw new Error('usarTutorialIntegracao deve ser usado dentro de AppLayout (provideTutorialIntegracao).')
    }
    return api
}
