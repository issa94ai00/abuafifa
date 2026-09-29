<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SiteLogo from './SiteLogo.vue';
import Icon from './Icon.vue';
import MobileMenu from './MobileMenu.vue';
import SearchModal from './SearchModal.vue';
import CategoryDropdown from './CategoryDropdown.vue';

const props = defineProps({
    /**
     * Let the page show through the bar (home page over the hero). The bar
     * turns to glass as soon as the reader scrolls off the hero.
     */
    transparent: { type: Boolean, default: false },
});

const page = usePage();

/** Past this the announcement bar is rolled up and the bar goes to glass. */
const SOLID_AT = 40;
/** Only start auto-hiding once well clear of the hero. */
const HIDE_AFTER = 560;

const scrolled = ref(false);
const hidden = ref(false);
const progress = ref(0);

const isMobileMenuOpen = ref(false);
const isSearchOpen = ref(false);
const isUserMenuOpen = ref(false);
const isCategoryMenuOpen = ref(false);
const userMenuRef = ref(null);
const barRef = ref(null);
const spacerHeight = ref(0);

const whatsapp = computed(() => page.props.settings?.whatsapp || '');
const user = computed(() => page.props.auth?.user);

/**
 * Transparent only while the home hero is still behind the bar. Every other
 * page — and every scrolled state — gets the frosted treatment.
 */
const isGlass = computed(() => !props.transparent || scrolled.value);

const navLinks = computed(() => [
    { label: 'الرئيسية', href: '/', active: page.url === '/' },
    { label: 'من نحن', href: '/about', active: page.url.startsWith('/about') },
    { label: 'منتجاتنا', href: '/products', active: page.url.startsWith('/products') },
    { label: 'العروض', href: '/offers', active: page.url.startsWith('/offers') },
    { label: 'المعرض', href: '/gallery', active: page.url.startsWith('/gallery') },
    { label: 'تواصل معنا', href: '/contact', active: page.url.startsWith('/contact') },
]);

/** An open overlay must never be left hanging by an auto-hide. */
const isOverlayOpen = computed(() =>
    isMobileMenuOpen.value || isSearchOpen.value || isUserMenuOpen.value || isCategoryMenuOpen.value
);

let lastY = 0;
let ticking = false;

function readScroll() {
    const y = window.scrollY;
    const max = document.documentElement.scrollHeight - window.innerHeight;

    scrolled.value = y > SOLID_AT;
    progress.value = max > 0 ? Math.min(100, (y / max) * 100) : 0;

    // Auto-hide going down, reveal the moment the reader heads back up.
    if (isOverlayOpen.value || y < HIDE_AFTER) {
        hidden.value = false;
    } else if (Math.abs(y - lastY) > 6) {
        hidden.value = y > lastY;
    }

    lastY = y;
    ticking = false;
}

function onScroll() {
    if (!ticking) {
        ticking = true;
        window.requestAnimationFrame(readScroll);
    }
}

/**
 * The bar is fixed, so non-transparent pages need a spacer to stand in for it.
 * Measured at rest only — re-measuring mid-scroll, while the announcement strip
 * is rolling up, would shunt the page content around under the reader.
 */
function measure() {
    if (barRef.value && !scrolled.value) {
        spacerHeight.value = barRef.value.offsetHeight;
    }
}

function handleClickOutside(e) {
    if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
        isUserMenuOpen.value = false;
    }
}

function handleEscape(e) {
    if (e.key === 'Escape') isUserMenuOpen.value = false;
}

onMounted(() => {
    lastY = window.scrollY;
    readScroll();
    measure();

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', measure, { passive: true });
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
    window.removeEventListener('resize', measure);
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleEscape);
});

function whatsappLink() {
    return `https://wa.me/${String(whatsapp.value).replace(/\D/g, '')}`;
}
</script>

