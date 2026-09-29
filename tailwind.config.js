import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        screens: {
            'xs': '420px',
            ...defaultTheme.screens,
        },
        extend: {
            fontFamily: {
                sans: ['"IBM Plex Sans Arabic"', ...defaultTheme.fontFamily.sans],
                display: ['"Noto Kufi Arabic"', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                brand: {
                    50:  '#fdf8f0',
                    100: '#f9edda',
                    200: '#f2d7b0',
                    300: '#e9bb7c',
                    400: '#df9a46',
                    500: '#d6802a',
                    600: '#c86820',
                    700: '#a64f1c',
                    800: '#86401e',
                    900: '#6e361c',
                    950: '#3b1a0c',
                },
                surface: {
                    50:  '#fafaf9',
                    100: '#f5f4f0',
                    200: '#e8e6df',
                    300: '#d6d2c7',
                    400: '#b8b2a2',
                    500: '#9f9785',
                    600: '#8a8172',
                    700: '#746b5f',
                    800: '#615a51',
                    900: '#524d45',
                    950: '#2c2924',
                },
                gold: {
                    50:  '#fefce8',
                    100: '#fef9c3',
                    200: '#fef08a',
                    300: '#fde047',
                    400: '#facc15',
                    500: '#c9a227',
                    600: '#a6831c',
                    700: '#836418',
                    800: '#6b4f1b',
                    900: '#5a421c',
                },
            },

            animation: {
                'fade-in': 'fadeIn 0.6s ease-out forwards',
                'fade-in-up': 'fadeInUp 0.7s ease-out forwards',
                'fade-in-down': 'fadeInDown 0.5s ease-out forwards',
                'slide-in-right': 'slideInRight 0.6s ease-out forwards',
                'slide-in-left': 'slideInLeft 0.6s ease-out forwards',
                'scale-in': 'scaleIn 0.5s ease-out forwards',
                'shimmer': 'shimmer 2s linear infinite',
            },

            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                fadeInUp: {
                    '0%': { opacity: '0', transform: 'translateY(24px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                fadeInDown: {
                    '0%': { opacity: '0', transform: 'translateY(-16px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideInRight: {
                    '0%': { opacity: '0', transform: 'translateX(32px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                slideInLeft: {
                    '0%': { opacity: '0', transform: 'translateX(-32px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                scaleIn: {
                    '0%': { opacity: '0', transform: 'scale(0.95)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                shimmer: {
                    '0%': { backgroundPosition: '-200% 0' },
                    '100%': { backgroundPosition: '200% 0' },
                },
            },

            backgroundImage: {
                'gradient-premium': 'linear-gradient(135deg, #2c2924 0%, #3b1a0c 50%, #524d45 100%)',
                'gradient-gold': 'linear-gradient(135deg, #c9a227 0%, #df9a46 50%, #c9a227 100%)',
                'gradient-warm': 'linear-gradient(180deg, #fdf8f0 0%, #f5f4f0 100%)',
            },

            boxShadow: {
                'premium': '0 4px 24px -4px rgba(44, 41, 36, 0.12)',
                'premium-lg': '0 12px 40px -8px rgba(44, 41, 36, 0.18)',
                'gold': '0 4px 16px -2px rgba(201, 162, 39, 0.3)',
                'inner-glow': 'inset 0 1px 2px rgba(201, 162, 39, 0.15)',
            },

            borderRadius: {
                '4xl': '2rem',
            },
        },
    },

    plugins: [forms],
};
