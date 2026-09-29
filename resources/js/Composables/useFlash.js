import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useFlash() {
    const visible = ref(false);
    const message = ref('');
    const type = ref('success');

    const page = usePage();

    watch(
        () => page.props.flash,
        (flash) => {
            if (flash?.success) {
                message.value = flash.success;
                type.value = 'success';
                visible.value = true;
            } else if (flash?.error) {
                message.value = flash.error;
                type.value = 'error';
                visible.value = true;
            } else if (flash?.warning) {
                message.value = flash.warning;
                type.value = 'warning';
                visible.value = true;
            }
        },
        { immediate: true }
    );

    function dismiss() {
        visible.value = false;
        message.value = '';
    }

    return { visible, message, type, dismiss };
}
