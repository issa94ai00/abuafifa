<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminIcon from '@/Components/Admin/AdminIcon.vue';
import FlashMessage from '@/Components/Public/FlashMessage.vue';

const page = usePage();

const collapsed = ref(localStorage.getItem('admin.sidebar.collapsed') === '1');
const mobileOpen = ref(false);
const userMenuOpen = ref(false);

const user = computed(() => page.props.auth?.user ?? {});
const unreadMessagesCount = computed(() => Number(page.props.unreadMessagesCount ?? 0));

const roleLabel = computed(
    () => user.value.role_labels?.join('، ') || (user.value.is_staff ? 'مسؤول' : ''),
);

const initials = computed(() => {
    const parts = String(user.value.name || '').trim().split(/\s+/).filter(Boolean);
    return ((parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '')).toUpperCase() || 'أ';
});

const visibleGroups = computed(() => {
    const groups = [];
    for (const group of navigation) {
        const items = group.items.filter(canSee);
        if (items.length) {
            groups.push({ ...group, items });
        }
    }
    return groups;
});

const currentTitle = computed(() => {
    for (const group of navigation) {
        const hit = group.items.find(isActive);
        if (hit) return hit.label;
    }
    return 'لوحة التحكم';
});

watch(mobileOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : '';
});

function canSee(item) {
    if (!user.value || !user.value.is_staff) return false;
    if (item.role && !(user.value.roles || []).includes(item.role)) return false;
    if (item.permission) {
        const perms = user.value.permissions || [];
        const needed = Array.isArray(item.permission) ? item.permission : [item.permission];
        if (!needed.some((p) => perms.includes(p))) return false;
    }
    return true;
}

function isActive(item) {
    return item.active.some((pattern) => route().current(pattern));
}

function toggleCollapsed() {
    collapsed.value = !collapsed.value;
    localStorage.setItem('admin.sidebar.collapsed', collapsed.value ? '1' : '0');
}

const navigation = [
    {
        title: 'الرئيسية',
        items: [
            {
                key: 'dashboard',
                label: 'لوحة التحكم',
                icon: 'dashboard',
                href: () => route('admin.dashboard'),
                active: ['admin.dashboard'],
                permission: 'dashboard.view',
            },
        ],
    },
    {
        title: 'المحتوى',
        items: [
            {
                key: 'products',
                label: 'المنتجات',
                icon: 'box',
                href: () => route('admin.products.index'),
                active: ['admin.products.*'],
                permission: 'product.view',
            },
            {
                key: 'categories',
                label: 'التصنيفات',
                icon: 'folder',
                href: () => route('admin.categories.index'),
                active: ['admin.categories.*'],
                permission: 'category.view',
            },
        ],
    },
    {
        title: 'التسويق',
        items: [
            {
                key: 'offers',
                label: 'العروض',
                icon: 'tag',
                href: () => route('admin.offers.index'),
                active: ['admin.offers.*'],
                permission: 'offer.view',
            },
            {
                key: 'galleries',
                label: 'المعرض',
                icon: 'image',
                href: () => route('admin.galleries.index'),
                active: ['admin.galleries.*'],
                permission: 'gallery.view',
            },
            {
                key: 'media',
                label: 'الوسائط',
                icon: 'collection',
                href: () => route('admin.media.index'),
                active: ['admin.media.*'],
                permission: ['media.upload', 'media.delete'],
            },
        ],
    },
    {
        title: 'التواصل',
        items: [
            {
                key: 'messages',
                label: 'الرسائل',
                icon: 'mail',
                href: () => route('admin.messages.index'),
                active: ['admin.messages.*'],
                permission: 'contact.view',
                badge: 'unread',
            },
        ],
    },
    {
        title: 'النظام',
        items: [
            {
                key: 'users',
                label: 'المستخدمون',
                icon: 'users',
                href: () => route('admin.users.index'),
                active: ['admin.users.*'],
                role: 'super-admin',
            },
            {
                key: 'activity',
                label: 'سجل النشاطات',
                icon: 'activity',
                href: () => route('admin.activity.index'),
                active: ['admin.activity.*'],
                permission: 'activity.view',
            },
            {
                key: 'settings',
                label: 'الإعدادات',
                icon: 'cog',
                href: () => route('admin.settings.index'),
                active: ['admin.settings.*'],
                permission: 'setting.view',
            },
        ],
    },
];
</script>

