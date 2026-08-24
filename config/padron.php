<?php

/*
|--------------------------------------------------------------------------
| Presentación del padrón
|--------------------------------------------------------------------------
|
| Cómo se agrupan y se etiquetan los campos de `personas` en la ficha 360°,
| en la exportación y en la vista previa de la importación.
|
| Vive en configuración y no en la vista para que las tres pantallas usen
| exactamente las mismas secciones y los mismos nombres: si un campo cambia
| de etiqueta, cambia en todas partes a la vez.
|
| `tipo` decide cómo se dibuja el valor:
|   texto | correo | telefono | fecha | booleano | numero | url | opcion
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Secciones de la ficha
    |--------------------------------------------------------------------------
    */

    'secciones' => [

        'identidad' => [
            'titulo' => 'Identidad',
            'icono' => 'user',
            'abierta' => true,
            'campos' => [
                'nombre_completo' => ['etiqueta' => 'Nombre completo', 'tipo' => 'texto'],
                'email' => ['etiqueta' => 'Correo electrónico', 'tipo' => 'correo'],
                'telefono' => ['etiqueta' => 'Teléfono', 'tipo' => 'telefono'],
                'telefono_secundario' => ['etiqueta' => 'Teléfono secundario', 'tipo' => 'telefono'],
            ],
        ],

        'identificacion' => [
            'titulo' => 'Identificación oficial',
            'icono' => 'shield',
            'abierta' => true,
            'campos' => [
                'curp' => ['etiqueta' => 'CURP', 'tipo' => 'texto'],
                'rfc' => ['etiqueta' => 'RFC', 'tipo' => 'texto'],
                'ine_clave' => ['etiqueta' => 'Clave de elector', 'tipo' => 'texto'],
            ],
        ],

        'domicilio' => [
            'titulo' => 'Domicilio',
            'icono' => 'mapa',
            'abierta' => true,
            'campos' => [
                'calle' => ['etiqueta' => 'Calle y número', 'tipo' => 'texto'],
                'calle_2' => ['etiqueta' => 'Entre calles', 'tipo' => 'texto'],
                'codigo_postal' => ['etiqueta' => 'Código postal', 'tipo' => 'texto'],
                'localidad' => ['etiqueta' => 'Localidad', 'tipo' => 'texto'],
                'ciudad' => ['etiqueta' => 'Ciudad', 'tipo' => 'texto'],
                'municipio' => ['etiqueta' => 'Municipio', 'tipo' => 'texto'],
                'estado' => ['etiqueta' => 'Estado', 'tipo' => 'texto'],
                'pais' => ['etiqueta' => 'País', 'tipo' => 'texto'],
                'latitud' => ['etiqueta' => 'Latitud', 'tipo' => 'numero'],
                'longitud' => ['etiqueta' => 'Longitud', 'tipo' => 'numero'],
            ],
        ],

        'demograficos' => [
            'titulo' => 'Datos demográficos',
            'icono' => 'user-group',
            'abierta' => false,
            'campos' => [
                'fecha_nacimiento' => ['etiqueta' => 'Fecha de nacimiento', 'tipo' => 'fecha'],
                'edad' => ['etiqueta' => 'Edad', 'tipo' => 'numero'],
                'sexo' => ['etiqueta' => 'Sexo', 'tipo' => 'opcion', 'opciones' => [
                    'M' => 'Masculino', 'F' => 'Femenino', 'Otro' => 'Otro',
                ]],
                'nivel_educativo' => ['etiqueta' => 'Nivel educativo', 'tipo' => 'texto'],
                'habla_maya' => ['etiqueta' => 'Habla maya', 'tipo' => 'booleano'],
            ],
        ],

        'negocio' => [
            'titulo' => 'Presencia digital del negocio',
            'icono' => 'externo',
            'abierta' => false,
            'campos' => [
                'sitio_web' => ['etiqueta' => 'Sitio web', 'tipo' => 'url'],
                'facebook_negocio' => ['etiqueta' => 'Facebook', 'tipo' => 'url'],
                'instagram_negocio' => ['etiqueta' => 'Instagram', 'tipo' => 'url'],
                'tiktok_negocio' => ['etiqueta' => 'TikTok', 'tipo' => 'url'],
            ],
        ],

        'gestion' => [
            'titulo' => 'Gestión y origen',
            'icono' => 'lista',
            'abierta' => false,
            'campos' => [
                'tipo_persona' => ['etiqueta' => 'Tipo de persona', 'tipo' => 'opcion', 'opciones' => [
                    'fisica' => 'Física', 'moral' => 'Moral',
                ]],
                'estado_persona' => ['etiqueta' => 'Estado', 'tipo' => 'opcion', 'opciones' => [
                    'activa' => 'Activa', 'inactiva' => 'Inactiva', 'bloqueada' => 'Bloqueada',
                ]],
                'idioma' => ['etiqueta' => 'Idioma de contacto', 'tipo' => 'texto'],
                'medio_ingreso' => ['etiqueta' => 'Cómo llegó al IYEM', 'tipo' => 'texto'],
                'creado_por_modulo' => ['etiqueta' => 'Módulo de origen', 'tipo' => 'texto'],
                'created_at' => ['etiqueta' => 'Alta en el padrón', 'tipo' => 'fecha'],
                'updated_at' => ['etiqueta' => 'Última modificación', 'tipo' => 'fecha'],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Etiquetas sugeridas
    |--------------------------------------------------------------------------
    |
    | Alimentan el autocompletado. No son una lista cerrada: se puede escribir
    | cualquier otra, y las que ya existen en la base también se sugieren.
    |
    */

    'etiquetas_sugeridas' => [
        'emprendedor',
        'artesano',
        'mayorista',
        'vip',
        'moroso',
        'joven',
        'mujer_emprendedora',
        'zona_rural',
        'adulto_mayor',
        'discapacidad',
        'cooperativa',
        'reincidente',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validación
    |--------------------------------------------------------------------------
    */

    'reglas' => [
        /*
         * CURP: 18 caracteres con la estructura de RENAPO.
         *
         * A propósito NO valida el catálogo de entidades federativas: el
         * padrón de demostración usa la entidad "ZZ" —inexistente— justo
         * para garantizar que ninguna CURP ficticia pueda pertenecer a una
         * persona real. Exigir el catálogo rechazaría esos datos.
         */
        'curp' => '/^[A-Z][AEIOUX][A-Z]{2}\d{6}[HM][A-Z]{2}[B-DF-HJ-NP-TV-Z]{3}[A-Z0-9]\d$/',

        // RFC de persona física (13) o moral (12).
        'rfc' => '/^([A-ZÑ&]{3,4})\d{6}([A-Z0-9]{3})$/',

        // Diez dígitos, como se marca en México sin lada internacional.
        'telefono' => '/^\d{10}$/',

        'codigo_postal' => '/^\d{5}$/',
    ],

];
