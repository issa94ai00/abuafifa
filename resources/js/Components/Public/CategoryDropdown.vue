<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Icon from './Icon.vue';

const props = defineProps({
    /** Rendered over the transparent navbar, so the trigger goes white. */
    light: { type: Boolean, default: false },
});

const emit = defineEmits(['open-change']);

const page = usePage();

/** How many categories the panel shows before deferring to the full listing. */
const VISIBLE = 8;
/** Hover intent: slow to open, slower to close, so a passing cursor is ignored. */
const OPEN_DELAY = 90;
const CLOSE_DELAY = 180;

const isOpen = ref(false);
const dropdownRef = ref(null);
const triggerRef = ref(null);
const panelRef = ref(null);

const allCategories = computed(() => {
    const cats = page.props.categories;

    return Array.isArray(cats) ? cats : [];
});

const categories = computed(() => allCategories.value.slice(0, VISIBLE));
const overflowCount = computed(() => Math.max(0, allCategories.value.length - VISIBLE));

/** Any catalogue page — the listing itself or a single category — lights the trigger. */
const onCatalog = computed(
    () => page.url.startsWith('/products') || page.url.startsWith('/categories')
);

function isCurrent(category) {
    return page.url.startsWith(`/categories/${category.slug}`);
}

// ------------------------------------------------------------------ open/close

let openTimer = null;
let closeTimer = null;

/**
 * Hover-to-open is a pointer affordance only. On a touch screen the synthetic
 * mouseenter fires just before the tap, which would open then immediately
 * toggle the panel shut again.
 */
function canHover() {
    return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
}

function clearTimers() {
    clearTimeout(openTimer);
    clearTimeout(closeTimer);
}

function setOpen(value) {
    clearTimers();
    isOpen.value = value;
    emit('open-change', value);
}

function open() {
    setOpen(true);
}

function close() {
    setOpen(false);
}

function onPointerEnter() {
    if (!canHover()) return;
    clearTimers();
    openTimer = setTimeout(open, OPEN_DELAY);
}

function onPointerLeave() {
    if (!canHover()) return;
    clearTimers();
    closeTimer = setTimeout(close, CLOSE_DELAY);
}

function onTriggerClick() {
    setOpen(!isOpen.value);
}

function closeAndRefocus() {
    close();
    triggerRef.value?.focus();
}

// -------------------------------------------------------------- keyboard nav

function items() {
    return panelRef.value ? Array.from(panelRef.value.querySelectorAll('[data-item]')) : [];
}

function focusItem(index) {
    const list = items();

    if (!list.length) return;

    list[(index + list.length) % list.length].focus();
}

async function openToItem(index) {
    open();
    await nextTick();
    focusItem(index);
}

function onPanelKeydown(e) {
    const list = items();
    const at = list.indexOf(document.activeElement);

    switch (e.key) {
        case 'ArrowDown':
            e.preventDefault();
            focusItem(at + 1);
            break;
        case 'ArrowUp':
            e.preventDefault();
            focusItem(at < 0 ? -1 : at - 1);
            break;
        case 'Home':
            e.preventDefault();
            focusItem(0);
            break;
        case 'End':
            e.preventDefault();
            focusItem(list.length - 1);
            break;
        case 'Escape':
            e.preventDefault();
            closeAndRefocus();
            break;
        case 'Tab':
            close();
            break;
    }
}

// ------------------------------------------------------------------ lifecycle

function handleClickOutside(e) {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) close();
}

function handleEscape(e) {
    if (e.key === 'Escape' && isOpen.value) closeAndRefocus();
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    if (isOpen.value) emit('open-change', false);
    clearTimers();
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleEscape);
});
</script>

