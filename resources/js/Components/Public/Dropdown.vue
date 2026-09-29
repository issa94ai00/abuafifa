<template>
    <div class="relative" ref="dropdownRef">
        <button
            @click="isOpen = !isOpen"
            class="p-2.5 rounded-xl text-surface-600 hover:text-brand-600 hover:bg-brand-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
            :aria-label="label"
            :aria-expanded="isOpen"
        >
            <slot name="trigger" />
        </button>

        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95 translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-1"
        >
            <div
                v-if="isOpen"
                class="absolute top-full mt-2 w-56 bg-white rounded-xl shadow-premium-lg border border-surface-100 py-2 z-50"
                :class="align === 'left' ? 'left-0' : 'right-0'"
            >
                <slot />
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

defineProps({
    label: { type: String, default: '' },
    align: { type: String, default: 'right' },
});

const isOpen = ref(false);
const dropdownRef = ref(null);

function handleClickOutside(e) {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isOpen.value = false;
    }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>
