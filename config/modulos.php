<?php

/*
|--------------------------------------------------------------------------
| Módulos del ecosistema IYEM
|--------------------------------------------------------------------------
|
| Cada entrada es un sistema al que se llega desde el hub. La clave del
| arreglo es el slug: se usa para construir el permiso `ver-{slug}` y para
| identificar al módulo en `accesos`, `personas_auditorias` y en la API.
|
| Claves de cada módulo:
|
|   nombre       Cómo se muestra en la interfaz.
|   descripcion  Una línea. Se lee dentro de la tarjeta del dashboard.
|   icono        Clave de `IconoModulo.vue`.
|   url          Absoluta si `externo`, ruta local si no.
|   externo      true = vive en otro dominio y pasa por `dashboard.acceder`.
|   estado       produccion | beta | desarrollo | planeado
|   categoria    financiero | operativo | comercial | institucional
|   responsable  Área dueña del sistema. PENDIENTE DE CONFIRMAR con el IYEM:
|                los valores actuales son marcadores, no el organigrama real.
|   api_salud    URL que el hub consulta para pintar el semáforo de la tarjeta.
|                null = ese módulo todavía no expone endpoint de salud.
|   color        Token de `tailwind.config.js` (sin prefijo `bg-`/`text-`).
|   orden        Posición en la cuadrícula del dashboard.
|
*/

