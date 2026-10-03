import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import plugin from 'tailwindcss/plugin';

/*
 * Estilos tipográficos del sistema de diseño. Cada uno trae su familia, así
 * `text-title` o `text-number` bastan: no hay que acordarse de agregar
 * `font-display` o `font-mono` al lado.
 */
const display = ['Archivo', ...defaultTheme.fontFamily.sans];
const sans = ['Inter', ...defaultTheme.fontFamily.sans];
const mono = ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono];

const tipografia = {
    'display-xl': { fontFamily: display, fontSize: '40px', lineHeight: '44px', fontWeight: '700', letterSpacing: '-0.01em' },
    'display-lg': { fontFamily: display, fontSize: '32px', lineHeight: '36px', fontWeight: '700', letterSpacing: '-0.01em' },
    'display-md': { fontFamily: display, fontSize: '24px', lineHeight: '30px', fontWeight: '600' },
    title: { fontFamily: display, fontSize: '20px', lineHeight: '28px', fontWeight: '600' },
    subtitle: { fontFamily: sans, fontSize: '16px', lineHeight: '24px', fontWeight: '600' },
    body: { fontFamily: sans, fontSize: '15px', lineHeight: '22px', fontWeight: '400' },
    'body-strong': { fontFamily: sans, fontSize: '15px', lineHeight: '22px', fontWeight: '600' },
    small: { fontFamily: sans, fontSize: '13px', lineHeight: '18px', fontWeight: '400' },
    caption: { fontFamily: sans, fontSize: '12px', lineHeight: '16px', fontWeight: '500' },
    overline: { fontFamily: sans, fontSize: '11px', lineHeight: '14px', fontWeight: '700', letterSpacing: '0.08em', textTransform: 'uppercase' },
    // Cifra de KPI: medida de display-md, en mono para que se lea como dato.
    'number-display': { fontFamily: mono, fontSize: '24px', lineHeight: '30px', fontWeight: '600', fontVariantNumeric: 'tabular-nums' },
    'number-lg': { fontFamily: mono, fontSize: '20px', lineHeight: '26px', fontWeight: '600', fontVariantNumeric: 'tabular-nums' },
    number: { fontFamily: mono, fontSize: '15px', lineHeight: '22px', fontWeight: '500', fontVariantNumeric: 'tabular-nums' },
    code: { fontFamily: mono, fontSize: '13px', lineHeight: '20px', fontWeight: '400' },
};

/** @type {import('tailwindcss').Config} */
export default {
    // El tema lo decide el atributo data-theme de <html>, que escribe un
    // script en línea de app.blade.php antes de pintar. Los colores
    // semánticos son variables CSS que ya cambian solas con el tema, así que
    // `dark:` solo hace falta con algún brand-* numérico.
    darkMode: ['class', '[data-theme="dark"]'],

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
        './config/modulos.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                display,
                sans,
                mono,
            },
            colors: {
                // Marca: igual en los dos temas.
                brand: {
                    50: '#F1F2FE',
                    100: '#DDE0FD',
                    200: '#BCC1FB',
                    300: '#9FA7F9',
                    400: '#7C87F7',
                    500: '#5B69F5',
                    600: '#4A56D6',
                    700: '#3B45B0',
                    800: '#2E3689',
                    900: '#1F2560',
                },
                // Solo la tarjeta del módulo CREA.
                guinda: {
                    700: '#6B1938',
                },

                // Semánticos: apuntan a variables de resources/css/tokens.css
                // y cambian con el tema sin escribir `dark:`.
                surface: {
                    DEFAULT: 'var(--surface-000)',
                    50: 'var(--surface-050)',
                    100: 'var(--surface-100)',
                    brand: 'var(--surface-brand)',
                    inverse: 'var(--surface-inverse)',
                },
                ink: {
                    DEFAULT: 'var(--ink-900)',
                    600: 'var(--ink-600)',
                    400: 'var(--ink-400)',
                    inverse: 'var(--ink-inverse)',
                },
                line: {
                    DEFAULT: 'var(--border)',
                    strong: 'var(--border-strong)',
                },
                action: {
                    DEFAULT: 'var(--action)',
                    hover: 'var(--action-hover)',
                    ink: 'var(--action-ink)',
                },
                focus: 'var(--focus-ring)',
                success: {
                    DEFAULT: 'var(--success)',
                    surface: 'var(--success-surface)',
                },
                warning: {
                    DEFAULT: 'var(--warning)',
                    surface: 'var(--warning-surface)',
                },
                danger: {
                    DEFAULT: 'var(--danger)',
                    surface: 'var(--danger-surface)',
                    fill: 'var(--danger-fill)',
                },

                // TRANSITORIO. Alias de la paleta guinda anterior, re-apuntados
                // al índigo para que las pantallas aún no migradas no queden
                // sin color. Se borran cuando
                // `grep -r "iyem-\|tinta-" resources/js` no devuelva nada.
                iyem: {
                    50: '#F1F2FE',
                    100: '#DDE0FD',
                    200: '#BCC1FB',
                    300: '#9FA7F9',
                    400: '#7C87F7',
                    500: '#5B69F5',
                    600: '#4A56D6',
                    700: '#3B45B0',
                    800: '#2E3689',
                    900: '#1F2560',
                    950: '#161A45',
                    primario: '#3B45B0',
                    secundario: '#4A56D6',
                    claro: '#F1F2FE',
                    neutro: '#F6F7FC',
                    dorado: '#8A5200',
                    exito: '#146138',
                    alerta: '#8A5200',
                    error: '#9B211A',
                },
                tinta: {
                    700: '#2E3689',
                    800: '#1F2560',
                    900: '#1F2560',
                    950: '#161A45',
                },
            },
            borderRadius: {
                none: '0',
                sm: '4px',
                DEFAULT: '8px',
                md: '8px',
                lg: '12px',
                xl: '16px',
                full: '9999px',
            },
            boxShadow: {
                sm: 'var(--shadow-sm)',
                DEFAULT: 'var(--shadow-sm)',
                md: 'var(--shadow-md)',
                lg: 'var(--shadow-lg)',
                // TRANSITORIO: alias de las sombras anteriores, se borran con iyem-*.
                soft: 'var(--shadow-sm)',
                'soft-lg': 'var(--shadow-md)',
                glow: 'none',
            },
            // TRANSITORIO: los degradados anteriores quedan como color plano
            // para que los botones y paneles aún no migrados no se queden sin
            // fondo. El sistema no tiene degradados; se borran con iyem-*.
            backgroundImage: {
                'iyem-gradient': 'linear-gradient(#3B45B0, #3B45B0)',
                'iyem-mesh': 'none',
                'tinta-gradient': 'linear-gradient(#1F2560, #1F2560)',
            },
            ringOffsetColor: {
                DEFAULT: 'var(--surface-000)',
            },
        },
    },

    plugins: [
        forms,
        typography,
        plugin(({ addUtilities }) => {
            addUtilities(
                Object.fromEntries(Object.entries(tipografia).map(([nombre, estilo]) => [`.text-${nombre}`, { ...estilo, fontFamily: estilo.fontFamily.join(', ') }])),
            );
        }),
    ],
};
