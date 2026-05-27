import { usePage } from '@inertiajs/vue3'

export function usePermissions() {
    const page = usePage<{ gates: Record<string, boolean> }>()

    const can = (permission: string): boolean => page.props.gates?.[permission] ?? false

    const canAny = (...permissions: string[]): boolean =>
        permissions.some((permission) => can(permission))

    const isAdmin = (): boolean => can('administrador')

    return { can, canAny, isAdmin }
}
