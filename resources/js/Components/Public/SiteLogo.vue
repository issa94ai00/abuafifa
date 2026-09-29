<template>
    <div class="flex items-center gap-3 select-none">
        <div
            class="relative w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-gradient-premium ring-1 ring-gold-500/30 flex items-center justify-center shadow-gold shrink-0"
            :class="{ 'w-14 h-14 sm:w-16 sm:h-16 rounded-2xl': large }"
        >
            <!-- Decorative: the wordmark beside it already names the gallery. -->
            <img
                src="/logo-mark.png"
                alt=""
                aria-hidden="true"
                width="288"
                height="201"
                class="w-[76%] h-[76%] object-contain"
            />
        </div>

        <div class="leading-tight">
            <span
                class="block font-display font-bold text-surface-900 text-lg sm:text-xl whitespace-nowrap"
                :class="{ 'text-2xl sm:text-3xl': large, 'text-white': light }"
            >
                معرض أبو عفيفة
            </span>
            <span
                class="block text-[11px] sm:text-xs tracking-wide text-gold-500 font-medium"
                :class="{ 'text-gold-400': light }"
            >
                للمفروشات والأثاث الفاخر
            </span>
        </div>
    </div>
</template>

<script setup>
defineProps({
    large: { type: Boolean, default: false },
    light: { type: Boolean, default: false },
});
</script>
