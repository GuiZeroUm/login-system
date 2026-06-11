<script setup lang="ts">
import {
    Activity,
    AlertCircle,
    ArrowRight,
    BookOpen,
    Building2,
    ChevronDown,
    ChevronRight,
    CircleX,
    Database,
    Eye,
    EyeOff,
    Folder,
    Grid3X3,
    Image,
    Info,
    KeyRound,
    Link as LinkIcon,
    Monitor,
    Plus,
    RefreshCw,
    Search,
    Shield,
    Trash2,
    User,
    Users,
    X,
} from 'lucide-vue-next'
import { computed } from 'vue'

const props = withDefaults(
    defineProps<{
        nome: string
        tamanho?: number
        stroke?: number
    }>(),
    {
        tamanho: 16,
        stroke: 2,
    },
)

const mapa = {
    activity: Activity,
    'alert-circle': AlertCircle,
    'arrow-right': ArrowRight,
    book: BookOpen,
    building: Building2,
    'chevron-down': ChevronDown,
    'chevron-right': ChevronRight,
    close: X,
    database: Database,
    eye: Eye,
    'eye-off': EyeOff,
    folder: Folder,
    grid: Grid3X3,
    image: Image,
    info: Info,
    key: KeyRound,
    link: LinkIcon,
    monitor: Monitor,
    plus: Plus,
    refresh: RefreshCw,
    search: Search,
    shield: Shield,
    trash: Trash2,
    user: User,
    users: Users,
    x: CircleX,
} as const

const componente = computed(() => mapa[props.nome as keyof typeof mapa] ?? Info)
</script>

<template>
    <component :is="componente" :size="tamanho" :stroke-width="stroke" />
</template>
