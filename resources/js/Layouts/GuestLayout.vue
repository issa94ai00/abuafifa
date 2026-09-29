<script setup>
import SiteLogo from '@/Components/Public/SiteLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-surface-950 px-4 py-12 sm:px-6"
    >
        <!-- Background image + overlay -->
        <div class="absolute inset-0" aria-hidden="true">
            <img
                src="https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=2400&q=80"
                alt=""
                class="h-full w-full object-cover"
                loading="eager"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-surface-950 via-surface-950/85 to-surface-950/70"></div>
        </div>

        <!-- Content -->
        <div class="relative z-10 flex w-full flex-col items-center">
            <Link href="/" class="mb-8 animate-fade-in-down">
                <SiteLogo light large />
            </Link>

            <div
                class="w-full max-w-md overflow-hidden rounded-3xl border border-white/10 bg-white/95 shadow-premium-lg backdrop-blur-xl animate-fade-in-up sm:max-w-lg"
            >
                <div class="px-6 py-8 sm:px-10 sm:py-10">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>