<template>
    <a
        :href="whatsappLink"
        target="_blank"
        rel="noopener noreferrer"
        :aria-label="ariaLabel"
        class="inline-flex items-center justify-center rounded-full bg-[#25D366] text-white
               transition-all duration-300 ease-out
               hover:bg-[#1fb858] hover:scale-105 active:scale-95
               focus:outline-none focus-visible:ring-2 focus-visible:ring-[#25D366] focus-visible:ring-offset-2
               shadow-[0_8px_24px_-4px_rgba(37,211,102,0.45)]"
        :class="floating ? 'p-4 w-14 h-14 fixed bottom-6 left-6 z-40' : 'px-6 py-3.5 gap-2'"
    >
        <span v-if="!floating && text">{{ text }}</span>
        <Icon name="whatsapp" class="w-7 h-7" :class="{ 'w-5 h-5': !floating }" />
    </a>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Icon from './Icon.vue';

const props = defineProps({
    floating: { type: Boolean, default: false },
    text: { type: String, default: 'تواصل عبر واتساب' },
    message: { type: String, default: 'مرحباً، أرغب بالاستفسار عن منتجاتكم في معرض أبو عفيفة للمفروشات.' },
});

const page = usePage();

const whatsappNumber = computed(() => props.floating ? null : page.props.settings?.whatsapp);

const whatsappLink = computed(() => {
    let number = whatsappNumber.value;

    if (props.floating && page.props.settings?.whatsapp) {
        number = page.props.settings.whatsapp;
    }

    if (!number) return 'https://wa.me/';

    return `https://wa.me/${number.replace(/\D/g, '')}?text=${encodeURIComponent(props.message)}`;
});

const ariaLabel = computed(() => props.text || 'تواصل معنا عبر واتساب');
</script>