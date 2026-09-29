<script setup>
/**
 * Renders the `flash` prop the admin controllers set with `back()->with(...)`.
 * The admin layout has no toast of its own, so CRUD screens mount this to
 * confirm that a save, delete or restore actually landed.
 */
import { onBeforeUnmount, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';

const props = defineProps({
    duration: { type: Number, default: 4000 },
});

const page = usePage();

const visible = ref(false);
const message = ref('');
const tone = ref('success');

let timer = null;

const tones = {
    success: { icon: 'check', cls: 'bg-emerald-600', ring: 'ring-emerald-500/30' },
    error: { icon: 'alert', cls: 'bg-red-600', ring: 'ring-red-500/30' },
    warning: { icon: 'alert', cls: 'bg-amber-500', ring: 'ring-amber-400/30' },
};

function show(text, type) {
    message.value = text;
    tone.value = type;
    visible.value = true;

    clearTimeout(timer);
    timer = setTimeout(dismiss, props.duration);
}

function dismiss() {
    clearTimeout(timer);
    visible.value = false;
}

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) show(flash.success, 'success');
        else if (flash?.error) show(flash.error, 'error');
        else if (flash?.warning) show(flash.warning, 'warning');
    },
    { immediate: true, deep: true },
);

onBeforeUnmount(() => clearTimeout(timer));

defineExpose({ show, dismiss });
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-3 opacity-0"
            enter-to-class="translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100"
            leave-to-class="translate-y-3 opacity-0"
        >
            <div
                v-if="visible"
                class="fixed inset-x-4 bottom-6 z-[60] mx-auto w-auto max-w-sm sm:inset-x-auto sm:start-6"
                role="status"
                aria-live="polite"
            >
                <div
                    class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold text-white shadow-premium-lg ring-4"
                    :class="[tones[tone].cls, tones[tone].ring]"
                >
                    <AdminIcon :name="tones[tone].icon" size="sm" class="shrink-0" />
                    <span class="min-w-0 flex-1 leading-6">{{ message }}</span>
                    <button
                        type="button"
                        class="grid h-7 w-7 shrink-0 place-items-center rounded-lg text-white/80 transition hover:bg-white/15 hover:text-white"
                        aria-label="إغلاق"
                        @click="dismiss"
                    >
                        <AdminIcon name="close" size="xs" />
                    </button>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
