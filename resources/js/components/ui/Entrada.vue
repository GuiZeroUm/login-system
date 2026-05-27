<script setup lang="ts">
const model = defineModel<string | number | null>()

withDefaults(
    defineProps<{
        id?: string
        type?: string
        placeholder?: string
        autocomplete?: string
        error?: boolean
    }>(),
    {
        type: 'text',
        placeholder: '',
        autocomplete: '',
        error: false,
    },
)
</script>

<template>
    <input
        :id="id"
        v-model="model"
        :type="type"
        :placeholder="placeholder"
        :autocomplete="autocomplete"
        :class="['input', error ? 'error' : '']"
    />
</template>
