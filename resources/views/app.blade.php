<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#2c2924">

        <title inertia>{{ config('app.name', 'معرض أبو عفيفة للمفروشات') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=ibm-plex-sans-arabic:300,400,500,600,700|noto-kufi-arabic:400,500,600;700&display=swap" rel="stylesheet" />

        {{-- The brand mark the preloader paints; fetched up front so the
             overlay never renders as an empty tile. --}}
        <link rel="preload" as="image" href="/logo-mark.png">

        {{-- Boot preloader styles are inlined rather than left to app.css:
             the overlay exists precisely for the window in which the bundle
             has not arrived yet, so it cannot depend on it. --}}
        <style>
            body.is-booting {
                overflow: hidden;
            }

            #app-preloader {
                position: fixed;
                inset: 0;
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 24px;
                background: linear-gradient(135deg, #2c2924 0%, #3b1a0c 50%, #524d45 100%);
                font-family: "Noto Kufi Arabic", "IBM Plex Sans Arabic", system-ui, -apple-system, "Segoe UI", sans-serif;
                opacity: 1;
                transition: opacity 0.5s ease;
            }

            #app-preloader.is-done {
                opacity: 0;
                pointer-events: none;
            }

            .preloader__inner {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .preloader__badge {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 104px;
                height: 104px;
                margin-bottom: 26px;
            }

            .preloader__ring {
                position: absolute;
                inset: 0;
                border: 2px solid rgba(201, 162, 39, 0.16);
                border-top-color: #c9a227;
                border-inline-end-color: rgba(223, 154, 70, 0.65);
                border-radius: 50%;
                animation: preloader-spin 1.1s linear infinite;
            }

            .preloader__tile {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 72px;
                height: 72px;
                border: 1px solid rgba(201, 162, 39, 0.3);
                border-radius: 18px;
                background: linear-gradient(135deg, #2c2924 0%, #3b1a0c 100%);
                box-shadow: 0 4px 16px -2px rgba(201, 162, 39, 0.3), inset 0 1px 2px rgba(201, 162, 39, 0.15);
            }

            .preloader__logo {
                width: 76%;
                height: 76%;
                object-fit: contain;
            }

            .preloader__title {
                margin: 0;
                font-size: 20px;
                font-weight: 700;
                color: #fafaf9;
            }

            .preloader__tagline {
                margin: 6px 0 0;
                font-size: 12px;
                letter-spacing: 0.4px;
                color: #c9a227;
            }

            .preloader__bar {
                position: relative;
                width: 168px;
                height: 2px;
                margin-top: 26px;
                border-radius: 2px;
                background: rgba(250, 250, 249, 0.12);
                overflow: hidden;
            }

            .preloader__bar span {
                position: absolute;
                top: 0;
                bottom: 0;
                inset-inline-start: -45%;
                width: 45%;
                border-radius: 2px;
                background: linear-gradient(90deg, transparent, #c9a227, #df9a46, transparent);
                animation: preloader-sweep 1.4s ease-in-out infinite;
            }

            .preloader__sr {
                position: absolute;
                width: 1px;
                height: 1px;
                margin: -1px;
                padding: 0;
                overflow: hidden;
                clip-path: inset(50%);
                white-space: nowrap;
                border: 0;
            }

            @keyframes preloader-spin {
                to { transform: rotate(360deg); }
            }

            /* Logical offset, so the sweep travels right-to-left under RTL. */
            @keyframes preloader-sweep {
                0%   { inset-inline-start: -45%; }
                100% { inset-inline-start: 100%; }
            }

            @media (prefers-reduced-motion: reduce) {
                #app-preloader {
                    transition-duration: 0.01ms;
                }

                .preloader__ring {
                    animation: none;
                }

                .preloader__bar span {
                    inset-inline-start: 0;
                    width: 100%;
                    opacity: 0.55;
                    animation: none;
                }
            }
        </style>

        <!-- Scripts -->
        {{-- Ziggy emits an inline <script>; the CSP nonce from the
             SecurityHeaders middleware is what allows it to run. --}}
        @routes(nonce: csp_nonce())
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-surface-50 text-surface-900 is-booting" aria-busy="true">
        {{-- Painted server-side so there is something on screen from the
             first frame; resources/js/preloader.js tears it down once Vue
             has mounted. --}}
        <div id="app-preloader" role="status" aria-live="polite">
            <div class="preloader__inner">
                <div class="preloader__badge">
                    <span class="preloader__ring" aria-hidden="true"></span>
                    <span class="preloader__tile">
                        <img src="/logo-mark.png" alt="" aria-hidden="true" width="288" height="201" class="preloader__logo">
                    </span>
                </div>

                <p class="preloader__title" aria-hidden="true">معرض أبو عفيفة</p>
                <p class="preloader__tagline" aria-hidden="true">للمفروشات والأثاث الفاخر</p>

                <div class="preloader__bar" aria-hidden="true"><span></span></div>

                <span class="preloader__sr">جارٍ تحميل الموقع…</span>
            </div>
        </div>

        <noscript>
            {{-- Without JavaScript the bundle never boots, so the overlay
                 would sit there for good. Drop it and say why the page is
                 empty instead. --}}
            <style>
                #app-preloader { display: none; }
                body.is-booting { overflow: auto; }
                .noscript-note {
                    margin: 48px auto;
                    max-width: 32rem;
                    padding: 20px 24px;
                    border: 1px solid #e8e6df;
                    border-radius: 12px;
                    background: #fff;
                    text-align: center;
                    line-height: 1.8;
                }
            </style>
            <p class="noscript-note">
                يتطلب هذا الموقع تفعيل JavaScript في المتصفح لعرض المحتوى.
            </p>
        </noscript>

        <!-- Loading bar will be injected by Inertia -->
        @inertia

        {{-- Fail-safe: if the bundle errors out, Vue never mounts and
             nothing would clear the overlay. Never strand the visitor on a
             loading screen. --}}
        <script nonce="{{ csp_nonce() }}">
            window.setTimeout(function () {
                var el = document.getElementById('app-preloader');

                if (el && ! el.classList.contains('is-done')) {
                    el.classList.add('is-done');
                    document.body.classList.remove('is-booting');
                    document.body.removeAttribute('aria-busy');
                }
            }, 10000);
        </script>
    </body>
</html>
