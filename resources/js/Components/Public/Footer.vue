<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import SiteLogo from './SiteLogo.vue';
import Icon from './Icon.vue';

const page = usePage();

const settings = computed(() => page.props.settings || {});

const currentYear = new Date().getFullYear();

const quickLinks = [
    { label: 'الرئيسية', href: '/' },
    { label: 'من نحن', href: '/about' },
    { label: 'المنتجات', href: '/products' },
    { label: 'العروض', href: '/offers' },
    { label: 'المعرض', href: '/gallery' },
    { label: 'تواصل معنا', href: '/contact' },
];

const services = [
    'تصميم داخلي',
    'توصيل وتركيب',
    'تخصيص الأثاث',
    'استشارة مجانية',
    'ضمان الجودة',
];

const socials = computed(() => {
    const list = [];
    if (settings.value.facebook) list.push({ name: 'Facebook', icon: 'facebook', url: settings.value.facebook });
    if (settings.value.instagram) list.push({ name: 'Instagram', icon: 'instagram', url: settings.value.instagram });
    if (settings.value.tiktok) list.push({ name: 'TikTok', icon: 'tiktok', url: settings.value.tiktok });
    return list;
});
</script>

<template>
    <footer class="bg-gradient-premium text-white" role="contentinfo">
        <!-- Top decorative line -->
        <div class="h-px bg-gradient-to-l from-transparent via-gold-500/40 to-transparent"></div>

        <div class="section-container pt-16 sm:pt-20 pb-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 sm:gap-8">
                <!-- Brand -->
                <div class="sm:col-span-2 lg:col-span-1">
                    <SiteLogo light class="mb-5" />
                    <p class="text-surface-400 text-sm leading-relaxed mb-6 max-w-xs">
                        معرض أبو عفيفة للمفروشات — أكثر من خمسة عشر عاماً من الخبرة في تقديم
                        الأثاث الفاخر بحرفية أصيلة، من قلب العقبة.
                    </p>
                    <div class="flex items-center gap-3">
                        <a
                            v-for="social in socials"
                            :key="social.name"
                            :href="social.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center text-surface-300 hover:bg-gold-500/20 hover:text-gold-400 transition-colors focus:outline-none focus:ring-2 focus:ring-gold-500/50"
                            :aria-label="social.name"
                        >
                            <Icon :name="social.icon" />
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="font-display font-semibold text-white text-sm mb-5">روابط سريعة</h4>
                    <ul class="space-y-2.5">
                        <li v-for="link in quickLinks" :key="link.href">
                            <Link
                                :href="link.href"
                                class="text-sm text-surface-400 hover:text-gold-400 transition-colors duration-200"
                            >
                                {{ link.label }}
                            </Link>
                        </li>
                    </ul>
                </div>

                <!-- Services -->
                <div>
                    <h4 class="font-display font-semibold text-white text-sm mb-5">خدماتنا</h4>
                    <ul class="space-y-2.5">
                        <li v-for="service in services" :key="service" class="flex items-center gap-2">
                            <span class="w-1 h-1 rounded-full bg-gold-500"></span>
                            <span class="text-sm text-surface-400">{{ service }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="font-display font-semibold text-white text-sm mb-5">تواصل معنا</h4>
                    <ul class="space-y-4">
                        <li v-if="settings.phone">
                            <a
                                :href="`tel:${settings.phone}`"
                                class="flex items-start gap-2.5 text-sm text-surface-400 hover:text-gold-400 transition-colors"
                            >
                                <Icon name="phone" class="mt-0.5 shrink-0" />
                                <span dir="ltr">{{ settings.phone }}</span>
                            </a>
                        </li>
                        <li v-if="settings.whatsapp">
                            <a
                                :href="`https://wa.me/${String(settings.whatsapp).replace(/\D/g, '')}`"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-start gap-2.5 text-sm text-surface-400 hover:text-[#25D366] transition-colors"
                            >
                                <Icon name="whatsapp" class="mt-0.5 shrink-0" />
                                <span dir="ltr">{{ settings.whatsapp }}</span>
                            </a>
                        </li>
                        <li v-if="settings.email">
                            <a
                                :href="`mailto:${settings.email}`"
                                class="flex items-start gap-2.5 text-sm text-surface-400 hover:text-gold-400 transition-colors"
                            >
                                <Icon name="mail" class="mt-0.5 shrink-0" />
                                <span class="break-all">{{ settings.email }}</span>
                            </a>
                        </li>
                        <li v-if="settings.address">
                            <div class="flex items-start gap-2.5 text-sm text-surface-400">
                                <Icon name="map-pin" class="mt-0.5 shrink-0" />
                                <span>{{ settings.address }}</span>
                            </div>
                        </li>
                        <li v-if="settings.working_hours">
                            <div class="flex items-start gap-2.5 text-sm text-surface-400">
                                <Icon name="clock" class="mt-0.5 shrink-0" />
                                <span>{{ settings.working_hours }}</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom bar -->
            <div class="mt-14 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-surface-500 text-xs sm:text-sm">
                    &copy; {{ currentYear }} معرض أبو عفيفة للمفروشات. جميع الحقوق محفوظة.
                </p>
                <div class="flex items-center gap-2 text-surface-500 text-xs">
                    <Icon name="sparkles" class="w-3.5 h-3.5 text-gold-500" />
                    <span>صُنع بشغف في العقبة</span>
                </div>
            </div>
        </div>
    </footer>
</template>