<template>
    <div
        class="relative"
        ref="dropdownRef"
        @mouseenter="onPointerEnter"
        @mouseleave="onPointerLeave"
    >
        <button
            ref="triggerRef"
            type="button"
            class="group relative inline-flex items-center gap-1.5 py-2 text-sm font-medium rounded-md transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50 focus-visible:ring-offset-2"
            :class="light
                ? (isOpen || onCatalog ? 'text-white text-shadow-nav' : 'text-white/85 hover:text-white text-shadow-nav')
                : (isOpen || onCatalog ? 'text-brand-700' : 'text-surface-700 hover:text-brand-600')"
            aria-haspopup="true"
            aria-controls="nav-categories-panel"
            :aria-expanded="isOpen"
            @click="onTriggerClick"
            @keydown.down.prevent="openToItem(0)"
            @keydown.up.prevent="openToItem(-1)"
        >
            منتجاتنا
            <Icon
                name="chevron-down"
                class="w-4 h-4 transition-transform duration-200 motion-reduce:transition-none"
                :class="{ 'rotate-180': isOpen }"
            />
            <!-- Same gold underline the sibling nav links use -->
            <span
                class="absolute bottom-0 inset-x-0 h-0.5 bg-gradient-gold rounded-full origin-right transition-transform duration-300 motion-reduce:transition-none"
                :class="onCatalog || isOpen ? 'scale-x-100' : 'scale-x-0 group-hover:scale-x-100'"
            ></span>
        </button>

        <Transition
            enter-active-class="transition ease-out duration-200 motion-reduce:transition-none"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-150 motion-reduce:transition-none"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-1"
        >
            <div
                v-if="isOpen"
                id="nav-categories-panel"
                ref="panelRef"
                class="absolute right-0 top-full pt-3 w-[min(32rem,calc(100vw-2rem))] origin-top-right"
                @keydown="onPanelKeydown"
            >
                <div class="bg-white rounded-2xl shadow-premium-lg border border-surface-100 overflow-hidden">
                    <div class="flex items-baseline justify-between px-5 pt-4 pb-3">
                        <p class="font-display text-base font-semibold text-surface-900">تصفّح الأقسام</p>
                        <p class="text-xs text-surface-400">أثاث مصنوع بحرفية حلبية</p>
                    </div>

                    <div v-if="categories.length" class="grid grid-cols-2 gap-1 px-2.5 pb-2.5">
                        <Link
                            v-for="category in categories"
                            :key="category.id"
                            data-item
                            :href="`/categories/${category.slug}`"
                            class="group/item flex items-center gap-3 rounded-xl p-2.5 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50"
                            :class="isCurrent(category) ? 'bg-brand-50' : 'hover:bg-surface-50'"
                            :aria-current="isCurrent(category) ? 'page' : undefined"
                            @click="close"
                        >
                            <span
                                class="relative w-12 h-12 shrink-0 rounded-xl bg-gradient-warm overflow-hidden flex items-center justify-center ring-1 transition-shadow duration-150"
                                :class="isCurrent(category) ? 'ring-brand-300' : 'ring-surface-100 group-hover/item:ring-brand-200'"
                            >
                                <img
                                    v-if="category.image_thumb || category.image_url"
                                    :src="category.image_thumb || category.image_url"
                                    alt=""
                                    aria-hidden="true"
                                    loading="lazy"
                                    decoding="async"
                                    class="w-full h-full object-cover transition-transform duration-300 group-hover/item:scale-105 motion-reduce:transition-none"
                                />
                                <span v-else class="font-display font-bold text-brand-600">
                                    {{ category.name_ar?.charAt(0) }}
                                </span>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span
                                    class="block truncate text-sm font-medium transition-colors"
                                    :class="isCurrent(category) ? 'text-brand-700' : 'text-surface-800 group-hover/item:text-brand-700'"
                                >
                                    {{ category.name_ar }}
                                </span>
                                <span v-if="category.products_count" class="mt-0.5 block text-[11px] text-surface-400">
                                    {{ category.products_count }} منتج
                                </span>
                                <span v-if="category.children?.length" class="mt-1 block truncate text-[11px] text-surface-400">
                                    {{ category.children.map((c) => c.name_ar).join(' · ') }}
                                </span>
                            </span>

                            <Icon
                                name="chevron-left"
                                class="w-4 h-4 shrink-0 text-surface-300 opacity-0 -translate-x-1 transition-all duration-200 group-hover/item:opacity-100 group-hover/item:translate-x-0 motion-reduce:transition-none"
                            />
                        </Link>
                    </div>

                    <p v-else class="px-5 pb-4 text-sm text-surface-400">
                        لا توجد أقسام متاحة حالياً.
                    </p>

                    <div class="border-t border-surface-100 p-2.5">
                        <Link
                            data-item
                            href="/products"
                            class="flex items-center justify-center gap-2 rounded-xl bg-brand-50 py-3 text-sm font-medium text-brand-700 transition-colors hover:bg-brand-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50"
                            @click="close"
                        >
                            <span>
                                عرض جميع المنتجات
                                <template v-if="overflowCount">‏(+{{ overflowCount }} قسم)</template>
                            </span>
                            <Icon name="arrow-left" class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