return [

    'crea' => [
        'nombre' => 'CREA',
        'descripcion' => 'Créditos para emprendedores yucatecos.',
        'icono' => 'banknotes',
        'url' => 'https://crea.iyemyucatan.com',
        'externo' => true,
        'estado' => 'produccion',
        'categoria' => 'financiero',
        'responsable' => 'Dirección de Financiamiento',
        'api_salud' => 'https://crea.iyemyucatan.com/api/salud',
        'color' => 'iyem-primario',
        'orden' => 1,
    ],

    'impulsate' => [
        'nombre' => 'Impúlsate',
        'descripcion' => 'Agenda y seguimiento de citas de asesoría.',
        'icono' => 'calendar',
        'url' => 'https://impulsate.iyemyucatan.com',
        'externo' => true,
        'estado' => 'produccion',
        'categoria' => 'operativo',
        'responsable' => 'Dirección de Capacitación',
        'api_salud' => 'https://impulsate.iyemyucatan.com/api/salud',
        'color' => 'iyem-secundario',
        'orden' => 2,
    ],

    'asistencia' => [
        'nombre' => 'Asistencia',
        'descripcion' => 'Control de asistencia del personal del instituto.',
        'icono' => 'finger-print',
        'url' => 'https://asistencia.iyemyucatan.com',
        'externo' => true,
        'estado' => 'produccion',
        'categoria' => 'institucional',
        'responsable' => 'Dirección Administrativa',
        'api_salud' => 'https://asistencia.iyemyucatan.com/api/salud',
        'color' => 'iyem-primario',
        'orden' => 3,
    ],

    'juridico' => [
        'nombre' => 'Jurídico',
        'descripcion' => 'Asesorías y asuntos legales del instituto.',
        'icono' => 'scale',
        'url' => 'https://juridico.iyemyucatan.com',
        'externo' => true,
        'estado' => 'produccion',
        'categoria' => 'institucional',
        'responsable' => 'Dirección Jurídica',
        'api_salud' => 'https://juridico.iyemyucatan.com/api/salud',
        'color' => 'iyem-primario',
        'orden' => 4,
    ],

    'indicadores' => [
        'nombre' => 'Indicadores',
        'descripcion' => 'Tablero de indicadores para dirección.',
        'icono' => 'chart-bar',
        'url' => 'https://indicadores.iyemyucatan.com',
        'externo' => true,
        'estado' => 'beta',
        'categoria' => 'institucional',
        'responsable' => 'Dirección General',
        'api_salud' => 'https://indicadores.iyemyucatan.com/api/salud',
        'color' => 'iyem-dorado',
        'orden' => 5,
    ],

    'herenciaviva' => [
        'nombre' => 'Herencia Viva',
        'descripcion' => 'Tablero de ventas de productos artesanales.',
        'icono' => 'shopping-bag',
        'url' => 'https://dashboard.herenciaviva.com',
        'externo' => true,
        'estado' => 'produccion',
        'categoria' => 'comercial',
        'responsable' => 'Dirección Comercial',
        'api_salud' => 'https://dashboard.herenciaviva.com/api/salud',
        'color' => 'iyem-secundario',
        'orden' => 6,
    ],

    'nodico' => [
        'nombre' => 'Nódico',
        'descripcion' => 'Espacio de coworking para emprendedores.',
        'icono' => 'building-office',
        'url' => 'https://nodico.com.mx',
        'externo' => true,
        'estado' => 'produccion',
        'categoria' => 'comercial',
        'responsable' => 'Dirección Comercial',
        'api_salud' => 'https://nodico.com.mx/api/salud',
        'color' => 'iyem-secundario',
        'orden' => 7,
    ],

    'coworkhub' => [
        'nombre' => 'Nódico 2.0',
        'descripcion' => 'Nueva plataforma de coworking (CoworkHub).',
        'icono' => 'desktop',
        'url' => 'https://coworking.iyemyucatan.com',
        'externo' => true,
        'estado' => 'desarrollo',
        'categoria' => 'comercial',
        'responsable' => 'Dirección Comercial',
        'api_salud' => null,
        'color' => 'iyem-secundario',
        'orden' => 8,
    ],

    'crm' => [
        'nombre' => 'CRM',
        'descripcion' => 'Seguimiento de contactos y oportunidades (NexusCRM).',
        'icono' => 'user-group',
        'url' => 'https://crm.iyemyucatan.com',
        'externo' => true,
        'estado' => 'desarrollo',
        'categoria' => 'operativo',
        'responsable' => 'Dirección de Vinculación',
        'api_salud' => null,
        'color' => 'iyem-primario',
        'orden' => 9,
    ],

    'tienda' => [
        'nombre' => 'Herencia Viva Tienda',
        'descripcion' => 'Tienda en línea de artesanía yucateca (ShopCore).',
        'icono' => 'shopping-cart',
        'url' => 'https://tienda.herenciaviva.com',
        'externo' => true,
        'estado' => 'planeado',
        'categoria' => 'comercial',
        'responsable' => 'Dirección Comercial',
        'api_salud' => null,
        'color' => 'iyem-secundario',
        'orden' => 10,
    ],

    'padron' => [
        'nombre' => 'Padrón Central',
        'descripcion' => 'Fuente única de verdad sobre las personas del IYEM.',
        'icono' => 'users',
        'url' => '/padron',
        'externo' => false,
        'estado' => 'produccion',
        'categoria' => 'institucional',
        'responsable' => 'Dirección de Informática',
        'api_salud' => null,
        'color' => 'iyem-primario',
        'orden' => 11,
    ],

    'consultas' => [
        'nombre' => 'Consultas 360°',
        'descripcion' => 'Cruces de información entre todos los módulos.',
        'icono' => 'magnifying-glass',
        'url' => '/consultas',
        'externo' => false,
        'estado' => 'produccion',
        'categoria' => 'institucional',
        'responsable' => 'Dirección de Informática',
        'api_salud' => null,
        'color' => 'iyem-dorado',
        'orden' => 12,
    ],

    'bitacora' => [
        'nombre' => 'Bitácora',
        'descripcion' => 'Registro de accesos y movimientos de la plataforma.',
        'icono' => 'clipboard-list',
        'url' => '/bitacora',
        'externo' => false,
        'estado' => 'desarrollo',
        'categoria' => 'institucional',
        'responsable' => 'Dirección de Informática',
        'api_salud' => null,
        'color' => 'iyem-primario',
        'orden' => 13,
    ],

];