<template>
    <div class="flex min-h-screen bg-surface-50">
        <!-- Sidebar (mobile drawer + desktop rail) -->
        <aside
            class="fixed inset-y-0 right-0 z-50 flex w-72 flex-col bg-surface-950 text-surface-300 shadow-premium-lg transition-all duration-300 ease-in-out lg:sticky lg:top-0 lg:z-30 lg:h-screen lg:shadow-none"
            :class="[
                mobileOpen ? 'translate-x-0' : 'translate-x-full',
                collapsed ? 'lg:w-[78px]' : 'lg:w-72',
                { 'lg:translate-x-0': true },
            ]"
        >
            <!-- Brand -->
            <div
                class="flex items-center gap-3 border-b border-white/10 bg-gradient-to-l from-brand-900/20 via-transparent to-transparent px-5 py-5"
                :class="collapsed ? 'lg:justify-center lg:px-0' : ''"
            >
                <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-gradient-gold shadow-gold">
                    <span class="font-display text-xl font-bold leading-none text-surface-950">أ</span>
                </div>
                <div v-if="!collapsed" class="min-w-0 leading-tight">
                    <span class="block truncate font-display text-sm font-bold text-white">
                        معرض أبو عفيفة
                    </span>
                    <span class="block text-[11px] font-medium text-gold-400">لوحة التحكم</span>
                </div>
                <button
                    type="button"
                    class="ms-auto grid h-8 w-8 place-items-center rounded-lg text-surface-400 transition hover:bg-white/10 hover:text-white lg:hidden"
                    aria-label="إغلاق القائمة"
                    @click="mobileOpen = false"
                >
                    <AdminIcon name="close" />
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
                <div v-for="group in visibleGroups" :key="group.title">
                    <div
                        v-if="!collapsed"
                        class="mb-2 px-3 text-[11px] font-bold uppercase tracking-wider text-surface-500"
                    >
                        {{ group.title }}
                    </div>
                    <div v-else class="mx-3 mb-3 border-t border-white/10"></div>

                    <div class="space-y-1">
                        <Link
                            v-for="item in group.items"
                            :key="item.key"
                            :href="item.href()"
                            :title="collapsed ? item.label : undefined"
                            class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-all duration-200"
                            :class="[
                                isActive(item)
                                    ? 'bg-gradient-to-l from-brand-600/25 to-brand-600/5 font-semibold text-brand-200 ring-1 ring-inset ring-brand-500/30'
                                    : 'text-surface-300 hover:bg-white/5 hover:text-white',
                                collapsed ? 'lg:justify-center' : '',
                            ]"
                            @click="mobileOpen = false"
                        >
                            <span
                                v-if="isActive(item) && !collapsed"
                                class="absolute start-0 top-1/2 h-6 w-1 -translate-y-1/2 rounded-full bg-gradient-gold"
                            ></span>
                            <span
                                class="grid shrink-0 place-items-center"
                                :class="isActive(item) ? 'text-brand-400' : 'text-surface-400 group-hover:text-brand-300'"
                            >
                                <AdminIcon :name="item.icon" size="md" />
                            </span>
                            <span
                                :class="collapsed ? 'lg:hidden' : ''"
                                class="flex-1 truncate"
                            >
                                {{ item.label }}
                            </span>
                            <span
                                v-if="item.badge === 'unread' && unreadMessagesCount > 0"
                                :class="collapsed ? 'lg:hidden' : ''"
                                class="inline-flex min-w-[20px] items-center justify-center rounded-full bg-brand-600 px-1.5 py-0.5 text-[11px] font-bold leading-4 text-white"
                            >
                                {{ unreadMessagesCount }}
                            </span>
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- Footer -->
            <div class="space-y-1 border-t border-white/10 p-3">
                <a
                    :href="route('home')"
                    :title="collapsed ? 'عرض الموقع' : undefined"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-surface-300 transition hover:bg-white/5 hover:text-white"
                >
                    <AdminIcon name="eye" size="md" class="text-surface-400" />
                    <span v-if="!collapsed" class="flex-1">عرض الموقع</span>
                    <AdminIcon v-if="!collapsed" name="external" size="sm" class="text-surface-500" />
                </a>

                <button
                    type="button"
                    class="hidden w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-surface-300 transition hover:bg-white/5 hover:text-white lg:flex"
                    :class="collapsed ? 'lg:justify-center' : ''"
                    @click="toggleCollapsed"
                >
                    <AdminIcon name="layers" size="md" class="text-surface-400" />
                    <span v-if="!collapsed" class="flex-1 text-start">تقليص الشريط</span>
                </button>
            </div>
        </aside>

        <!-- Mobile backdrop -->
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="mobileOpen"
                class="fixed inset-0 z-40 bg-surface-950/60 backdrop-blur-sm lg:hidden"
                @click="mobileOpen = false"
            ></div>
        </Transition>

        <!-- Content column -->
        <div class="flex min-w-0 flex-1 flex-col">
            <!-- Topbar -->
            <header class="sticky top-0 z-30 flex h-16 items-center gap-2 border-b border-surface-200/80 bg-white/90 px-4 backdrop-blur sm:gap-3 sm:px-6">
                <button
                    type="button"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-surface-600 transition hover:bg-surface-100 lg:hidden"
                    aria-label="فتح القائمة"
                    @click="mobileOpen = true"
                >
                    <AdminIcon name="menu" size="lg" />
                </button>

                <div class="min-w-0 flex-1">
                    <slot name="header">
                        <p class="text-[11px] font-medium leading-4 text-surface-400">لوحة التحكم</p>
                        <h1 class="truncate text-base font-bold text-surface-900 sm:text-lg">
                            {{ currentTitle }}
                        </h1>
                    </slot>
                </div>

                <Link
                    v-if="canSee({ permission: 'contact.view' })"
                    :href="route('admin.messages.index')"
                    class="relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-surface-600 transition hover:bg-surface-100"
                    aria-label="الرسائل"
                >
                    <AdminIcon name="mail" />
                    <span
                        v-if="unreadMessagesCount > 0"
                        class="absolute -end-0.5 -top-0.5 grid h-5 min-w-[20px] place-items-center rounded-full bg-brand-600 px-1 text-[10px] font-bold leading-4 text-white ring-2 ring-white"
                    >
                        {{ unreadMessagesCount }}
                    </span>
                </Link>

                <a
                    :href="route('home')"
                    class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl text-surface-600 transition hover:bg-surface-100 xl:inline-flex"
                    aria-label="عرض الموقع"
                    title="عرض الموقع"
                >
                    <AdminIcon name="external" />
                </a>

                <!-- User dropdown -->
                <div class="relative shrink-0 ms-1">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-xl p-1 transition hover:bg-surface-100"
                        @click="userMenuOpen = !userMenuOpen"
                    >
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-gold font-display text-sm font-bold text-surface-950">
                            {{ initials }}
                        </span>
                        <span class="hidden text-start sm:block">
                            <span class="block max-w-[130px] truncate text-sm font-semibold leading-4 text-surface-900">
                                {{ user.name }}
                            </span>
                            <span class="block text-[11px] font-medium text-surface-400">
                                {{ roleLabel }}
                            </span>
                        </span>
                        <AdminIcon name="chevron-down" size="sm" class="hidden text-surface-400 sm:block" />
                    </button>

                    <Transition
                        enter-active-class="transition ease-out duration-150"
                        enter-from-class="opacity-0 scale-95"
                        enter-to-class="opacity-100 scale-100"
                        leave-active-class="transition ease-in duration-100"
                        leave-from-class="opacity-100 scale-100"
                        leave-to-class="opacity-0 scale-95"
                    >
                        <div
                            v-if="userMenuOpen"
                            class="absolute end-0 top-full z-50 mt-2 w-56 overflow-hidden rounded-2xl border border-surface-200 bg-white py-2 shadow-premium-lg"
                        >
                            <div class="border-b border-surface-100 px-4 pb-3 pt-1">
                                <p class="truncate text-sm font-semibold text-surface-900">{{ user.name }}</p>
                                <p class="truncate text-xs text-surface-400">{{ user.email }}</p>
                            </div>
                            <div class="mt-1 space-y-0.5 p-1.5">
                                <Link
                                    :href="route('profile.edit')"
                                    class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-surface-600 transition hover:bg-surface-100 hover:text-surface-900"
                                    @click="userMenuOpen = false"
                                >
                                    <AdminIcon name="user" size="sm" />
                                    الملف الشخصي
                                </Link>
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-red-600 transition hover:bg-red-50"
                                    @click="userMenuOpen = false"
                                >
                                    <AdminIcon name="logout" size="sm" />
                                    تسجيل الخروج
                                </Link>
                            </div>
                        </div>
                    </Transition>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                <div class="mx-auto w-full max-w-7xl">
                    <slot />
                </div>
            </main>
        </div>

        <FlashMessage />
    </div>
</template>