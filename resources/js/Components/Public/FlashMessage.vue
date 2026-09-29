<script setup>
import { useFlash } from '@/Composables/useFlash';
import Icon from './Icon.vue';

const { visible, message, type, dismiss } = useFlash();

const styles = {
    success: {
        wrapper: 'bg-green-600 border-green-600/20',
        text: 'text-green-200',
        icon: 'text-green-300',
        ring: 'focus:ring-green-500',
    },
    error: {
        wrapper: 'bg-red-600 border-red-600/20',
        text: 'text-red-100',
        icon: 'text-red-300',
        ring: 'focus:ring-red-500',
    },
    warning: {
        wrapper: 'bg-amber-600 border-amber-600/20',
        text: 'text-amber-100',
        icon: 'text-amber-300',
        ring: 'focus:ring-amber-500',
    },
};
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="opacity-0 -translate-y-4"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="visible"
            class="fixed top-24 inset-x-0 z-50 flex justify-center px-4 pointer-events-none"
            role="status"
            aria-live="polite"
        >
            <div
                class="pointer-events-auto flex items-center gap-3 px-5 py-3.5 rounded-xl text-white shadow-premium-lg backdrop-blur-sm border"
                :class="styles[type].wrapper"
            >
                <span class="w-6 h-6 shrink-0 flex items-center justify-center rounded-full bg-white/20">
                    <Icon name="check" v-if="type === 'success'" class="w-4 h-4" />
                    <Icon name="close" v-else class="w-4 h-4" />
                </span>
                <span class="text-sm font-medium">{{ message }}</span>
                <button
                    type="button"
                    class="p-1 rounded-md hover:bg-white/20 transition-colors focus:outline-none focus:ring-2"
                    :class="styles[type].ring"
                    aria-label="إغلاق الإشعار"
                    @click="dismiss"
                >
                    <Icon name="close" class="w-4 h-4" :class="styles[type].icon" />
                </button>
            </div>
        </div>
    </Transition>
</template>