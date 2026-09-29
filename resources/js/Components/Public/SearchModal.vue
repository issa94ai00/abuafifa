<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Icon from './Icon.vue';

const props = defineProps({
    open: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);

const page = usePage();
const term = ref('');
const inputRef = ref(null);
const suggestedCategories = computed(() => {
    const cats = page.props.categories;
    return Array.isArray(cats) ? cats.slice(0, 6) : [];
});

function close() {
    emit('close');
    term.value = '';
}

function submit() {
    const q = term.value.trim();
    if (!q) return;
    router.get('/search', { q }, {
        preserveState: true,
        onSuccess: () => close(),
    });
}

function handleEscape(e) {
    if (e.key === 'Escape') close();
}

onMounted(() => {
    document.addEventListener('keydown', handleEscape);
    inputRef.value?.focus();
});

onUnmounted(() => document.removeEventListener('keydown', handleEscape));
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" aria-label="بحث في المنتجات">
        <!-- Backdrop -->
        <div class="min-h-screen flex items-start justify-center">
            <div class="fixed inset-0 bg-surface-950/70 backdrop-blur-sm" @click="close"></div>

            <!-- Panel -->
            <div class="relative mt-24 sm:mt-28 w-[calc(100%-2rem)] max-w-2xl">
                <div class="bg-white rounded-2xl shadow-premium-lg overflow-hidden animate-scale-in" style="animation-duration: 0.25s">
                    <!-- Search form -->
                    <form class="flex items-center gap-3 px-5 py-4 border-b border-surface-100" @submit.prevent="submit">
                        <Icon name="search" class="w-5 h-5 text-surface-400 shrink-0" />
                        <input
                            ref="inputRef"
                            v-model="term"
                            type="search"
                            placeholder="ابحث عن غرف نوم، كنب، طاولات..."
                            aria-label="بحث في المنتجات"
                            class="flex-1 bg-transparent border-0 p-0 text-surface-900 placeholder:text-surface-400 focus:ring-0 text-base"
                        />
                        <button
                            type="submit"
                            class="btn-primary !px-5 !py-2 text-sm"
                            :disabled="!term.trim()"
                        >
                            بحث
                        </button>
                        <button
                            type="button"
                            class="p-2 rounded-lg text-surface-500 hover:bg-surface-100 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                            aria-label="إغلاق البحث"
                            @click="close"
                        >
                            <Icon name="close" />
                        </button>
                    </form>

                    <!-- Suggestions -->
                    <div class="px-6 py-5">
                        <p class="text-xs font-semibold text-surface-400 uppercase tracking-wider mb-3">
                            تصفح حسب التصنيف
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <a
                                v-for="category in suggestedCategories"
                                :key="category.id"
                                :href="`/categories/${category.slug}`"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-surface-100 text-sm text-surface-700 hover:bg-brand-50 hover:text-brand-700 transition-colors"
                                @click="close"
                            >
                                {{ category.name_ar }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>