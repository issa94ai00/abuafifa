<template>
    <button
        type="button"
        :aria-label="isFavorited ? 'إزالة من المفضلة' : 'أضف إلى المفضلة'"
        :aria-pressed="isFavorited"
        :disabled="!canFavorite || submitting"
        class="group/fav relative inline-flex items-center justify-center rounded-full p-2.5 transition-all duration-300
               focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2
               disabled:opacity-60 disabled:cursor-not-allowed"
        :class="[
            variant === 'solid'
                ? isFavorited
                    ? 'bg-brand-600 text-white shadow-gold'
                    : 'bg-white/90 text-surface-600 hover:text-brand-600 shadow-premium'
                : 'bg-white/90 text-surface-600 hover:text-brand-600 shadow-premium backdrop-blur-sm',
            'hover:scale-110 active:scale-95',
        ]"
        @click="toggle"
    >
        <Icon
            :name="isFavorited ? 'heart-filled' : 'heart'"
            class="w-5 h-5"
            :class="{ 'animate-scale-in': justToggled }"
        />

        <span
            v-if="submitting"
            class="absolute inset-0 flex items-center justify-center"
        >
            <svg class="animate-spin h-5 w-5 text-brand-600" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </span>
    </button>
</template>

<script setup>
import { computed, ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Icon from './Icon.vue';

const props = defineProps({
    productId: { type: [Number, String], required: true },
    variant: { type: String, default: 'solid', validator: (v) => ['solid', 'ghost'].includes(v) },
});

const page = usePage();
const submitting = ref(false);
const justToggled = ref(false);

const user = computed(() => page.props.auth?.user);
const canFavorite = computed(() => Boolean(user.value));

function toggle() {
    if (!canFavorite.value) return;

    submitting.value = true;

    router.post(
        route('favorites.toggle'),
        { product_id: props.productId },
        {
            preserveScroll: true,
            onSuccess: () => {
                submitting.value = false;
                justToggled.value = true;
                setTimeout(() => (justToggled.value = false), 500);
            },
            onError: () => {
                submitting.value = false;
            },
        }
    );
}
</script>