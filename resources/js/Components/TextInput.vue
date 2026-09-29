<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-xl border-surface-300 bg-white px-4 py-2.5 text-surface-900 shadow-sm placeholder:text-surface-400 focus:border-brand-500 focus:ring-brand-500"
        v-model="model"
        ref="input"
    />
</template>
