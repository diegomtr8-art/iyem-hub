<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Módulos del ecosistema IYEM
    |--------------------------------------------------------------------------
    |
    | Cada entrada representa un módulo accesible desde el dashboard central.
    | "slug" se usa como identificador de permiso (modulo del permiso) y
    | "url" es interna (ruta local) o externa (subdominio del módulo).
    |
    */

    'crea' => [
        'nombre' => 'CREA',
        'descripcion' => 'Sistema de gestión de créditos para emprendedores.',
        'icono' => 'banknotes',
        'url' => 'https://crea.iyemyucatan.com',
        'externo' => true,
    ],

    'impulsate' => [
        'nombre' => 'Impúlsate',
        'descripcion' => 'Agenda y gestión de citas de asesoría.',
        'icono' => 'calendar',
        'url' => 'https://impulsate.iyemyucatan.com',
        'externo' => true,
    ],

    'nodico' => [
        'nombre' => 'Nodico',
        'descripcion' => 'Espacio de coworking para emprendedores.',
        'icono' => 'building-office',
        'url' => 'https://nodico.com.mx',
        'externo' => true,
    ],

    'herenciaviva' => [
        'nombre' => 'Herencia Viva',
        'descripcion' => 'Comercio de productos artesanales yucatecos.',
        'icono' => 'shopping-bag',
        'url' => 'https://herenciaviva.com',
        'externo' => true,
    ],

    'juridico' => [
        'nombre' => 'Jurídico',
        'descripcion' => 'Gestión de asuntos legales del instituto.',
        'icono' => 'scale',
        'url' => 'https://juridico.iyemyucatan.com',
        'externo' => true,
    ],

    'padron' => [
        'nombre' => 'Padrón',
        'descripcion' => 'Contactos centrales, fuente única de verdad.',
        'icono' => 'users',
        'url' => '/padron',
        'externo' => false,
    ],

];
