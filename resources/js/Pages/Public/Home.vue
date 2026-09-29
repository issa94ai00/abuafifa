<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/Public/PublicLayout.vue';
import HeroSection from '@/Components/Public/HeroSection.vue';
import SectionHeader from '@/Components/Public/SectionHeader.vue';
import CategoryCard from '@/Components/Public/CategoryCard.vue';
import ProductCard from '@/Components/Public/ProductCard.vue';
import OfferCard from '@/Components/Public/OfferCard.vue';
import GalleryGrid from '@/Components/Public/GalleryGrid.vue';
import QuickViewModal from '@/Components/Public/QuickViewModal.vue';
import RevealOnScroll from '@/Components/Public/RevealOnScroll.vue';
import Icon from '@/Components/Public/Icon.vue';

const props = defineProps({
    featured: { type: Array, default: () => [] },
    newArrivals: { type: Array, default: () => [] },
    offers: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    galleries: { type: Array, default: () => [] },
    seo: { type: Object, default: () => ({}) },
});

const quickViewProduct = ref(null);

const page = usePage();
const settings = computed(() => page.props.settings || {});

const aboutText = computed(() =>
    settings.value.site_name
    ? `${settings.value.site_name} — أكثر من خمسة عشر عاماً من الخبرة في تقديم الأثاث الفاخر والتصاميم العصرية، بحرفية أصيلة نقدّمها اليوم من قلب العقبة بأرقى الخامات وأدق التفاصيل.`
    : ''
);

const whatsappNumber = computed(
    () => String(settings.value.whatsapp || '').replace(/\D/g, '')
);

const whatsappLink = computed(() => `https://wa.me/${whatsappNumber.value}`);

const galleryImages = computed(() => {
    const allImages = [];
    if (Array.isArray(props.galleries)) {
        props.galleries.forEach(gallery => {
            if (gallery.images_count && gallery.cover) {
                allImages.push({
                    id: gallery.id,
                    url: gallery.cover,
                    caption: gallery.title,
                });
            }
        });
    }
    return allImages;
});

// ------------------------------------------------------------------ features

const features = [
    { icon: 'truck',  title: 'توصيل وتركيب', note: 'مجاني داخل المدينة' },
    { icon: 'shield', title: 'ضمان الجودة',  note: 'على جميع المنتجات' },
    { icon: 'star',   title: 'خامات فاخرة',  note: 'اختيار دقيق ومميز' },
    { icon: 'tag',    title: 'أسعار تنافسية', note: 'قيم مميزة وعروض حصرية' },
];

// ------------------------------------------------------- craftsmanship band

const craftPoints = [
    {
        image: '/images/home/craft-leather-800.webp',
        alt: 'كنبة جلدية بلون العسل بتصميم عصري',
        title: 'خامات مصمتة',
        text: 'أخشاب طبيعية وجلود منتقاة تُختبر قطعةً قطعة قبل أن تصل إلى صالة العرض.',
    },
    {
        image: '/images/home/craft-textile-800.webp',
        alt: 'تفاصيل أقمشة ووسائد بألوان دافئة',
        title: 'أقمشة مختارة',
        text: 'تشكيلة أقمشة تجمع بين الملمس الفاخر والمتانة التي تليق بالاستعمال اليومي.',
    },
    {
        image: '/images/home/craft-chair-800.webp',
        alt: 'كرسي منجّد بهيكل خشبي وخطوط انسيابية',
        title: 'تنجيد يدوي',
        text: 'حرفية أصيلة في كل غرزة، من هيكل القطعة حتى آخر تفصيلة في التنجيد.',
    },
];

// ------------------------------------------------------- new arrivals rail

const rail = ref(null);
const atStart = ref(true);
const atEnd = ref(false);

/**
 * RTL scrolling: `scrollLeft` counts down from 0 into negatives, so work off
 * its absolute value and move by negative deltas to travel forward.
 */
function updateRailEdges() {
    const el = rail.value;
    if (!el) return;

    const max = el.scrollWidth - el.clientWidth;
    const pos = Math.abs(el.scrollLeft);

    atStart.value = pos <= 8;
    atEnd.value = max <= 8 || pos >= max - 8;
}

