<script setup lang="ts">
import AuthLayout from '@/components/Layout/AuthLayout.vue'
import { Head, Link } from '@inertiajs/vue3'

defineProps<{
    slug: string
}>()
</script>

<template>
    <AuthLayout title="Sistema não encontrado">
        <Head title="Sistema não encontrado" />
        <div
            class="mx-auto max-w-md rounded-xl border border-white/10 bg-[#141516] p-8 text-center text-[#ececee]"
            style="margin-top: 10vh"
        >
            <h1 class="mb-2 text-lg font-semibold">Sistema não encontrado</h1>
            <p class="mb-4 text-sm text-white/60">
                Não existe sistema ativo com o slug
                <code class="rounded bg-white/10 px-1.5 py-0.5 text-white/80">{{ slug }}</code>.
            </p>
            <p class="mb-6 text-sm text-white/50">
                Confira se o slug cadastrado em <strong>Sistemas</strong> é igual ao
                <code class="text-white/70">ACL_SLUG</code> do aplicativo cliente.
            </p>
            <Link
                href="/login"
                class="inline-block rounded-md bg-[#5e6ad2] px-4 py-2 text-sm font-medium text-white hover:opacity-90"
            >
                Ir para login do painel
            </Link>
        </div>
    </AuthLayout>
</template>
