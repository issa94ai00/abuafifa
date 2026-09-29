<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SiteLogo from './SiteLogo.vue';
import Icon from './Icon.vue';

const props = defineProps({
    open: { type: Boolean, default: true },
});

const emit = defineEmits(['close']);

const page = usePage();
const expandedCategories = ref(false);

const links = computed(() => [
    { label: 'الرئيسية', href: '/', active: page.url === '/' },
    { label: 'من نحن', href: '/about', active: page.url.startsWith('/about') },
    { label: 'المعرض', href: '/gallery', active: page.url.startsWith('/gallery') },
    { label: 'العروض', href: '/offers', active: page.url.startsWith('/offers') },
    { label: 'تواصل معنا', href: '/contact', active: page.url.startsWith('/contact') },
]);

const categories = computed(() => {
    const cats = page.props.categories;
    return Array.isArray(cats) ? cats.slice(0, 8) : [];
});

const whatsapp = computed(() => page.props.settings?.whatsapp || '');

const user = computed(() => page.props.auth?.user);

function close() {
    emit('close');
}

function openSearch() {
    close();
    emit('search');
}

function handleEscape(e) {
    if (e.key === 'Escape') close();
}

watch(() => props.open, (open) => {
    if (open) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

onMounted(() => {
    document.addEventListener('keydown', handleEscape);
    document.body.style.overflow = 'hidden';
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
    document.body.style.overflow = '';
});
</script>

<template>
    <div class="fixed inset-0 z-50">
        <!-- Backdrop -->
        <div
            class="absolute inset-0 bg-surface-950/60 backdrop-blur-sm"
            @click="close"
        ></div>

        <!-- Drawer -->
        <div
            class="absolute inset-y-0 right-0 w-[85%] max-w-sm bg-white shadow-2xl flex flex-col"
            :class="open ? 'animate-slide-in-right' : ''"
            style="animation-duration: 0.35s"
        >
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-surface-100">
                <SiteLogo />
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        class="p-2 rounded-lg text-surface-500 hover:bg-surface-100 hover:text-surface-800 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                        aria-label="بحث"
                        @click="openSearch"
                    >
                        <Icon name="search" class="w-5 h-5" />
                    </button>
                    <button
                        type="button"
                        class="p-2 rounded-lg text-surface-500 hover:bg-surface-100 hover:text-surface-800 transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                        aria-label="إغلاق القائمة"
                        @click="close"
                    >
                        <Icon name="close" />
                    </button>
                </div>
            </div>

            <!-- Body -->
            <nav class="flex-1 overflow-y-auto py-4 px-3" aria-label="القائمة الرئيسية">
                <ul class="space-y-1">
                    <li>
                        <Link
                            href="/products"
                            class="flex items-center justify-between px-4 py-3 rounded-xl text-surface-800 font-medium hover:bg-brand-50 transition-colors"
                            @click="close"
                        >
                            <span>منتجاتنا</span>
                            <Icon name="chevron-left" class="w-4 h-4 text-surface-400" />
                        </Link>

                        <button
                            v-if="categories.length"
                            type="button"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-brand-600 font-medium hover:bg-brand-50 transition-colors"
                            :aria-expanded="expandedCategories"
                            @click="expandedCategories = !expandedCategories"
                        >
                            <span>تسوق حسب التصنيف</span>
                            <Icon name="chevron-down" class="w-4 h-4 transition-transform" :class="{ 'rotate-180': expandedCategories }" />
                        </button>

                        <Transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 -translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition ease-in duration-150"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 -translate-y-1"
                        >
                            <ul v-if="expandedCategories" class="mr-4 border-r-2 border-brand-100 pr-2 space-y-0.5 py-1">
                                <li v-for="category in categories" :key="category.id">
                                    <Link
                                        :href="`/categories/${category.slug}`"
                                        class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm text-surface-600 hover:bg-brand-50 hover:text-brand-700 transition-colors"
                                        @click="close"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-brand-400"></span>
                                        {{ category.name_ar }}
                                    </Link>
                                </li>
                            </ul>
                        </Transition>
                    </li>

                    <li v-for="link in links" :key="link.href">
                        <Link
                            :href="link.href"
                            class="flex items-center justify-between px-4 py-3 rounded-xl transition-colors"
                            :class="link.active ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-surface-800 font-medium hover:bg-brand-50'"
                            @click="close"
                        >
                            <span>{{ link.label }}</span>
                            <span v-if="link.active" class="w-1.5 h-1.5 rounded-full bg-brand-500"></span>
                        </Link>
                    </li>

                    <li class="pt-2 mt-2 border-t border-surface-100">
                        <button
                            type="button"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-surface-800 font-medium hover:bg-brand-50 transition-colors"
                            @click="openSearch"
                        >
                            <span>بحث</span>
                            <Icon name="search" class="w-4 h-4 text-surface-400" />
                        </button>
                    </li>

                    <li v-if="user?.id">
                        <Link
                            href="/favorites"
                            class="flex items-center justify-between px-4 py-3 rounded-xl text-surface-800 font-medium hover:bg-brand-50 transition-colors"
                            @click="close"
                        >
                            <span>المفضلة</span>
                            <Icon name="heart" class="w-4 h-4 text-surface-400" />
                        </Link>
                    </li>
                </ul>
            </nav>

            <!-- Footer -->
            <div class="border-t border-surface-100 p-4 space-y-3">
                <div v-if="user?.id" class="space-y-2">
                    <div class="flex items-center gap-3 px-1">
                        <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-brand-50 text-brand-700 shrink-0">
                            <Icon name="user" class="w-5 h-5" />
                        </span>
                        <span class="text-sm font-semibold text-surface-900 truncate">{{ user.name }}</span>
                    </div>
                    <div class="flex gap-2">
                        <Link
                            :href="user.is_staff ? '/admin' : '/profile'"
                            class="flex-1 inline-flex items-center justify-center gap-2 border border-surface-200 text-surface-700 text-sm font-medium py-3 rounded-xl hover:bg-surface-50 transition-colors"
                            @click="close"
                        >
                            {{ user.is_staff ? 'لوحة التحكم' : 'حسابي' }}
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="inline-flex items-center justify-center gap-2 border border-red-200 text-red-600 text-sm font-medium py-3 px-4 rounded-xl hover:bg-red-50 transition-colors"
                        >
                            <Icon name="logout" class="w-5 h-5" />
                        </Link>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a
                        :href="`https://wa.me/${String(whatsapp).replace(/\D/g, '')}`"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="flex-1 inline-flex items-center justify-center gap-2 bg-[#25D366] text-white text-sm font-medium py-3 rounded-xl hover:bg-[#1fb858] transition-colors"
                    >
                        <Icon name="whatsapp" class="w-5 h-5" />
                        واتساب
                    </a>
                </div>
                <p class="text-xs text-center text-surface-400">
                    معرض أبو عفيفة للمفروشات — أثاث فاخر يليق بذوقك
                </p>
            </div>
        </div>
    </div>
</template>