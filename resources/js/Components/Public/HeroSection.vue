<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Icon from './Icon.vue';

const SLIDE_DURATION = 6500;

const page = usePage();
const settings = computed(() => page.props.settings || {});

const slides = [
    {
        title: 'بيتٌ يليق بذوقك الرفيع',
        subtitle: 'تشكيلة فاخرة من غرف النوم والجلوس والسفرة، صُممت بشغف لتتحول كل زاوية في منزلك إلى لوحة فنية تعبّر عنك.',
        image: '/images/home/hero-bedroom',
        alt: 'غرفة نوم فاخرة بتصميم عصري وإضاءة ذهبية',
        badge: 'أجواء فاخرة',
    },
    {
        title: 'فخامةٌ تُصنع بإتقان',
        subtitle: 'خامات مختارة بعناية وحرفية أصيلة في كل تفصيلة، لأن راحتك وجودة تفاصيل منزلك خطان لا يفترقان عندنا.',
        image: '/images/home/hero-living',
        alt: 'غرفة جلوس دافئة بأثاث فاخر وإضاءة طبيعية',
        badge: 'حرفية أصيلة',
    },
    {
        title: 'أثاثٌ يرافق أحلامك',
        subtitle: 'تصاميم عصرية وألوان متناغمة تلهم يومك، مع خدمة استشارة مجانية تساعدك في تنسيق قطع تليق بمساحتك.',
        image: '/images/home/hero-dining',
        alt: 'غرفة سفرة أنيقة بكراسي مخملية ولمسات ذهبية',
        badge: 'تشكيلة عصرية',
    },
];

/** Widths we shipped to public/images/home — see CREDITS.md there. */
const HERO_WIDTHS = [768, 1280, 1920];

/** Images are served from our own origin at three fixed widths, as WebP. */
function srcFor(image, width) {
    return `${image}-${width}.webp`;
}

function srcsetFor(image) {
    return HERO_WIDTHS.map(w => `${srcFor(image, w)} ${w}w`).join(', ');
}

const currentSlide = ref(0);
const isHovered = ref(false);
const isPageVisible = ref(true);
const prefersReducedMotion = ref(false);
const userPaused = ref(false);

/** Autoplay only when nobody — and nothing — has a reason to stop it. */
const isPlaying = computed(() =>
    !userPaused.value && !isHovered.value && isPageVisible.value && !prefersReducedMotion.value
);

let slideTimer = null;

function stopTimer() {
    if (slideTimer) {
        clearInterval(slideTimer);
        slideTimer = null;
    }
}

function startTimer() {
    stopTimer();
    if (isPlaying.value) {
        slideTimer = setInterval(nextSlide, SLIDE_DURATION);
    }
}

function goTo(index) {
    currentSlide.value = (index + slides.length) % slides.length;
    startTimer(); // a manual jump restarts the dwell time
}

function nextSlide() {
    currentSlide.value = (currentSlide.value + 1) % slides.length;
}

function prevSlide() {
    goTo(currentSlide.value - 1);
}

function togglePlay() {
    userPaused.value = !userPaused.value;
}

/** RTL: ArrowLeft moves forward, ArrowRight moves back. */
function onKeydown(event) {
    if (event.key === 'ArrowLeft') {
        event.preventDefault();
        goTo(currentSlide.value + 1);
    } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        prevSlide();
    }
}

function onVisibilityChange() {
    isPageVisible.value = !document.hidden;
}

let motionQuery = null;

function onMotionChange(event) {
    prefersReducedMotion.value = event.matches;
}

watch(isPlaying, startTimer);

// Warm the next image so the cross-fade never lands on a blank frame.
watch(currentSlide, (index) => {
    const next = slides[(index + 1) % slides.length];
    const img = new Image();
    img.src = srcFor(next.image, 1920);
});

onMounted(() => {
    motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    prefersReducedMotion.value = motionQuery.matches;
    motionQuery.addEventListener('change', onMotionChange);

    document.addEventListener('visibilitychange', onVisibilityChange);
    startTimer();
});

onUnmounted(() => {
    stopTimer();
    motionQuery?.removeEventListener('change', onMotionChange);
    document.removeEventListener('visibilitychange', onVisibilityChange);
});

