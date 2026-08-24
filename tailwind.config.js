import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class', // Nunca se aplica la clase "dark": el modo oscuro está deshabilitado en toda la plataforma.

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Arial', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Escala completa derivada de los colores institucionales IYEM
                // (50-950), más los alias históricos usados en la app.
                iyem: {
                    50: '#FBF3F5',
                    100: '#F5EAEE',
                    200: '#EAD0D8',
                    300: '#D9A9B7',
                    400: '#C0728A',
                    500: '#9F2241',
                    600: '#871E38',
                    700: '#691C32',
                    800: '#4D1526',
                    900: '#33101A',
                    950: '#1F0A10',
                    primario: '#691C32',
                    secundario: '#9F2241',
                    claro: '#F5EAEE',
                    neutro: '#F4F4F6',
                },
                // Escala oscura (con matiz guinda) para el sidebar y superficies "tech".
                tinta: {
                    700: '#3A1D24',
                    800: '#271217',
                    900: '#170B0E',
                    950: '#0C0507',
                },
            },
            boxShadow: {
                soft: '0 1px 2px 0 rgb(0 0 0 / 0.04), 0 8px 24px -8px rgb(105 28 50 / 0.12)',
                'soft-lg': '0 12px 32px -12px rgb(105 28 50 / 0.25)',
                glow: '0 0 0 1px rgb(159 34 65 / 0.15), 0 8px 24px -4px rgb(159 34 65 / 0.35)',
            },
            backgroundImage: {
                'iyem-gradient': 'linear-gradient(135deg, #691C32 0%, #9F2241 55%, #B93A5C 100%)',
                'iyem-mesh': 'radial-gradient(at 20% 10%, rgba(159,34,65,0.20) 0px, transparent 50%), radial-gradient(at 85% 0%, rgba(105,28,50,0.18) 0px, transparent 50%), radial-gradient(at 90% 90%, rgba(159,34,65,0.14) 0px, transparent 50%)',
                'tinta-gradient': 'linear-gradient(180deg, #271217 0%, #170B0E 60%, #0C0507 100%)',
            },
        },
    },

    plugins: [forms, typography],
};