function scrollRail(direction) {
    const el = rail.value;
    if (!el) return;

    // One card-and-a-bit per press, so something always stays on screen.
    const step = Math.max(240, el.clientWidth * 0.8);
    el.scrollBy({ left: direction * step, behavior: 'smooth' });
}

onMounted(() => {
    // Measure after the rail has actually been laid out, otherwise
    // scrollWidth still reads as the unwrapped width and both buttons
    // start out disabled.
    nextTick(updateRailEdges);
    window.addEventListener('resize', updateRailEdges, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('resize', updateRailEdges);
});
</script>

<template>
    <PublicLayout :meta="seo" transparent-nav>
        <!-- Hero -->
        <HeroSection />

        <!-- Features strip, floated over the base of the hero -->
        <RevealOnScroll animation="fade-in-up">
            <section class="relative -mt-16 z-10">
                <div class="section-container">
                    <div class="glass-panel rounded-3xl p-6 sm:p-8">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8">
                            <div
                                v-for="(feature, index) in features"
                                :key="feature.title"
                                class="group flex flex-col items-center gap-3 text-center"
                                :class="index ? 'md:border-r md:border-surface-200/70' : ''"
                            >
                                <div
                                    class="w-12 h-12 rounded-2xl bg-gradient-to-br from-brand-50 to-gold-50 ring-1 ring-brand-100 flex items-center justify-center text-brand-600 transition-transform duration-300 group-hover:-translate-y-0.5 group-hover:shadow-gold"
                                >
                                    <Icon :name="feature.icon" />
                                </div>
                                <div>
                                    <p class="font-semibold text-surface-900 text-sm">{{ feature.title }}</p>
                                    <p class="text-xs text-surface-500 mt-0.5">{{ feature.note }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </RevealOnScroll>

        <!-- Categories -->
        <RevealOnScroll v-if="categories.length" animation="fade-in-up">
            <section class="section-container pt-20 sm:pt-24 pb-12 sm:pb-16">
                <SectionHeader
                    eyebrow="تصفح حسب النمط"
                    title="تصاميم لكل ذوق"
                    subtitle="اختر التصنيف الذي يلهمك واستكشف تشكيلتنا المتنوعة من الأثاث الفاخر"
                    link-text="عرض الكل"
                    link-href="/categories"
                />
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
                    <RevealOnScroll
                        v-for="(category, index) in categories"
                        :key="category.id"
                        :delay="index * 80"
                    >
                        <CategoryCard :category="category" />
                    </RevealOnScroll>
                </div>
            </section>
        </RevealOnScroll>

        <!-- Featured Products -->
        <RevealOnScroll v-if="featured.length" animation="fade-in-up">
            <section class="relative overflow-hidden bg-surface-100/60 border-y border-surface-200/60 py-12 sm:py-16">
                <div class="absolute -top-24 left-1/4 w-[28rem] h-[28rem] rounded-full bg-gold-500/5 blur-3xl" aria-hidden="true"></div>
                <div class="relative section-container">
                    <SectionHeader
                        eyebrow="اختياراتنا المميزة"
                        title="الأكثر طلباً"
                        subtitle="تحفة فنية في كل قطعة، منتقاة بعناية لتكون نجمة مجموعتك المنزلية"
                        link-text="تصفح جميع المنتجات"
                        link-href="/products"
                    />
                    <div class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                        <ProductCard
                            v-for="product in featured"
                            :key="product.id"
                            :product="product"
                            @quick-view="(p) => quickViewProduct = p"
                        />
                    </div>
                </div>
            </section>
        </RevealOnScroll>

        <!-- Statement divider -->
        <RevealOnScroll animation="fade-in-up">
            <section class="section-container py-16 sm:py-20">
                <div class="relative text-center max-w-3xl mx-auto">
                    <Icon name="sparkles" class="w-8 h-8 text-gold-500 mx-auto mb-6" />
                    <span class="block h-px w-24 mx-auto bg-gradient-gold mb-8" aria-hidden="true"></span>
                    <p class="font-display text-2xl sm:text-3xl lg:text-4xl font-bold text-surface-900 leading-[1.6]">
                        الأثاث الجيد لا يُشترى فقط…
                        <span class="block text-gradient-gold mt-3">بل يُصنع بحبٍ يعرف معنى التفاصيل</span>
                    </p>
                    <span class="block h-px w-24 mx-auto bg-gradient-gold mt-8" aria-hidden="true"></span>
                </div>
            </section>
        </RevealOnScroll>

        <!-- Offers -->
        <RevealOnScroll v-if="offers.length" animation="fade-in-up">
            <section class="relative overflow-hidden bg-gradient-premium py-14 sm:py-20 mt-4">
                <div
                    class="absolute inset-0 opacity-[0.07] bg-cover bg-center"
                    style="background-image: url('/images/home/texture-offers-1200.jpg')"
                    aria-hidden="true"
                ></div>
                <!-- Warm glow so the band doesn't read as flat charcoal -->
                <div class="absolute -top-32 right-0 w-[28rem] h-[28rem] rounded-full bg-gold-500/10 blur-3xl" aria-hidden="true"></div>

                <div class="relative section-container">
                    <SectionHeader
                        eyebrow="لحظات لا تُفوّت"
                        title="عروض مميزة"
                        subtitle="استمتع بخصومات حصرية على تشكيلة مختارة بعناية"
                        link-text="جميع العروض"
                        link-href="/offers"
                        dark
                    />
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                        <RevealOnScroll
                            v-for="(offer, index) in offers"
                            :key="offer.id"
                            :delay="index * 100"
                        >
                            <OfferCard :offer="offer" />
                        </RevealOnScroll>
                    </div>
                </div>
            </section>
        </RevealOnScroll>

        <!-- New Arrivals: horizontal rail -->
        <RevealOnScroll v-if="newArrivals.length" animation="fade-in-up">
            <section class="section-container py-12 sm:py-16">
                <SectionHeader
                    eyebrow="وصل حديثاً"
                    title="أحدث التشكيلات"
                    subtitle="اكتشف أحدث الإضافات إلى مجموعتنا، تصاميم عصرية تحمل الحرفية الحلبية الأصيلة"
                    link-text="عرض الجديد"
                    link-href="/products?sort=latest"
                />

                <div class="relative">
                    <!--
                        Focusable so the rail can be scrolled with the arrow
                        keys; without it the only way through is a swipe or
                        the buttons below.
                    -->
                    <div
                        ref="rail"
                        role="region"
                        aria-label="أحدث التشكيلات"
                        tabindex="0"
                        class="flex gap-4 sm:gap-6 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-hide rounded-2xl focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50 focus-visible:ring-offset-4 focus-visible:ring-offset-surface-50"
                        @scroll.passive="updateRailEdges"
                        @keydown.left.prevent="scrollRail(-1)"
                        @keydown.right.prevent="scrollRail(1)"
                    >
                        <div
                            v-for="product in newArrivals"
                            :key="product.id"
                            class="snap-start shrink-0 w-[75%] xs:w-[60%] sm:w-[45%] lg:w-[30%] xl:w-[23%]"
                        >
                            <ProductCard
                                :product="product"
                                @quick-view="(p) => quickViewProduct = p"
                            />
                        </div>
                    </div>

                    <!-- Edge fades hint that the rail keeps going -->
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 w-16 bg-gradient-to-l from-surface-50 to-transparent transition-opacity duration-300 hidden sm:block"
                        :class="atEnd ? 'opacity-0' : 'opacity-100'"
                        aria-hidden="true"
                    ></div>
                    <div
                        class="pointer-events-none absolute inset-y-0 right-0 w-16 bg-gradient-to-r from-surface-50 to-transparent transition-opacity duration-300 hidden sm:block"
                        :class="atStart ? 'opacity-0' : 'opacity-100'"
                        aria-hidden="true"
                    ></div>
                </div>

                <!-- Rail controls: pointer users get buttons, touch users just swipe -->
                <div class="hidden sm:flex items-center justify-end gap-2 mt-2">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center w-11 h-11 rounded-full border border-surface-200 bg-white text-surface-700 shadow-premium transition-all duration-200 hover:border-brand-300 hover:text-brand-600 hover:shadow-premium-lg disabled:opacity-35 disabled:cursor-not-allowed disabled:hover:border-surface-200 disabled:hover:text-surface-700 focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                        aria-label="السابق"
                        :disabled="atStart"
                        @click="scrollRail(1)"
                    >
                        <Icon name="chevron-left" class="w-5 h-5 rotate-180" />
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center w-11 h-11 rounded-full border border-surface-200 bg-white text-surface-700 shadow-premium transition-all duration-200 hover:border-brand-300 hover:text-brand-600 hover:shadow-premium-lg disabled:opacity-35 disabled:cursor-not-allowed disabled:hover:border-surface-200 disabled:hover:text-surface-700 focus:outline-none focus:ring-2 focus:ring-brand-500/40"
                        aria-label="التالي"
                        :disabled="atEnd"
                        @click="scrollRail(-1)"
                    >
                        <Icon name="chevron-left" class="w-5 h-5" />
                    </button>
                </div>
            </section>
        </RevealOnScroll>

        <!-- Craftsmanship -->
        <RevealOnScroll animation="fade-in-up">
            <section class="relative overflow-hidden bg-gradient-warm py-16 sm:py-20 border-y border-surface-200/60">
                <div class="section-container">
                    <SectionHeader
                        eyebrow="ما الذي يصنع الفرق"
                        title="الحرفة قبل القطعة"
                        subtitle="كل قطعة تخرج من معرضنا تمرّ بثلاث مراحل لا نتنازل عن أيٍّ منها"
                    />
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 sm:gap-6">
                        <RevealOnScroll
                            v-for="(point, index) in craftPoints"
                            :key="point.title"
                            :delay="index * 100"
                        >
                            <article class="group h-full bg-white rounded-3xl overflow-hidden border border-surface-100 shadow-premium transition-all duration-300 hover:shadow-premium-lg hover:-translate-y-1">
                                <div class="relative aspect-[4/3] overflow-hidden bg-surface-100">
                                    <img
                                        :src="point.image"
                                        :alt="point.alt"
                                        loading="lazy"
                                        decoding="async"
                                        class="img-cover transition-transform duration-700 ease-out group-hover:scale-105"
                                    />
                                    <span
                                        class="absolute top-4 right-4 inline-flex items-center justify-center w-9 h-9 rounded-full bg-white/90 backdrop-blur-sm font-display font-bold text-sm text-brand-700 shadow-premium"
                                    >
                                        {{ index + 1 }}
                                    </span>
                                </div>
                                <div class="p-6">
                                    <h3 class="font-display font-bold text-lg text-surface-900 mb-2">{{ point.title }}</h3>
                                    <p class="text-sm text-surface-500 leading-relaxed">{{ point.text }}</p>
                                </div>
                            </article>
                        </RevealOnScroll>
                    </div>
                </div>
            </section>
        </RevealOnScroll>

        <!-- About -->
        <RevealOnScroll animation="fade-in-up">
            <section class="section-container py-16 sm:py-20">
                <div class="relative bg-gradient-premium rounded-3xl overflow-hidden shadow-premium-lg">
                    <div class="absolute -top-28 -right-20 w-72 h-72 rounded-full bg-gold-500/10 blur-3xl" aria-hidden="true"></div>
                    <div class="grid lg:grid-cols-2 gap-0">
                        <div class="relative min-h-[320px] lg:min-h-[460px] overflow-hidden">
                            <img
                                src="/images/home/about-showroom-1400.webp"
                                srcset="/images/home/about-showroom-800.webp 800w, /images/home/about-showroom-1400.webp 1400w"
                                sizes="(min-width: 1024px) 50vw, 100vw"
                                alt="صالة عرض أبو عفيفة للمفروشات بإضاءة طبيعية وأثاث فاخر"
                                loading="lazy"
                                decoding="async"
                                class="absolute inset-0 w-full h-full object-cover"
                            />
                            <div class="absolute inset-0 bg-gradient-to-r from-surface-950/60 to-transparent lg:bg-gradient-to-l"></div>
                            <div class="absolute bottom-5 right-5 inline-flex items-center gap-2.5 px-4 py-2.5 rounded-full glass-strong">
                                <span class="w-2 h-2 rounded-full bg-gold-400 animate-pulse"></span>
                                <span class="text-white text-sm font-medium">منذ عام 2009</span>
                            </div>
                        </div>

                        <div class="p-8 sm:p-10 lg:p-14 flex flex-col justify-center">
                            <span class="inline-flex items-center gap-2 text-gold-400 text-sm font-medium mb-4">
                                <span class="h-px w-8 bg-gradient-to-l from-gold-500 to-transparent"></span>
                                من نحن
                            </span>
                            <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-bold text-white leading-[1.2] mb-5">
                                معرض أبو عفيفة
                                <span class="text-gold-400 block">للمفروشات والأثاث الفاخر</span>
                            </h2>
                            <p v-if="aboutText" class="text-surface-300 leading-relaxed mb-6">
                                {{ aboutText }}
                            </p>
                            <div class="flex flex-wrap gap-4 mb-8">
                                <div class="text-center px-5">
                                    <span class="block text-2xl font-bold text-gold-400 font-display">+15</span>
                                    <span class="text-xs text-surface-400">سنة خبرة</span>
                                </div>
                                <div class="text-center px-5 border-x border-white/10">
                                    <span class="block text-2xl font-bold text-gold-400 font-display">+50</span>
                                    <span class="text-xs text-surface-400">تصميماً فاخراً</span>
                                </div>
                                <div class="text-center px-5">
                                    <span class="block text-2xl font-bold text-gold-400 font-display">+1000</span>
                                    <span class="text-xs text-surface-400">عميل سعيد</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <Link href="/about" class="btn-primary">
                                    تعرف علينا أكثر
                                    <Icon name="arrow-left" class="w-4 h-4" />
                                </Link>
                                <Link href="/contact" class="btn-secondary">
                                    تواصل معنا
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </RevealOnScroll>

        <!-- Gallery -->
        <RevealOnScroll v-if="galleryImages.length" animation="fade-in-up">
            <section class="section-container py-12 sm:py-16">
                <SectionHeader
                    eyebrow="من صالة العرض"
                    title="معرض الصور"
                    subtitle="لقطات من داخل معرضنا تعكس جودة وفخامة تشكيلتنا"
                    link-text="عرض المعرض كاملاً"
                    link-href="/gallery"
                />
                <GalleryGrid :images="galleryImages" />
            </section>
        </RevealOnScroll>

        <!-- CTA Banner -->
        <RevealOnScroll animation="fade-in-up">
            <section class="section-container py-12 sm:py-16">
                <div class="relative bg-gradient-gold rounded-3xl overflow-hidden shadow-premium-lg">
                    <div
                        class="absolute inset-0 bg-cover bg-center opacity-15 mix-blend-overlay"
                        style="background-image: url('/images/home/texture-cta-1200.jpg')"
                        aria-hidden="true"
                    ></div>
                    <div class="relative px-8 sm:px-12 lg:px-16 py-14 sm:py-20 text-center">
                        <h2 class="font-display text-2xl sm:text-3xl lg:text-4xl font-bold text-surface-950 mb-4">
                            هل تبحث عن الأثاث المثالي لمنزلك؟
                        </h2>
                        <p class="text-surface-800 max-w-2xl mx-auto mb-8 leading-relaxed">
                            فريقنا من المصممين جاهز لمساعدتك في اختيار القطع المثالية التي تليق بذوقك ومساحتك.
                            تواصل معنا اليوم لاستشارة مجانية.
                        </p>
                        <div class="flex flex-wrap justify-center gap-4">
                            <a
                                v-if="whatsappNumber"
                                :href="whatsappLink"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="btn-dark shadow-premium"
                            >
                                <Icon name="whatsapp" class="w-5 h-5" />
                                تواصل عبر واتساب
                            </a>
                            <Link href="/contact" class="btn-white shadow-premium">
                                نموذج التواصل
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </RevealOnScroll>

        <!-- Quick view modal -->
        <QuickViewModal
            v-if="quickViewProduct"
            :product="quickViewProduct"
            :open="Boolean(quickViewProduct)"
            @close="quickViewProduct = null"
        />
    </PublicLayout>
</template>
