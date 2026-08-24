<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        {{--
            viewport-fit=cover deja que la página se dibuje debajo del notch y
            de la barra de gestos del iPhone. A cambio, todo lo pegado a los
            bordes debe respetar las variables env(safe-area-inset-*), que se
            aplican en resources/css/app.css y en AppLayout.vue.
        --}}
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        <title inertia>{{ config('app.name', 'IYEM Yucatán') }}</title>

        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="192x192" href="/favicon-192x192.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

        {{-- Aplicación instalable --}}
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#691C32">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="IYEM Hub">
        {{-- black-translucent: la barra de estado de iOS se pinta sobre el
             encabezado guinda en vez de dejar una franja blanca encima. --}}
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="format-detection" content="telephone=no">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