<template>
    <div>
        <!-- Fixed bar -->
        <div
            ref="barRef"
            class="fixed inset-x-0 top-0 z-50 transition-transform duration-500 ease-out motion-reduce:transition-none"
            :class="hidden ? '-translate-y-full' : 'translate-y-0'"
        >
            <!-- Main navbar -->
            <header
                class="relative transition-[background-color,box-shadow,backdrop-filter] duration-500"
                :class="isGlass ? 'glass-nav' : 'bg-transparent border-b border-white/10'"
            >
                <nav class="section-container" aria-label="التنقل الرئيسي">
                    <div
                        class="flex items-center justify-between transition-all duration-300"
                        :class="scrolled ? 'h-14 lg:h-16' : 'h-16 lg:h-20'"
                    >
                        <!-- Mobile menu button -->
                        <button
                            type="button"
                            class="lg:hidden p-2 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                            :class="isGlass ? 'text-surface-700 hover:bg-surface-100' : 'text-white hover:bg-white/15'"
                            aria-label="فتح القائمة"
                            :aria-expanded="isMobileMenuOpen"
                            @click="isMobileMenuOpen = true"
                        >
                            <Icon name="menu" />
                        </button>

                        <!-- Logo -->
                        <Link href="/" class="shrink-0" aria-label="معرض أبو عفيفة - الرئيسية">
                            <SiteLogo :light="!isGlass" />
                        </Link>

                        <!-- Desktop nav -->
                        <div class="hidden lg:flex items-center gap-7">
                            <template v-for="link in navLinks" :key="link.href">
                                <CategoryDropdown
                                    v-if="link.label === 'منتجاتنا'"
                                    :light="!isGlass"
                                    @open-change="isCategoryMenuOpen = $event"
                                />
                                <Link
                                    v-else
                                    :href="link.href"
                                    class="group relative py-2 text-sm font-medium transition-colors duration-200 focus:outline-none"
                                    :class="[
                                        isGlass
                                            ? (link.active ? 'text-brand-700' : 'text-surface-700 hover:text-brand-600')
                                            : (link.active ? 'text-white text-shadow-nav' : 'text-white/85 hover:text-white text-shadow-nav'),
                                    ]"
                                >
                                    {{ link.label }}
                                    <span
                                        class="absolute bottom-0 inset-x-0 h-0.5 bg-gradient-gold rounded-full origin-right transition-transform duration-300"
                                        :class="link.active ? 'scale-x-100 group-hover:scale-x-100' : 'scale-x-0 group-hover:scale-x-100'"
                                    ></span>
                                </Link>
                            </template>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1.5 sm:gap-2">
                            <button
                                type="button"
                                class="hidden sm:inline-flex items-center justify-center p-2.5 w-10 h-10 rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                                :class="isGlass ? 'text-surface-700 hover:bg-surface-100' : 'text-white hover:bg-white/15'"
                                aria-label="بحث"
                                @click="isSearchOpen = true"
                            >
                                <Icon name="search" class="w-5 h-5" />
                            </button>

                            <Link
                                :href="user?.id ? '/favorites' : '/login'"
                                class="hidden sm:inline-flex items-center justify-center p-2.5 w-10 h-10 rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                                :class="isGlass ? 'text-surface-700 hover:bg-surface-100' : 'text-white hover:bg-white/15'"
                                :aria-label="user?.id ? 'المفضلة' : 'تسجيل الدخول لعرض المفضلة'"
                            >
                                <Icon name="heart" class="w-5 h-5" />
                            </Link>

                            <!-- User menu (logged in) -->
                            <div v-if="user?.id" class="relative hidden sm:block" ref="userMenuRef">
                                <button
                                    type="button"
                                    class="flex items-center gap-2 text-sm font-medium transition-colors px-2 py-1.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                                    :class="isGlass ? 'text-surface-700 hover:text-brand-600' : 'text-white/90 hover:text-white text-shadow-nav'"
                                    :aria-expanded="isUserMenuOpen"
                                    aria-haspopup="true"
                                    @click="isUserMenuOpen = !isUserMenuOpen"
                                >
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gradient-gold text-surface-950 shadow-gold">
                                        <Icon name="user" class="w-4 h-4" />
                                    </span>
                                    <span class="max-w-[5.5rem] truncate">{{ user.name }}</span>
                                    <Icon
                                        name="chevron-down"
                                        class="w-4 h-4 transition-transform duration-300"
                                        :class="[isGlass ? 'text-surface-400' : 'text-white/70', { 'rotate-180': isUserMenuOpen }]"
                                    />
                                </button>

                                <Transition
                                    enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 scale-95 -translate-y-1"
                                    enter-to-class="opacity-100 scale-100 translate-y-0"
                                    leave-active-class="transition ease-in duration-150"
                                    leave-from-class="opacity-100 scale-100 translate-y-0"
                                    leave-to-class="opacity-0 scale-95 -translate-y-1"
                                >
                                    <div
                                        v-if="isUserMenuOpen"
                                        class="absolute left-0 top-full mt-2 w-60 bg-white rounded-2xl border border-surface-200 shadow-premium-lg overflow-hidden"
                                    >
                                        <div class="border-b border-surface-100 px-4 py-3">
                                            <p class="truncate text-sm font-semibold text-surface-900">{{ user.name }}</p>
                                            <p class="truncate text-xs text-surface-400">{{ user.email }}</p>
                                        </div>
                                        <div class="p-1.5 space-y-0.5">
                                            <Link
                                                v-if="user.is_staff"
                                                href="/admin"
                                                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-surface-600 hover:bg-surface-100 hover:text-surface-900 transition-colors"
                                                @click="isUserMenuOpen = false"
                                            >
                                                <Icon name="shield" class="w-4 h-4" />
                                                لوحة التحكم
                                            </Link>
                                            <Link
                                                href="/profile"
                                                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-surface-600 hover:bg-surface-100 hover:text-surface-900 transition-colors"
                                                @click="isUserMenuOpen = false"
                                            >
                                                <Icon name="user" class="w-4 h-4" />
                                                حسابي
                                            </Link>
                                            <Link
                                                href="/favorites"
                                                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-surface-600 hover:bg-surface-100 hover:text-surface-900 transition-colors"
                                                @click="isUserMenuOpen = false"
                                            >
                                                <Icon name="heart" class="w-4 h-4" />
                                                المفضلة
                                            </Link>
                                            <Link
                                                :href="route('logout')"
                                                method="post"
                                                as="button"
                                                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors"
                                                @click="isUserMenuOpen = false"
                                            >
                                                <Icon name="logout" class="w-4 h-4" />
                                                تسجيل الخروج
                                            </Link>
                                        </div>
                                    </div>
                                </Transition>
                            </div>

                            <!-- WhatsApp CTA -->
                            <a
                                :href="whatsappLink()"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="hidden md:inline-flex items-center gap-2 bg-[#25D366] text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-all duration-200 hover:bg-[#1fb858] hover:scale-[1.02] active:scale-95 focus:outline-none focus:ring-2 focus:ring-[#25D366] focus:ring-offset-2"
                            >
                                <Icon name="whatsapp" />
                                <span>واتساب</span>
                            </a>
                            <a
                                :href="whatsappLink()"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="md:hidden inline-flex items-center justify-center w-10 h-10 bg-[#25D366] text-white rounded-xl transition-all duration-200 hover:bg-[#1fb858] focus:outline-none focus:ring-2 focus:ring-[#25D366] focus:ring-offset-2"
                                aria-label="تواصل عبر واتساب"
                            >
                                <Icon name="whatsapp" class="w-5 h-5" />
                            </a>
                        </div>
                    </div>
                </nav>

                <!-- Reading progress, pinned to the bottom edge of the bar -->
                <div class="absolute inset-x-0 bottom-0 h-0.5 overflow-hidden pointer-events-none" aria-hidden="true">
                    <div
                        class="h-full bg-gradient-gold origin-right transition-[width] duration-150 ease-out"
                        :style="{ width: `${progress}%` }"
                    ></div>
                </div>
            </header>
        </div>

        <!-- Stands in for the fixed bar on pages that don't run under it -->
        <div v-if="!transparent" :style="{ height: `${spacerHeight}px` }" aria-hidden="true"></div>

        <!-- Mobile drawer -->
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <MobileMenu v-if="isMobileMenuOpen" @close="isMobileMenuOpen = false" @search="isSearchOpen = true" />
        </Transition>

        <!-- Search modal -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <SearchModal v-if="isSearchOpen" @close="isSearchOpen = false" />
        </Transition>
    </div>
</template>
