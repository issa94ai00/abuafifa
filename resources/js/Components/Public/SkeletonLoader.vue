<template>
    <div
        class="animate-pulse rounded-xl bg-surface-100"
        :class="className"
    ></div>
</template>

<script setup>
defineProps({
    className: { type: String, default: 'h-24 w-full' },
});
</script>