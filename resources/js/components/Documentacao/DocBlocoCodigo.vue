<script setup lang="ts">
import { ref } from 'vue'
import type { DocCodigo } from '@/data/documentacao/tipos'

const props = defineProps<{
    bloco: DocCodigo
}>()

const copiado = ref(false)

function copiarComFallback(texto: string): boolean {
    const textarea = document.createElement('textarea')
    textarea.value = texto
    textarea.setAttribute('readonly', '')
    textarea.style.position = 'fixed'
    textarea.style.left = '-9999px'
    document.body.appendChild(textarea)
    textarea.select()
    const ok = document.execCommand('copy')
    document.body.removeChild(textarea)

    return ok
}

async function copiar() {
    const texto = props.bloco.conteudo

    try {
        if (window.isSecureContext && navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(texto)
        } else if (!copiarComFallback(texto)) {
            return
        }

        copiado.value = true
        setTimeout(() => {
            copiado.value = false
        }, 2000)
    } catch {
        if (copiarComFallback(texto)) {
            copiado.value = true
            setTimeout(() => {
                copiado.value = false
            }, 2000)
        }
    }
}
</script>

<template>
    <div class="tutorial-code doc-code-block">
        <div class="doc-code-header">
            <div class="tutorial-code-label">{{ bloco.rotulo }}</div>
            <button type="button" class="doc-copy-btn" @click="copiar">
                {{ copiado ? 'Copiado' : 'Copiar' }}
            </button>
        </div>
        <pre><code>{{ bloco.conteudo }}</code></pre>
    </div>
</template>
