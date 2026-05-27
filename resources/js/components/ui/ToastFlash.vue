<script setup lang="ts">
import Icone from '@/components/ui/Icone.vue'
import { usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const page = usePage<{ flash?: { success?: string; error?: string } }>()
const mensagem = ref('')
const tipo = ref<'success' | 'error' | 'info'>('info')

const flashSuccess = computed(() => page.props.flash?.success ?? '')
const flashError = computed(() => page.props.flash?.error ?? '')

watch([flashSuccess, flashError], ([ok, err]) => {
    if (ok) {
        mensagem.value = ok
        tipo.value = 'success'
    } else if (err) {
        mensagem.value = err
        tipo.value = 'error'
    } else {
        return
    }

    setTimeout(() => {
        mensagem.value = ''
    }, 2800)
}, { immediate: true })
</script>

<template>
    <div v-if="mensagem" class="toast-stack">
        <div class="toast" :class="tipo">
            <Icone class="ico" :nome="tipo === 'success' ? 'shield' : 'alert-circle'" :tamanho="18" />
            <span>{{ mensagem }}</span>
        </div>
    </div>
</template>
