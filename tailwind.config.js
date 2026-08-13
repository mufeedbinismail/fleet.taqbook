import { classNames } from './resources/icons/names.mjs';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './public/themes/**/renderer.php',
        `./resources/**/*.{js,css,php,blade.php}`,
    ],

    // Rules written into a layer are kept only when something is seen using them, and an icon's
    // class is put together out of a name the server picks — so the templates are searched for
    // spellings that are never written down there. Taken from the stylesheet that declares them,
    // which is the only place the full set exists.
    safelist: classNames(new URL('./', import.meta.url)),
    theme: {
        fontFamily: {
            sans: ['system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'Noto Sans', 'Liberation Sans', 'sans-serif', 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'],
            mono: ['SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', 'Liberation Mono', 'Courier New', 'monospace'],
        },
        extend: {
            screens: {
              '2xl': '100rem',
              '3xl': '120rem'
            },
            colors: {
                'main-bg': '#ffffff',
                'header-bg': '#c2d3db',
                'header-txt': '#364a59',
                'header-icon': '#4a5d6b',
                'footer-bg': '#e8f0f3',
                'footer-txt': '#5a6b76',
                'table-bg': '#f5f7f8',
                'table-header-bg': '#b4c7d2',
                'table-header-txt': '#364a59',
                'table-header-border': '#a6b8c3',
                'card-header-bg': '#2c3e50',
                'card-header-txt': '#d1e0e5',
                'sidebar-bg': '#d1e0e5',
                'primary-txt': '#2c3e50',
                'sidebar-txt': '#2c3e50',
                'sidebar-hover-bg': '#c2d3db',
                'sidebar-selected-bg': '#2c3e50',
                'sidebar-selected-txt': '#d1e0e5',
                'sidebar-section-txt': '#5a6b76',
                'sidebar-guide': '#adc2cb',
                'sidebar-border': '#d9e2e7',
                'breadcrumb-bg': '#e8f0f3',
                'breadcrumb-txt': '#3d4c57',
                'breadcrumb-current-txt': '#2c3e50',
                'breadcrumb-border': '#d9e2e7',
                'general-txt': '#3d4c57',
                'soft-border': '#d9e2e7',
                'primary-accent': '#00a3a3',
                'secondary-accent': '#ff7066',
                'ternary-accent': '#9d8df1',
                'warning-accent': '#ffb547',
                'success-accent': '#4caf89',
                'error-accent': '#e94f64',
                'label-bg': '#f1f5f7'
            },
            width: {
                sidebar: '280px',
            },
            height: {
                header: '60px',
                footer: '30px',
            },
        },
    },
}

