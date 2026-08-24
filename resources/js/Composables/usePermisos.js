import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

/**
 * Permisos del usuario en la sesión actual.
 *
 * Los publica `HandleInertiaRequests` como prop de primer nivel. Sirven
 * únicamente para no ofrecer botones que el servidor va a rechazar: la
 * autorización de verdad vive en el middleware y en los controladores.
 * Nunca uses esto como única barrera.
 */
export function usePermisos() {
    const page = usePage();

    const permisos = computed(() => page.props.permisos ?? []);
    const usuario = computed(() => page.props.auth?.user ?? null);

    const puede = (...requeridos) => requeridos.some((p) => permisos.value.includes(p));

    const esRol = (rol) => usuario.value?.rol_actual === rol;

    return {
        permisos,
        usuario,
        puede,
        esRol,
        esSuperAdmin: computed(() => usuario.value?.es_super_admin === true),
        esTester: computed(() => usuario.value?.es_tester === true),
    };
}
