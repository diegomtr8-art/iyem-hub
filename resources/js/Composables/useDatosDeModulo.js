import { ref } from 'vue';
import axios from 'axios';

/**
 * Datos de un tablero de módulo, pedidos al ERP después de pintar la página.
 *
 * El ERP responde siempre 200 con `falla` dentro de cada parte cuando el
 * módulo no contestó; este `error` es para cuando el propio ERP falló (sesión
 * vencida, periodo inválido, servidor caído), que se avisa igual: nunca con
 * ceros.
 */
export function useDatosDeModulo() {
    const datos = ref(null);
    const cargando = ref(false);
    const error = ref(null);

    let solicitud = 0;

    const cargar = async (url) => {
        const esta = ++solicitud;
        cargando.value = true;
        error.value = null;

        try {
            const { data } = await axios.get(url);
            // Si el periodo cambió mientras esta viajaba, gana la más nueva.
            if (esta === solicitud) datos.value = data;
        } catch (e) {
            if (esta !== solicitud) return;
            datos.value = null;
            error.value = {
                tipo: 'erp',
                mensaje: e.response?.status === 422
                    ? (Object.values(e.response.data?.errors ?? {})[0]?.[0] ?? 'El periodo no es válido.')
                    : 'No se pudieron cargar los datos del tablero.',
                accion: e.response?.status === 419 || e.response?.status === 401
                    ? 'Tu sesión terminó. Recarga la página para volver a entrar.'
                    : null,
                reintentable: e.response?.status !== 422,
                ultimo_dato: null,
            };
        } finally {
            if (esta === solicitud) cargando.value = false;
        }
    };

    return { datos, cargando, error, cargar };
}

/**
 * Baja un CSV a través del ERP. Si falla, devuelve la falla para avisarla en
 * la página; un enlace directo mostraría una pantalla de error en su lugar.
 */
export async function descargarCsv(url) {
    try {
        const respuesta = await axios.get(url, { responseType: 'blob' });
        const nombre = /filename="?([^";]+)"?/.exec(respuesta.headers['content-disposition'] ?? '')?.[1] ?? 'informe.csv';
        const enlace = document.createElement('a');

        enlace.href = URL.createObjectURL(respuesta.data);
        enlace.download = nombre;
        document.body.appendChild(enlace);
        enlace.click();
        enlace.remove();
        URL.revokeObjectURL(enlace.href);

        return null;
    } catch (e) {
        try {
            const cuerpo = JSON.parse(await e.response.data.text());
            if (cuerpo.falla) return cuerpo.falla;
        } catch {
            // Sin cuerpo legible: se avisa genérico.
        }

        return { tipo: 'erp', mensaje: 'No se pudo descargar el informe.', accion: null, reintentable: false, ultimo_dato: null };
    }
}