// ------------------------------------------------------------------ location

const address = computed(() => settings.value.address || 'شارع الملك حسين، وسط المدينة، العقبة — الأردن');

/**
 * Short "city، country" label for the chip, pulled off the full address so the
 * admin panel stays the single source of truth.
 */
const shortLocation = computed(() => {
    const [locality, country] = address.value.split('—').map(part => part.trim());
    const city = (locality || '').split(/[،,]/).map(part => part.trim()).filter(Boolean).pop();

    return [city, country].filter(Boolean).join('، ') || 'العقبة، الأردن';
});

const mapUrl = computed(() => {
    const { map_lat: lat, map_lng: lng } = settings.value;

    return lat && lng
        ? `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`
        : null;
});

const phone = computed(() => settings.value.phone);
const workingHours = computed(() => settings.value.working_hours);

const trustPoints = ['+15 سنة خبرة', '+1000 عميل سعيد', 'حرفية أصيلة'];
</script>

<template>
    <section
        class="relative overflow-hidden bg-surface-950"
        role="region"
        aria-roledescription="عرض شرائح"
        aria-label="الواجهة الرئيسية"
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false"
        @focusin="isHovered = true"
        @focusout="isHovered = false"
    >
        <div class="relative h-[92vh] min-h-[720px] max-h-[960px]" id="hero-slideshow">
            <!-- Slides -->
            <div
                v-for="(slide, index) in slides"
                :key="index"
                class="absolute inset-0 transition-opacity duration-1000 ease-out motion-reduce:transition-none"
                :class="currentSlide === index ? 'opacity-100' : 'opacity-0'"
                :aria-hidden="currentSlide !== index"
            >
                <img
                    :src="srcFor(slide.image, 1920)"
                    :srcset="srcsetFor(slide.image)"
                    sizes="100vw"
                    :alt="slide.alt"
                    class="w-full h-full object-cover"
                    :class="{ 'hero-kenburns': currentSlide === index && !prefersReducedMotion }"
                    :loading="index === 0 ? 'eager' : 'lazy'"
                    :fetchpriority="index === 0 ? 'high' : 'low'"
                    decoding="async"
                />
                <!-- Legibility scrim, in layers:
                     1. bottom-up, so the slide controls and the features card read
                     2. right edge, which is where the RTL copy sits
                     3. top band, so the transparent navbar has something to sit on -->
                <div class="absolute inset-0 bg-gradient-to-t from-surface-950 via-surface-950/55 to-surface-950/20"></div>
                <div class="absolute inset-0 bg-gradient-to-l from-surface-950/85 via-surface-950/35 to-transparent"></div>
                <div class="absolute inset-x-0 top-0 h-48 bg-gradient-to-b from-surface-950/70 to-transparent"></div>
            </div>

            <!-- Content -->
            <div class="relative z-10 section-container h-full flex items-center">
                <div class="max-w-3xl pt-32 sm:pt-36 pb-28 sm:pb-32 text-right">
                    <!-- Badge + location chip -->
                    <div class="flex flex-wrap items-center gap-3 mb-6 animate-fade-in-down">
                        <span
                            class="inline-flex items-center gap-2.5 px-4 py-2 glass-chip text-gold-300 text-sm font-medium"
                        >
                            <Icon name="sparkles" class="w-4 h-4" />
                            <span>{{ slides[currentSlide].badge }}</span>
                        </span>

                        <component
                            :is="mapUrl ? 'a' : 'span'"
                            v-bind="mapUrl ? { href: mapUrl, target: '_blank', rel: 'noopener noreferrer' } : {}"
                            class="group inline-flex items-center gap-2 px-4 py-2 glass-chip text-white text-sm font-medium transition-colors"
                            :class="mapUrl ? 'hover:bg-white/20 hover:border-gold-400/50 focus:outline-none focus:ring-2 focus:ring-gold-400' : ''"
                            :title="address"
                        >
                            <Icon name="map-pin" class="w-4 h-4 text-gold-300" />
                            <span>{{ shortLocation }}</span>
                            <Icon
                                v-if="mapUrl"
                                name="arrow-left"
                                class="w-3.5 h-3.5 text-gold-300 transition-transform group-hover:-translate-x-0.5"
                            />
                        </component>
                    </div>

                    <!-- Headline -->
                    <Transition name="hero-text" mode="out-in">
                        <h1
                            :key="`title-${currentSlide}`"
                            class="font-display text-4xl sm:text-5xl md:text-6xl xl:text-7xl font-bold text-white leading-[1.2] md:leading-[1.15] mb-6"
                        >
                            {{ slides[currentSlide].title }}
                            <span class="block text-transparent bg-clip-text bg-gradient-to-l from-gold-300 via-gold-400 to-brand-300 mt-1">
                                من قلب العقبة
                            </span>
                        </h1>
                    </Transition>

                    <Transition name="hero-text" mode="out-in">
                        <p
                            :key="`sub-${currentSlide}`"
                            class="max-w-2xl text-surface-200 text-base sm:text-lg md:text-xl leading-relaxed mb-7 font-light"
                        >
                            {{ slides[currentSlide].subtitle }}
                        </p>
                    </Transition>

                    <!-- Screen-reader announcement for the slide change -->
                    <p class="sr-only" aria-live="polite">
                        الشريحة {{ currentSlide + 1 }} من {{ slides.length }}: {{ slides[currentSlide].title }}
                    </p>

                    <!-- CTAs -->
                    <div class="flex flex-wrap gap-4 mb-7">
                        <Link href="/products" class="btn-primary text-base sm:text-lg animate-fade-in-up stagger-3">
                            تصفح منتجاتنا
                            <Icon name="arrow-left" class="w-5 h-5" />
                        </Link>
                        <Link href="/contact" class="btn-secondary text-base animate-fade-in-up stagger-4">
                            زورونا في المعرض
                        </Link>
                    </div>

                    <!-- Where to find us -->
                    <div
                        class="inline-flex flex-col sm:flex-row sm:items-stretch gap-3 sm:gap-0 rounded-2xl glass p-4 sm:px-5 sm:py-4 animate-fade-in-up stagger-5"
                    >
                        <a
                            v-if="mapUrl"
                            :href="mapUrl"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex items-start gap-3 sm:pl-5 text-right group focus:outline-none focus:ring-2 focus:ring-gold-400 rounded-lg"
                        >
                            <Icon name="map-pin" class="w-5 h-5 mt-0.5 shrink-0 text-gold-300" />
                            <span>
                                <span class="block text-[11px] uppercase tracking-wide text-surface-400 mb-0.5">موقع المعرض</span>
                                <span class="block text-sm text-white group-hover:text-gold-300 transition-colors">{{ address }}</span>
                            </span>
                        </a>
                        <div v-else class="flex items-start gap-3 sm:pl-5 text-right">
                            <Icon name="map-pin" class="w-5 h-5 mt-0.5 shrink-0 text-gold-300" />
                            <span>
                                <span class="block text-[11px] uppercase tracking-wide text-surface-400 mb-0.5">موقع المعرض</span>
                                <span class="block text-sm text-white">{{ address }}</span>
                            </span>
                        </div>

                        <div v-if="workingHours" class="hidden sm:block w-px bg-white/15 shrink-0"></div>

                        <div v-if="workingHours" class="flex items-start gap-3 sm:px-5 text-right">
                            <Icon name="clock" class="w-5 h-5 mt-0.5 shrink-0 text-gold-300" />
                            <span>
                                <span class="block text-[11px] uppercase tracking-wide text-surface-400 mb-0.5">ساعات العمل</span>
                                <span class="block text-sm text-white">{{ workingHours }}</span>
                            </span>
                        </div>

                        <div v-if="phone" class="hidden sm:block w-px bg-white/15 shrink-0"></div>

                        <a
                            v-if="phone"
                            :href="`tel:${String(phone).replace(/\s/g, '')}`"
                            class="flex items-start gap-3 sm:pr-5 text-right group focus:outline-none focus:ring-2 focus:ring-gold-400 rounded-lg"
                        >
                            <Icon name="phone" class="w-5 h-5 mt-0.5 shrink-0 text-gold-300" />
                            <span>
                                <span class="block text-[11px] uppercase tracking-wide text-surface-400 mb-0.5">اتصل بنا</span>
                                <span class="block text-sm text-white group-hover:text-gold-300 transition-colors" dir="ltr">{{ phone }}</span>
                            </span>
                        </a>
                    </div>

                    <!-- Trust indicators -->
                    <div class="flex flex-wrap items-center gap-x-8 gap-y-3 mt-6 animate-fade-in-up stagger-6">
                        <div v-for="point in trustPoints" :key="point" class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-gold-400"></span>
                            <span class="text-sm text-surface-200">{{ point }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slide controls: kept clear of the features card that overlaps the hero bottom -->
            <div
                class="absolute bottom-24 sm:bottom-28 z-20 section-container inset-x-0 flex items-center justify-end gap-3"
                @keydown="onKeydown"
            >
                <button
                    type="button"
                    class="hidden sm:flex items-center p-2.5 glass-chip text-white hover:bg-white/20 transition-colors focus:outline-none focus:ring-2 focus:ring-gold-400"
                    aria-label="الشريحة السابقة"
                    @click="prevSlide"
                >
                    <Icon name="chevron-left" class="w-5 h-5 rotate-180" />
                </button>

                <div class="flex items-center gap-2" role="group" aria-label="شرائح العرض">
                    <button
                        v-for="(slide, index) in slides"
                        :key="index"
                        type="button"
                        class="relative h-1.5 rounded-full overflow-hidden transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-gold-400"
                        :class="currentSlide === index ? 'w-10 bg-white/30' : 'w-4 bg-white/40 hover:bg-white/70'"
                        :aria-current="currentSlide === index ? 'true' : undefined"
                        :aria-label="`الانتقال إلى الشريحة ${index + 1}: ${slide.title}`"
                        @click="goTo(index)"
                    >
                        <!-- Fill doubles as the autoplay countdown -->
                        <span
                            v-if="currentSlide === index"
                            :key="`progress-${currentSlide}`"
                            class="absolute inset-y-0 right-0 bg-gold-400 rounded-full"
                            :class="isPlaying ? 'hero-progress' : 'w-full'"
                        ></span>
                    </button>
                </div>

                <button
                    type="button"
                    class="flex items-center p-2.5 glass-chip text-white hover:bg-white/20 transition-colors focus:outline-none focus:ring-2 focus:ring-gold-400"
                    aria-label="الشريحة التالية"
                    @click="goTo(currentSlide + 1)"
                >
                    <Icon name="chevron-left" class="w-5 h-5" />
                </button>

                <button
                    v-if="!prefersReducedMotion"
                    type="button"
                    class="flex items-center p-2.5 glass-chip text-white hover:bg-white/20 transition-colors focus:outline-none focus:ring-2 focus:ring-gold-400"
                    :aria-label="userPaused ? 'تشغيل العرض التلقائي' : 'إيقاف العرض التلقائي'"
                    @click="togglePlay"
                >
                    <Icon :name="userPaused ? 'play' : 'pause'" class="w-4 h-4" />
                </button>
            </div>
        </div>
    </section>
</template>

<style scoped>
.hero-text-enter-active {
    transition: opacity 0.6s ease, transform 0.6s ease;
}

.hero-text-leave-active {
    transition: opacity 0.35s ease;
}

.hero-text-enter-from {
    opacity: 0;
    transform: translateY(12px);
}

.hero-text-leave-to {
    opacity: 0;
}

/* Slow drift on the active slide so a still photo still feels alive. */
.hero-kenburns {
    animation: hero-kenburns 9s ease-out forwards;
}

@keyframes hero-kenburns {
    from {
        transform: scale(1);
    }
    to {
        transform: scale(1.07);
    }
}

/* Countdown fill inside the active dot; grows RTL, matching the slide timer. */
.hero-progress {
    animation: hero-progress 6500ms linear forwards;
}

@keyframes hero-progress {
    from {
        width: 0;
    }
    to {
        width: 100%;
    }
}

@media (prefers-reduced-motion: reduce) {
    .hero-text-enter-active,
    .hero-text-leave-active {
        transition: none;
    }

    .hero-kenburns,
    .hero-progress {
        animation: none;
    }
}
</style>
