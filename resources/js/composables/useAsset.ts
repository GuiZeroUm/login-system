import { usePage } from '@inertiajs/vue3'

export function useAsset() {
    const page = usePage<{ asset?: string }>()
    const base = (page.props.asset ?? '/').replace(/\/$/, '')

    const asset = (path: string): string => {
        const normalized = path.replace(/^\//, '')

        return `${base}/${normalized}`
    }

    const storage = (path: string | null | undefined, fallback: string): string => {
        if (!path) {
            return asset(fallback)
        }

        if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('/storage/')) {
            return path.startsWith('/') ? `${base}${path}` : path
        }

        return asset(`storage/${path.replace(/^\//, '')}`)
    }

    return { asset, storage }
}
