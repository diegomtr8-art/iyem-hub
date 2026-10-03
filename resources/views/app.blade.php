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

        {{--
            El tema se aplica aquí, en línea y antes de @vite: si esperara a
            app.js habría un destello blanco en cada carga, y en tema oscuro eso
            deslumbra. El try/catch es a propósito: en ventana privada o con
            almacenamiento bloqueado localStorage lanza excepción, y sin él la
            página no se pintaría.
        --}}
        <script>try{var t=localStorage.getItem('tema')||(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>

        <title inertia>{{ config('app.name', 'IYEM Yucatán') }}</title>

        {{-- PENDIENTE: favicons e iconos siguen en guinda hasta que diseño entregue
             el SVG del logotipo IYEM ERP (ver docs del rediseño). --}}
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="192x192" href="/favicon-192x192.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">

        {{-- Aplicación instalable --}}
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#5b69f5" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#12141f" media="(prefers-color-scheme: dark)">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="IYEM ERP">
        {{-- black-translucent: la barra de estado de iOS se pinta sobre el
             encabezado de la aplicación en vez de dejar una franja blanca encima. --}}
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="format-detection" content="telephone=no">

        <!-- Scripts -->
        {{-- Las fuentes del sistema, precargadas: son autoalojadas en public/fonts. --}}
        <link rel="preload" href="/fonts/inter-latin-400.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/archivo-latin-700.woff2" as="font" type="font/woff2" crossorigin>

        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="bg-surface-50 font-sans text-ink antialiased">
        @inertia
    </body>
</html>
