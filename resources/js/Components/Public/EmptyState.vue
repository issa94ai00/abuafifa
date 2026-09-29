<template>
    <div class="flex flex-col items-center justify-center text-center py-12 px-4">
        <div class="relative mb-4">
            <div class="w-20 h-20 rounded-full bg-gradient-warm flex items-center justify-center">
                <Icon v-if="icon" :name="icon" class="w-8 h-8 text-surface-400" />
                <Icon v-else name="search" class="w-8 h-8 text-surface-400" />
            </div>
            <span
                class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-gold-500/20 flex items-center justify-center"
            >
                <span class="w-2 h-2 rounded-full bg-gold-500 animate-pulse"></span>
            </span>
        </div>
        <h3 class="font-display text-lg font-semibold text-surface-700">
            {{ title }}
        </h3>
        <p v-if="description" class="mt-2 text-sm text-surface-500 max-w-sm leading-relaxed">
            {{ description }}
        </p>
        <div v-if="hasAction" class="mt-5">
            <slot name="action">
                <button
                    type="button"
                    class="btn-ghost"
                    @click="$emit('action')"
                >
                    {{ actionText }}
                </button>
            </slot>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Icon from './Icon.vue';

const props = defineProps({
    icon: { type: String, default: 'search' },
    title: { type: String, default: 'لا توجد نتائج' },
    description: { type: String, default: '' },
    actionText: { type: String, default: '' },
});

const hasAction = computed(() => Boolean(props.actionText));

defineEmits(['action']);
</script>